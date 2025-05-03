document.addEventListener("DOMContentLoaded", function() {
    const registerPassword = document.getElementById("registerPassword");
    const strengthBar = document.getElementById("strengthBar");
    const strengthMessage = document.getElementById("strengthMessage");
    const passwordValidationMessage = document.getElementById("passwordValidationMessage");
    const registerButton = document.getElementById("registerButton");

    // Cambio entre Sign In y Sign Up usando jQuery
    $(".btnSign-in").click(function() {
        $(".container").addClass("active"); // Activa la transición
    });

    $(".btnSign-up").click(function() {
        $(".container").removeClass("active"); // Desactiva la transición
    });
    // Añadir evento para pantallas pequeñas
    function handleResize() {
        if (window.innerWidth <= 768) {
            container.classList.add('mobile-view')
        } else {
            container.classList.remove('mobile-view')
        }
    }

    // Ejecutar al cargar y al cambiar tamaño
    window.addEventListener('load', handleResize)
    window.addEventListener('resize', handleResize)

    // Función para evaluar la fortaleza de la contraseña
    function evaluatePasswordStrength(password) {
        const errors = [];
        let hasNumber = false;
        let hasSpecialChar = false;
        let hasUpperCase = false;
        let hasLowerCase = false;

        // Eliminar espacios en la contraseña
        const passwordWithoutSpaces = password.replace(/\s/g, '');

        // Validar longitud mínima (4 caracteres)
        if (passwordWithoutSpaces.length < 4) {
            errors.push("La contraseña debe tener al menos 4 caracteres.");
        }

        // Validar mayúsculas y minúsculas
        if (/[A-Z]/.test(passwordWithoutSpaces)) {
            hasUpperCase = true;
        }
        if (/[a-z]/.test(passwordWithoutSpaces)) {
            hasLowerCase = true;
        }

        // Validar números
        if (/[0-9]/.test(passwordWithoutSpaces)) {
            hasNumber = true;
        }

        // Validar caracteres especiales
        if (/[\W_]/.test(passwordWithoutSpaces)) {
            hasSpecialChar = true;
        }

        return { errors, hasNumber, hasSpecialChar, hasUpperCase, hasLowerCase };
    }

    // Función para actualizar la barra de seguridad y el mensaje
    function updatePasswordValidation(password) {
        const { errors, hasNumber, hasSpecialChar, hasUpperCase, hasLowerCase } = evaluatePasswordStrength(password);

        if (password.length === 0) {
            // Si el campo está vacío, ocultar la barra y el mensaje
            strengthBar.style.display = "none";
            strengthMessage.style.display = "none";
            registerButton.disabled = true; // Deshabilitar el botón de registro
        } else if (errors.length > 0) {
            strengthBar.style.display = "block";
            strengthMessage.style.display = "block";
            strengthBar.className = "strength-bar red";
            strengthMessage.textContent = "Débil";
            strengthMessage.style.color = "red";
            registerButton.disabled = true; // Deshabilitar el botón de registro
        } else if (hasUpperCase && hasLowerCase && hasNumber && hasSpecialChar) {
            strengthBar.style.display = "block";
            strengthMessage.style.display = "block";
            strengthBar.className = "strength-bar green";
            strengthMessage.textContent = "Seguro";
            strengthMessage.style.color = "green";
            registerButton.disabled = false; // Habilitar el botón de registro
        } else if (hasNumber || hasSpecialChar) {
            strengthBar.style.display = "block";
            strengthMessage.style.display = "block";
            strengthBar.className = "strength-bar orange";
            strengthMessage.textContent = "Aceptable";
            strengthMessage.style.color = "orange";
            registerButton.disabled = true; // Deshabilitar el botón de registro
        } else {
            strengthBar.style.display = "block";
            strengthMessage.style.display = "block";
            strengthBar.className = "strength-bar red";
            strengthMessage.textContent = "Débil";
            strengthMessage.style.color = "red";
            registerButton.disabled = true; // Deshabilitar el botón de registro
        }
    }

    loginPassword.addEventListener("input", () => {
        toggleEyeVisibility(loginPassword, "togglePassword"); // Controla la visibilidad del ojo en login
    });

    // Aplicamos la validación en el campo de contraseña de registro
    registerPassword.addEventListener("input", () => {
        updatePasswordValidation(registerPassword.value);
        toggleEyeVisibility(registerPassword, "toggleRegisterPassword"); // Controla la visibilidad del ojo en register
    });

    // Función para alternar la visibilidad de la contraseña de login
    const toggleLoginPassword = document.getElementById("togglePassword");
    toggleLoginPassword.addEventListener("click", function() {
        const type = loginPassword.type === "password" ? "text" : "password";
        loginPassword.type = type;
        // Cambia el icono de "ojo"
        this.classList.toggle("bi-eye");
        this.classList.toggle("bi-eye-slash");
    });

    // Función para alternar la visibilidad de la contraseña de registro
    const toggleRegisterPassword = document.getElementById("toggleRegisterPassword");
    toggleRegisterPassword.addEventListener("click", function() {
        const type = registerPassword.type === "password" ? "text" : "password";
        registerPassword.type = type;
        // Cambia el icono de "ojo"
        this.classList.toggle("bi-eye");
        this.classList.toggle("bi-eye-slash");
    });

    // Función para mostrar u ocultar el icono de ojo dependiendo de si el campo tiene texto
    function toggleEyeVisibility(passwordField, toggleButtonId) {
        const toggleButton = document.getElementById(toggleButtonId);
        if (passwordField.value.length > 0) {
            toggleButton.style.display = "inline"; // Muestra el ojo
        } else {
            toggleButton.style.display = "none"; // Oculta el ojo
        }
    }

    // Aplicamos la validación en el campo de contraseña de registro
    registerPassword.addEventListener("input", () => {
        toggleEyeVisibility(registerPassword, "toggleRegisterPassword"); // Controla la visibilidad del ojo en register
    });


});