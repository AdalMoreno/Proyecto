<?php
session_start();
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $id_cita = $_GET['id'];
    $sql = "DELETE FROM Citas WHERE id_cita = $id_cita";
    if ($conexion->query($sql)) {
        echo json_encode(['status' => 'success', 'message' => 'Cita eliminada correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al eliminar la cita.']);
    }
}
?>