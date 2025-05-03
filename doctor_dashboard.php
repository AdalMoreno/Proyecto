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

    // Obtener los días ya registrados
    $sql_dias_registrados = "SELECT dia FROM HorarioDoctor WHERE id_doctor = :id_doctor";
    $stmt_dias = $conexion->prepare($sql_dias_registrados);
    $stmt_dias->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
    $stmt_dias->execute();
    $dias_registrados = $stmt_dias->fetchAll(PDO::FETCH_COLUMN, 0);

    // Obtener citas médicas asignadas al doctor con ID del paciente
    $sql_citas = "SELECT c.id_cita, c.id_paciente, u.nombre AS paciente, c.fecha_cita, c.estado 
                  FROM Citas c
                  JOIN Usuario u ON c.id_paciente = u.id_usuario
                  WHERE c.id_doctor = :id_doctor";
    $stmt_citas = $conexion->prepare($sql_citas);
    $stmt_citas->bindParam(':id_doctor', $id_doctor, PDO::PARAM_INT);
    $stmt_citas->execute();
    $citas = $stmt_citas->fetchAll(PDO::FETCH_ASSOC);

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
    <title>Panel del Doctor</title>
    <link rel="stylesheet" href="doctor.css">
</head>
<body>
    <header>
        <h1>Bienvenido, Dr. <?php echo htmlspecialchars($doctor['nombre']); ?></h1>
        <button id="logout" onclick="window.location.href='cerrar.php'">Cerrar Sesión</button>
    </header>

    <main>
        <!-- Configurar Horario de Atención -->
        <section id="horario">
            <h2>Configurar Horario de Atención</h2>
            <form id="formHorario" action="guardar_horario.php" method="POST">
                <label for="dia">Día de la semana:</label>
                <select id="dia" name="dia" required>
                    <option value="" selected disabled>Seleccione un día</option>
                    <?php
                    $dias_semana = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"];
                    foreach ($dias_semana as $dia) {
                        $disabled = in_array($dia, $dias_registrados) ? 'disabled' : '';
                        echo "<option value='$dia' $disabled>$dia</option>";
                    }
                    ?>
                </select>

                <label for="horaInicio">Hora de inicio:</label>
                <input type="time" id="horaInicio" name="hora_inicio" required>

                <label for="horaFin">Hora de fin:</label>
                <input type="time" id="horaFin" name="hora_fin" required>

                <button type="submit" class="primary">Guardar Horario</button>
            </form>
        </section>

        <!-- Mostrar Horario en Tabla -->
        <section id="mostrarHorario">
            <h2>Mi Horario de Atención</h2>
            <table>
                <thead>
                    <tr>
                        <th>Día</th>
                        <th>Hora de Inicio</th>
                        <th>Hora de Fin</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($horarios as $horario): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($horario['dia']); ?></td>
                            <td><?php echo htmlspecialchars($horario['hora_inicio']); ?></td>
                            <td><?php echo htmlspecialchars($horario['hora_fin']); ?></td>
                            <td>
                                <button class="primary" onclick="editarHorario(<?php echo $horario['id_horario']; ?>)">Editar</button>
                                <button class="secondary" onclick="eliminarHorario(<?php echo $horario['id_horario']; ?>)">Eliminar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($horarios)): ?>
                        <tr>
                            <td colspan="4">No has configurado horarios.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- Mis Citas Médicas -->
        <section id="citas">
            <h2>Mis Citas Médicas</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID Paciente</th>
                        <th>Paciente</th>
                        <th>Fecha y Hora</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($citas as $cita): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($cita['id_paciente']); ?></td>
                            <td><?php echo htmlspecialchars($cita['paciente']); ?></td>
                            <td><?php echo htmlspecialchars($cita['fecha_cita']); ?></td>
                            <td>
                                <select id="estado_<?php echo $cita['id_cita']; ?>" onchange="cambiarEstado(<?php echo $cita['id_cita']; ?>)">
                                    <option value="Pendiente" <?php echo ($cita['estado'] == 'Pendiente') ? 'selected' : ''; ?>>Pendiente</option>
                                    <option value="Confirmada" <?php echo ($cita['estado'] == 'Confirmada') ? 'selected' : ''; ?>>Confirmada</option>
                                    <option value="Cancelada" <?php echo ($cita['estado'] == 'Cancelada') ? 'selected' : ''; ?>>Cancelada</option>
                                    <option value="Completada" <?php echo ($cita['estado'] == 'Completada') ? 'selected' : ''; ?>>Completada</option>
                                </select>
                            </td>
                            <td>
                                <button class="secondary" onclick="eliminarCita(<?php echo $cita['id_cita']; ?>)">Eliminar</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($citas)): ?>
                        <tr>
                            <td colspan="5">No tienes citas programadas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        <!-- Registro de Notas Médicas -->
        <section id="notas">
            <h2>Registro de Notas Médicas</h2>
            <form id="formNotas">
                <label for="id_paciente">ID del Paciente:</label>
                <input type="number" id="id_paciente" name="id_paciente" placeholder="Ingrese el ID del paciente" required>

                <label for="diagnostico">Diagnóstico:</label>
                <textarea id="diagnostico" name="diagnostico" placeholder="Escribe el diagnóstico aquí..." required></textarea>

                <label for="tratamiento">Tratamiento:</label>
                <textarea id="tratamiento" name="tratamiento" placeholder="Escribe el tratamiento aquí..." required></textarea>

                <button type="button" onclick="guardarNota()" class="primary">Guardar Nota</button>
            </form>
        </section>
    </main>
    <script>
        // Función para eliminar horario
         function eliminarHorario(id_horario) {
            if (confirm("¿Estás seguro de eliminar este horario?")) {
                fetch(`eliminar_horario.php?id=${id_horario}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }

        // Función para editar horario
        function editarHorario(id_horario) {
            const nuevoInicio = prompt("Ingresa la nueva hora de inicio (HH:MM):");
            const nuevoFin = prompt("Ingresa la nueva hora de fin (HH:MM):");

            if (nuevoInicio && nuevoFin) {
                fetch(`editar_horario.php?id=${id_horario}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        hora_inicio: nuevoInicio,
                        hora_fin: nuevoFin
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }
        // Función para cambiar el estado de la cita
        function cambiarEstado(id_cita) {
            const nuevoEstado = document.getElementById(`estado_${id_cita}`).value;

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
                    alert(data.message);
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Función para eliminar una cita
        function eliminarCita(id_cita) {
            if (confirm("¿Estás seguro de eliminar esta cita?")) {
                fetch(`eliminar_cita.php?id=${id_cita}`, {
                    method: 'DELETE'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
        }
       // Función para guardar una nota médica
        function guardarNota() {
            const id_paciente = document.getElementById('id_paciente').value;
            const diagnostico = document.getElementById('diagnostico').value;
            const tratamiento = document.getElementById('tratamiento').value;

            // Verificar que todos los campos estén llenos
            if (!id_paciente || !diagnostico || !tratamiento) {
                alert('Por favor, completa todos los campos.');
                return;
            }

            // Crear el objeto con los datos de la nota médica
            const notaData = {
                id_paciente: id_paciente,
                diagnostico: diagnostico,
                tratamiento: tratamiento
            };

            // Enviar la solicitud al servidor
            fetch('guardar_historial.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(notaData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    alert(data.message);
                    location.reload(); // Recargar la página después de guardar
                } else {
                    alert(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Hubo un error al guardar la nota médica.');
            });
        }
    </script>
</body>
</html>
