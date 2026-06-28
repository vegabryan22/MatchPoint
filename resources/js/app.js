import './bootstrap';
import * as bootstrap from 'bootstrap';

window.bootstrap = bootstrap;

document.querySelectorAll('.toast').forEach((element) => new bootstrap.Toast(element).show());

document.querySelector('[data-theme-toggle]')?.addEventListener('click', () => {
    const root = document.documentElement;
    const theme = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';
    root.setAttribute('data-bs-theme', theme);
    localStorage.setItem('matchpoint-theme', theme);
});

document.querySelector('[data-sidebar-toggle]')?.addEventListener('click', () => {
    document.querySelector('.mp-sidebar')?.classList.toggle('show');
});

document.querySelectorAll('[data-confirm]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (! window.confirm(form.dataset.confirm)) {
            event.preventDefault();
        }
    });
});

document.querySelectorAll('[data-table-search]').forEach((input) => {
    input.addEventListener('input', () => {
        const query = input.value.trim().toLocaleLowerCase();
        document.querySelectorAll(input.dataset.tableSearch).forEach((row) => {
            row.hidden = ! row.textContent.toLocaleLowerCase().includes(query);
        });
    });
});

document.querySelectorAll('.needs-validation').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (! form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        form.classList.add('was-validated');
    });
});

document.querySelectorAll('[data-ajax-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (! form.checkValidity()) {
            form.classList.add('was-validated');
            return;
        }

        const errorBox = form.querySelector('[data-ajax-errors]');
        const submitButton = form.querySelector('[data-submit-button]');
        errorBox?.classList.add('d-none');
        submitButton?.setAttribute('disabled', 'disabled');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const payload = await response.json();

            if (! response.ok) {
                const messages = Object.values(payload.errors ?? {}).flat();
                if (errorBox) {
                    errorBox.replaceChildren(...messages.map((message) => {
                        const item = document.createElement('div');
                        item.textContent = message;
                        return item;
                    }));
                    errorBox.classList.remove('d-none');
                }
                return;
            }

            window.location.assign(payload.redirect);
        } catch {
            if (errorBox) {
                errorBox.textContent = 'No fue posible guardar el resultado. Intenta nuevamente.';
                errorBox.classList.remove('d-none');
            }
        } finally {
            submitButton?.removeAttribute('disabled');
        }
    });
});

const dashboardLive = document.querySelector('[data-dashboard-live]');
if (dashboardLive) {
    window.setInterval(async () => {
        try {
            const response = await fetch(dashboardLive.dataset.dashboardUrl, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (! response.ok) {
                return;
            }

            const payload = await response.json();
            Object.entries(payload.metrics).forEach(([key, value]) => {
                document.querySelector(`[data-dashboard-metric="${key}"]`)?.replaceChildren(String(value));
            });
            dashboardLive.innerHTML = payload.live_html;
        } catch {
            // El siguiente ciclo reintentará sin interrumpir la navegación.
        }
    }, 30000);
}
