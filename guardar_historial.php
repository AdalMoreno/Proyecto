<?php
session_start();
include 'db.php'; // Asegúrate de que db.php esté configurado para PostgreSQL

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obtener los datos del cuerpo de la solicitud
    $data = json_decode(file_get_contents('php://input'), true);

    $id_paciente = $data['id_paciente'];
    $diagnostico = $data['diagnostico'];
    $tratamiento = $data['tratamiento'];

    // Validar que los datos no estén vacíos
    if (empty($id_paciente) || empty($diagnostico) || empty($tratamiento)) {
        echo json_encode(['status' => 'error', 'message' => 'Todos los campos son obligatorios.']);
        exit();
    }

    // Insertar la nota médica en la base de datos
    $sql = "INSERT INTO HistorialMedico (id_paciente, diagnostico, tratamiento) VALUES ($1, $2, $3)";
    
    // Preparar la consulta en PostgreSQL
    $stmt = pg_prepare($db, "insert_historial", $sql);

    // Ejecutar la consulta con los parámetros
    $result = pg_execute($db, "insert_historial", array($id_paciente, $diagnostico, $tratamiento));

    if ($result) {
        echo json_encode(['status' => 'success', 'message' => 'Nota médica guardada correctamente.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error al guardar la nota médica.']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Método no permitido.']);
}
?>
