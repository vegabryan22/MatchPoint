import './bootstrap';
import * as bootstrap from 'bootstrap';
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import 'flatpickr/dist/flatpickr.min.css';
import 'flatpickr/dist/themes/dark.css';

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

document.querySelectorAll('[data-copy-url]').forEach((button) => {
    button.addEventListener('click', async () => {
        await navigator.clipboard.writeText(button.dataset.copyUrl);
        const originalText = button.textContent;
        button.textContent = 'Copiado';
        window.setTimeout(() => { button.textContent = originalText; }, 1500);
    });
});

document.querySelectorAll('.js-datetime-picker').forEach((input) => {
    flatpickr(input, {
        allowInput: true,
        altFormat: 'd/m/Y H:i',
        altInput: true,
        dateFormat: 'Y-m-d H:i',
        disableMobile: true,
        enableTime: true,
        locale: Spanish,
        minuteIncrement: 5,
        time_24hr: true,
    });
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

const revealValidationErrors = (form) => {
    form.classList.add('was-validated');
    form.querySelector('[data-validation-summary]')?.classList.remove('d-none');
    const invalidField = form.querySelector(':invalid');
    invalidField?.focus({ preventScroll: true });
    invalidField?.scrollIntoView({ behavior: 'smooth', block: 'center' });
};

document.querySelectorAll('.needs-validation').forEach((form) => {
    form.addEventListener('submit', (event) => {
        if (! form.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            revealValidationErrors(form);
            return;
        }
        form.querySelector('[data-submit-button]')?.setAttribute('disabled', 'disabled');
    });
});

document.querySelectorAll('[data-ajax-form]').forEach((form) => {
    form.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (! form.checkValidity()) {
            revealValidationErrors(form);
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

document.querySelectorAll('[data-bracket-stage]').forEach((stage) => {
    let zoom = 1;
    const zoomLabel = document.querySelector('[data-bracket-zoom="reset"]');
    const liveStatus = document.querySelector('[data-bracket-live-status]');
    let liveVersion = null;
    const applyZoom = () => {
        stage.querySelectorAll('[data-bracket-canvas]').forEach((canvas) => { canvas.style.zoom = zoom; });
        if (zoomLabel) zoomLabel.textContent = `${Math.round(zoom * 100)}%`;
    };
    const bindScroll = () => {
        stage.querySelectorAll('[data-bracket-scroll]').forEach((scroll) => {
            let startX = 0;
            let startScroll = 0;
            scroll.addEventListener('pointerdown', (event) => {
                startX = event.clientX;
                startScroll = scroll.scrollLeft;
                scroll.classList.add('is-dragging');
                scroll.setPointerCapture(event.pointerId);
            });
            scroll.addEventListener('pointermove', (event) => {
                if (scroll.classList.contains('is-dragging')) scroll.scrollLeft = startScroll - (event.clientX - startX);
            });
            scroll.addEventListener('pointerup', () => scroll.classList.remove('is-dragging'));
            scroll.addEventListener('pointercancel', () => scroll.classList.remove('is-dragging'));
        });
    };

    document.querySelectorAll('[data-bracket-zoom]').forEach((button) => {
        button.addEventListener('click', () => {
            zoom = button.dataset.bracketZoom === 'in' ? Math.min(1.4, zoom + .1)
                : button.dataset.bracketZoom === 'out' ? Math.max(.7, zoom - .1) : 1;
            applyZoom();
        });
    });
    document.querySelector('[data-bracket-fullscreen]')?.addEventListener('click', () => {
        if (document.fullscreenElement) document.exitFullscreen();
        else stage.requestFullscreen();
    });
    bindScroll();

    if (stage.dataset.bracketLiveUrl) {
        window.setInterval(async () => {
            try {
                const response = await fetch(stage.dataset.bracketLiveUrl, {
                    cache: 'no-store',
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (! response.ok) throw new Error('Bracket refresh failed');

                const payload = await response.json();
                if (liveVersion === null || liveVersion !== payload.version) {
                    const scrollPositions = Array.from(stage.querySelectorAll('[data-bracket-scroll]')).map((scroll) => scroll.scrollLeft);
                    stage.innerHTML = payload.html;
                    stage.querySelectorAll('[data-bracket-scroll]').forEach((scroll, index) => { scroll.scrollLeft = scrollPositions[index] ?? 0; });
                    bindScroll();
                    applyZoom();
                }
                liveVersion = payload.version;
                if (liveStatus) liveStatus.textContent = `En vivo · ${new Date().toLocaleTimeString()}`;
            } catch {
                if (liveStatus) liveStatus.textContent = 'Reconectando actualización automática…';
            }
        }, 5000);
    }
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
