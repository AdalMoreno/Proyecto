// Funcionalidad para la foto del paciente
document.addEventListener('DOMContentLoaded', function() {
    const patientPhoto = document.getElementById('patientPhoto');
    const editPhotoBtn = document.getElementById('editPhotoBtn');
    const photoUpload = document.getElementById('photoUpload');
    const photoModal = document.getElementById('photoModal');
    const modalPhoto = document.getElementById('modalPhoto');
    const closeModalBtn = document.getElementById('closeModalBtn');
    const changePhotoBtn = document.getElementById('changePhotoBtn');
    const savePhotoBtn = document.getElementById('savePhotoBtn');

    // Cargar foto guardada al iniciar
    cargarFotoGuardada();

    // Mostrar modal al hacer clic en la foto
    patientPhoto.addEventListener('click', function(e) {
        if (e.target === patientPhoto) {
            modalPhoto.src = patientPhoto.src;
            photoModal.classList.remove('hidden');
        }
    });
    // Mostrar modal al hacer clic en la foto
    patientPhoto.addEventListener('click', function(e) {
        // Evitar que el clic se propague al contenedor
        if (e.target === patientPhoto) {
            modalPhoto.src = patientPhoto.src;
            photoModal.classList.remove('hidden');
        }
    });

    // Cerrar modal
    closeModalBtn.addEventListener('click', function() {
        photoModal.classList.add('hidden');
    });

    // Editar foto
    editPhotoBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        photoUpload.value = ''; // Resetear el input
        photoUpload.click();
    });

    // Cambiar foto desde el modal
    changePhotoBtn.addEventListener('click', function(e) {
        e.preventDefault();
        photoUpload.value = ''; // Resetear el input
        photoUpload.click();
    });

    // Manejar selección de nueva imagen
    photoUpload.addEventListener('change', function(e) {
        if (e.target.files && e.target.files[0]) {
            const reader = new FileReader();

            reader.onload = function(event) {
                patientPhoto.src = event.target.result;
                modalPhoto.src = event.target.result;
                savePhotoBtn.classList.remove('hidden');
            }

            reader.readAsDataURL(e.target.files[0]);
        }
    });

    savePhotoBtn.addEventListener('click', async() => {
        const formData = new FormData();
        formData.append('foto', photoUpload.files[0]);

        const response = await fetch('guardar_foto.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            patientPhoto.src = data.url;
            mostrarNotificacion('Foto guardada en el servidor');
        } else {
            mostrarNotificacion('Error: ' + data.error);
        }
    });

    // Cerrar modal al hacer clic fuera de la imagen
    photoModal.addEventListener('click', function(e) {
        if (e.target === photoModal) {
            photoModal.classList.add('hidden');
        }
    });
});

// Función para cargar foto guardada
async function cargarFotoGuardada() {
    try {
        const response = await fetch('obtener_foto.php');
        const data = await response.json();

        if (data.success && data.url) {
            document.getElementById('patientPhoto').src = data.url;
        }
    } catch (error) {
        console.error('Error al cargar la foto:', error);
    }
}

