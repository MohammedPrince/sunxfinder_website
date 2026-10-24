document.addEventListener('DOMContentLoaded', () => {

    // ==========================================
    // 1. Display today's date
    // ==========================================
    const date = document.getElementById('todayDate');
    const time = document.getElementById('todayTime');
    const greeting = document.querySelector('[data-greeting]');

    const updateClock = () => {
        const now = new Date();

        if (date) {
            date.textContent = new Intl.DateTimeFormat(undefined, {
                weekday: 'short',
                month: 'short',
                day: 'numeric',
                year: 'numeric'
            }).format(now);
        }

        if (time) {
            time.textContent = new Intl.DateTimeFormat(undefined, {
                hour: '2-digit',
                minute: '2-digit'
            }).format(now);
        }

        if (greeting) {
            const hour = now.getHours();
            const part = hour < 12 ? 'Good morning' : hour < 18 ? 'Good afternoon' : 'Good evening';

            greeting.textContent = `${part}, ${greeting.dataset.name}`;
        }
    };

    updateClock();
    setInterval(updateClock, 30000);


    // ==========================================
    // 1a. Top bar menus: one open at a time, close on outside click / Escape
    // ==========================================
    const topbarMenus = [...document.querySelectorAll('.topbar-menu')];

    topbarMenus.forEach(menu => {
        menu.addEventListener('toggle', () => {
            if (menu.open) {
                topbarMenus.forEach(other => {
                    if (other !== menu) other.open = false;
                });
            }
        });
    });

    document.addEventListener('click', e => {
        topbarMenus.forEach(menu => {
            if (menu.open && !menu.contains(e.target)) menu.open = false;
        });
    });

    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;

        topbarMenus.forEach(menu => {
            if (menu.open) {
                menu.open = false;
                menu.querySelector('summary')?.focus();
            }
        });
    });


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
    // 4a. Page guides: remember if a guide was collapsed
    // ==========================================
    document.querySelectorAll('details.page-guide[data-guide]').forEach(guide => {
        const key = 'sxf-guide-' + guide.dataset.guide;

        try {
            if (localStorage.getItem(key) === 'closed') {
                guide.open = false;
            }
        } catch (e) {
            // Storage unavailable (private mode): the guide simply stays open.
        }

        guide.addEventListener('toggle', () => {
            try {
                localStorage.setItem(key, guide.open ? 'open' : 'closed');
            } catch (e) {
                // Ignore storage errors.
            }
        });
    });


    // ==========================================
    // 4b. Modals (open / close)
    // ==========================================
    let lastFocused = null;

    function openModal(modal) {
        lastFocused = document.activeElement;
        modal.hidden = false;
        document.body.classList.add('modal-open');
        modal.querySelector('input, button:not([data-modal-close])')?.focus();
    }

    function closeModal(modal) {
        modal.hidden = true;
        document.body.classList.remove('modal-open');
        lastFocused?.focus();
    }

    document.querySelectorAll('[data-modal-open]').forEach(button => {
        button.addEventListener('click', () => {
            const modal = document.getElementById(button.dataset.modalOpen);

            if (modal) openModal(modal);
        });
    });

    document.querySelectorAll('[role="dialog"]').forEach(modal => {
        modal.querySelectorAll('[data-modal-close]').forEach(button => {
            button.addEventListener('click', () => closeModal(modal));
        });

        // Click on the dimmed backdrop closes the modal.
        modal.addEventListener('click', e => {
            if (e.target === modal) closeModal(modal);
        });
    });

    document.addEventListener('keydown', e => {
        if (e.key !== 'Escape') return;

        const openDialog = document.querySelector('[role="dialog"]:not([hidden])');

        if (openDialog) closeModal(openDialog);
    });


    // ==========================================
    // 4c. Forgot password: check registered email
    // ==========================================
    document.querySelectorAll('[data-forgot-password-form]').forEach(form => {
        const result = form.querySelector('[data-forgot-password-result]');
        const submit = form.querySelector('[data-forgot-password-submit]');
        const email = form.querySelector('input[name="email"]');

        const showResult = (message, type) => {
            result.textContent = message;
            result.className = `login-modal-result is-${type}`;
            result.hidden = false;
        };

        form.addEventListener('submit', async e => {
            e.preventDefault();

            if (!email.checkValidity()) {
                showResult('Please enter a valid email address.', 'error');
                email.focus();
                return;
            }

            submit.disabled = true;
            submit.textContent = 'Sending code…';

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new FormData(form),
                });

                const data = await response.json().catch(() => ({}));

                if (response.status === 429) {
                    showResult('Too many attempts. Please wait a minute and try again.', 'error');
                } else if (response.status === 419) {
                    showResult('Your session expired. Please refresh the page and try again.', 'error');
                } else if (!response.ok) {
                    const firstError = data.errors ? Object.values(data.errors)[0]?.[0] : null;
                    showResult(firstError || data.message || 'Something went wrong. Please try again.', 'error');
                } else {
                    showResult(data.message, data.exists ? 'success' : 'error');

                    // Code sent: continue on the reset page.
                    if (data.redirect) {
                        submit.disabled = true;
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 900);
                        return;
                    }
                }
            } catch {
                showResult('Unable to reach the server. Check your connection and try again.', 'error');
            } finally {
                // After a successful send the page redirects; keep the button locked.
                if (!result.classList.contains('is-success')) {
                    submit.disabled = false;
                    submit.textContent = 'Send code';
                }
            }
        });
    });


    // ==========================================
    // 4d. Reset password: OTP countdown and digits-only input
    // ==========================================
    document.querySelectorAll('[data-otp-countdown]').forEach(el => {
        const expiresAt = new Date(el.dataset.expiresAt).getTime();

        if (Number.isNaN(expiresAt)) return;

        const tick = () => {
            const seconds = Math.max(0, Math.round((expiresAt - Date.now()) / 1000));

            if (seconds > 0) {
                el.textContent = `Code expires in 0:${String(seconds).padStart(2, '0')}`;
                el.classList.remove('is-expired');
                return true;
            }

            el.textContent = 'This code has expired. Send a new code below.';
            el.classList.add('is-expired');
            return false;
        };

        if (tick()) {
            const timer = setInterval(() => {
                if (!tick()) clearInterval(timer);
            }, 1000);
        }
    });

    document.querySelectorAll('.otp-input').forEach(input => {
        input.addEventListener('input', () => {
            input.value = input.value.replace(/\D/g, '').slice(0, 6);
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

// ------------------------------------------
// Sidebar groups (admin + company): collapse/expand, remembered per panel.
// The group holding the current page always starts open.
// ------------------------------------------
(function () {
    const groups = document.querySelectorAll('.nav-group[data-nav-group]');
    if (!groups.length) return;

    const panel = location.pathname.split('/')[1] || 'panel';
    const storageKey = 'sx-nav-collapsed:' + panel;

    let collapsed = [];
    try {
        collapsed = JSON.parse(localStorage.getItem(storageKey) || '[]');
    } catch (e) {
        collapsed = [];
    }

    const save = () => {
        try {
            localStorage.setItem(storageKey, JSON.stringify(collapsed));
        } catch (e) { /* private mode: just don't remember */ }
    };

    const setOpen = (group, open) => {
        group.classList.toggle('is-open', open);
        group.querySelector('.nav-group-toggle')?.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    groups.forEach(group => {
        const key = group.dataset.navGroup;
        const hasActive = group.classList.contains('has-active') || !!group.querySelector('.nav-link.active');

        setOpen(group, hasActive || !collapsed.includes(key));

        group.querySelector('.nav-group-toggle')?.addEventListener('click', () => {
            const open = !group.classList.contains('is-open');
            setOpen(group, open);

            collapsed = collapsed.filter(item => item !== key);
            if (!open) collapsed.push(key);
            save();
        });
    });
})();
