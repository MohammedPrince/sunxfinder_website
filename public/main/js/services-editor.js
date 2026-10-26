// Services editor: one input per service, joined with commas into the hidden field.
// English inputs keep Latin letters only, Arabic inputs keep Arabic letters only
// (digits, spaces and . & ' ( ) - / + are allowed in both). Must match storeCompany.
//
// Markup: [data-services-editor data-remove-label] > [data-service-inputs="<name>" data-placeholder]
//         > .provider-service-row > input, plus input[type=hidden][name=<name>] and [data-add-service="<name>"].
(() => {
    // Characters NOT allowed in each field (commas are the separator, so they are removed too).
    const notAllowed = {
        services: /[^A-Za-z0-9 .&'()\-\/+]/g,
        ar_services: /[^؀-ۿݐ-ݿࢠ-ࣿ0-9 .&'()\-\/+]|[،؛]/g,
    };

    document.querySelectorAll('[data-services-editor]').forEach(editor => {
        const removeLabel = editor.dataset.removeLabel || 'Remove';

        const sync = name => {
            const values = [...editor.querySelectorAll(`[data-service-inputs="${name}"] input`)]
                .map(input => input.value.trim()).filter(Boolean);
            const hidden = editor.querySelector(`input[type="hidden"][name="${name}"]`);
            if (hidden) hidden.value = values.join(',');
        };

        // Delegated listeners, so rows added later behave like the first one.
        editor.addEventListener('input', event => {
            const container = event.target.closest('[data-service-inputs]');
            if (!container || event.target.tagName !== 'INPUT') return;

            const name = container.dataset.serviceInputs;
            const input = event.target;
            const cleaned = notAllowed[name] ? input.value.replace(notAllowed[name], '') : input.value;
            if (cleaned !== input.value) input.value = cleaned;
            sync(name);
        });

        editor.addEventListener('click', event => {
            const remove = event.target.closest('.provider-service-remove');
            if (remove) {
                const name = remove.closest('[data-service-inputs]').dataset.serviceInputs;
                remove.closest('.provider-service-row').remove();
                sync(name);
                return;
            }

            const add = event.target.closest('[data-add-service]');
            if (!add) return;

            event.preventDefault();
            const name = add.dataset.addService;
            const container = editor.querySelector(`[data-service-inputs="${name}"]`);
            if (!container) return;

            const row = document.createElement('div');
            row.className = 'provider-service-row';

            const input = document.createElement('input');
            input.type = 'text';
            input.maxLength = 5000;
            input.className = container.querySelector('input')?.className || '';
            input.placeholder = container.dataset.placeholder || '';
            if (name === 'ar_services') {
                input.dir = 'rtl';
                input.lang = 'ar';
            }

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.className = 'provider-service-remove';
            removeButton.textContent = removeLabel;

            row.append(input, removeButton);
            container.append(row);
            input.focus();
        });

        editor.querySelectorAll('[data-service-inputs]').forEach(container => sync(container.dataset.serviceInputs));
        editor.closest('form')?.addEventListener('submit', () => {
            editor.querySelectorAll('[data-service-inputs]').forEach(container => sync(container.dataset.serviceInputs));
        });
    });
})();
