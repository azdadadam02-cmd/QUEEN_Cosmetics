// public/js/staff.js — confirmation avant les actions sensibles (supprimer, refuser...)
// Tout <form data-confirm="Message ?"> demande une confirmation avant l'envoi.
document.addEventListener('submit', function (event) {
    var form = event.target;
    if (form.matches && form.matches('form[data-confirm]')) {
        if (!window.confirm(form.getAttribute('data-confirm'))) {
            event.preventDefault();
        }
    }
});
