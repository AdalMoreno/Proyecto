<?php
session_start();
include 'Login_Rosi/db.php'; 

// Verificar si el usuario es un doctor autenticado
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo'] != 'Doctor') {
    header("Location: login.php");
    exit();
}

$id_doctor = $_SESSION['id_usuario'];


try {
     // Obtener nombre del doctor usando PDO
     $sql_doctor = "SELECT nombre FROM Usuario WHERE id_usuario = :id_doctor";
     $stmt_doctor = $conexion->prepare($sql_doctor);
     $stmt_doctor->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
     $stmt_doctor->execute();
     $doctor = $stmt_doctor->fetch(PDO::FETCH_ASSOC);
 
     if (!$doctor) {
         throw new Exception("No se encontró información del doctor");
     }
} catch (PDOException $e) {
    die("Error al obtener datos del doctor: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal del Doctor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .hero-bg {
            background-image: url('https://images.pexels.com/photos/4033148/pexels-photo-4033148.jpeg');
            background-size: cover;
            background-position: center;
        }
    </style>
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-user-md text-blue-500 text-2xl"></i>
                <h1 class="text-xl font-bold text-gray-800">Dr. [Nombre]</h1>
            </div>
            <nav class="hidden md:flex space-x-6">
                <a href="index.php" class="text-blue-500 font-medium">Inicio</a>
                <a href="schedule.php" class="text-gray-600 hover:text-blue-500">Agendar</a>
                <a href="View_citas.php" class="text-gray-600 hover:text-blue-500">Citas</a>
                <a href="notes.php" class="text-gray-600 hover:text-blue-500">Notas</a>
            </nav>
            <button class="md:hidden text-gray-600">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero-bg h-96 flex items-center justify-center bg-gray-900 bg-opacity-50">
        <div class="text-center text-white px-4">
            <h1 class="text-4xl md:text-5xl font-bold mb-4">Bienvenido al Portal del Doctor</h1>
            <p class="text-xl mb-8">Gestión de citas y pacientes en un solo lugar</p>
            <a href="schedule.html" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                Agendar Horario
            </a>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-4">
            <h2 class="text-3xl font-bold text-center mb-16 text-gray-800">Funcionalidades Principales</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-10 max-w-5xl mx-auto">
                <!-- Tarjeta 1 -->
                <div class="bg-gray-50 p-8 rounded-xl shadow-sm hover:shadow-md transition duration-300 flex flex-col items-center text-center">
                    <div class="text-blue-500 mb-6">
                        <i class="fas fa-calendar-plus text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-gray-800">Agendar Horario</h3>
                    <p class="text-gray-600">Programe su horaro de manera sencilla.</p>
                </div>

                <!-- Tarjeta 2 -->
                <div class="bg-gray-50 p-8 rounded-xl shadow-sm hover:shadow-md transition duration-300 flex flex-col items-center text-center">
                    <div class="text-blue-500 mb-6">
                        <i class="fas fa-list text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-gray-800">Lista de Citas</h3>
                    <p class="text-gray-600">Vea todas sus citas programadas en un calendario organizado.</p>
                </div>

                <!-- Tarjeta 3 -->
                <div class="bg-gray-50 p-8 rounded-xl shadow-sm hover:shadow-md transition duration-300 flex flex-col items-center text-center">
                    <div class="text-blue-500 mb-6">
                        <i class="fas fa-sticky-note text-4xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold mb-3 text-gray-800">Notas Médicas</h3>
                    <p class="text-gray-600">Registre y consulte notas importantes sobre sus pacientes.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-8">
        <div class="container mx-auto px-4">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-4 md:mb-0">
                    <h2 class="text-xl font-bold">Dr. [Nombre]</h2>
                    <p class="text-gray-400">Medicina General</p>
                </div>
                <div class="flex space-x-4">
                    <a href="#" class="text-gray-400 hover:text-white">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white">
                        <i class="fab fa-instagram"></i>
                    </a>
                </div>
            </div>
            <div class="border-t border-gray-700 mt-6 pt-6 text-center text-gray-400">
                <p>© 2025 Portal del Doctor. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <script src="script.js"></script>
</body>

</html>