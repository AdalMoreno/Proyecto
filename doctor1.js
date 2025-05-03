// Shared functions for the doctor's portal

/**
 * Saves an appointment to localStorage
 * @param {Object} appointmentData - The appointment data to save
 */
function saveAppointment(appointmentData) {
    let appointments = JSON.parse(localStorage.getItem('doctorAppointments')) || [];
    appointmentData.id = appointments.length > 0 ? Math.max(...appointments.map(a => a.id)) + 1 : 1;
    appointments.push(appointmentData);
    localStorage.setItem('doctorAppointments', JSON.stringify(appointments));
}

/**
 * Gets all appointments from localStorage
 * @returns {Array} Array of appointments
 */
function getAppointments() {
    return JSON.parse(localStorage.getItem('doctorAppointments')) || [];
}

/**
 * Updates an existing appointment in localStorage
 * @param {Number} id - The appointment ID
 * @param {Object} updatedData - The updated appointment data
 */
function updateAppointment(id, updatedData) {
    let appointments = getAppointments();
    const index = appointments.findIndex(a => a.id === id);
    if (index !== -1) {
        appointments[index] = {...appointments[index], ...updatedData };
        localStorage.setItem('doctorAppointments', JSON.stringify(appointments));
    }
}

/**
 * Deletes an appointment from localStorage
 * @param {Number} id - The appointment ID to delete
 */
function deleteAppointment(id) {
    let appointments = getAppointments().filter(a => a.id !== id);
    localStorage.setItem('doctorAppointments', JSON.stringify(appointments));
}

/**
 * Saves a patient note to localStorage
 * @param {Object} noteData - The note data to save
 */
function saveNote(noteData) {
    let notes = JSON.parse(localStorage.getItem('doctorNotes')) || [];
    noteData.id = notes.length > 0 ? Math.max(...notes.map(n => n.id)) + 1 : 1;
    noteData.date = new Date().toISOString();
    notes.push(noteData);
    localStorage.setItem('doctorNotes', JSON.stringify(notes));
}

/**
 * Gets all notes from localStorage
 * @returns {Array} Array of notes
 */
function getNotes() {
    return JSON.parse(localStorage.getItem('doctorNotes')) || [];
}

/**
 * Deletes a note from localStorage
 * @param {Number} id - The note ID to delete
 */
function deleteNote(id) {
    let notes = getNotes().filter(n => n.id !== id);
    localStorage.setItem('doctorNotes', JSON.stringify(notes));
}

/**
 * Formats a date string to a readable format
 * @param {String} dateString - ISO date string
 * @returns {String} Formatted date string
 */
function formatDate(dateString) {
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    return new Date(dateString).toLocaleDateString('es-ES', options);
}

/**
 * Shows a toast notification
 * @param {String} message - The message to display
 * @param {String} type - The type of notification (success, error, warning)
 */
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-4 py-2 rounded-md text-white ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        'bg-yellow-500'
    }`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Initialize localStorage if empty
if (!localStorage.getItem('doctorAppointments')) {
    localStorage.setItem('doctorAppointments', JSON.stringify([]));
}

if (!localStorage.getItem('doctorNotes')) {
    localStorage.setItem('doctorNotes', JSON.stringify([]));
}