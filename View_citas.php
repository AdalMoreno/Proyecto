<?php
session_start();
include 'db.php'; // Asegúrate de que este archivo tenga la conexión a PostgreSQL

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
    die("Error de base de datos: " . $e->getMessage());
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citas - Portal del Doctor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4 py-4 flex justify-between items-center">
            <div class="flex items-center space-x-2">
                <i class="fas fa-user-md text-blue-500 text-2xl"></i>
                <h1 class="text-xl font-bold text-gray-800">Dr. <?php echo htmlspecialchars($doctor['nombre']); ?></h1>
            </div>
            <nav class="hidden md:flex space-x-6">
                <a href="View_doctor.php" class="text-gray-600 hover:text-blue-500">Inicio</a>
                <a href="View_horario.php" class="text-gray-600 hover:text-blue-500">Agendar</a>
                <a href="View_citas.php" class="text-blue-500 font-medium">Citas</a>
                <a href="View_notas.php" class="text-gray-600 hover:text-blue-500">Notas</a>
                <form action="cerrar.php" method="post">
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white font-medium py-2 px-4 rounded-lg transition duration-300 flex items-center space-x-2">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Cerrar Sesión</span>
                    </button>
                </form>
            </nav>
            <button class="md:hidden text-gray-600">
                <i class="fas fa-bars text-xl"></i>
            </button>
        </div>
    </header>

    <!-- Main Content -->
    <main class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Mis Citas Médicas</h2>

            <!-- Appointments Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Paciente</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Paciente</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha y Hora</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="appointmentsTable" class="bg-white divide-y divide-gray-200">
                            <!-- Contenido generado dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Success Modal -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <i class="fas fa-check text-green-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mt-3">¡Operación Exitosa!</h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500" id="modalMessage">La acción se realizó correctamente.</p>
                </div>
                <div class="mt-4">
                    <button type="button" onclick="document.getElementById('successModal').classList.add('hidden')" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        Aceptar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Function to render appointments table
        function renderAppointments() {
            const tableBody = document.getElementById('appointmentsTable');
            tableBody.innerHTML = '';

            if (appointments.length === 0) {
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                            No tienes citas programadas.
                        </td>
                    </tr>
                `;
                return;
            }

            appointments.forEach(appointment => {
                const row = document.createElement('tr');
                const fechaHora = `${appointment.fecha} ${appointment.hora}:00`;

                row.innerHTML = `
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${appointment.id_paciente}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${appointment.paciente}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${fechaHora}</td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <select id="estado_${appointment.id}" onchange="cambiarEstado(${appointment.id})"
                            class="px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="Pendiente" ${appointment.estado === 'Pendiente' ? 'selected' : ''}>Pendiente</option>
                            <option value="Confirmada" ${appointment.estado === 'Confirmada' ? 'selected' : ''}>Confirmada</option>
                            <option value="Cancelada" ${appointment.estado === 'Cancelada' ? 'selected' : ''}>Cancelada</option>
                            <option value="Completada" ${appointment.estado === 'Completada' ? 'selected' : ''}>Completada</option>
                        </select>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        <button onclick="eliminarCita(${appointment.id})" 
                                class="px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600 focus:outline-none focus:ring-1 focus:ring-red-500">
                            Eliminar
                        </button>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        }

        // Function to change appointment status
        function cambiarEstado(id_cita) {
            const nuevoEstado = document.getElementById(`estado_${id_cita}`).value;

            // Aquí iría la llamada a tu API para actualizar el estado
            fetch(`actualizar_estado_cita.php?id=${id_cita}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        estado: nuevoEstado
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        mostrarMensajeExito(data.message);
                    }
                });

            // Simulación para el ejemplo
            const index = appointments.findIndex(a => a.id === id_cita);
            if (index !== -1) {
                appointments[index].estado = nuevoEstado;
                mostrarMensajeExito("Estado de cita actualizado correctamente.");
            }
        }

        // Function to delete appointment
        function eliminarCita(id_cita) {
            if (confirm("¿Estás seguro de eliminar esta cita?")) {
                fetch(`eliminar_cita.php?id=${id_cita}`, {
                        method: 'DELETE'
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            appointments = appointments.filter(a => a.id !== id_cita);
                            renderAppointments();
                            mostrarMensajeExito(data.message);
                        }
                    });

                // Simulación para el ejemplo
                appointments = appointments.filter(a => a.id !== id_cita);
                renderAppointments();
                mostrarMensajeExito("Cita eliminada correctamente.");
            }
        }

        // Function to show success message
        function mostrarMensajeExito(mensaje) {
            const modal = document.getElementById('successModal');
            const modalMessage = document.getElementById('modalMessage');

            modalMessage.textContent = mensaje;
            modal.classList.remove('hidden');
        }

        // Initial render
        document.addEventListener('DOMContentLoaded', renderAppointments);
    </script>
</body>

</html>