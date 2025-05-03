<?php
header('Content-Type: application/json');

session_start();

// Verificar si hay una foto guardada en sesión
if (isset($_SESSION['foto_paciente'])) {
    $fileName = $_SESSION['foto_paciente'];
    $filePath = 'fotos_pacientes/' . $fileName;
    
    if (file_exists($filePath)) {
        echo json_encode([
            'success' => true,
            'url' => $filePath,
            'fileName' => $fileName
        ]);
        exit;
    }
}

// Si no hay foto, devolver un estado false
echo json_encode([
    'success' => false,
    'error' => 'No se encontró foto del paciente'
]);
?>