import './bootstrap';
import * as bootstrap from 'bootstrap';
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js';
import 'flatpickr/dist/flatpickr.min.css';
import 'flatpickr/dist/themes/dark.css';

window.bootstrap = bootstrap;

document.querySelectorAll('.toast').forEach((element) => new bootstrap.Toast(element).show());

const initializeTooltips = (container = document) => {
    container.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((element) => {
        bootstrap.Tooltip.getOrCreateInstance(element);
    });
};

initializeTooltips();

const showToast = (message, variant = 'success') => {
    let container = document.querySelector('[data-dynamic-toast-container]');
    if (! container) {
        container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        container.dataset.dynamicToastContainer = '';
        document.body.append(container);
    }
    const toastElement = document.createElement('div');
    toastElement.className = `toast text-bg-${variant} border-0`;
    toastElement.setAttribute('role', 'status');
    toastElement.innerHTML = `<div class="d-flex"><div class="toast-body"></div><button class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
    toastElement.querySelector('.toast-body').textContent = message;
    container.append(toastElement);
    const toast = new bootstrap.Toast(toastElement, { delay: 3500 });
    toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove());
    toast.show();
};

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

document.querySelectorAll('[data-arrival-draw-form]').forEach((form) => {
    const checkboxes = Array.from(form.querySelectorAll('[data-arrival-participant]:not(:disabled)'));
    const countLabel = form.querySelector('[data-present-count]');
    const warning = form.querySelector('[data-arrival-warning]');
    const submit = form.querySelector('[data-arrival-submit]');
    const synchronize = () => {
        const selected = checkboxes.filter((checkbox) => checkbox.checked);
        selected.forEach((checkbox, index) => {
            const position = checkbox.closest('tr')?.querySelector('[data-arrival-position]');
            if (position) {
                position.disabled = false;
                position.value = String(index + 1);
            }
        });
        checkboxes.filter((checkbox) => ! checkbox.checked).forEach((checkbox) => {
            const position = checkbox.closest('tr')?.querySelector('[data-arrival-position]');
            if (position) position.disabled = true;
        });
        if (countLabel) countLabel.textContent = `${selected.length} presentes`;
        const invalid = selected.length < 2 || selected.length % 2 !== 0;
        warning?.classList.toggle('d-none', ! invalid);
        if (submit) submit.disabled = invalid;
    };
    checkboxes.forEach((checkbox) => checkbox.addEventListener('change', synchronize));
    form.querySelectorAll('[data-select-arrivals]').forEach((button) => button.addEventListener('click', () => {
        checkboxes.forEach((checkbox) => { checkbox.checked = button.dataset.selectArrivals === 'all'; });
        synchronize();
    }));
    synchronize();
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

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-score-step]');
    if (! button) return;

    const input = document.getElementById(button.dataset.scoreTarget);
    if (! input) return;
    const nextValue = Math.max(0, Math.min(99, Number(input.value || 0) + Number(button.dataset.scoreStep)));
    input.value = String(nextValue);
    input.dispatchEvent(new Event('input', { bubbles: true }));
});

document.addEventListener('input', (event) => {
    const form = event.target.closest('[data-inline-result-form], [data-native-result-form]');
    if (form) form.dataset.dirty = 'true';
});

document.addEventListener('submit', async (event) => {
    const form = event.target.closest('[data-inline-result-form]');
    if (! form) return;
    event.preventDefault();

    if (! form.checkValidity()) {
        revealValidationErrors(form);
        return;
    }

    const isCorrection = Boolean(form.querySelector('input[name="_method"][value="PUT"]'));
    if (isCorrection && ! window.confirm('¿Guardar esta corrección y recalcular la siguiente ronda?')) return;

    const errorBox = form.querySelector('[data-inline-result-errors]');
    const submitButton = form.querySelector('[data-inline-submit]');
    errorBox?.classList.add('d-none');
    submitButton?.setAttribute('disabled', 'disabled');

    try {
        const response = await fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const responseBody = await response.text();
        let payload = {};
        try {
            payload = JSON.parse(responseBody);
        } catch {
            if (response.status === 419) {
                throw new Error('La sesión venció. Recarga la página e intenta nuevamente.');
            }
            throw new Error(`El servidor respondió ${response.status} sin datos válidos.`);
        }
        if (! response.ok) {
            const messages = Object.values(payload.errors ?? {}).flat();
            if (errorBox) {
                const visibleMessages = messages.length > 0 ? messages : [payload.message ?? `No fue posible guardar (${response.status}).`];
                errorBox.replaceChildren(...visibleMessages.map((message) => {
                    const item = document.createElement('div');
                    item.textContent = message;
                    return item;
                }));
                errorBox.classList.remove('d-none');
            }
            return;
        }

        const card = form.closest('.mp-world-match, .mp-mobile-match, .mp-dashboard-match');
        card?.classList.add('is-completed');
        card?.querySelector('[data-inline-status]')?.replaceChildren(payload.status);
        card?.querySelector('[data-inline-score-a]')?.replaceChildren(String(payload.score_a));
        card?.querySelector('[data-inline-score-b]')?.replaceChildren(String(payload.score_b));
        form.dataset.dirty = 'false';
        if (! isCorrection) {
            const method = document.createElement('input');
            method.type = 'hidden';
            method.name = '_method';
            method.value = 'PUT';
            form.append(method);
            form.querySelector('summary')?.replaceChildren('Corregir marcador');
            submitButton?.replaceChildren('Guardar corrección');
        }
        showToast(payload.message);
        document.querySelector('[data-bracket-stage]')?.dispatchEvent(new Event('matchpoint:refresh'));
    } catch (error) {
        if (errorBox) {
            errorBox.textContent = error instanceof Error
                ? error.message
                : 'No fue posible guardar. Verifica la conexión e inténtalo nuevamente.';
            errorBox.classList.remove('d-none');
        }
    } finally {
        submitButton?.removeAttribute('disabled');
    }
});

document.querySelectorAll('[data-bracket-stage]').forEach((stage) => {
    let zoom = 1;
    const zoomLabel = document.querySelector('[data-bracket-zoom="reset"]');
    const liveStatus = document.querySelector('[data-bracket-live-status]');
    let liveVersion = null;
    const applyZoom = () => {
        stage.querySelectorAll('[data-bracket-canvas]').forEach((canvas) => { canvas.style.zoom = zoom; });
        if (zoomLabel) zoomLabel.textContent = `${Math.round(zoom * 100)}%`;
        window.requestAnimationFrame(() => drawBracketConnectors());
    };
    const drawBracketConnectors = () => {
        stage.querySelectorAll('.mp-world-bracket.is-symmetric').forEach((bracket) => {
            bracket.querySelector(':scope > .mp-bracket-connectors')?.remove();
            const columns = Array.from(bracket.querySelectorAll(':scope > [data-round-number]'));
            if (columns.length < 2) return;

            const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
            svg.classList.add('mp-bracket-connectors');
            svg.setAttribute('aria-hidden', 'true');
            svg.setAttribute('viewBox', `0 0 ${bracket.offsetWidth} ${bracket.offsetHeight}`);
            svg.innerHTML = '<defs><linearGradient id="mp-connector-gradient" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#7c5cff" stop-opacity=".42"/><stop offset=".5" stop-color="#ffd166" stop-opacity=".72"/><stop offset="1" stop-color="#20e3b2" stop-opacity=".42"/></linearGradient></defs>';
            const bracketRect = bracket.getBoundingClientRect();
            const scale = bracket.offsetWidth > 0 ? bracketRect.width / bracket.offsetWidth : 1;
            const centerColumn = columns.find((column) => column.dataset.bracketSide === 'center');

            ['left', 'right'].forEach((side) => {
                const sideColumns = columns
                    .filter((column) => column.dataset.bracketSide === side)
                    .sort((first, second) => Number(first.dataset.roundNumber) - Number(second.dataset.roundNumber));
                if (centerColumn) sideColumns.push(centerColumn);

                sideColumns.slice(0, -1).forEach((sourceColumn, columnIndex) => {
                    const targetColumn = sideColumns[columnIndex + 1];
                    const sources = Array.from(sourceColumn.querySelectorAll('.mp-world-match'));
                    const targets = Array.from(targetColumn.querySelectorAll('.mp-world-match'));
                    sources.forEach((source, sourceIndex) => {
                        const target = targets[Math.min(targets.length - 1, Math.floor(sourceIndex * targets.length / sources.length))];
                        if (! target) return;
                        const sourceRect = source.getBoundingClientRect();
                        const targetRect = target.getBoundingClientRect();
                        const travelsRight = sourceRect.left < targetRect.left;
                        const startX = ((travelsRight ? sourceRect.right : sourceRect.left) - bracketRect.left) / scale;
                        const endX = ((travelsRight ? targetRect.left : targetRect.right) - bracketRect.left) / scale;
                        const startY = (sourceRect.top + sourceRect.height / 2 - bracketRect.top) / scale;
                        const endY = (targetRect.top + targetRect.height / 2 - bracketRect.top) / scale;
                        const controlX = (startX + endX) / 2;
                        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                        path.setAttribute('d', `M ${startX} ${startY} C ${controlX} ${startY}, ${controlX} ${endY}, ${endX} ${endY}`);
                        svg.append(path);
                    });
                });
            });

            bracket.prepend(svg);
            bracket.classList.add('has-svg-connectors');
        });
    };
    const bindScroll = () => {
        stage.querySelectorAll('[data-bracket-scroll]').forEach((scroll) => {
            let startX = 0;
            let startScroll = 0;
            scroll.addEventListener('pointerdown', (event) => {
                if (event.target.closest('button, input, a, select, textarea, label, form, details, summary')) {
                    return;
                }
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
    window.requestAnimationFrame(() => drawBracketConnectors());

    if (stage.dataset.bracketLiveUrl) {
        const refreshBracket = async () => {
            if (stage.querySelector('[data-inline-result-form][data-dirty="true"], [data-native-result-form][data-dirty="true"]')) {
                if (liveStatus) liveStatus.textContent = 'Marcador pendiente de guardar';
                return;
            }
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
                    initializeTooltips(stage);
                    bindScroll();
                    applyZoom();
                    window.requestAnimationFrame(() => drawBracketConnectors());
                }
                liveVersion = payload.version;
                if (liveStatus) liveStatus.textContent = `En vivo · ${new Date().toLocaleTimeString()}`;
            } catch {
                if (liveStatus) liveStatus.textContent = 'Reconectando actualización automática…';
            }
        };
        stage.addEventListener('matchpoint:refresh', refreshBracket);
        window.setInterval(refreshBracket, 5000);
    }

    let connectorResizeTimer;
    window.addEventListener('resize', () => {
        window.clearTimeout(connectorResizeTimer);
        connectorResizeTimer = window.setTimeout(drawBracketConnectors, 120);
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
