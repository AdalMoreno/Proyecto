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
    $sql_doctores = "SELECT d.id_doctor, d.especialidad, h.dia, h.hora_inicio, h.hora_fin 
                     FROM Doctor d
                     LEFT JOIN HorarioDoctor h ON d.id_doctor = h.id_doctor";
    $stmt_doctores = $conexion->query($sql_doctores);
    $doctores = [];
    while ($doctor = $stmt_doctores->fetch(PDO::FETCH_ASSOC)) {
        $doctores[$doctor['id_doctor']][] = $doctor;
    }

    // Obtener historial médico del paciente
    $sql_historial = "SELECT * FROM HistorialMedico 
                      WHERE id_paciente = :id_paciente 
                      ORDER BY fecha_registro DESC 
                      LIMIT 1";
    $stmt_historial = $conexion->prepare($sql_historial);
    $stmt_historial->bindParam(':id_paciente', $id_paciente, PDO::PARAM_INT);
    $stmt_historial->execute();
    $historial = $stmt_historial->fetch(PDO::FETCH_ASSOC);

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
    <title>Panel del Paciente</title>
    <link rel="stylesheet" href="paciente.css">
</head>
<body>
    <div class="header">
        <div>Bienvenido, <?php echo htmlspecialchars($paciente['nombre']); ?></div>
        <a href="cerrar.php" class="logout">Cerrar Sesión</a>
    </div>
    
    <div class="container">
        <div class="content">
            <div class="section" onclick="showSection('appointments')">
                <h3>📅 Ver Citas Médicas</h3>
                <p>Visualiza todas tus citas médicas programadas, incluyendo la fecha, hora, especialidad y el nombre del doctor asignado.</p>
            </div>
            
            <div class="section" onclick="showSection('schedule')">
                <h3>📝 Agendar Citas</h3>
                <p>Solicita y agenda nuevas citas médicas, eligiendo la especialidad, el doctor y la fecha disponible que más te convenga.</p>
            </div>
            
            <div class="section" onclick="showSection('medical-history')">
                <h3>📖 Ver Historial Médico</h3>
                <p>Accede a tu historial médico, incluyendo diagnósticos previos, tratamientos, medicamentos recetados y resultados de exámenes.</p>
            </div>
        </div>
        
        <div id="appointments" class="appointments hidden">
            <h3>📅 Tus Citas Médicas</h3>
            <ul>
                <?php foreach ($citas as $cita): ?>
                    <li>
                        <strong>Fecha:</strong> <?php echo htmlspecialchars($cita['fecha_cita']); ?> | 
                        <strong>Especialidad:</strong> <?php echo htmlspecialchars($cita['especialidad']); ?> | 
                        <strong>Doctor:</strong> <?php echo htmlspecialchars($cita['doctor']); ?>
                    </li>
                <?php endforeach; ?>
                <?php if (empty($citas)): ?>
                    <li>No tienes citas programadas.</li>
                <?php endif; ?>
            </ul>
        </div>
        
        <div id="schedule" class="schedule hidden">
            <h3>📝 Agendar una Nueva Cita</h3>
            <form id="citaForm" onsubmit="agendarCita(event)">
                <label for="id_doctor">Seleccionar Doctor:</label>
                <select name="id_doctor" id="id_doctor" required>
                    <option value="" disabled selected>Seleccione un doctor</option>
                    <?php foreach ($doctores as $id_doctor => $horarios): ?>
                        <option value="<?php echo htmlspecialchars($id_doctor); ?>">
                            <?php echo htmlspecialchars($horarios[0]['especialidad']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit">Agendar Cita</button>
            </form>
        </div>
        
        <div id="medical-history" class="medical-history hidden">
            <h3>📖 Tu Historial Médico</h3>
            <?php if ($historial): ?>
                <p>Última consulta: <?php echo htmlspecialchars($historial['fecha_registro']); ?> - 
                   Diagnóstico: <?php echo htmlspecialchars($historial['diagnostico']); ?></p>
            <?php else: ?>
                <p>No tienes historial médico registrado.</p>
            <?php endif; ?>
        </div>
    </div>

    <script>
        function showSection(sectionId) {
            var section = document.getElementById(sectionId);
            
            // Verifica si la sección ya está visible
            var isVisible = !section.classList.contains('hidden');

            // Oculta todas las secciones
            document.querySelectorAll('.appointments, .schedule, .medical-history').forEach(function(sec) {
                sec.classList.add('hidden');
            });

            // Si la sección no estaba visible, la muestra; si ya estaba visible, la oculta
            if (!isVisible) {
                section.classList.remove('hidden');
            }
        }
        function agendarCita(event) {
            event.preventDefault();
            
            if (!confirm("¿Estás seguro de agendar esta cita?")) {
                return;
            }
            
            const formData = new FormData(document.getElementById('citaForm'));
            
            fetch('guardar_cita.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Error en la respuesta del servidor');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert("Cita agendada con éxito para el día: " + data.fecha_cita);
                    location.reload(); // Recarga la página para mostrar la nueva cita
                } else {
                    alert("Error: " + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Ocurrió un error al procesar tu solicitud. Por favor, intenta nuevamente.");
            });
        }
    </script>
</body>
</html>