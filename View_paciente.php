<?php
    session_start();
    include 'db.php';

    // Verificar si el usuario está autenticado y es paciente
    if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo'] != 'Paciente') {
        header("Location: login.php");
        exit();
    }

    $id_paciente = $_SESSION['id_usuario'];

    try {
        // Obtener nombre del paciente usando PDO
        $sql_paciente = "SELECT nombre FROM Usuario WHERE id_usuario = :id_paciente";
        $stmt_paciente = $conexion->prepare($sql_paciente);
        $stmt_paciente->bindParam(':id_paciente', $id_paciente, PDO::PARAM_INT);
        $stmt_paciente->execute();
        $paciente = $stmt_paciente->fetch(PDO::FETCH_ASSOC);

        if (!$paciente) {
            throw new Exception("No se encontró información del paciente");
        }

        // Obtener citas médicas del paciente
        $sql_citas = "SELECT c.id_cita, d.especialidad, u.nombre AS doctor, c.fecha_cita 
                      FROM Citas c
                      JOIN Doctor d ON c.id_doctor = d.id_doctor
                      JOIN Usuario u ON d.id_doctor = u.id_usuario
                      WHERE c.id_paciente = :id_paciente";
        $stmt_citas = $conexion->prepare($sql_citas);
        $stmt_citas->bindParam(':id_paciente', $id_paciente, PDO::PARAM_INT);
        $stmt_citas->execute();
        $citas = $stmt_citas->fetchAll(PDO::FETCH_ASSOC);

        // Obtener los horarios de los doctores
        $sql_doctores = "SELECT d.id_doctor, d.especialidad, u.nombre, h.dia, h.hora_inicio, h.hora_fin 
                         FROM Doctor d
                         JOIN Usuario u ON d.id_doctor = u.id_usuario
                         LEFT JOIN HorarioDoctor h ON d.id_doctor = h.id_doctor";
        $stmt_doctores = $conexion->query($sql_doctores);
        $doctores = [];
        while ($doctor = $stmt_doctores->fetch(PDO::FETCH_ASSOC)) {
            $doctores[$doctor['id_doctor']]['info'] = [
                'nombre' => $doctor['nombre'],
                'especialidad' => $doctor['especialidad']
            ];
            if ($doctor['dia']) {
                $doctores[$doctor['id_doctor']]['horarios'][] = [
                    'dia' => $doctor['dia'],
                    'hora_inicio' => $doctor['hora_inicio'],
                    'hora_fin' => $doctor['hora_fin']
                ];
            }
        }

        // Obtener historial médico del paciente
        $sql_historial = "SELECT * FROM HistorialMedico 
                          WHERE id_paciente = :id_paciente 
                          ORDER BY fecha_registro DESC";
        $stmt_historial = $conexion->prepare($sql_historial);
        $stmt_historial->bindParam(':id_paciente', $id_paciente, PDO::PARAM_INT);
        $stmt_historial->execute();
        $historial = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Panel de Control del Paciente</title>
    <!-- Using Tailwind CSS via CDN for development only -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="pacient.css">
</head>

<body class="font-['Roboto'] bg-gray-100">

        <div class="min-h-screen flex flex-col md:flex-row">
            <!-- Sidebar -->
            <div class="bg-blue-700 text-white w-full md:w-64 p-4">
                <div class="flex flex-col items-center mb-8">
                   <!-- Foto del paciente con funcionalidad editable -->
                    <div class="relative">
                        <img id="patientPhoto" src="<?php echo !empty($paciente['foto']) ? 'fotos_pacientes/' . htmlspecialchars($paciente['foto']) : 'https://via.placeholder.com/200'; ?>" 
                            alt="Foto del paciente" class="w-32 h-32 rounded-full object-cover cursor-pointer hover:opacity-80 transition">
                        <button id="editPhotoBtn" class="absolute -bottom-1 -right-1 bg-blue-600 text-white rounded-full p-2 hover:bg-blue-700 transition">
                            <i class="fas fa-pencil-alt"></i>
                        </button>
                        <input type="file" id="photoUpload" accept="image/*" class="hidden">
                    </div>

                    <!-- Modal para imagen ampliada -->
                    <div id="photoModal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
                        <div class="bg-white rounded-lg p-6 max-w-md w-full">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-xl font-semibold">Foto del paciente</h3>
                                <button id="closeModalBtn" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                      </button>
                            </div>
                            <img id="modalPhoto" src="" alt="Foto ampliada" class="w-full rounded mb-4">
                            <div class="flex space-x-2">
                                <button id="changePhotoBtn" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                        <i class="fas fa-upload mr-2"></i>Cambiar
                      </button>
                      <button id="savePhotoBtn" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        <i class="fas fa-save mr-2"></i>Guardar
                    </button>
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <h2 class="text-xl font-semibold text-white">
                            <?php echo htmlspecialchars($paciente['nombre']); ?>
                        </h2>
                        <p class="text-blue-200">ID:
                            <?php echo htmlspecialchars($id_paciente); ?>
                        </p>
                    </div>
                </div>

                <nav>
                    <ul class="space-y-2">
                        <li>
                            <a href="#" class="flex items-center space-x-2 p-2 rounded hover:bg-blue-600" id="historialBtn">
                                <i class="fas fa-history"></i>
                                <span>Historial Médico</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="flex items-center space-x-2 p-2 rounded hover:bg-blue-600" id="agendarBtn">
                                <i class="fas fa-calendar-plus"></i>
                                <span>Agendar Cita</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="flex items-center space-x-2 p-2 rounded hover:bg-blue-600" id="citasBtn">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Mis Citas</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="flex items-center space-x-2 p-2 rounded hover:bg-blue-600" id="cancelarBtn">
                                <i class="fas fa-calendar-times"></i>
                                <span>Cancelar Cita</span>
                            </a>
                        </li>
                        <li>
                            <a href="cerrar.php" class="flex items-center space-x-2 p-2 rounded hover:bg-blue-600">
                                <i class="fas fa-sign-out-alt"></i>
                                <span>Cerrar Sesión</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="flex-1 p-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h1 class="text-2xl font-bold text-gray-800 mb-6">Panel de Control del Paciente</h1>

                    <!-- Notification Area -->
                    <div id="notificaciones" class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 mb-6 hidden">
                        <div class="flex justify-between items-center">
                            <p id="notificacionTexto"></p>
                            <button id="cerrarNotificacion" class="text-yellow-700 hover:text-yellow-900">
                            <i class="fas fa-times"></i>
                        </button>
                        </div>
                    </div>

                    <!-- Content Sections -->
                    <div id="historialContent">
                        <h2 class="text-xl font-semibold mb-4">Historial Médico</h2>
                        <?php if (!empty($historial)): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full bg-white">
                                <thead>
                                    <tr class="bg-gray-200 text-gray-700">
                                        <th class="py-2 px-4">Fecha</th>
                                        <th class="py-2 px-4">Diagnóstico</th>
                                        <th class="py-2 px-4">Tratamiento</th>
                                        <th class="py-2 px-4">Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody id="historialTable">
                                    <?php foreach ($historial as $registro): ?>
                                    <tr class="border-b">
                                        <td class="py-2 px-4">
                                            <?php echo htmlspecialchars($registro['fecha_registro']); ?>
                                        </td>
                                        <td class="py-2 px-4">
                                            <?php echo htmlspecialchars($registro['diagnostico']); ?>
                                        </td>
                                        <td class="py-2 px-4">
                                            <?php echo htmlspecialchars($registro['tratamiento']); ?>
                                        </td>
                                        <td class="py-2 px-4">
                                            <?php echo htmlspecialchars($registro['observaciones']); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-gray-600">No tienes historial médico registrado.</p>
                        <?php endif; ?>
                    </div>

                    <div id="agendarContent" class="hidden">
                        <h2 class="text-xl font-semibold mb-6 text-gray-800">Agendar Nueva Cita</h2>
                        
                        <form id="agendarForm" class="space-y-6">
                            <div class="space-y-2">
                                <label for="id_doctor" class="block text-gray-700 font-medium">Seleccione su médico:</label>
                                <select id="id_doctor" name="id_doctor" class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-blue-500 focus:border-blue-500 transition duration-150" required>
                                    <option value="" disabled selected>-- Seleccione un médico --</option>
                                    <?php foreach ($doctores as $id_doctor => $doctor): ?>
                                        <option value="<?php echo htmlspecialchars($id_doctor); ?>">
                                            Dr. <?php echo htmlspecialchars($doctor['info']['nombre']); ?> - <?php echo htmlspecialchars($doctor['info']['especialidad']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 shadow-sm">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                                    </div>
                                    <div class="ml-3">
                                        <h3 class="text-sm font-medium text-blue-800">Cómo funciona el agendamiento</h3>
                                        <div class="mt-1 text-sm text-blue-700">
                                            <p>El sistema asignará automáticamente la próxima cita disponible con el médico seleccionado.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg shadow-md transition duration-200 flex items-center justify-center">
                                <i class="fas fa-calendar-check mr-2"></i> Agendar Cita Automáticamente
                            </button>
                        </form>
                    </div>

                    <div id="citasContent" class="hidden">
                        <h2 class="text-xl font-semibold mb-4">Mis Citas</h2>
                        <?php if (!empty($citas)): ?>
                        <div class="space-y-4">
                            <?php foreach ($citas as $cita): ?>
                            <div class="border rounded-lg p-4">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <h3 class="font-semibold">
                                            <?php echo htmlspecialchars($cita['especialidad']); ?>
                                        </h3>
                                        <p class="text-gray-600">Dr.
                                            <?php echo htmlspecialchars($cita['doctor']); ?>
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <p class="font-medium">
                                            <?php echo htmlspecialchars($cita['fecha_cita']); ?>
                                        </p>
                                        <p class="text-sm text-gray-500">ID:
                                            <?php echo htmlspecialchars($cita['id_cita']); ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-gray-600">No tienes citas programadas.</p>
                        <?php endif; ?>
                    </div>

                    <div id="cancelarContent" class="hidden">
                        <h2 class="text-xl font-semibold mb-4">Cancelar Cita</h2>
                        <?php if (!empty($citas)): ?>
                        <div class="space-y-4">
                            <?php foreach ($citas as $cita): ?>
                            <div class="border rounded-lg p-4 flex justify-between items-center">
                                <div>
                                    <h3 class="font-semibold">
                                        <?php echo htmlspecialchars($cita['especialidad']); ?>
                                    </h3>
                                    <p class="text-gray-600">Dr.
                                        <?php echo htmlspecialchars($cita['doctor']); ?>
                                    </p>
                                    <p class="text-sm">
                                        <?php echo htmlspecialchars($cita['fecha_cita']); ?>
                                    </p>
                                </div>
                                <button onclick="cancelarCita(<?php echo htmlspecialchars($cita['id_cita']); ?>)" class="bg-red-600 text-white py-1 px-3 rounded hover:bg-red-700">
                                        Cancelar
                                    </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <p class="text-gray-600">No tienes citas para cancelar.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Mostrar secciones
            document.getElementById('historialBtn').addEventListener('click', function(e) {
                e.preventDefault();
                hideAllSections();
                document.getElementById('historialContent').classList.remove('hidden');
            });

            document.getElementById('agendarBtn').addEventListener('click', function(e) {
                e.preventDefault();
                hideAllSections();
                document.getElementById('agendarContent').classList.remove('hidden');
            });

            document.getElementById('citasBtn').addEventListener('click', function(e) {
                e.preventDefault();
                hideAllSections();
                document.getElementById('citasContent').classList.remove('hidden');
            });

            document.getElementById('cancelarBtn').addEventListener('click', function(e) {
                e.preventDefault();
                hideAllSections();
                document.getElementById('cancelarContent').classList.remove('hidden');
            });

            function hideAllSections() {
                document.getElementById('historialContent').classList.add('hidden');
                document.getElementById('agendarContent').classList.add('hidden');
                document.getElementById('citasContent').classList.add('hidden');
                document.getElementById('cancelarContent').classList.add('hidden');
            }

            // Mostrar historial por defecto
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('historialContent').classList.remove('hidden');
            });

            function agendarCita(event) {
                event.preventDefault();
                
                const form = document.getElementById('agendarForm');
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalBtnText = submitBtn.innerHTML;
                
                // Mostrar estado de carga
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Buscando disponibilidad...';
                submitBtn.disabled = true;
                
                fetch('guardar_cita.php', {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(response => {
                    if (!response.ok) throw new Error('Error en la respuesta');
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showNotification(`<strong>Cita agendada:</strong><br>${data.fecha_cita}`, 'success');
                        // Actualizar la lista de citas después de 3 segundos
                        setTimeout(() => {
                            document.getElementById('citasBtn').click();
                        }, 3000);
                    } else {
                        showNotification(data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showNotification("No se pudo completar la solicitud. Intente nuevamente.", 'error');
                })
                .finally(() => {
                    submitBtn.innerHTML = originalBtnText;
                    submitBtn.disabled = false;
                });
            }

            function cancelarCita(id_cita) {
                if (!confirm("¿Estás seguro de cancelar esta cita?")) {
                    return;
                }

                fetch('cancelar_cita.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            id_cita: id_cita
                        })
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error en la respuesta del servidor');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            showNotification("Cita cancelada con éxito");
                            // Recargar la página después de 2 segundos
                            setTimeout(() => location.reload(), 2000);
                        } else {
                            showNotification("Error: " + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification("Ocurrió un error al procesar tu solicitud. Por favor, intenta nuevamente.", 'error');
                    });
            }

            function showNotification(message, type = 'success') {
                const notification = document.getElementById('notificaciones');
                const notificationText = document.getElementById('notificacionTexto');

                notificationText.textContent = message;

                // Cambiar colores según el tipo
                if (type === 'error') {
                    notification.classList.remove('bg-yellow-100', 'border-yellow-500', 'text-yellow-700');
                    notification.classList.add('bg-red-100', 'border-red-500', 'text-red-700');
                } else {
                    notification.classList.remove('bg-red-100', 'border-red-500', 'text-red-700');
                    notification.classList.add('bg-yellow-100', 'border-yellow-500', 'text-yellow-700');
                }

                notification.classList.remove('hidden');

                // Cerrar automáticamente después de 5 segundos
                setTimeout(() => {
                    notification.classList.add('hidden');
                }, 5000);
            }

            // Cerrar notificación manualmente
            document.getElementById('cerrarNotificacion').addEventListener('click', function() {
                document.getElementById('notificaciones').classList.add('hidden');
            });

            // Funcionalidad para la foto de perfil (puedes implementarla según necesites)
            document.getElementById('editPhotoBtn').addEventListener('click', function() {
                document.getElementById('photoUpload').click();
            });

            document.getElementById('photoUpload').addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        document.getElementById('patientPhoto').src = event.target.result;
                        // Aquí podrías agregar lógica para guardar la foto
                    };
                    reader.readAsDataURL(e.target.files[0]);
                }
            });
           // Funcionalidad para la foto de perfil
            document.getElementById('editPhotoBtn').addEventListener('click', function() {
                document.getElementById('photoUpload').click();
            });

            document.getElementById('photoUpload').addEventListener('change', function(e) {
                if (e.target.files && e.target.files[0]) {
                    const file = e.target.files[0];
                    
                    // Validar el tipo de archivo
                    if (!file.type.match('image.*')) {
                        showNotification("Por favor, selecciona un archivo de imagen válido.", 'error');
                        return;
                    }
                    
                    // Validar el tamaño del archivo (máximo 2MB)
                    if (file.size > 2 * 1024 * 1024) {
                        showNotification("La imagen no debe superar los 2MB de tamaño.", 'error');
                        return;
                    }
                    
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        // Mostrar vista previa en el modal
                        const modalImg = document.getElementById('modalPhoto');
                        modalImg.src = event.target.result;
                        
                        // Mostrar el modal
                        const modal = document.getElementById('photoModal');
                        modal.classList.remove('hidden');
                        
                        // Mostrar el botón de guardar
                        document.getElementById('savePhotoBtn').classList.remove('hidden');
                        
                        // Configurar el botón de guardar
                        document.getElementById('savePhotoBtn').onclick = function() {
                            // Crear FormData para enviar la imagen
                            const formData = new FormData();
                            formData.append('foto', file);
                            
                            // Mostrar estado de carga
                            const saveBtn = document.getElementById('savePhotoBtn');
                            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...';
                            saveBtn.disabled = true;
                            
                            // Enviar la imagen al servidor
                            fetch('guardar_foto.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => {
                                if (!response.ok) throw new Error('Error en la respuesta');
                                return response.json();
                            })
                            .then(data => {
                                if (data.success) {
                                    showNotification("Foto actualizada correctamente", 'success');
                                    // Actualizar la foto en la página
                                    document.getElementById('patientPhoto').src = data.url;
                                    // Cerrar el modal
                                    modal.classList.add('hidden');
                                } else {
                                    throw new Error(data.error || 'Error desconocido');
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                showNotification("Error al actualizar la foto: " + error.message, 'error');
                            })
                            .finally(() => {
                                saveBtn.innerHTML = '<i class="fas fa-save mr-2"></i> Guardar';
                                saveBtn.disabled = false;
                                // Ocultar el botón de guardar después de guardar
                                saveBtn.classList.add('hidden');
                            });
                        };
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Modal para ver la foto en grande
            document.getElementById('patientPhoto').addEventListener('click', function() {
                const modal = document.getElementById('photoModal');
                const modalImg = document.getElementById('modalPhoto');
                
                modalImg.src = this.src;
                modal.classList.remove('hidden');
                // Ocultar el botón de guardar cuando solo se está viendo la foto
                document.getElementById('savePhotoBtn').classList.add('hidden');
            });

            document.getElementById('closeModalBtn').addEventListener('click', function() {
                document.getElementById('photoModal').classList.add('hidden');
                // Ocultar el botón de guardar al cerrar el modal
                document.getElementById('savePhotoBtn').classList.add('hidden');
            });

            document.getElementById('changePhotoBtn').addEventListener('click', function() {
                document.getElementById('photoUpload').click();
            });
        </script>

</body>

</html>