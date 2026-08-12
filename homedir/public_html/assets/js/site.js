/* RASPINA SITE SCRIPT — upload only to /public_html/assets/js/site.js */
(function () {
    'use strict';

    var documentElement = document.documentElement;
    var body = document.body;
    var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var shortlistKey = 'raspina_shortlist_v1';
    var shortlistLimit = 30;
    var activeOverlay = null;

    function one(selector, context) {
        return (context || document).querySelector(selector);
    }

    function all(selector, context) {
        return Array.prototype.slice.call((context || document).querySelectorAll(selector));
    }

    function parseJsonScript(selector) {
        var node = one(selector);
        if (!node) {
            return null;
        }
        try {
            return JSON.parse(node.textContent || 'null');
        } catch (error) {
            return null;
        }
    }

    function polishCatalogText(value) {
        return String(value || '')
            .replace(/\bwhith\b/gi, 'with')
            .replace(/\bplated pants\b/gi, 'pleated pants')
            .replace(/\blnner top\b/gi, 'inner top');
    }

    function polishVisibleCatalogTypos() {
        all('.catalog-tile > span, .requested-product strong, .product-adjacent a').forEach(function (element) {
            element.textContent = polishCatalogText(element.textContent);
        });
    }

    function installImageFallback(image, label) {
        if (!image || image.getAttribute('data-fallback-ready') === 'true') {
            return;
        }
        image.setAttribute('data-fallback-ready', 'true');
        function fallbackNode() {
            var fallback = document.createElement('span');
            fallback.className = 'image-fallback';
            fallback.setAttribute('data-generated-image-fallback', '');
            fallback.setAttribute('role', 'img');
            fallback.setAttribute('aria-label', label || 'Product image unavailable');
            var mark = document.createElement('b');
            mark.setAttribute('aria-hidden', 'true');
            mark.textContent = 'R';
            var copy = document.createElement('span');
            copy.textContent = 'Image archive pending';
            fallback.appendChild(mark);
            fallback.appendChild(copy);
            return fallback;
        }
        function clearFallback() {
            var fallback = image.parentNode ? one('[data-generated-image-fallback]', image.parentNode) : null;
            if (fallback) {
                fallback.remove();
            }
            image.hidden = false;
            image.removeAttribute('aria-hidden');
        }
        function showFallback() {
            if (!image.parentNode || one('[data-generated-image-fallback]', image.parentNode)) {
                return;
            }
            image.hidden = true;
            image.setAttribute('aria-hidden', 'true');
            image.insertAdjacentElement('afterend', fallbackNode());
        }
        image.addEventListener('load', clearFallback);
        image.addEventListener('error', showFallback);
        if (image.complete && image.getAttribute('src') && image.naturalWidth === 0) {
            window.setTimeout(showFallback, 0);
        }
    }

    function setupImageFallbacks() {
        all('img[data-image-fallback], .product-media img, .catalog-mosaic img').forEach(function (image) {
            installImageFallback(image, image.getAttribute('data-fallback-label') || image.alt || 'Product image unavailable');
        });
    }

    function focusable(container) {
        return all('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])', container)
            .filter(function (element) {
                return !element.hidden && element.getAttribute('aria-hidden') !== 'true' && element.offsetParent !== null;
            });
    }

    function setPageLocked(locked) {
        body.classList.toggle('is-locked', locked);
    }

    function openOverlay(element, trigger, firstFocus) {
        if (!element) {
            return;
        }
        if (activeOverlay && activeOverlay.element !== element) {
            closeOverlay(activeOverlay.element, false);
        }
        element.__raspinaCloseToken = (element.__raspinaCloseToken || 0) + 1;
        element.classList.remove('is-closing');
        element.removeAttribute('inert');
        element.hidden = false;
        element.setAttribute('aria-hidden', 'false');
        activeOverlay = {
            element: element,
            trigger: trigger || document.activeElement
        };
        setPageLocked(true);
        window.requestAnimationFrame(function () {
            var target = firstFocus || focusable(element)[0];
            if (target) {
                target.focus();
            }
        });
    }

    function closeOverlay(element, restoreFocus, options) {
        if (!element || element.hidden) {
            return;
        }
        options = options || {};
        var trigger = activeOverlay && activeOverlay.element === element ? activeOverlay.trigger : null;
        element.setAttribute('aria-hidden', 'true');
        if (options.animate) {
            var closeToken = (element.__raspinaCloseToken || 0) + 1;
            element.__raspinaCloseToken = closeToken;
            element.classList.add('is-closing');
            element.setAttribute('inert', '');
            window.setTimeout(function () {
                if (element.__raspinaCloseToken !== closeToken || !element.classList.contains('is-closing')) {
                    return;
                }
                element.hidden = true;
                element.classList.remove('is-closing');
                element.removeAttribute('inert');
            }, 400);
        } else {
            element.hidden = true;
            element.classList.remove('is-closing');
            element.removeAttribute('inert');
        }
        activeOverlay = null;
        setPageLocked(false);
        if (restoreFocus !== false && trigger && typeof trigger.focus === 'function') {
            trigger.focus();
        }
    }

    document.addEventListener('keydown', function (event) {
        if (!activeOverlay) {
            return;
        }
        if (event.key === 'Escape') {
            event.preventDefault();
            var overlayTrigger = activeOverlay.trigger;
            var animateClose = activeOverlay.element.hasAttribute('data-search-dialog');
            closeOverlay(activeOverlay.element, true, animateClose ? { animate: true } : null);
            if (overlayTrigger && overlayTrigger.hasAttribute('aria-expanded')) {
                overlayTrigger.setAttribute('aria-expanded', 'false');
            }
            return;
        }
        if (event.key !== 'Tab') {
            return;
        }
        var items = focusable(activeOverlay.element);
        if (!items.length) {
            event.preventDefault();
            return;
        }
        var first = items[0];
        var last = items[items.length - 1];
        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    });

    function setupNavigation() {
        var menuButtons = all('[data-menu-open]');
        var drawer = one('[data-menu-drawer]');
        var searchButton = one('[data-search-open]');
        var searchDialog = one('[data-search-dialog]');
        var searchInput = one('#global-search');

        function setMenuExpanded(expanded) {
            menuButtons.forEach(function (button) {
                button.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            });
        }

        if (menuButtons.length && drawer) {
            menuButtons.forEach(function (menuButton) {
                menuButton.addEventListener('click', function () {
                    setMenuExpanded(true);
                    drawer.hidden = false;
                    drawer.setAttribute('aria-hidden', 'false');
                    openOverlay(drawer, menuButton, one('[data-menu-close]', drawer));
                });
            });
            all('[data-menu-close]', drawer).forEach(function (button) {
                button.addEventListener('click', function () {
                    setMenuExpanded(false);
                    closeOverlay(drawer, true);
                    setTimeout(function () {
                        drawer.hidden = true;
                        drawer.setAttribute('aria-hidden', 'true');
                    }, 400);
                });
            });
            all('a[href]', drawer).forEach(function (link) {
                link.addEventListener('click', function () {
                    setMenuExpanded(false);
                    closeOverlay(drawer, false);
                    setTimeout(function () {
                        drawer.hidden = true;
                        drawer.setAttribute('aria-hidden', 'true');
                    }, 400);
                });
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    setMenuExpanded(false);
                    closeOverlay(drawer, true);
                    setTimeout(function () {
                        drawer.hidden = true;
                        drawer.setAttribute('aria-hidden', 'true');
                    }, 400);
                }
            });
        }

        if (searchButton && searchDialog) {
            searchButton.setAttribute('aria-expanded', 'false');
            searchButton.addEventListener('click', function () {
                searchButton.setAttribute('aria-expanded', 'true');
                searchDialog.hidden = false;
                searchDialog.setAttribute('aria-hidden', 'false');
                openOverlay(searchDialog, searchButton, searchInput);
            });
            all('[data-search-close]', searchDialog).forEach(function (button) {
                button.addEventListener('click', function () {
                    searchButton.setAttribute('aria-expanded', 'false');
                    closeOverlay(searchDialog, true, { animate: true });
                });
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !searchDialog.hidden && !searchDialog.classList.contains('is-closing')) {
                    searchButton.setAttribute('aria-expanded', 'false');
                    closeOverlay(searchDialog, true, { animate: true });
                }
            });
        }
    }

    function setupHeader() {
        var header = one('[data-site-header]');
        if (!header) {
            return;
        }
        var pending = false;
        var lastScrollY = window.pageYOffset;
        var scrollThreshold = 32;
        var isMobile = window.innerWidth <= 768;

        function update() {
            var currentScrollY = window.pageYOffset;
            var isScrolled = currentScrollY > scrollThreshold;
            var isScrollingDown = currentScrollY > lastScrollY;
            var isScrollingUp = currentScrollY < lastScrollY;

            // Add/remove scrolled class
            header.classList.toggle('is-scrolled', isScrolled);

            // Mobile: hide header when scrolling down, show when scrolling up
            if (isMobile && currentScrollY > 100) {
                if (isScrollingDown) {
                    header.classList.add('is-hidden');
                    header.classList.remove('is-visible');
                } else if (isScrollingUp) {
                    header.classList.remove('is-hidden');
                    header.classList.add('is-visible');
                }
            } else {
                header.classList.remove('is-hidden');
                header.classList.remove('is-visible');
            }

            lastScrollY = currentScrollY;
            pending = false;
        }

        window.addEventListener('scroll', function () {
            if (!pending) {
                pending = true;
                window.requestAnimationFrame(update);
            }
        }, {passive: true});

        // Initial update
        update();

        // Update on resize
        window.addEventListener('resize', function () {
            isMobile = window.innerWidth <= 768;
            if (!pending) {
                pending = true;
                window.requestAnimationFrame(update);
            }
        }, {passive: true});
    }

    function pad(value) {
        return value < 10 ? '0' + value : String(value);
    }

    function setupHero() {
        var hero = one('[data-hero]');
        var stage = one('[data-hero-stage]', hero || document);
        var images = parseJsonScript('#hero-data');
        if (!hero || !stage || !Array.isArray(images) || images.length === 0) {
            return;
        }

        var main = one('[data-hero-main]', hero);
        var supportA = one('[data-hero-support-a]', hero);
        var supportB = one('[data-hero-support-b]', hero);
        var status = one('[data-hero-status]', hero);
        var previous = one('[data-hero-prev]', hero);
        var next = one('[data-hero-next]', hero);
        var index = 0;
        var timer = null;
        var pointerStart = null;

        if (status) {
            status.setAttribute('aria-live', 'polite');
            status.setAttribute('aria-atomic', 'true');
        }

        function setImage(node, item) {
            if (!node || !item) {
                return;
            }
            node.classList.add('is-changing');
            window.setTimeout(function () {
                node.src = item.src;
                node.alt = item.alt || '';
                node.classList.remove('is-changing');
            }, reducedMotion ? 0 : 140);
        }

        function render() {
            setImage(main, images[index]);
            setImage(supportA, images[(index + 1) % images.length]);
            setImage(supportB, images[(index + 2) % images.length]);
            if (status) {
                status.textContent = pad(index + 1) + ' / ' + pad(images.length);
            }
        }

        function go(amount) {
            index = (index + amount + images.length) % images.length;
            render();
            restart();
        }

        function stop() {
            if (timer !== null) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        function restart() {
            stop();
            if (!reducedMotion && !document.hidden) {
                timer = window.setInterval(function () { go(1); }, 6500);
            }
        }

        if (previous) {
            previous.addEventListener('click', function () { go(-1); });
        }
        if (next) {
            next.addEventListener('click', function () { go(1); });
        }
        stage.addEventListener('mouseenter', stop);
        stage.addEventListener('mouseleave', restart);
        stage.addEventListener('focusin', stop);
        stage.addEventListener('focusout', restart);
        document.addEventListener('visibilitychange', restart);

        stage.addEventListener('pointerdown', function (event) {
            pointerStart = {x: event.clientX, y: event.clientY};
        }, {passive: true});
        stage.addEventListener('pointerup', function (event) {
            if (!pointerStart) {
                return;
            }
            var distanceX = event.clientX - pointerStart.x;
            var distanceY = event.clientY - pointerStart.y;
            pointerStart = null;
            if (Math.abs(distanceX) > 50 && Math.abs(distanceX) > Math.abs(distanceY)) {
                go(distanceX < 0 ? 1 : -1);
            }
        }, {passive: true});

        if (!reducedMotion && window.matchMedia('(pointer: fine)').matches) {
            stage.addEventListener('pointermove', function (event) {
                var bounds = stage.getBoundingClientRect();
                var x = ((event.clientX - bounds.left) / bounds.width - 0.5) * 12;
                var y = ((event.clientY - bounds.top) / bounds.height - 0.5) * 12;
                stage.style.setProperty('--hero-x', x.toFixed(2) + 'px');
                stage.style.setProperty('--hero-y', y.toFixed(2) + 'px');
            }, {passive: true});
            stage.addEventListener('pointerleave', function () {
                stage.style.setProperty('--hero-x', '0px');
                stage.style.setProperty('--hero-y', '0px');
            });
        }
        restart();
    }

    function setupReveal() {
        var items = all('.reveal');
        if (!items.length) {
            return;
        }
        if (reducedMotion || !('IntersectionObserver' in window)) {
            items.forEach(function (item) { item.classList.add('is-visible'); });
            return;
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {rootMargin: '0px 0px -8% 0px', threshold: 0.08});
        items.forEach(function (item) { observer.observe(item); });
    }

    function setupFilters() {
        var toggle = one('[data-filter-toggle]');
        var form = one('[data-filter-form]');
        if (!toggle || !form) {
            return;
        }
        toggle.addEventListener('click', function () {
            var open = form.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.textContent = open ? 'Filters −' : 'Filters +';
            if (open) {
                var firstField = one('input, select', form);
                if (firstField) {
                    firstField.focus();
                }
            }
        });
    }

    function validSlug(value) {
        return typeof value === 'string' && /^[a-z0-9][a-z0-9-]{0,119}$/.test(value);
    }

    function normaliseShortlist(value) {
        if (!Array.isArray(value)) {
            return [];
        }
        var unique = [];
        value.forEach(function (slug) {
            if (validSlug(slug) && unique.indexOf(slug) === -1 && unique.length < shortlistLimit) {
                unique.push(slug);
            }
        });
        return unique;
    }

    function readShortlist() {
        try {
            return normaliseShortlist(JSON.parse(window.localStorage.getItem(shortlistKey) || '[]'));
        } catch (error) {
            return [];
        }
    }

    function writeShortlist(slugs) {
        slugs = normaliseShortlist(slugs);
        try {
            window.localStorage.setItem(shortlistKey, JSON.stringify(slugs));
        } catch (error) {
            /* Private browsing or a full storage quota should not block browsing. */
        }
        refreshShortlist(slugs);
        return slugs;
    }

    function getAnnouncer() {
        var announcer = one('[data-site-announcer]');
        if (!announcer) {
            announcer = document.createElement('div');
            announcer.className = 'sr-only';
            announcer.setAttribute('aria-live', 'polite');
            announcer.setAttribute('aria-atomic', 'true');
            announcer.setAttribute('data-site-announcer', '');
            body.appendChild(announcer);
        }
        return announcer;
    }

    function announce(message) {
        var announcer = getAnnouncer();
        announcer.textContent = '';
        window.setTimeout(function () { announcer.textContent = message; }, 20);
    }

    function updateToggle(button, saved) {
        button.classList.toggle('is-saved', saved);
        button.setAttribute('aria-pressed', saved ? 'true' : 'false');
        var slug = button.getAttribute('data-shortlist-toggle') || 'item';
        var itemName = polishCatalogText(button.getAttribute('data-shortlist-name') || slug.replace(/-/g, ' '));
        var accessibleLabel = (saved ? 'Remove ' : 'Save ') + itemName + (saved ? ' from shortlist' : ' to shortlist');
        var hiddenLabel = one('.sr-only', button);
        if (hiddenLabel) {
            hiddenLabel.textContent = accessibleLabel;
        } else {
            button.textContent = saved ? '✓ Saved to inquiry shortlist' : '＋ Add to inquiry shortlist';
        }
        button.setAttribute('aria-label', accessibleLabel);
    }

    function refreshShortlist(slugs) {
        slugs = normaliseShortlist(slugs || readShortlist());
        all('[data-shortlist-count]').forEach(function (counter) {
            counter.textContent = String(slugs.length);
            if (counter.getAttribute('aria-hidden') !== 'true') {
                counter.setAttribute('aria-label', slugs.length + (slugs.length === 1 ? ' saved item' : ' saved items'));
            }
        });
        all('[data-shortlist-link]').forEach(function (link) {
            link.setAttribute('aria-label', 'Open inquiry shortlist, ' + slugs.length + (slugs.length === 1 ? ' saved item' : ' saved items'));
        });
        all('[data-shortlist-clear]').forEach(function (button) {
            button.disabled = slugs.length === 0;
            button.setAttribute('aria-disabled', slugs.length === 0 ? 'true' : 'false');
        });
        all('[data-shortlist-toggle]').forEach(function (button) {
            updateToggle(button, slugs.indexOf(button.getAttribute('data-shortlist-toggle')) !== -1);
        });
        renderWishlist(slugs);
        updateInquiryForm(slugs);
    }

    function toggleShortlist(slug) {
        if (!validSlug(slug)) {
            return;
        }
        var slugs = readShortlist();
        var index = slugs.indexOf(slug);
        var saved;
        if (index === -1) {
            if (slugs.length >= shortlistLimit) {
                announce('Your shortlist can contain up to ' + shortlistLimit + ' pieces.');
                return;
            }
            slugs.push(slug);
            saved = true;
        } else {
            slugs.splice(index, 1);
            saved = false;
        }
        writeShortlist(slugs);
        announce(saved ? 'Piece added to your inquiry shortlist.' : 'Piece removed from your inquiry shortlist.');
    }

    function setupShortlist() {
        document.addEventListener('click', function (event) {
            var target = event.target;
            var toggle = target instanceof Element ? target.closest('[data-shortlist-toggle]') : null;
            if (!toggle) {
                return;
            }
            event.preventDefault();
            toggleShortlist(toggle.getAttribute('data-shortlist-toggle') || '');
        });
        window.addEventListener('storage', function (event) {
            if (event.key === shortlistKey) {
                refreshShortlist(readShortlist());
            }
        });
        var clearButton = one('[data-shortlist-clear]');
        if (clearButton) {
            clearButton.addEventListener('click', function () {
                var slugs = readShortlist();
                if (!slugs.length || window.confirm('Clear every piece from your inquiry shortlist?')) {
                    writeShortlist([]);
                    announce('Your inquiry shortlist has been cleared.');
                }
            });
        }
        refreshShortlist(readShortlist());
    }

    function textElement(tag, className, text) {
        var element = document.createElement(tag);
        if (className) {
            element.className = className;
        }
        element.textContent = text;
        return element;
    }

    function renderWishlist(slugs) {
        var grid = one('[data-wishlist-grid]');
        if (!grid) {
            return;
        }
        var catalogue = parseJsonScript('#wishlist-catalog');
        if (!Array.isArray(catalogue)) {
            catalogue = [];
        }
        var bySlug = {};
        catalogue.forEach(function (product) {
            if (product && validSlug(product.slug)) {
                bySlug[product.slug] = product;
            }
        });
        var products = slugs.map(function (slug) { return bySlug[slug]; }).filter(Boolean);
        grid.textContent = '';

        products.forEach(function (product) {
            var article = document.createElement('article');
            article.className = 'wishlist-item';

            if (product.image) {
                var image = document.createElement('img');
                image.src = product.image;
                image.alt = polishCatalogText(product.name || 'Raspina product');
                image.width = 180;
                image.height = 220;
                image.loading = 'lazy';
                image.decoding = 'async';
                article.appendChild(image);
                installImageFallback(image, 'Image unavailable for ' + image.alt);
            } else {
                article.appendChild(textElement('div', 'wishlist-placeholder', 'R'));
            }

            var copy = document.createElement('div');
            copy.appendChild(textElement('small', '', (product.category || 'Raspina collection') + ' / SKU ' + (product.sku || 'N/A')));
            copy.appendChild(textElement('h2', '', polishCatalogText(product.name || product.slug)));
            copy.appendChild(textElement('p', '', product.stock_status === 'instock' ? 'Available to quote' : 'Archive / confirm availability'));
            article.appendChild(copy);

            var actions = document.createElement('div');
            actions.className = 'wishlist-item-actions';
            var view = textElement('a', '', 'View piece ↗');
            view.href = product.url || '#';
            actions.appendChild(view);
            var remove = textElement('button', '', 'Remove');
            remove.type = 'button';
            remove.setAttribute('data-shortlist-toggle', product.slug);
            remove.setAttribute('data-shortlist-name', polishCatalogText(product.name || product.slug));
            remove.setAttribute('aria-pressed', 'true');
            remove.setAttribute('aria-label', 'Remove ' + polishCatalogText(product.name || product.slug) + ' from shortlist');
            actions.appendChild(remove);
            article.appendChild(actions);
            grid.appendChild(article);
        });

        var empty = one('[data-wishlist-empty]');
        var callToAction = one('[data-wishlist-cta]');
        if (empty) {
            empty.hidden = products.length > 0;
        }
        if (callToAction) {
            callToAction.hidden = products.length === 0;
        }
    }

    function updateInquiryForm(slugs) {
        var form = one('[data-inquiry-form]');
        var input = one('[data-product-slugs]', form || document);
        if (!form || !input) {
            return;
        }
        var initial = input.getAttribute('data-initial-slugs');
        if (initial === null) {
            initial = input.value || '';
            input.setAttribute('data-initial-slugs', initial);
        }
        var requested = initial.split(',').filter(validSlug);
        input.value = normaliseShortlist(requested.concat(slugs)).join(',');
    }

    function setupInquiryForm() {
        var form = one('[data-inquiry-form]');
        if (!form) {
            return;
        }
        try {
            if (/[?&]sent=1(?:&|$)/.test(window.location.search) && window.sessionStorage.getItem('raspina_inquiry_submitted') === '1') {
                window.sessionStorage.removeItem('raspina_inquiry_submitted');
                writeShortlist([]);
            }
        } catch (error) {
            /* Session storage is an enhancement only. */
        }
        form.addEventListener('submit', function () {
            updateInquiryForm(readShortlist());
            if (!form.checkValidity()) {
                return;
            }
            try {
                window.sessionStorage.setItem('raspina_inquiry_submitted', '1');
            } catch (error) {
                /* Continue with the server submission. */
            }
            var submit = one('[type="submit"]', form);
            if (submit) {
                submit.disabled = true;
                submit.setAttribute('aria-disabled', 'true');
                submit.textContent = 'Sending request…';
            }
        });
    }

    function setupDisclosures() {
        all('details[data-disclosure]').forEach(function (details) {
            var summary = one('summary', details);
            var marker = summary ? one('span[aria-hidden="true"]', summary) : null;
            if (!summary) {
                return;
            }
            function sync() {
                summary.setAttribute('aria-expanded', details.open ? 'true' : 'false');
                if (marker) {
                    marker.textContent = details.open ? '−' : '+';
                }
            }
            details.addEventListener('toggle', sync);
            sync();
        });
    }

    function setupGallery() {
        var dialog = one('[data-gallery-dialog]');
        var dataNode = one('[data-gallery-data]', dialog || document);
        if (!dialog || !dataNode) {
            return;
        }
        var images;
        try {
            images = JSON.parse(dataNode.textContent || '[]');
        } catch (error) {
            images = [];
        }
        if (!Array.isArray(images) || !images.length) {
            return;
        }
        var image = one('[data-gallery-image]', dialog);
        var caption = one('[data-gallery-caption]', dialog);
        var closeButton = one('[data-gallery-close]', dialog);
        var previousButton = one('[data-gallery-prev]', dialog);
        var nextButton = one('[data-gallery-next]', dialog);
        var current = 0;
        var opener = null;
        var swipeStart = null;

        function render(index) {
            current = (index + images.length) % images.length;
            if (image) {
                image.src = images[current].src;
                image.alt = images[current].alt || '';
            }
            if (caption) {
                caption.textContent = (current + 1) + ' / ' + images.length;
            }
            if (previousButton) {
                previousButton.setAttribute('aria-label', 'Previous product image, currently ' + (current + 1) + ' of ' + images.length);
            }
            if (nextButton) {
                nextButton.setAttribute('aria-label', 'Next product image, currently ' + (current + 1) + ' of ' + images.length);
            }
        }

        function close() {
            if (typeof dialog.close === 'function' && dialog.open) {
                dialog.close();
            } else {
                dialog.removeAttribute('open');
                dialog.hidden = true;
            }
            dialog.setAttribute('aria-hidden', 'true');
            setPageLocked(false);
            if (opener) {
                opener.focus();
            }
        }

        all('[data-gallery-open]').forEach(function (button) {
            button.addEventListener('click', function () {
                opener = button;
                render(parseInt(button.getAttribute('data-gallery-open') || '0', 10));
                if (typeof dialog.showModal === 'function') {
                    dialog.showModal();
                } else {
                    dialog.hidden = false;
                    dialog.setAttribute('open', '');
                    dialog.setAttribute('role', 'dialog');
                    dialog.setAttribute('aria-modal', 'true');
                }
                dialog.setAttribute('aria-hidden', 'false');
                setPageLocked(true);
                if (closeButton) {
                    closeButton.focus();
                }
            });
        });
        if (closeButton) {
            closeButton.addEventListener('click', close);
        }
        if (previousButton) {
            previousButton.addEventListener('click', function () { render(current - 1); });
        }
        if (nextButton) {
            nextButton.addEventListener('click', function () { render(current + 1); });
        }
        dialog.addEventListener('cancel', function (event) {
            event.preventDefault();
            close();
        });
        dialog.addEventListener('click', function (event) {
            if (event.target === dialog) {
                close();
            }
        });
        dialog.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                event.preventDefault();
                close();
            } else if (event.key === 'ArrowLeft' && images.length > 1) {
                event.preventDefault();
                render(current - 1);
            } else if (event.key === 'ArrowRight' && images.length > 1) {
                event.preventDefault();
                render(current + 1);
            } else if (event.key === 'Tab') {
                var items = focusable(dialog);
                if (!items.length) {
                    event.preventDefault();
                } else if (event.shiftKey && document.activeElement === items[0]) {
                    event.preventDefault();
                    items[items.length - 1].focus();
                } else if (!event.shiftKey && document.activeElement === items[items.length - 1]) {
                    event.preventDefault();
                    items[0].focus();
                }
            }
        });
        dialog.addEventListener('pointerdown', function (event) {
            swipeStart = {x: event.clientX, y: event.clientY};
        }, {passive: true});
        dialog.addEventListener('pointerup', function (event) {
            if (!swipeStart) {
                return;
            }
            var dx = event.clientX - swipeStart.x;
            var dy = event.clientY - swipeStart.y;
            swipeStart = null;
            if (Math.abs(dx) > 55 && Math.abs(dx) > Math.abs(dy)) {
                render(current + (dx < 0 ? 1 : -1));
            }
        }, {passive: true});
    }

    function setupFooter() {
        var footer = one('[data-site-footer]');
        if (!footer) {
            return;
        }

        var backToTop = one('[data-back-to-top]', footer);
        var revealItems = all('.footer-reveal', footer);
        var pointerFrame = 0;
        var pointerX = 50;
        var pointerY = 28;

        if (backToTop) {
            backToTop.addEventListener('click', function () {
                window.scrollTo({
                    top: 0,
                    left: 0,
                    behavior: reducedMotion ? 'auto' : 'smooth'
                });
            });
        }

        if (reducedMotion || !('IntersectionObserver' in window)) {
            revealItems.forEach(function (item) {
                item.classList.add('is-visible');
            });
        } else {
            var footerObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        footerObserver.unobserve(entry.target);
                    }
                });
            }, {
                threshold: .08,
                rootMargin: '0px 0px -6% 0px'
            });

            revealItems.forEach(function (item) {
                footerObserver.observe(item);
            });
        }

        function paintGlow() {
            footer.style.setProperty('--footer-glow-x', pointerX.toFixed(2) + '%');
            footer.style.setProperty('--footer-glow-y', pointerY.toFixed(2) + '%');
            pointerFrame = 0;
        }

        footer.addEventListener('pointermove', function (event) {
            if (reducedMotion || event.pointerType === 'touch') {
                return;
            }
            var bounds = footer.getBoundingClientRect();
            pointerX = Math.max(0, Math.min(100, ((event.clientX - bounds.left) / bounds.width) * 100));
            pointerY = Math.max(0, Math.min(100, ((event.clientY - bounds.top) / bounds.height) * 100));
            if (!pointerFrame) {
                pointerFrame = window.requestAnimationFrame(paintGlow);
            }
        }, {passive: true});

        footer.addEventListener('pointerleave', function () {
            if (reducedMotion) {
                return;
            }
            pointerX = 50;
            pointerY = 28;
            if (!pointerFrame) {
                pointerFrame = window.requestAnimationFrame(paintGlow);
            }
        }, {passive: true});
    }

    setupNavigation();
    setupHeader();
    setupHero();
    setupReveal();
    setupFilters();
    setupImageFallbacks();
    polishVisibleCatalogTypos();
    setupShortlist();
    setupInquiryForm();
    setupDisclosures();
    setupGallery();
    setupFooter();

    documentElement.classList.add('js-ready');
}());

