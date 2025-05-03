<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_doctor = $_SESSION['id_usuario'];
    $dia = $_POST['dayOfWeek'];
    $hora_inicio = $_POST['startTime'];
    $hora_fin = $_POST['endTime'];

    // Validar formato de hora
    function isValidTime($time) {
        return preg_match('/^(0?[1-9]|1[0-2]):[0-5][0-9] (AM|PM)$/i', $time);
    }

    if (!isValidTime($hora_inicio) || !isValidTime($hora_fin)) {
        $_SESSION['mensaje_error'] = 'Formato de hora inválido. Use HH:MM AM/PM (ej. 9:00 AM)';
        header("Location: View_horario.php");
        exit();
    }

    // Convertir a formato 24 horas
    function to24Hour($time) {
        $period = substr($time, -2);
        $time = substr($time, 0, -3);
        list($hours, $minutes) = explode(':', $time);
        
        if ($period == 'PM' && $hours != 12) $hours += 12;
        if ($period == 'AM' && $hours == 12) $hours = 0;
        
        return sprintf("%02d:%02d:00", $hours, $minutes);
    }

    try {
        // Verificar si el día ya está registrado
        $stmt = $conexion->prepare("SELECT id_horario FROM HorarioDoctor WHERE id_doctor = ? AND dia = ?");
        $stmt->execute([$id_doctor, $dia]);
        
        if ($stmt->rowCount() > 0) {
            $_SESSION['mensaje_error'] = 'Ya tienes un horario registrado para ' . $dia;
            header("Location: View_horario.php");
            exit();
        }

        // Insertar nuevo horario
        $stmt = $conexion->prepare("INSERT INTO HorarioDoctor (id_doctor, dia, hora_inicio, hora_fin) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $id_doctor,
            $dia,
            to24Hour($hora_inicio),
            to24Hour($hora_fin)
        ]);

        $_SESSION['mensaje_exito'] = 'Horario para ' . $dia . ' guardado correctamente';
        header("Location: View_horario.php");
        exit();

    } catch (PDOException $e) {
        $_SESSION['mensaje_error'] = 'Error al guardar el horario: ' . $e->getMessage();
        header("Location: View_horario.php");
        exit();
    }
}
?>