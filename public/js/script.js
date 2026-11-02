// public/js/script.js

// Afficher / Masquer le mot de passe
function togglePasswordVisibility(inputId, button) {
    const input = document.getElementById(inputId);
    if (!input) return;

    if (input.type === "password") {
        input.type = "text";
        button.textContent = "🙈";
    } else {
        input.type = "password";
        button.textContent = "👁";
    }
}

document.addEventListener("DOMContentLoaded", () => {
    // Expressions régulières
    const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;
    const uppercasePattern = /[A-Z]/;

    // Afficher une erreur sur un champ
    function showError(input, errorId, message) {
        const errorSpan = document.getElementById(errorId);
        input.classList.add("is-invalid");
        input.classList.remove("is-valid");
        if (errorSpan) errorSpan.textContent = message;
    }

    // Valider visuellement un champ
    function showValid(input, errorId) {
        const errorSpan = document.getElementById(errorId);
        input.classList.remove("is-invalid");
        input.classList.add("is-valid");
        if (errorSpan) errorSpan.textContent = "";
    }

    // 1. Vérification du Nom (> 3 caractères)
    function checkNom(input, errorId) {
        const val = input.value.trim();
        if (val === "") {
            showError(input, errorId, "Le nom complet est obligatoire.");
            return false;
        }
        if (val.length <= 3) {
            showError(input, errorId, "Le nom doit contenir plus de 3 caractères (minimum 4).");
            return false;
        }
        showValid(input, errorId);
        return true;
    }

    // 2. Vérification de la structure de l'Email
    function checkEmail(input, errorId) {
        const val = input.value.trim();
        if (val === "") {
            showError(input, errorId, "L'adresse email est obligatoire.");
            return false;
        }
        if (!emailPattern.test(val)) {
            showError(input, errorId, "Structure d'adresse email invalide (ex: nom@domaine.com).");
            return false;
        }
        showValid(input, errorId);
        return true;
    }

    // 3. Vérification du Mot de passe (>= 8 chars + 1 Majuscule)
    function checkPassword(input, errorId) {
        const val = input.value;
        if (val === "") {
            showError(input, errorId, "Le mot de passe est obligatoire.");
            return false;
        }
        if (val.length < 8) {
            showError(input, errorId, "Le mot de passe doit contenir au moins 8 caractères.");
            return false;
        }
        if (!uppercasePattern.test(val)) {
            showError(input, errorId, "Le mot de passe doit contenir au moins une lettre MAJUSCULE.");
            return false;
        }
        showValid(input, errorId);
        return true;
    }

    // =========================================================
    // FORMULAIRE 1 : SE CONNECTER
    // =========================================================
    const loginForm = document.getElementById("loginForm");
    if (loginForm) {
        const loginEmail = document.getElementById("login_email");
        const loginPassword = document.getElementById("login_password");

        loginEmail.addEventListener("input", () => checkEmail(loginEmail, "login_email_error"));
        loginPassword.addEventListener("input", () => checkPassword(loginPassword, "login_password_error"));

        loginForm.addEventListener("submit", (e) => {
            const validEmail = checkEmail(loginEmail, "login_email_error");
            const validPass = checkPassword(loginPassword, "login_password_error");

            if (!validEmail || !validPass) {
                e.preventDefault();
            }
        });
    }

    // =========================================================
    // FORMULAIRE 2 : CRÉER UN COMPTE (CLIENT UNIQUEMENT)
    // =========================================================
    const registerForm = document.getElementById("registerForm");
    if (registerForm) {
        const regNom = document.getElementById("reg_nom");
        const regEmail = document.getElementById("reg_email");
        const regPassword = document.getElementById("reg_password");
        const ruleLength = document.getElementById("rule_length");
        const ruleUpper = document.getElementById("rule_upper");

        regNom.addEventListener("input", () => checkNom(regNom, "reg_nom_error"));
        regEmail.addEventListener("input", () => checkEmail(regEmail, "reg_email_error"));

        regPassword.addEventListener("input", () => {
            const val = regPassword.value;
            if (ruleLength) ruleLength.classList.toggle("rule-ok", val.length >= 8);
            if (ruleUpper) ruleUpper.classList.toggle("rule-ok", uppercasePattern.test(val));

            checkPassword(regPassword, "reg_password_error");
        });

        registerForm.addEventListener("submit", (e) => {
            const validNom = checkNom(regNom, "reg_nom_error");
            const validEmail = checkEmail(regEmail, "reg_email_error");
            const validPass = checkPassword(regPassword, "reg_password_error");

            if (!validNom || !validEmail || !validPass) {
                e.preventDefault();
            }
        });
    }
});