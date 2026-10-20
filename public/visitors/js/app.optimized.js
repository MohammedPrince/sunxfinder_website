/* SunXFinder - Optimized JavaScript */
(() => {
    const menuButton = document.querySelector('.menu-toggle');
    const nav = document.querySelector('.nav-links');

    if (menuButton && nav) {
        menuButton.addEventListener('click', () => {
            const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
            menuButton.setAttribute('aria-expanded', String(!isOpen));
            nav.classList.toggle('open', !isOpen);
        });

        nav.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
            menuButton.setAttribute('aria-expanded', 'false');
            nav.classList.remove('open');
        }));
    }

    const modal = document.getElementById('languageModal');
    const openLanguage = document.getElementById('openLanguageModal');
    const closeLanguage = document.getElementById('closeLanguageModal');

    const showModal = () => {
        if (!modal) return;
        modal.hidden = false;
        document.body.classList.add('modal-open');
        const firstLanguage = modal.querySelector('a');
        if (firstLanguage) firstLanguage.focus();
    };

    const hideModal = () => {
        if (!modal) return;
        modal.hidden = true;
        document.body.classList.remove('modal-open');
        if (openLanguage) openLanguage.focus();
    };

    if (modal && !modal.hidden) document.body.classList.add('modal-open');
    if (openLanguage) openLanguage.addEventListener('click', showModal);
    if (closeLanguage) closeLanguage.addEventListener('click', hideModal);
    if (modal) modal.addEventListener('click', event => {
        if (event.target === modal) hideModal();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Escape' && modal && !modal.hidden) hideModal();
    });

    document.querySelectorAll('.faq-question').forEach(question => {
        question.setAttribute('role', 'button');
        question.setAttribute('tabindex', '0');

        const toggleFaq = () => {
            const item = question.parentElement;
            item.classList.toggle('active');
            const toggle = question.querySelector('.faq-toggle');
            if (toggle) toggle.textContent = item.classList.contains('active') ? '−' : '+';
            question.setAttribute('aria-expanded', String(item.classList.contains('active')));
        };

        question.addEventListener('click', toggleFaq);
        question.addEventListener('keydown', event => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleFaq();
            }
        });
        question.setAttribute('aria-expanded', 'false');
    });

    const animated = document.querySelectorAll('.animate-fade-in-up');
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.08
        });
        animated.forEach(element => observer.observe(element));
    } else {
        animated.forEach(element => element.classList.add('animate-in'));
    }

    // Active navigation link on scroll
    const activeNavLinks = document.querySelectorAll(
        '.nav-links a[href^="#"]:not(.btn)'
    );

    const activeNavSections = [...activeNavLinks]
        .map(link => {
            const target = link.getAttribute('href');
            return target && target.length > 1 ?
                document.querySelector(target) :
                null;
        })
        .filter(Boolean);

    if ('IntersectionObserver' in window && activeNavSections.length) {
        const activeNavObserver = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                if (!entry.isIntersecting) return;

                activeNavLinks.forEach(link => {
                    const isActive =
                        link.getAttribute('href') === `#${entry.target.id}`;

                    link.classList.toggle('active', isActive);

                    if (isActive) {
                        link.setAttribute('aria-current', 'location');
                    } else {
                        link.removeAttribute('aria-current');
                    }
                });
            });
        }, {
            rootMargin: '-20% 0px -65% 0px',
            threshold: 0
        });

        activeNavSections.forEach(section => {
            activeNavObserver.observe(section);
        });
    }
})();