<?php
session_start();
include 'db.php'; // Asegúrate de que db.php esté configurado para PostgreSQL con PDO

// Verificar autenticación y tipo de usuario
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo'] != 'Paciente') {
    header("Location: login.php");
    exit();
}

$id_paciente = $_SESSION['id_usuario'];
$id_doctor = $_POST['id_doctor'];

try {
    // Obtener el horario del doctor usando PDO
    $sql_horario = "SELECT dia, hora_inicio, hora_fin FROM HorarioDoctor WHERE id_doctor = :id_doctor";
    $stmt_horario = $conexion->prepare($sql_horario);
    $stmt_horario->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
    $stmt_horario->execute();
    $horarios = $stmt_horario->fetchAll(PDO::FETCH_ASSOC);

    if (empty($horarios)) {
        throw new Exception("El doctor no tiene horarios configurados.");
    }

    // Obtener las citas agendadas del doctor
    $sql_citas = "SELECT fecha_cita FROM Citas WHERE id_doctor = :id_doctor ORDER BY fecha_cita";
    $stmt_citas = $conexion->prepare($sql_citas);
    $stmt_citas->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
    $stmt_citas->execute();
    $citas = $stmt_citas->fetchAll(PDO::FETCH_ASSOC);

    /**
     * Genera una fecha disponible para la cita médica
     * 
     * @param array $horarios Horarios disponibles del doctor
     * @param array $citas Citas ya agendadas del doctor
     * @return string|null Fecha disponible en formato 'Y-m-d H:i:s' o null si no hay disponibilidad
     */
    function generarFechaDisponible(array $horarios, array $citas): ?string {
        $fecha_actual = new DateTime();
        $dia_semana_actual = $fecha_actual->format('N'); // 1 (Lunes) - 7 (Domingo)

        // Si es fin de semana, programar para el próximo Lunes
        if ($dia_semana_actual >= 6) {
            $fecha_actual->modify('next Monday');
        } else {
            // Si es entre semana, programar para la siguiente semana
            $fecha_actual->modify('next week');
        }

        // Mapeo de días de la semana
        $dias_semana = [
            'Lunes' => 1,
            'Martes' => 2,
            'Miércoles' => 3,
            'Jueves' => 4,
            'Viernes' => 5,
            'Sábado' => 6,
            'Domingo' => 7,
        ];

        // Buscar un horario disponible
        foreach ($horarios as $horario) {
            $dia_horario = $horario['dia'];
            $hora_inicio = $horario['hora_inicio'];

            if (!isset($dias_semana[$dia_horario])) {
                continue; // Día no válido, saltar
            }

            $dia_numero = $dias_semana[$dia_horario];
            $fecha_horario = clone $fecha_actual;
            $fecha_horario->modify('+' . ($dia_numero - $fecha_actual->format('N')) . ' days');

            // Combinar fecha y hora
            $fecha_cita = $fecha_horario->format('Y-m-d') . ' ' . $hora_inicio;

            // Verificar disponibilidad
            $disponible = true;
            foreach ($citas as $cita) {
                if ($cita['fecha_cita'] === $fecha_cita) {
                    $disponible = false;
                    break;
                }
            }

            if ($disponible) {
                return $fecha_cita;
            }
        }

        return null;
    }

    // Generar fecha disponible
    $fecha_cita = generarFechaDisponible($horarios, $citas);

    if (!$fecha_cita) {
        throw new Exception("No hay horarios disponibles para agendar una cita.");
    }

    // Insertar la cita en la base de datos
    $sql_insert = "INSERT INTO Citas (id_paciente, id_doctor, fecha_cita, estado) 
                   VALUES (:id_paciente, :id_doctor, :fecha_cita, 'Pendiente')";
    
    $stmt_insert = $conexion->prepare($sql_insert);
    $stmt_insert->bindParam(':id_paciente', $id_paciente, PDO::PARAM_INT);
    $stmt_insert->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
    $stmt_insert->bindParam(':fecha_cita', $fecha_cita);
  
    if ($stmt_insert->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Cita agendada exitosamente',
            'fecha_cita' => date('d/m/Y H:i', strtotime($fecha_cita))
        ]);
        exit();
    } else {
        throw new Exception("Error al agendar la cita.");
    }

} catch (PDOException $e) {
    error_log("Error de base de datos: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => "Ocurrió un error al procesar tu solicitud. Por favor, intenta nuevamente."
    ]);
    exit();
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    exit();
}