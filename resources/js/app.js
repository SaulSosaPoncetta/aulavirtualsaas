import axios from 'axios';
import * as bootstrap from 'bootstrap';

// ============================================================
// Axios (peticiones AJAX con token CSRF)
// ============================================================
window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const csrf = document.querySelector('meta[name="csrf-token"]');
if (csrf) {
    window.axios.defaults.headers.common['X-CSRF-TOKEN'] = csrf.getAttribute('content');
}

window.bootstrap = bootstrap;

// ============================================================
// Modal de confirmación global (reemplaza confirm() nativo)
// Uso en formularios: <form data-confirmar="¿Eliminar esta tarea?">
// Uso en links:       <a href="..." data-confirmar="¿Seguro?">
// ============================================================
document.addEventListener('DOMContentLoaded', () => {
    const modalEl = document.getElementById('modalConfirmar');
    if (!modalEl) return;

    const modal = new bootstrap.Modal(modalEl);
    const mensaje = document.getElementById('modalConfirmarMensaje');
    const btnAceptar = document.getElementById('modalConfirmarAceptar');
    let accion = null;

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (form.dataset.confirmar && !form.dataset.confirmado) {
            e.preventDefault();
            mensaje.textContent = form.dataset.confirmar;
            accion = () => {
                form.dataset.confirmado = '1';
                form.submit();
            };
            modal.show();
        }
    });

    document.addEventListener('click', (e) => {
        const link = e.target.closest('a[data-confirmar]');
        if (link) {
            e.preventDefault();
            mensaje.textContent = link.dataset.confirmar;
            accion = () => { window.location.href = link.href; };
            modal.show();
        }
    });

    btnAceptar.addEventListener('click', () => {
        modal.hide();
        if (accion) accion();
        accion = null;
    });

    // Tooltips
    document.querySelectorAll('[data-bs-toggle="tooltip"]')
        .forEach(el => new bootstrap.Tooltip(el));
});
