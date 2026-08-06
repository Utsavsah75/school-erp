/**
 * Shared behaviors for every auth page (registration wizard, login,
 * 2FA verify): auto-advancing OTP digit boxes, a resend-cooldown timer,
 * and a client-side password strength meter. No dependencies beyond
 * SweetAlert2 (already loaded on every auth page) for toasts.
 */

/**
 * Wires up a group of single-digit <input> boxes inside `container` so
 * typing auto-advances, backspace goes back, and pasting a full code
 * fills every box. Returns a function that reads the combined code.
 */
function initOtpBoxes(container) {
    if (!container) return () => '';
    const boxes = Array.from(container.querySelectorAll('.otp-box'));

    boxes.forEach((box, idx) => {
        box.addEventListener('input', () => {
            box.value = box.value.replace(/\D/g, '').slice(0, 1);
            if (box.value && idx < boxes.length - 1) {
                boxes[idx + 1].focus();
            }
            container.dispatchEvent(new CustomEvent('otp-change'));
        });
        box.addEventListener('keydown', (e) => {
            if (e.key === 'Backspace' && !box.value && idx > 0) {
                boxes[idx - 1].focus();
            }
        });
        box.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
            text.split('').forEach((ch, i) => {
                if (boxes[i]) boxes[i].value = ch;
            });
            const last = Math.min(text.length, boxes.length) - 1;
            if (last >= 0) boxes[last].focus();
            container.dispatchEvent(new CustomEvent('otp-change'));
        });
    });

    if (boxes[0]) boxes[0].focus();

    return () => boxes.map((b) => b.value).join('');
}

/** Builds the N-box markup for an OTP group and returns the wrapping element. */
function buildOtpBoxes(container, length) {
    container.innerHTML = '';
    for (let i = 0; i < length; i++) {
        const input = document.createElement('input');
        input.type = 'text';
        input.inputMode = 'numeric';
        input.maxLength = 1;
        input.className = 'form-control otp-box text-center fw-bold';
        input.autocomplete = i === 0 ? 'one-time-code' : 'off';
        container.appendChild(input);
    }
    return initOtpBoxes(container);
}

/**
 * Starts (or restarts) a "Resend code in Ns" countdown. `button` is
 * disabled while counting down and shows the remaining seconds; once it
 * hits zero it's re-enabled with its original label.
 */
function startResendTimer(button, seconds) {
    const originalLabel = button.dataset.label || button.textContent.trim();
    button.dataset.label = originalLabel;
    button.disabled = true;

    let remaining = seconds;
    button.textContent = `Resend in ${remaining}s`;

    const tick = setInterval(() => {
        remaining -= 1;
        if (remaining <= 0) {
            clearInterval(tick);
            button.disabled = false;
            button.textContent = originalLabel;
            return;
        }
        button.textContent = `Resend in ${remaining}s`;
    }, 1000);
}

/**
 * Live password strength meter. Attach to a password <input> plus a
 * progress-bar-style element; scores 0-4 using a simple heuristic
 * (length + character variety) — good enough for UX feedback, the real
 * policy is always re-checked server-side (Validator::strong_password).
 */
function initPasswordStrengthMeter(input, barEl, labelEl) {
    if (!input || !barEl) return;

    const evaluate = (value) => {
        let score = 0;
        if (value.length >= 8) score++;
        if (value.length >= 12) score++;
        if (/[A-Z]/.test(value) && /[a-z]/.test(value)) score++;
        if (/[0-9]/.test(value)) score++;
        if (/[^A-Za-z0-9]/.test(value)) score++;
        return Math.min(score, 4);
    };

    const levels = [
        { pct: 20, cls: 'bg-danger', label: 'Very weak' },
        { pct: 40, cls: 'bg-danger', label: 'Weak' },
        { pct: 60, cls: 'bg-warning', label: 'Fair' },
        { pct: 80, cls: 'bg-info', label: 'Good' },
        { pct: 100, cls: 'bg-success', label: 'Strong' },
    ];

    input.addEventListener('input', () => {
        const value = input.value;
        const score = value.length === 0 ? -1 : evaluate(value);
        if (score < 0) {
            barEl.style.width = '0%';
            barEl.className = 'progress-bar';
            if (labelEl) labelEl.textContent = '';
            return;
        }
        const level = levels[score];
        barEl.style.width = level.pct + '%';
        barEl.className = 'progress-bar ' + level.cls;
        if (labelEl) labelEl.textContent = level.label;
    });
}

/** Small helper: POST JSON-friendly form data and parse the JSON response, never throwing on non-2xx. */
async function postForm(url, data) {
    const body = new URLSearchParams(data);
    const res = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body,
    });
    let json;
    try {
        json = await res.json();
    } catch (e) {
        json = { ok: false, message: 'Unexpected server response. Please try again.' };
    }
    return json;
}

function toastError(message) {
    if (window.Swal) {
        Swal.fire({ icon: 'error', title: 'Error', text: message, timer: 4000, showConfirmButton: true });
    } else {
        alert(message);
    }
}

function toastSuccess(message) {
    if (window.Swal) {
        Swal.fire({ icon: 'success', title: 'Success', text: message, timer: 2500, showConfirmButton: false });
    } else {
        alert(message);
    }
}
