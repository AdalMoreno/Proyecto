<?php
session_start();
require 'db.php'; // Asegúrate de que este archivo tenga la conexión PDO a PostgreSQL

// Verificar que el usuario es un paciente
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo'] !== 'Paciente') {
    header("Location: login.php");
    exit();
}

$id_paciente = $_SESSION['id_usuario'];

try {
    $sql = "SELECT d.id_doctor, u.nombre, d.especialidad 
            FROM Doctor d
            JOIN Usuario u ON d.id_doctor = u.id_usuario";
    $stmt = $conexion->prepare($sql);
    $stmt->execute();
    $doctores = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error al cargar doctores: " . $e->getMessage());
    die("Error al cargar los doctores.");
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Agendar Cita</title>
    <link rel="stylesheet" href="style1.css">
</head>
<body>
    <div class="container">
        <h1>Agendar Nueva Cita</h1>
        <form action="guardar_cita.php" method="POST">
            <label for="id_doctor">Selecciona un Doctor:</label>
            <select name="id_doctor" required>
                <option value="" disabled selected>Seleccione un doctor</option>
                <?php foreach ($doctores as $doctor): ?>
                    <option value="<?= htmlspecialchars($doctor['id_doctor']) ?>">
                        <?= htmlspecialchars($doctor['nombre']) ?> - <?= htmlspecialchars($doctor['especialidad']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="fecha_cita">Fecha y Hora:</label>
            <input type="datetime-local" name="fecha_cita" required>

            <label for="motivo">Motivo de la Cita:</label>
            <textarea name="motivo" required></textarea>

            <button type="submit">Agendar Cita</button>
        </form>

        <a href="paciente_dashboard.php" class="btn">Volver</a>
    </div>
</body>
</html>
