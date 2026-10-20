
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('companyEditor')?.closest('form');

    if (!form || typeof Quill === 'undefined') {
        return;
    }

    const editors = [
        {
            editorId: 'companyEditor',
            inputId: 'description',
            placeholder: 'Describe the company and its solar services...'
        },
        {
            editorId: 'companyEditorAr',
            inputId: 'ar_description',
            placeholder: 'اكتب نبذة عن الشركة وخدماتها...'
        }
    ];

    editors.forEach(function (item) {
        const editorElement = document.getElementById(item.editorId);
        const hiddenInput = document.getElementById(item.inputId);

        if (!editorElement || !hiddenInput) {
            return;
        }

        const quill = new Quill(editorElement, {
            theme: 'snow',
            placeholder: item.placeholder,
            modules: {
                toolbar: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link', 'clean']
                ]
            }
        });

        const existingContent = hiddenInput.value.trim();

        if (existingContent) {
            quill.clipboard.dangerouslyPasteHTML(existingContent);
        }

        form.addEventListener('submit', function () {
            hiddenInput.value = quill.getText().trim().length === 0
                ? ''
                : quill.getSemanticHTML();
        });
    });
});
