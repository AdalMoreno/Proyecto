<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_cita = $_GET['id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $estado = $data['estado'];

    $sql = "UPDATE Citas SET estado = '$estado' WHERE id_cita = $id_cita";
    if ($conexion->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Estado de la cita actualizado correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el estado de la cita.']);
    }
}
?>