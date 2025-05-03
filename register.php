<?php
require 'db.php';
header('Content-Type: application/json');

$response = ["status" => "error", "message" => ""];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Verificar campos obligatorios
        if (!isset($_POST["nombre"], $_POST["email"], $_POST["contrasena"], $_POST["role"])) {
            throw new Exception("Faltan campos obligatorios");
        }

        $nombre = trim($_POST["nombre"]);
        $email = trim($_POST["email"]);
        $contrasena = trim($_POST["contrasena"]);
        $tipo = trim($_POST["role"]);

        // Validar contraseña (manteniendo tus reglas actuales)
        $errors = [];
        if (strlen($contrasena) < 4) $errors[] = "La contraseña debe tener al menos 4 caracteres.";
        if (!preg_match("/[a-z]/", $contrasena) || !preg_match("/[A-Z]/", $contrasena)) $errors[] = "Debe tener mayúsculas y minúsculas.";
        if (!preg_match("/[0-9]/", $contrasena)) $errors[] = "Debe tener al menos un número.";
        if (!preg_match("/[\W_]/", $contrasena)) $errors[] = "Debe tener un carácter especial.";
        if (strpos($contrasena, ' ') !== false) $errors[] = "No puede tener espacios.";

        if (!empty($errors)) throw new Exception(implode(" ", $errors));

        // Hash de la contraseña
        $hash = password_hash($contrasena, PASSWORD_BCRYPT);

        // Iniciar transacción
        $conexion->beginTransaction();

        // Insertar en Usuario (usando RETURNING para PostgreSQL)
        $sql = "INSERT INTO Usuario (nombre, email, contrasena, tipo) VALUES (:nombre, :email, :contrasena, :tipo) RETURNING id_usuario";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':contrasena', $hash);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->execute();
        $id_usuario = $stmt->fetchColumn();

        // Insertar en tabla específica según el rol
        if ($tipo == "Paciente") {
            if (!isset($_POST["fecha_nacimiento"], $_POST["telefono"], $_POST["direccion"])) {
                throw new Exception("Faltan datos requeridos para paciente");
            }
            
            $sql = "INSERT INTO Paciente (id_paciente, fecha_nacimiento, telefono, direccion) 
                    VALUES (:id, :fecha, :telefono, :direccion)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                ':id' => $id_usuario,
                ':fecha' => $_POST["fecha_nacimiento"],
                ':telefono' => $_POST["telefono"],
                ':direccion' => $_POST["direccion"]
            ]);
        } 
        elseif ($tipo == "Doctor") {
            if (!isset($_POST["especialidad"], $_POST["telefono"], $_POST["direccion"])) {
                throw new Exception("Faltan datos requeridos para doctor");
            }
            
            $sql = "INSERT INTO Doctor (id_doctor, especialidad, telefono, direccion) 
                    VALUES (:id, :especialidad, :telefono, :direccion)";
            $stmt = $conexion->prepare($sql);
            $stmt->execute([
                ':id' => $id_usuario,
                ':especialidad' => $_POST["especialidad"],
                ':telefono' => $_POST["telefono"],
                ':direccion' => $_POST["direccion"]
            ]);
        }

        // Confirmar transacción
        $conexion->commit();

        $response["status"] = "success";
        $response["message"] = "Registro exitoso";

    } catch (PDOException $e) {
        // Revertir en caso de error
        if ($conexion->inTransaction()) {
            $conexion->rollBack();
        }
        
        // Manejar errores específicos de PostgreSQL
        if (strpos($e->getMessage(), 'unique constraint') !== false) {
            $response["message"] = "El email ya está registrado";
        } else {
            $response["message"] = "Error en la base de datos: " . $e->getMessage();
        }
    } catch (Exception $e) {
        $response["message"] = $e->getMessage();
    }
} else {
    $response["message"] = "Método de solicitud no válido";
}

// Asegurarse de que no hay nada más que pueda interferir con la respuesta JSON
ob_clean();
echo json_encode($response);
exit();