// Sign-up forms (customer and company): small helpers that match the server rules,
// so people fix mistakes while typing instead of after submitting.
(() => {
    // Show / hide a password field.
    document.querySelectorAll('[data-password-toggle]').forEach(button => {
        const input = document.getElementById(button.dataset.passwordToggle);
        if (!input) return;

        button.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            button.textContent = show ? button.dataset.hide : button.dataset.show;
        });
    });

    // Live checklist of the password rules.
    const password = document.querySelector('[data-password-input]');
    const confirm = document.querySelector('[data-password-confirm]');
    const rules = document.querySelector('[data-password-rules]');

    if (password && rules) {
        const lengthRule = rules.querySelector('[data-rule="length"]');
        const min = Number(lengthRule?.dataset.min) || 8;

        const checks = {
            length: value => value.length >= min,
            upper: value => /[A-Z]/.test(value),
            lower: value => /[a-z]/.test(value),
            number: value => /[0-9]/.test(value),
            symbol: value => /[^A-Za-z0-9]/.test(value),
            match: value => value.length > 0 && confirm && value === confirm.value,
        };

        const update = () => {
            const value = password.value;
            let allPassed = true;

            Object.entries(checks).forEach(([rule, test]) => {
                const item = rules.querySelector(`[data-rule="${rule}"]`);
                const passed = test(value);
                if (item) item.classList.toggle('is-done', passed);
                if (rule !== 'match' && !passed) allPassed = false;
            });

            // Native validation then blocks the submit with a clear message.
            password.setCustomValidity(allPassed || !value ? '' : rules.dataset.invalid);
            if (confirm) {
                confirm.setCustomValidity(confirm.value && confirm.value !== value ? rules.dataset.mismatch : '');
            }
        };

        password.addEventListener('input', update);
        confirm?.addEventListener('input', update);
        update();
    }

    // Fields that accept English letters only (usernames, company name).
    document.querySelectorAll('[data-latin-only]').forEach(input => {
        const error = input.parentElement.querySelector('[data-latin-error]');
        const arabic = /[؀-ۿݐ-ݿࢠ-ࣿ]/;

        input.addEventListener('input', () => {
            if (!arabic.test(input.value)) return;

            input.value = input.value.replace(new RegExp(arabic.source, 'g'), '');
            if (error) {
                error.textContent = input.dataset.latinMessage;
                error.hidden = false;
                clearTimeout(error.timer);
                error.timer = setTimeout(() => { error.hidden = true; }, 4000);
            }
        });
    });

    // Phone numbers: digits only.
    document.querySelectorAll('[data-digits-only]').forEach(input => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '').slice(0, Number(input.maxLength) || 10);
        });
    });

    // Names: capital first letter of each word (Latin letters only).
    document.querySelectorAll('[data-capitalize]').forEach(input => {
        input.addEventListener('input', () => {
            const start = input.selectionStart;
            const end = input.selectionEnd;
            input.value = input.value.replace(/\b[a-z]/g, letter => letter.toUpperCase());
            input.setSelectionRange(start, end);
        });
    });

    // Clear the red border once the person edits a field the server rejected.
    document.querySelectorAll('[data-register-form] .is-invalid').forEach(field => {
        field.addEventListener('input', () => field.classList.remove('is-invalid'), { once: true });
        field.addEventListener('change', () => field.classList.remove('is-invalid'), { once: true });
    });

    // Avoid double submits: lock the button once the browser accepts the form.
    document.querySelectorAll('[data-register-form]').forEach(form => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('[type="submit"]');
            if (button) {
                button.disabled = true;
                button.classList.add('is-loading');
            }
        });
    });
})();
