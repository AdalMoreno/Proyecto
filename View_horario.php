<?php
session_start();
include 'db.php';

// Verificar si el usuario es un doctor autenticado
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo'] != 'Doctor') {
    header("Location: login.php");
    exit();
}

// Mostrar mensaje de éxito si existe
$mensaje_exito = '';
if (isset($_SESSION['mensaje_exito'])) {
    $mensaje_exito = $_SESSION['mensaje_exito'];
    unset($_SESSION['mensaje_exito']); // Limpiar el mensaje después de mostrarlo
}

// Manejar mensajes de éxito/error
$mensaje_exito = '';
$mensaje_error = '';
if (isset($_SESSION['mensaje_exito'])) {
    $mensaje_exito = $_SESSION['mensaje_exito'];
    unset($_SESSION['mensaje_exito']);
}
if (isset($_SESSION['mensaje_error'])) {
    $mensaje_error = $_SESSION['mensaje_error'];
    unset($_SESSION['mensaje_error']);
}

$id_doctor = $_SESSION['id_usuario'];

try {
    // Obtener nombre del doctor
    $sql_doctor = "SELECT nombre FROM Usuario WHERE id_usuario = :id_doctor";
    $stmt_doctor = $conexion->prepare($sql_doctor);
    $stmt_doctor->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
    $stmt_doctor->execute();
    $doctor = $stmt_doctor->fetch(PDO::FETCH_ASSOC);

    if (!$doctor) {
        throw new Exception("No se encontró información del doctor");
    }

    // Obtener los días ya registrados
    $sql_dias_registrados = "SELECT dia FROM HorarioDoctor WHERE id_doctor = :id_doctor";
    $stmt_dias = $conexion->prepare($sql_dias_registrados);
    $stmt_dias->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
    $stmt_dias->execute();
    $dias_registrados = $stmt_dias->fetchAll(PDO::FETCH_COLUMN, 0);

    // Obtener horarios guardados por el doctor
    $sql_horarios = "SELECT * FROM HorarioDoctor WHERE id_doctor = :id_doctor";
    $stmt_horarios = $conexion->prepare($sql_horarios);
    $stmt_horarios->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
    $stmt_horarios->execute();
    $horarios = $stmt_horarios->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Configurar Horario - Portal del Doctor</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .confirmation-buttons {
            display: none;
        }
        .show-confirmation .confirmation-buttons {
            display: inline-flex;
        }
        .show-confirmation .delete-button {
            display: none;
        }
    </style>
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
                <a href="View_horario.php" class="text-blue-500 font-medium">Agendar</a>
                <a href="View_citas.php" class="text-gray-600 hover:text-blue-500">Citas</a>
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
    <main class="container mx-auto px-4 py-8 space-y-8">
        <!-- Mostrar mensaje de éxito si existe -->
        <?php if (!empty($mensaje_exito)): ?>
            <div id="autoDismissAlert" class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded max-w-sm">
                <div class="flex items-center">
                    <div class="py-1">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                    </div>
                    <div>
                        <span class="block sm:inline"><?php echo htmlspecialchars($mensaje_exito); ?></span>
                    </div>
                    <div class="ml-4">
                        <button onclick="document.getElementById('autoDismissAlert').remove()" class="text-green-700 hover:text-green-900">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Formulario para agregar horario -->
        <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Configurar Horario de Atención</h2>
            
            <!-- Mostrar mensajes -->
            <?php if (!empty($mensaje_error)): ?>
                <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-700 rounded">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($mensaje_error); ?>
                </div>
            <?php endif; ?>
            
            <form id="scheduleForm" class="space-y-4" action="guardar_horario.php" method="POST">
                <!-- Campos del formulario -->
                <div>
                    <label for="dayOfWeek" class="block text-sm font-medium text-gray-700 mb-1">Día de la semana:</label>
                    <select id="dayOfWeek" name="dayOfWeek" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="" selected disabled>Seleccione un día</option>
                        <?php 
                        $dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'];
                        foreach ($dias_semana as $dia): 
                            $disabled = in_array($dia, $dias_registrados) ? 'disabled class="bg-gray-200 text-gray-500"' : '';
                        ?>
                            <option value="<?= $dia ?>" <?= $disabled ?>><?= $dia ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="startTime" class="block text-sm font-medium text-gray-700 mb-1">Hora de inicio:</label>
                        <input type="text" id="startTime" name="startTime" placeholder="HH:MM AM/PM" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="endTime" class="block text-sm font-medium text-gray-700 mb-1">Hora de fin:</label>
                        <input type="text" id="endTime" name="endTime" placeholder="HH:MM AM/PM" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex justify-end pt-4">
                    <button type="submit" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        Guardar Horario
                    </button>
                </div>
            </form>
        </div>

        <!-- Modal de éxito -->
        <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
            <div class="bg-white rounded-lg p-6 max-w-sm w-full">
                <div class="text-center">
                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                        <i class="fas fa-check text-green-600"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mt-3">¡Horario Guardado!</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">El horario ha sido configurado exitosamente.</p>
                    </div>
                    <div class="mt-4">
                        <button type="button" onclick="cerrarModalYRecargar()" 
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                            Aceptar
                        </button>
                    </div>
                </div>
            </div>
        </div>
         <!-- Tabla de horarios existentes -->
         <div class="max-w-4xl mx-auto bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Mi Horario de Atención</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Día</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hora Inicio</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hora Fin</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="scheduleTableBody" class="bg-white divide-y divide-gray-200">
                        <?php if (empty($horarios)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    No has configurado horarios.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($horarios as $horario): ?>
                            <tr id="row-<?= $horario['id_horario'] ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($horario['dia']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date("g:i A", strtotime($horario['hora_inicio'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date("g:i A", strtotime($horario['hora_fin'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <button onclick="editarHorario(<?= $horario['id_horario'] ?>)" 
                                            class="px-3 py-1 bg-blue-500 text-white rounded-md hover:bg-blue-600 mr-2">
                                        Editar
                                    </button>
                                    <button id="delete-<?= $horario['id_horario'] ?>" 
                                            class="delete-button px-3 py-1 bg-red-500 text-white rounded-md hover:bg-red-600"
                                            onclick="mostrarConfirmacionEliminar(<?= $horario['id_horario'] ?>)">
                                        Eliminar
                                    </button>
                                    <div id="confirm-<?= $horario['id_horario'] ?>" class="confirmation-buttons">
                                        <span class="mr-2 text-sm">¿Eliminar?</span>
                                        <button onclick="confirmarEliminacion(<?= $horario['id_horario'] ?>)" 
                                                class="px-2 py-1 bg-red-600 text-white rounded-md hover:bg-red-700 mr-1">
                                            Sí
                                        </button>
                                        <button onclick="cancelarEliminacion(<?= $horario['id_horario'] ?>)" 
                                                class="px-2 py-1 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                                            No
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal de éxito -->
    <div id="successModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-6 max-w-sm w-full">
            <div class="text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <i class="fas fa-check text-green-600"></i>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mt-3" id="modalTitle">¡Operación Exitosa!</h3>
                <div class="mt-2">
                    <p class="text-sm text-gray-500" id="modalMessage"></p>
                </div>
                <div class="mt-4">
                    <button type="button" onclick="cerrarModalYRecargar()" class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                        Aceptar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Función para cerrar el modal y recargar la página
        function cerrarModalYRecargar() {
            document.getElementById('successModal').classList.add('hidden');
            window.location.reload();
        }

        // Función para validar formato de hora AM/PM
        function isValidTimeFormat(time) {
            return /^(0?[1-9]|1[0-2]):[0-5][0-9] (AM|PM)$/i.test(time);
        }

        // Función para convertir AM/PM a formato 24 horas (HH:MM:SS)
        function convertTo24Hour(time) {
            if (!isValidTimeFormat(time)) return time;
            
            const [timePart, period] = time.split(' ');
            let [hours, minutes] = timePart.split(':').map(Number);

            if (period === 'PM' && hours !== 12) hours += 12;
            if (period === 'AM' && hours === 12) hours = 0;

            return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:00`;
        }

        // Función para validar horario (acepta horarios nocturnos)
        function isValidSchedule(startTime, endTime) {
            const startMinutes = timeToMinutes(startTime);
            const endMinutes = timeToMinutes(endTime);

            // Horario normal (mismo día)
            if (startMinutes < endMinutes) return true;

            // Horario nocturno (cruza medianoche)
            if (startMinutes >= 12 * 60 && endMinutes <= 12 * 60) return true;

            return false;
        }

        // Función para convertir AM/PM a minutos desde medianoche
        function timeToMinutes(time) {
            if (!isValidTimeFormat(time)) return 0;

            const [timePart, period] = time.split(' ');
            let [hours, minutes] = timePart.split(':').map(Number);

            if (period === 'PM' && hours !== 12) hours += 12;
            if (period === 'AM' && hours === 12) hours = 0;

            return hours * 60 + minutes;
        }

        // Función para mostrar confirmación de eliminación
        function mostrarConfirmacionEliminar(id_horario) {
            const deleteButton = document.getElementById(`delete-${id_horario}`);
            const confirmDiv = document.getElementById(`confirm-${id_horario}`);
            
            deleteButton.classList.add('hidden');
            confirmDiv.style.display = 'inline-flex';
        }

        // Función para cancelar eliminación
        function cancelarEliminacion(id_horario) {
            const deleteButton = document.getElementById(`delete-${id_horario}`);
            const confirmDiv = document.getElementById(`confirm-${id_horario}`);
            
            deleteButton.classList.remove('hidden');
            confirmDiv.style.display = 'none';
        }

        // Función para confirmar eliminación
        function confirmarEliminacion(id_horario) {
            fetch(`eliminar_horario.php?id=${id_horario}`, {
                method: 'DELETE'
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.status === 'success') {
                    // Eliminar la fila de la tabla
                    document.getElementById(`row-${id_horario}`).remove();
                    
                    // Mostrar mensaje de éxito
                    const modal = document.getElementById('successModal');
                    const modalMessage = document.getElementById('modalMessage');
                    
                    modalMessage.textContent = data.message;
                    modal.classList.remove('hidden');
                    
                    // Verificar si no quedan horarios
                    if (document.querySelectorAll('#scheduleTableBody tr').length === 0) {
                        const tbody = document.getElementById('scheduleTableBody');
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="4" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                    No has configurado horarios.
                                </td>
                            </tr>
                        `;
                    }
                } else {
                    alert(data.message);
                    cancelarEliminacion(id_horario);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ocurrió un error al eliminar el horario');
                cancelarEliminacion(id_horario);
            });
        }

        // Función para editar horario
        function editarHorario(id_horario) {
            const nuevoInicio = prompt("Ingresa la nueva hora de inicio (HH:MM AM/PM):");
            const nuevoFin = prompt("Ingresa la nueva hora de fin (HH:MM AM/PM):");

            if (nuevoInicio && nuevoFin) {
                if (!isValidTimeFormat(nuevoInicio) || !isValidTimeFormat(nuevoFin)) {
                    alert("Formato de hora inválido. Usa HH:MM AM/PM (ej. 7:00 PM)");
                    return;
                }

                if (!isValidSchedule(nuevoInicio, nuevoFin)) {
                    alert("Horario no válido. La hora de fin debe ser posterior a la de inicio.");
                    return;
                }

                // Convertir a formato 24 horas para la base de datos
                const hora_inicio = convertTo24Hour(nuevoInicio);
                const hora_fin = convertTo24Hour(nuevoFin);

                fetch(`editar_horario.php?id=${id_horario}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        hora_inicio: hora_inicio,
                        hora_fin: hora_fin
                    })
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Error en la respuesta del servidor');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.status === 'success') {
                        mostrarMensajeExito(data.message);
                        // Recargar la página después de 1 segundo
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ocurrió un error al actualizar el horario');
                });
            }
        }

        // Función para mostrar mensaje de éxito
        function mostrarMensajeExito(mensaje) {
            const modal = document.getElementById('successModal');
            const modalMessage = document.getElementById('modalMessage');

            modalMessage.textContent = mensaje;
            modal.classList.remove('hidden');
        }

        // Validación del formulario
document.getElementById('scheduleForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const dayOfWeek = document.getElementById('dayOfWeek').value;
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;
    
    if (!dayOfWeek || !startTime || !endTime) {
        alert('Todos los campos son obligatorios');
        return;
    }
    
    if (!isValidTimeFormat(startTime) || !isValidTimeFormat(endTime)) {
        alert('Formato de hora inválido. Usa HH:MM AM/PM (ej. 9:00 AM)');
        return;
    }
    
    // Validación mejorada para horarios nocturnos
    const startMinutes = timeToMinutes(startTime);
    const endMinutes = timeToMinutes(endTime);
    
    // Permitir horarios nocturnos (que cruzan medianoche)
    if (startMinutes >= endMinutes && !(startMinutes >= 12*60 && endMinutes <= 12*60)) {
        alert('Horario no válido. La hora de fin debe ser posterior a la de inicio, excepto para horarios nocturnos.');
        return;
    }
    
    // Si todo está bien, enviar el formulario
    this.submit();
});
    </script>
</body>
</html>