// Modificar la función de guardar para manejar la respuesta
savePhotoBtn.addEventListener('click', async() => {
    const formData = new FormData();
    formData.append('foto', photoUpload.files[0]);

    try {
        const response = await fetch('guardar_foto.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();

        if (data.success) {
            patientPhoto.src = data.url;
            mostrarNotificacion('Foto guardada correctamente');
            savePhotoBtn.classList.add('hidden');
        } else {
            mostrarNotificacion('Error: ' + data.error);
        }
    } catch (error) {
        mostrarNotificacion('Error al conectar con el servidor');
    }
});
// Datos de ejemplo
const historialMedico = [{
        fecha: '2023-10-15',
        medico: 'Dr. Pérez',
        diagnostico: 'Hipertensión arterial',
        tratamiento: 'Control periódico y medicación'
    },
    {
        fecha: '2023-08-22',
        medico: 'Dra. Gómez',
        diagnostico: 'Resfriado común',
        tratamiento: 'Reposo y antigripales'
    },
    {
        fecha: '2023-05-10',
        medico: 'Dr. Martínez',
        diagnostico: 'Dolor de cabeza crónico',
        tratamiento: 'Analgésicos y estudio neurológico'
    }
];

let citas = [{
        id: 1,
        fecha: '2023-12-10',
        hora: '10:30',
        medico: 'Dr. Pérez',
        motivo: 'Control de presión arterial',
        estado: 'confirmada'
    },
    {
        id: 2,
        fecha: '2023-12-15',
        hora: '16:00',
        medico: 'Dra. Gómez',
        motivo: 'Revisión anual',
        estado: 'pendiente'
    }
];

// Elementos del DOM
const navButtons = {
    historial: document.getElementById('historialBtn'),
    agendar: document.getElementById('agendarBtn'),
    citas: document.getElementById('citasBtn'),
    cancelar: document.getElementById('cancelarBtn')
};

const contentSections = {
    historial: document.getElementById('historialContent'),
    agendar: document.getElementById('agendarContent'),
    citas: document.getElementById('citasContent'),
    cancelar: document.getElementById('cancelarContent')
};

const notificacion = document.getElementById('notificaciones');
const notificacionTexto = document.getElementById('notificacionTexto');
const cerrarNotificacion = document.getElementById('cerrarNotificacion');

// Mostrar sección por defecto
function mostrarSeccion(seccion) {
    // Ocultar todas las secciones
    Object.values(contentSections).forEach(section => {
        section.classList.add('hidden');
    });

    // Mostrar la sección seleccionada
    contentSections[seccion].classList.remove('hidden');
}

// Cargar historial médico
function cargarHistorial() {
    const tabla = document.getElementById('historialTable');
    tabla.innerHTML = '';

    historialMedico.forEach(registro => {
        const fila = document.createElement('tr');
        fila.innerHTML = `
              <td class="py-2 px-4 border-b">${registro.fecha}</td>
              <td class="py-2 px-4 border-b">${registro.medico}</td>
              <td class="py-2 px-4 border-b">${registro.diagnostico}</td>
              <td class="py-2 px-4 border-b">${registro.tratamiento}</td>
          `;
        tabla.appendChild(fila);
    });
}

// Cargar citas
function cargarCitas() {
    const contenedor = document.getElementById('citasList');
    contenedor.innerHTML = '';

    citas.forEach(cita => {
        const card = document.createElement('div');
        card.className = 'cita-card bg-white p-4 rounded-lg shadow border-l-4 border-blue-500';
        card.innerHTML = `
              <div class="flex justify-between items-start">
                  <div>
                      <h3 class="font-semibold">${cita.medico}</h3>
                      <p class="text-gray-600">${cita.fecha} a las ${cita.hora}</p>
                      <p class="mt-2">${cita.motivo}</p>
                  </div>
                  <span class="estado-${cita.estado} font-medium">${cita.estado.toUpperCase()}</span>
              </div>
          `;
        contenedor.appendChild(card);
    });
}

// Cargar citas para cancelar
function cargarCitasParaCancelar() {
    const contenedor = document.getElementById('citasParaCancelar');
    contenedor.innerHTML = '';

    citas.filter(cita => cita.estado !== 'cancelada').forEach(cita => {
        const card = document.createElement('div');
        card.className = 'cita-card bg-white p-4 rounded-lg shadow';
        card.innerHTML = `
              <div class="flex justify-between items-center">
                  <div>
                      <h3 class="font-semibold">${cita.medico}</h3>
                      <p class="text-gray-600">${cita.fecha} a las ${cita.hora}</p>
                  </div>
                  <button onclick="cancelarCita(${cita.id})" class="btn-cancelar bg-red-500 text-white py-1 px-3 rounded hover:bg-red-600">
                      Cancelar
                  </button>
              </div>
          `;
        contenedor.appendChild(card);
    });
}

// Cancelar cita
function cancelarCita(id) {
    citas = citas.map(cita => {
        if (cita.id === id) {
            return {...cita, estado: 'cancelada' };
        }
        return cita;
    });

    mostrarNotificacion('Cita cancelada correctamente');
    cargarCitas();
    cargarCitasParaCancelar();
}

// Agendar nueva cita
document.getElementById('agendarForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const nuevaCita = {
        id: citas.length + 1,
        fecha: document.getElementById('fecha').value,
        hora: document.getElementById('hora').value,
        medico: document.getElementById('medico').value,
        motivo: document.getElementById('motivo').value,
        estado: 'pendiente'
    };

    citas.push(nuevaCita);
    this.reset();

    mostrarNotificacion('Cita agendada correctamente');
    cargarCitas();
    cargarCitasParaCancelar();
});

// Mostrar notificación
function mostrarNotificacion(mensaje) {
    notificacionTexto.textContent = mensaje;
    notificacion.classList.remove('hidden');

    // Ocultar después de 5 segundos
    setTimeout(() => {
        notificacion.classList.add('hidden');
    }, 5000);
}

// Event listeners
navButtons.historial.addEventListener('click', () => {
    mostrarSeccion('historial');
    cargarHistorial();
});

navButtons.agendar.addEventListener('click', () => {
    mostrarSeccion('agendar');
});

navButtons.citas.addEventListener('click', () => {
    mostrarSeccion('citas');
    cargarCitas();
});

navButtons.cancelar.addEventListener('click', () => {
    mostrarSeccion('cancelar');
    cargarCitasParaCancelar();
});

cerrarNotificacion.addEventListener('click', () => {
    notificacion.classList.add('hidden');
});

// Inicialización
mostrarSeccion('historial');
cargarHistorial();

// Mostrar notificación de próxima cita al cargar
window.addEventListener('DOMContentLoaded', () => {
    const proximaCita = citas.find(cita => cita.estado === 'confirmada');
    if (proximaCita) {
        mostrarNotificacion(`Tienes una cita confirmada con ${proximaCita.medico} el ${proximaCita.fecha} a las ${proximaCita.hora}`);
    }
});