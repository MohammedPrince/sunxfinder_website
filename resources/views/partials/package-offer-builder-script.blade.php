{{-- Behaviour for partials.package-offer-builder. Include once per page. --}}
@include('company.quote-requests.partials.offer-item-picker-script')
<script>
    window.PackageOfferBuilder = (function () {
        const round = (amount) => Math.round((amount + Number.EPSILON) * 100) / 100;
        const money = (amount) => amount.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        const rows = (root) => Array.from(root.querySelectorAll('[data-pkg-items] [data-invoice-item]'));

        function syncRemoveButtons(root) {
            const all = rows(root);
            all.forEach((row) => { row.querySelector('[data-remove-invoice-item]').disabled = all.length <= 1; });
        }

        // Mirrors PackageOfferRepository::save(), which has the final say.
        function recalculate(root) {
            let subtotal = 0;
            rows(root).forEach((row) => {
                const quantity = Math.max(0, Number(row.querySelector('[name$="[quantity]"]').value || 0));
                const unitPrice = Math.max(0, Number(row.querySelector('[data-invoice-price]').value || 0));
                const line = round(quantity * round(unitPrice));
                subtotal += line;
                row.querySelector('[data-line-total]').textContent = money(line);
            });
            subtotal = round(subtotal);
            const discount = Math.min(subtotal, Math.max(0, Number(root.querySelector('[data-pkg-discount]').value || 0)));
            const total = round(subtotal - discount);
            const currency = root.querySelector('[data-pkg-currency]').value;

            root.querySelector('[data-pkg-subtotal]').textContent = money(subtotal);
            root.querySelector('[data-pkg-discount-total]').textContent = '− ' + money(discount);
            root.querySelector('[data-pkg-total]').textContent = money(total);
            root.querySelector('[data-pkg-currency-label]').textContent = currency;
            root.querySelector('[data-pkg-minimum]').hidden = total >= 1000 || root.dataset.disabled === '1';

            const summary = {
                subtotal, discount, total, currency,
                valid: total >= 1000,
                titleEn: root.querySelector('[data-pkg-title-en]').value.trim(),
                titleAr: root.querySelector('[data-pkg-title-ar]').value.trim(),
                load: root.querySelector('[data-pkg-load]').value,
                expiresAt: root.querySelector('[data-pkg-expires]').value,
            };
            root.dispatchEvent(new CustomEvent('pkg:change', { detail: summary, bubbles: true }));

            return summary;
        }

        function addRow(root, description = '', type = '') {
            const template = root.querySelector('[data-pkg-item-template]');
            const holder = document.createElement('template');
            holder.innerHTML = template.innerHTML.replaceAll('__INDEX__', root.pkgNextIndex++);
            const row = holder.content.firstElementChild;
            row.querySelector('[data-item-description]').value = description;
            row.querySelector('[data-item-type]').value = type;
            root.querySelector('[data-pkg-items]').appendChild(row);
            if (root.dataset.company) filterProducts(row, root.dataset.company);
            syncRemoveButtons(root);
            recalculate(root);

            return row;
        }

        // Admin: show only the selected company's products; drop picks of another company.
        function filterProducts(scope, companyId) {
            scope.querySelectorAll('[data-product-picker]').forEach((picker) => {
                picker.querySelectorAll('option[data-company]').forEach((option) => {
                    option.hidden = option.dataset.company !== String(companyId);
                    option.disabled = option.hidden;
                });
                picker.querySelectorAll('optgroup').forEach((group) => {
                    group.hidden = !group.querySelector('option:not([hidden])');
                });
                if (picker.selectedOptions[0]?.hidden) {
                    picker.value = '';
                    picker.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }

        function setCompany(root, companyId) {
            root.dataset.company = companyId || '';
            filterProducts(root, companyId || '');
            const anyProduct = !!root.querySelector('[data-pkg-items] option[data-company]:not([hidden])');
            root.querySelector('[data-pkg-no-products]').hidden = anyProduct;
        }

        // Admin: the builder is only posted when "build a new offer" is chosen.
        function setEnabled(root, enabled) {
            root.dataset.disabled = enabled ? '0' : '1';
            root.querySelectorAll('input, select, textarea, button').forEach((field) => { field.disabled = !enabled; });
            if (enabled && root.dataset.company) filterProducts(root, root.dataset.company);
            if (enabled) syncRemoveButtons(root);
            recalculate(root);
        }

        function init(root) {
            root.pkgNextIndex = rows(root).length;

            root.querySelector('[data-pkg-add-item]').addEventListener('click', () => addRow(root).querySelector('select, input')?.focus());
            root.querySelectorAll('[data-pkg-suggestion]').forEach((button) => {
                button.addEventListener('click', () => addRow(root, button.dataset.pkgSuggestion, button.dataset.type).querySelector('[data-invoice-price]')?.focus());
            });
            root.querySelector('[data-pkg-items]').addEventListener('click', (event) => {
                const remove = event.target.closest('[data-remove-invoice-item]');
                if (!remove || rows(root).length <= 1) return;
                remove.closest('[data-invoice-item]').remove();
                syncRemoveButtons(root);
                recalculate(root);
            });
            root.addEventListener('input', () => recalculate(root));
            root.addEventListener('change', (event) => { if (event.type === 'change') recalculate(root); });

            syncRemoveButtons(root);
            recalculate(root);
        }

        document.querySelectorAll('[data-package-builder]').forEach(init);

        return { recalculate, setCompany, setEnabled };
    })();
</script>
