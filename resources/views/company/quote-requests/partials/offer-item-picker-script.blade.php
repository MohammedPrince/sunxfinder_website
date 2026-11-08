{{-- Picking a product fills the line's description and type. Works for rows added later too.
     The description also narrows the product list: "Solar panels" shows only panels, and so on. --}}
<script>
    (function () {
        // Word stems (English and Arabic) that tell which product type a line is about.
        const TYPE_KEYWORDS = {
            solar_panel: ['panel', 'pv', 'module', 'لوح', 'الواح', 'ألواح', 'لوحات'],
            inverter: ['inverter', 'محول', 'عاكس', 'انفرتر', 'إنفرتر'],
            battery: ['batter', 'بطاري', 'بطارية'],
            other: ['mount', 'structure', 'cable', 'protection', 'هيكل', 'كابل', 'حماية'],
        };

        function typeFromDescription(text) {
            const value = (text || '').toLowerCase();
            if (!value.trim()) return '';
            for (const [type, words] of Object.entries(TYPE_KEYWORDS)) {
                if (words.some(word => value.includes(word))) return type;
            }
            return '';
        }

        // Hide products of other types. No match (or a custom text) shows them all.
        // The chosen product always stays visible so the line never loses it.
        function filterRow(row) {
            const picker = row.querySelector('[data-product-picker]');
            const description = row.querySelector('[data-item-description]');
            if (!picker || !description) return;

            const type = typeFromDescription(description.value);
            // Admin campaign builder: also keep only the chosen company's products.
            const company = row.parentElement?.closest('[data-company]')?.dataset.company || '';
            picker.querySelectorAll('option[value]:not([value=""])').forEach(function (option) {
                const sameCompany = !company || !option.dataset.company || option.dataset.company === company;
                const show = sameCompany && (!type || option.dataset.type === type || option.selected);
                option.hidden = !show;
                option.disabled = !show;
            });
            picker.querySelectorAll('optgroup').forEach(function (group) {
                group.hidden = !Array.from(group.children).some(option => !option.hidden);
            });
        }

        document.addEventListener('change', function (event) {
            const picker = event.target.closest('[data-product-picker]');
            if (!picker) return;

            const row = picker.closest('[data-invoice-item]');
            const option = picker.selectedOptions[0];
            const description = row.querySelector('[data-item-description]');
            const type = row.querySelector('[data-item-type]');

            // Replace the text only when it is empty or still the previous product's name.
            if (option && option.value) {
                if (!description.value.trim() || description.value === picker.dataset.lastLabel) {
                    description.value = option.dataset.label;
                }
                type.value = option.dataset.type || '';
                picker.dataset.lastLabel = option.dataset.label;
            } else {
                type.value = '';
                picker.dataset.lastLabel = '';
            }

            description.dispatchEvent(new Event('input', { bubbles: true }));
        });

        // Typing a description narrows the list right away.
        document.addEventListener('input', function (event) {
            if (!event.target.matches('[data-item-description]')) return;
            const row = event.target.closest('[data-invoice-item]');
            if (row) filterRow(row);
        });

        // Rows filled by "Quick add" or added later: narrow the list just before it opens.
        ['focusin', 'mousedown'].forEach(function (name) {
            document.addEventListener(name, function (event) {
                const picker = event.target.closest && event.target.closest('[data-product-picker]');
                if (picker) filterRow(picker.closest('[data-invoice-item]'));
            }, true);
        });

        // Remember each preselected product's name, so changing the product replaces it.
        document.querySelectorAll('[data-product-picker]').forEach(function (picker) {
            picker.dataset.lastLabel = picker.selectedOptions[0]?.dataset.label || '';
            filterRow(picker.closest('[data-invoice-item]'));
        });
    })();
</script>
