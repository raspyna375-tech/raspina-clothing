(function () {
    'use strict';

    var sidebar = document.querySelector('[data-admin-sidebar]');
    var scrim = document.querySelector('[data-admin-scrim]');
    var openButton = document.querySelector('[data-admin-sidebar-open]');
    var closeButton = document.querySelector('[data-admin-sidebar-close]');

    function toggleSidebar(open) {
        if (!sidebar || !scrim) {
            return;
        }
        sidebar.classList.toggle('is-open', open);
        scrim.classList.toggle('is-visible', open);
        document.body.classList.toggle('admin-nav-open', open);
        if (openButton) {
            openButton.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
    }

    if (openButton) {
        openButton.addEventListener('click', function () { toggleSidebar(true); });
    }
    if (closeButton) {
        closeButton.addEventListener('click', function () { toggleSidebar(false); });
    }
    if (scrim) {
        scrim.addEventListener('click', function () { toggleSidebar(false); });
    }
    document.querySelectorAll('.admin-nav a').forEach(function (link) {
        link.addEventListener('click', function () { toggleSidebar(false); });
    });

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var question = form.getAttribute('data-confirm');
            if (question && !window.confirm(question)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('textarea').forEach(function (textarea) {
        textarea.addEventListener('input', function () {
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 520) + 'px';
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            toggleSidebar(false);
        }
    });
}());
