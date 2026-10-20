document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // 1. Display today's date
    // ==========================================
    const date = document.getElementById('todayDate');

    if (date) {
        date.textContent = new Intl.DateTimeFormat(undefined, {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        }).format(new Date());
    }


    // ==========================================
    // 2. Admin sidebar toggle
    // ==========================================
    const sidebar = document.getElementById('adminSidebar');
    const toggle = document.getElementById('sidebarToggle');

    if (sidebar && toggle) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });

        document.addEventListener('click', e => {
            if (
                window.innerWidth <= 920 &&
                sidebar.classList.contains('open') &&
                !sidebar.contains(e.target) &&
                !toggle.contains(e.target)
            ) {
                sidebar.classList.remove('open');
            }
        });
    }


    // ==========================================
    // 3. Demo toast notifications
    // ==========================================
    document.querySelectorAll('[data-demo-toast]').forEach(el => {
        el.addEventListener('click', e => {
            if (
                el.tagName === 'A' &&
                el.getAttribute('href') === '#'
            ) {
                e.preventDefault();
            }

            toast(el.dataset.demoToast);
        });
    });


    // ==========================================
    // 4. Show / hide password
    // ==========================================
    document.querySelectorAll('[data-toggle-password]').forEach(button => {
        button.addEventListener('click', () => {
            const input = document.querySelector(
                button.dataset.togglePassword
            );

            if (input) {
                input.type =
                    input.type === 'password' ? 'text' : 'password';

                button.textContent =
                    input.type === 'password' ? 'Show' : 'Hide';
            }
        });
    });


    // ==========================================
    // 5. Initialize data tables
    // ==========================================
    document.querySelectorAll('.js-data-table').forEach(initTable);


    function initTable(table) {
        const body = table.tBodies[0];

        if (!body) return;

        const rows = [...body.rows];
        const panel = table.closest('.table-panel');

        const search = panel?.querySelector('[data-table-search]');
        const filter = panel?.querySelector(
            '[data-table-filter="status"]'
        );

        // Sorting state only. Pagination belongs to Laravel.
        let sort = -1;
        let dir = 1;


        // ------------------------------------------
        // Enable column sorting
        // ------------------------------------------
        table.querySelectorAll('thead th').forEach((th, index) => {
            th.tabIndex = 0;
            th.style.cursor = 'pointer';

            const sortColumn = () => {
                if (sort === index) {
                    dir *= -1;
                } else {
                    sort = index;
                    dir = 1;
                }

                render();
            };

            th.addEventListener('click', sortColumn);

            th.addEventListener('keydown', e => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    sortColumn();
                }
            });
        });


        // ------------------------------------------
        // Search and filter current Laravel page
        // ------------------------------------------
        function render() {
            const searchValue = (search?.value || '')
                .toLowerCase()
                .trim();

            const statusValue = (filter?.value || '')
                .toLowerCase()
                .trim();

            // Identify the empty-state row, if present.
            const emptyRow = rows.find(row =>
                row.querySelector('td[colspan]')
            );

            const dataRows = rows.filter(row =>
                !row.querySelector('td[colspan]')
            );

            // Apply search and status filters.
            let result = dataRows.filter(row => {
                const text = row.innerText.toLowerCase();

                const statusElement = row.querySelector('[data-status]');

                const rowStatus = (
                    statusElement?.dataset.status || ''
                ).toLowerCase();

                const matchesSearch =
                    text.includes(searchValue);

                const matchesStatus =
                    !statusValue || rowStatus === statusValue;

                return matchesSearch && matchesStatus;
            });

            // Sort matching rows when a heading is selected.
            if (sort >= 0) {
                result.sort((a, b) => {
                    const valueA =
                        a.cells[sort]?.innerText.trim() || '';

                    const valueB =
                        b.cells[sort]?.innerText.trim() || '';

                    return valueA.localeCompare(
                        valueB,
                        undefined,
                        {
                            numeric: true,
                            sensitivity: 'base'
                        }
                    ) * dir;
                });
            }

            // IMPORTANT:
            // Do not slice rows or create JavaScript pagination.
            // Display every matching row on the current Laravel page.
            dataRows.forEach(row => {
                row.hidden = !result.includes(row);
            });

            // Show the empty-state row when nothing matches.
            if (emptyRow) {
                emptyRow.hidden = result.length > 0;
            }
        }


        // ------------------------------------------
        // Search input
        // ------------------------------------------
        if (search) {
            search.addEventListener('input', render);
        }


        // ------------------------------------------
        // Status filter
        // ------------------------------------------
        if (filter) {
            filter.addEventListener('change', render);
        }


        // Initial render
        render();
    }


    // ==========================================
    // 6. Toast notification helper
    // ==========================================
    function toast(message) {
        let element = document.querySelector('.toast');

        if (!element) {
            element = document.createElement('div');
            element.className = 'toast';
            element.setAttribute('role', 'status');

            document.body.appendChild(element);
        }

        element.textContent = message;

        clearTimeout(toast.timer);

        toast.timer = setTimeout(() => {
            element.remove();
        }, 3000);
    }

});
