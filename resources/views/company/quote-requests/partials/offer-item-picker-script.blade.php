{{-- Picking a product fills the line's description and type. Works for rows added later too. --}}
<script>
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

    // Remember each preselected product's name, so changing the product replaces it.
    document.querySelectorAll('[data-product-picker]').forEach(function (picker) {
        picker.dataset.lastLabel = picker.selectedOptions[0]?.dataset.label || '';
    });
</script>
