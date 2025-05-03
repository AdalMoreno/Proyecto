<?php
header('Content-Type: application/json');

// Validar método POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

// Validar que se haya subido un archivo
if (!isset($_FILES['foto']) || $_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No se recibió ninguna foto válida']);
    exit;
}

// Configuración
$uploadDir = 'fotos_pacientes/';
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
$maxSize = 3 * 1024 * 1024; // 2MB

// Validar tipo y tamaño
if (!in_array($_FILES['foto']['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'error' => 'Solo se permiten imágenes JPEG, PNG o GIF']);
    exit;
}

if ($_FILES['foto']['size'] > $maxSize) {
    echo json_encode(['success' => false, 'error' => 'La imagen es demasiado grande (máximo 2MB)']);
    exit;
}

// Crear directorio si no existe
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Generar nombre único y guardar
$fileExt = pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION);
$fileName = uniqid('paciente_', true) . '.' . $fileExt;
$uploadPath = $uploadDir . $fileName;

if (move_uploaded_file($_FILES['foto']['tmp_name'], $uploadPath)) {
    // Guardar el nombre del archivo en sesión (o base de datos)
    session_start();
    $_SESSION['foto_paciente'] = $fileName;
    
    echo json_encode([
        'success' => true,
        'url' => $uploadPath,
        'fileName' => $fileName
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Error al guardar la foto']);
}
?>