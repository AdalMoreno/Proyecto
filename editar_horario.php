<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_horario = $_GET['id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $hora_inicio = $data['hora_inicio'];
    $hora_fin = $data['hora_fin'];

    $sql = "UPDATE HorarioDoctor SET hora_inicio = '$hora_inicio', hora_fin = '$hora_fin' WHERE id_horario = $id_horario";
    if ($conexion->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Horario actualizado correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el horario.']);
    }
}
?>