/* Interactive product vortex used only on the homepage. */
(function () {
    'use strict';

    var hero = document.getElementById('raspinaHero');
    var canvas = document.getElementById('raspinaVortexCanvas');
    var dataNode = document.getElementById('raspina-hero-data');
    if (!hero || !canvas || !dataNode || !canvas.getContext) {
        return;
    }

    var context = canvas.getContext('2d');
    if (!context) {
        return;
    }

    var items;
    try {
        items = JSON.parse(dataNode.textContent || '[]');
    } catch (error) {
        items = [];
    }
    if (!Array.isArray(items) || !items.length) {
        return;
    }

    var mediaQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
    var reducedMotion = !!(mediaQuery && mediaQuery.matches);
    var images = items.map(function (item) {
        var image = new Image();
        image.decoding = 'async';
        image.src = item.src;
        return image;
    });
    var cards = [];
    var pointerX = 0;
    var pointerY = 0;
    var targetAngle = -.45;
    var currentAngle = -.45;
    var frameId = 0;
    var pageVisible = !document.hidden;
    var lastTimestamp = 0;
    var lastFocused = null;

    var modal = document.getElementById('raspinaHeroModal');
    var modalImage = document.getElementById('raspinaHeroModalImg');
    var modalCategory = document.getElementById('raspinaHeroModalCat');
    var modalTitle = document.getElementById('raspinaHeroModalTitle');
    var modalText = document.getElementById('raspinaHeroModalText');
    var closeButton = document.getElementById('raspinaHeroClose');

    canvas.setAttribute('tabindex', '0');
    canvas.setAttribute('role', 'button');
    canvas.setAttribute('aria-haspopup', 'dialog');

    function roundedRectangle(x, y, width, height, radius) {
        var safeRadius = Math.min(radius, width / 2, height / 2);
        context.beginPath();
        context.moveTo(x + safeRadius, y);
        context.arcTo(x + width, y, x + width, y + height, safeRadius);
        context.arcTo(x + width, y + height, x, y + height, safeRadius);
        context.arcTo(x, y + height, x, y, safeRadius);
        context.arcTo(x, y, x + width, y, safeRadius);
        context.closePath();
    }

    function coverCrop(image, frameWidth, frameHeight) {
        var imageRatio = image.naturalWidth / image.naturalHeight;
        var frameRatio = frameWidth / frameHeight;
        var sourceWidth = image.naturalWidth;
        var sourceHeight = image.naturalHeight;
        var sourceX = 0;
        var sourceY = 0;
        if (imageRatio > frameRatio) {
            sourceWidth = image.naturalHeight * frameRatio;
            sourceX = (image.naturalWidth - sourceWidth) / 2;
        } else {
            sourceHeight = image.naturalWidth / frameRatio;
            sourceY = (image.naturalHeight - sourceHeight) / 2;
        }
        return [sourceX, sourceY, sourceWidth, sourceHeight];
    }

    function resizeCanvas() {
        var bounds = canvas.getBoundingClientRect();
        var ratio = Math.min(window.devicePixelRatio || 1, 2);
        var width = Math.max(1, Math.round(bounds.width * ratio));
        var height = Math.max(1, Math.round(bounds.height * ratio));
        if (canvas.width !== width || canvas.height !== height) {
            canvas.width = width;
            canvas.height = height;
        }
        context.setTransform(ratio, 0, 0, ratio, 0, 0);
        draw(performance.now());
    }

    function buildCards(width, height) {
        var mobile = width < 768;
        var centreX = mobile ? width * .44 + pointerX * 28 : width * .285 + pointerX * 96;
        var centreY = mobile ? height * .3 + pointerY * 12 : height * .53 + pointerY * 54;
        var base = Math.min(width, height);
        var nextCards = [];

        items.forEach(function (item, index) {
            var angle = (index / items.length) * Math.PI * 2 + currentAngle;
            var depth = (Math.sin(angle) + 1) / 2;
            var radius = base * (mobile ? (.21 + depth * .31) : (.2 + depth * .29));
            var x = centreX + Math.cos(angle) * radius;
            var y = centreY + Math.sin(angle) * radius * (mobile ? .48 : .58);
            var cardWidth = mobile ? 72 + depth * 92 : 128 + depth * 164;
            var cardHeight = cardWidth * 1.36;
            nextCards.push({
                index: index,
                x: x - cardWidth / 2,
                y: y - cardHeight / 2,
                width: cardWidth,
                height: cardHeight,
                depth: depth,
                alpha: .38 + depth * .62
            });
        });

        nextCards.sort(function (first, second) {
            return first.depth - second.depth;
        });
        return nextCards;
    }

    function draw(timestamp) {
        var width = canvas.clientWidth;
        var height = canvas.clientHeight;
        if (!width || !height) {
            return;
        }

        var elapsed = lastTimestamp ? Math.min(34, timestamp - lastTimestamp) : 16;
        lastTimestamp = timestamp;
        currentAngle += (targetAngle - currentAngle) * .065;
        if (!reducedMotion && pageVisible) {
            targetAngle += elapsed * .000075;
        }

        context.clearRect(0, 0, width, height);
        cards = buildCards(width, height);

        cards.forEach(function (card) {
            var image = images[card.index];
            var radius = Math.max(12, 22 * (.7 + card.depth * .3));

            context.save();
            context.globalAlpha = card.alpha;
            context.shadowColor = 'rgba(21,18,13,' + (.1 + card.depth * .14).toFixed(3) + ')';
            context.shadowBlur = 12 + card.depth * 22;
            context.shadowOffsetY = 8 + card.depth * 12;
            roundedRectangle(card.x, card.y, card.width, card.height, radius);
            context.fillStyle = '#fffaf0';
            context.fill();
            context.clip();

            if (image.complete && image.naturalWidth > 0) {
                var crop = coverCrop(image, card.width, card.height);
                context.drawImage(
                    image,
                    crop[0],
                    crop[1],
                    crop[2],
                    crop[3],
                    card.x,
                    card.y,
                    card.width,
                    card.height
                );
            } else {
                var gradient = context.createLinearGradient(card.x, card.y, card.x + card.width, card.y + card.height);
                gradient.addColorStop(0, '#fffaf0');
                gradient.addColorStop(1, '#e5d5ae');
                context.fillStyle = gradient;
                context.fillRect(card.x, card.y, card.width, card.height);
            }
            context.restore();

            context.save();
            context.globalAlpha = card.alpha * .92;
            context.strokeStyle = 'rgba(216,182,90,.62)';
            context.lineWidth = 1;
            roundedRectangle(card.x, card.y, card.width, card.height, radius);
            context.stroke();
            context.restore();
        });

        if (!reducedMotion && pageVisible) {
            frameId = window.requestAnimationFrame(draw);
        } else {
            frameId = 0;
        }
    }

    function startAnimation() {
        if (!frameId && pageVisible) {
            lastTimestamp = 0;
            frameId = window.requestAnimationFrame(draw);
        }
    }

    function stopAnimation() {
        if (frameId) {
            window.cancelAnimationFrame(frameId);
            frameId = 0;
        }
    }

    function cardAt(clientX, clientY) {
        var bounds = canvas.getBoundingClientRect();
        var x = clientX - bounds.left;
        var y = clientY - bounds.top;
        var index;
        for (index = cards.length - 1; index >= 0; index -= 1) {
            var card = cards[index];
            if (x >= card.x && x <= card.x + card.width && y >= card.y && y <= card.y + card.height) {
                return card;
            }
        }
        return null;
    }

    function openModal(itemIndex) {
        if (!modal || !items[itemIndex]) {
            return;
        }
        var item = items[itemIndex];
        lastFocused = document.activeElement;
        modalImage.src = item.src;
        modalImage.alt = item.title || 'Selected Raspina product';
        modalCategory.textContent = item.category || 'Raspina Collection';
        modalTitle.textContent = item.title || 'Raspina Product';
        modalText.textContent = item.text || '';
        modal.classList.add('active');
        modal.setAttribute('aria-hidden', 'false');
        if (closeButton) {
            closeButton.focus();
        }
    }

    function closeModal() {
        if (!modal || !modal.classList.contains('active')) {
            return;
        }
        modal.classList.remove('active');
        modal.setAttribute('aria-hidden', 'true');
        if (lastFocused && lastFocused.focus) {
            lastFocused.focus();
        }
    }

    hero.addEventListener('pointermove', function (event) {
        if (reducedMotion || event.pointerType === 'touch') {
            return;
        }
        var bounds = hero.getBoundingClientRect();
        pointerX = (event.clientX - bounds.left) / bounds.width - .5;
        pointerY = (event.clientY - bounds.top) / bounds.height - .5;
    }, {passive: true});

    hero.addEventListener('pointerleave', function () {
        pointerX = 0;
        pointerY = 0;
    }, {passive: true});

    hero.addEventListener('wheel', function (event) {
        if (!reducedMotion) {
            targetAngle += event.deltaY * .0022;
        }
    }, {passive: true});

    canvas.addEventListener('click', function (event) {
        var card = cardAt(event.clientX, event.clientY);
        if (card) {
            openModal(card.index);
        }
    });

    canvas.addEventListener('keydown', function (event) {
        if ((event.key === 'Enter' || event.key === ' ') && cards.length) {
            event.preventDefault();
            openModal(cards[cards.length - 1].index);
        }
    });

    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeModal();
        }
    });

    document.addEventListener('pointerdown', function (event) {
        if (
            modal &&
            modal.classList.contains('active') &&
            !modal.contains(event.target) &&
            event.target !== canvas
        ) {
            closeModal();
        }
    });

    document.addEventListener('visibilitychange', function () {
        pageVisible = !document.hidden;
        if (pageVisible) {
            startAnimation();
        } else {
            stopAnimation();
        }
    });

    function motionPreferenceChanged(event) {
        reducedMotion = event.matches;
        if (reducedMotion) {
            stopAnimation();
            draw(performance.now());
        } else {
            startAnimation();
        }
    }

    if (mediaQuery) {
        if (mediaQuery.addEventListener) {
            mediaQuery.addEventListener('change', motionPreferenceChanged);
        } else if (mediaQuery.addListener) {
            mediaQuery.addListener(motionPreferenceChanged);
        }
    }

    images.forEach(function (image) {
        image.addEventListener('load', function () {
            if (reducedMotion || !frameId) {
                draw(performance.now());
            }
        });
    });

    if (window.ResizeObserver) {
        new ResizeObserver(resizeCanvas).observe(canvas);
    } else {
        window.addEventListener('resize', resizeCanvas, {passive: true});
    }

    resizeCanvas();
    startAnimation();
}());
