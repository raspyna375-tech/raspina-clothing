<?php
$page_title = isset($page_title) ? (string) $page_title : 'Raspina Clothing';
$meta_description = isset($meta_description) ? (string) $meta_description : 'Raspina Clothing wholesale collections.';
$canonical_path = isset($canonical_path) ? (string) $canonical_path : '/';
$body_class = isset($body_class) ? (string) $body_class : '';
$fullTitle = $page_title === 'Raspina Clothing' ? $page_title : $page_title . ' — Raspina Clothing';
$cssVersion = (string) (@filemtime(dirname(__DIR__, 2) . '/assets/css/site.css') ?: '20260723');
$jsVersion = (string) (@filemtime(dirname(__DIR__, 2) . '/assets/js/site.js') ?: '20260723');
$siteStylesheet = asset('css/site.css') . '?v=' . rawurlencode($cssVersion);
$siteScript = asset('js/site.js') . '?v=' . rawurlencode($jsVersion);
$rtlStylesheet = asset('css/rtl-fix.css') . '?v=' . rawurlencode($cssVersion);
$catalogFeatureStylesheet = asset('css/catalog-feature.css') . '?v=' . rawurlencode($cssVersion);
$organizationSchema = array(
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'Raspina Clothing',
    'url' => canonical_url('/'),
    'logo' => canonical_url('/assets/images/brand/raspina-logo.png'),
    'email' => $config['site']['email'],
    'telephone' => $config['site']['phone_link'],
    'address' => array('@type' => 'PostalAddress', 'addressLocality' => 'Tehran', 'addressCountry' => 'IR'),
    'sameAs' => array($config['site']['instagram']),
);

$current_lang = get_current_lang();
?>
<!doctype html>
<html lang="<?= e($current_lang) ?>" dir="<?= $current_lang === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e($meta_description) ?>">
    <meta name="theme-color" content="#15120d">
    <title><?= e($fullTitle) ?></title>
    <link rel="canonical" href="<?= e(canonical_url($canonical_path)) ?>">
    <meta property="og:type" content="<?= isset($product) ? 'product' : 'website' ?>">
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <meta property="og:description" content="<?= e($meta_description) ?>">
    <meta property="og:url" content="<?= e(canonical_url($canonical_path)) ?>">
    <meta property="og:site_name" content="Raspina Clothing">
    <meta property="og:image" content="<?= e(isset($product) && product_image($product) !== '' ? canonical_url(product_image($product)) : canonical_url('/assets/images/editorial/shop_01.jpg')) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="icon" href="<?= e(asset('images/brand/raspina-logo.png')) ?>">
    <link rel="preload" href="<?= e($siteStylesheet) ?>" as="style">
    <link rel="stylesheet" href="<?= e($siteStylesheet) ?>">
    <link rel="stylesheet" href="<?= e($catalogFeatureStylesheet) ?>">
    <?php if ($current_lang === 'ar'): ?>
    <link rel="stylesheet" href="<?= e($rtlStylesheet) ?>">
    <?php endif; ?>
    <script type="application/ld+json"><?= json_for_html($organizationSchema) ?></script>
    <?php if (isset($product)): ?>
        <?php
        $productSchema = array(
            '@context' => 'https://schema.org', '@type' => 'Product', 'name' => $product['name'],
            'sku' => $product['sku'], 'description' => product_meta_description($product),
            'url' => canonical_url('/product/' . $product['slug']), 'brand' => array('@type' => 'Brand', 'name' => 'Raspina'),
            'image' => array_map('canonical_url', $product['images']),
        );
        ?>
        <script type="application/ld+json"><?= json_for_html($productSchema) ?></script>
    <?php endif; ?>
</head>
<body class="<?= e($body_class) ?>">
<a class="skip-link" href="#main-content">Skip to content</a>
<div class="sr-only" aria-live="polite" aria-atomic="true" data-site-announcer></div>
<div class="announcement">
    <span><?= e(t('International women’s clothing wholesale')) ?></span>
    <a href="<?= e(url('/wholesale')) ?>"><?= e(t('Start an enquiry')) ?> <span aria-hidden="true">↗</span></a>
</div>
<header class="site-header" data-site-header>
    <div class="header-container">
        <!-- Mobile Menu Toggle (Left) -->
        <button class="header-toggle menu-toggle" type="button" aria-label="Open site menu" aria-expanded="false" aria-controls="mobile-navigation" data-menu-open>
            <span class="sr-only">Open menu</span>
            <span class="toggle-bars" aria-hidden="true"><span></span><span></span><span></span></span>
        </button>

        <!-- Brand Logo (Center) -->
        <a class="header-brand" href="<?= e(url('/')) ?>" aria-label="Raspina Clothing home">
            <img src="<?= e(asset('images/brand/raspina-logo.png')) ?>" width="140" height="52" alt="Raspina Clothing">
        </a>

        <!-- Desktop Navigation (Center-Right) -->
        <nav class="header-nav desktop-nav" aria-label="Primary navigation">
            <a href="<?= e(url('/')) ?>"<?= active_nav('/') ?>><?= e(t('Home')) ?></a>
            <a href="<?= e(url('/shop')) ?>"<?= active_nav('/shop') ?>><?= e(t('Shop')) ?></a>
            <a href="<?= e(url('/about-us')) ?>"<?= active_nav('/about-us') ?>><?= e(t('About us')) ?></a>
            <a href="<?= e(url('/contact')) ?>"<?= active_nav('/contact') ?>><?= e(t('Contact us')) ?></a>
            <a href="<?= e(url('/catalog')) ?>"<?= active_nav('/catalog') ?>><?= e(t('Catalog')) ?></a>
        </nav>

        <!-- Header Actions (Right) -->
        <div class="header-actions">
            <!-- Language Switcher -->
            <div class="lang-switcher" style="display: flex; gap: 0.5rem; font-family: monospace; font-size: 0.85rem; margin-right: 0.5rem; margin-left: 0.5rem; align-items: center;">
                <a href="<?= e(lang_url('en')) ?>" style="<?= $current_lang === 'en' ? 'opacity: 1; font-weight: bold; text-decoration: underline;' : 'opacity: 0.5; text-decoration: none;' ?> color: inherit;">EN</a>
                <span style="opacity: 0.3;">|</span>
                <a href="<?= e(lang_url('ru')) ?>" style="<?= $current_lang === 'ru' ? 'opacity: 1; font-weight: bold; text-decoration: underline;' : 'opacity: 0.5; text-decoration: none;' ?> color: inherit;">RU</a>
                <span style="opacity: 0.3;">|</span>
                <a href="<?= e(lang_url('ar')) ?>" style="<?= $current_lang === 'ar' ? 'opacity: 1; font-weight: bold; text-decoration: underline;' : 'opacity: 0.5; text-decoration: none;' ?> color: inherit;">AR</a>
            </div>

            <button class="header-action search-toggle" type="button" aria-label="Open product search" data-search-open aria-haspopup="dialog" aria-expanded="false" aria-controls="site-search-dialog">
                <span class="action-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 18 18" fill="none" stroke="currentColor" stroke-width="1.5">
                        <circle cx="7.5" cy="7.5" r="6"/>
                        <path d="M12 12L16.5 16.5"/>
                    </svg>
                </span>
                <span class="sr-only"><?= e(t('Search')) ?></span>
            </button>
            <a class="header-action shortlist-link" href="<?= e(url('/wishlist')) ?>" aria-label="Open inquiry shortlist, 0 saved items" data-shortlist-link>
                <span class="action-icon" aria-hidden="true">♡</span>
                <span class="shortlist-label"><?= e(t('Shortlist')) ?></span>
                <span class="shortlist-count" data-shortlist-count aria-hidden="true">0</span>
            </a>
        </div>
    </div>
</header>

<div class="nav-drawer" id="mobile-navigation" role="dialog" aria-modal="true" aria-label="Site navigation" aria-hidden="true" hidden data-menu-drawer>
    <button class="drawer-backdrop" type="button" aria-label="Close menu" data-menu-close></button>
    <nav class="drawer-panel" aria-label="Mobile navigation">
        <div class="drawer-top"><span><?= e(t('Raspina / Menu')) ?></span><button type="button" class="icon-button" aria-label="Close site menu" data-menu-close><span aria-hidden="true">×</span></button></div>
        <a href="<?= e(url('/shop')) ?>"><?= e(t('Shop all')) ?> <span>01</span></a>
        <a href="<?= e(url('/collection/collection-2026')) ?>"><?= e(t('Collection 2026')) ?> <span>02</span></a>
        <a href="<?= e(url('/catalog')) ?>"><?= e(t('Catalog')) ?> <span>03</span></a>
        <a href="<?= e(url('/about-us')) ?>"><?= e(t('About')) ?> <span>04</span></a>
        <a href="<?= e(url('/contact')) ?>"><?= e(t('Contact')) ?> <span>05</span></a>
        <a href="<?= e(url('/wholesale')) ?>"><?= e(t('Wholesale enquiry')) ?> <span>06</span></a>
    </nav>
</div>

<div class="search-dialog" id="site-search-dialog" role="dialog" aria-modal="true" aria-label="Search the collection" aria-hidden="true" hidden data-search-dialog>
    <button class="search-backdrop" type="button" aria-label="Close search" data-search-close></button>
    <div class="search-panel">
        <div class="search-label"><span><?= e(t('Search the archive')) ?></span><button type="button" aria-label="Close site search" data-search-close><?= e(t('Close')) ?> <span aria-hidden="true">×</span></button></div>
        <form action="<?= e(url('/search')) ?>" method="get" role="search">
            <label for="global-search"><?= e(t('What are you looking for?')) ?></label>
            <div class="search-line"><input id="global-search" name="q" type="search" maxlength="80" autocomplete="off" placeholder="<?= e(t('Dress, blouse, SKU…')) ?>"><button type="submit"><?= e(t('Search')) ?> ↗</button></div>
        </form>
        <div class="search-suggestions"><span><?= e(t('Explore')) ?></span><a href="<?= e(url('/collection/dress')) ?>"><?= e(t('Dresses')) ?></a><a href="<?= e(url('/collection/blouse')) ?>"><?= e(t('Blouses')) ?></a><a href="<?= e(url('/collection/collection-2026')) ?>"><?= e(t('2026 collection')) ?></a></div>
    </div>
</div>

<main id="main-content">
    <?= $content ?>
</main>

<footer class="site-footer" data-site-footer aria-labelledby="footer-heading">
    <div class="footer-ambient" aria-hidden="true"></div>
    <div class="footer-shell">
        <div class="footer-intro footer-reveal">
            <p class="footer-index"><?= e(t('Raspina / International wholesale')) ?></p>
            <p class="footer-status"><span aria-hidden="true"></span><?= e(t('Wholesale enquiries open')) ?></p>
        </div>

        <section class="footer-cta footer-reveal">
            <div class="footer-cta-copy">
                <p class="footer-kicker"><?= e(t('Your next edit starts here')) ?></p>
                <h2 id="footer-heading"><?= t('Let’s shape <em>your next</em> collection.') ?></h2>
            </div>
            <div class="footer-cta-action">
                <p><?= e(t('Tell us what your market needs. We’ll help you build a focused Raspina selection for your boutique, distribution network or retail concept.')) ?></p>
                <a class="footer-enquiry-button" href="<?= e(url('/wholesale')) ?>">
                    <span><?= e(t('Start wholesale enquiry')) ?></span>
                    <span class="footer-button-arrow" aria-hidden="true">↗</span>
                </a>
            </div>
        </section>

        <div class="footer-directory footer-reveal">
            <div class="footer-provenance">
                <p class="footer-kicker"><?= e(t('Raspina Clothing')) ?></p>
                <p><?= e(t('Women’s fashion shaped in Tehran and prepared for independent boutiques and international wholesale partners.')) ?></p>
            </div>

            <div class="footer-links">
                <nav aria-label="Footer collection navigation">
                    <p class="footer-label"><?= e(t('Collection')) ?></p>
                    <a href="<?= e(url('/shop')) ?>"><?= e(t('Shop all')) ?> <span aria-hidden="true">↗</span></a>
                    <a href="<?= e(url('/collection/collection-2026')) ?>"><?= e(t('Collection 2026')) ?> <span aria-hidden="true">↗</span></a>
                    <a href="<?= e(url('/catalog')) ?>"><?= e(t('Catalog archive')) ?> <span aria-hidden="true">↗</span></a>
                    <a href="<?= e(url('/wishlist')) ?>"><?= e(t('Inquiry shortlist')) ?> <span aria-hidden="true">↗</span></a>
                </nav>

                <nav aria-label="Footer company navigation">
                    <p class="footer-label"><?= e(t('Company')) ?></p>
                    <a href="<?= e(url('/about-us')) ?>"><?= e(t('About')) ?> <span aria-hidden="true">↗</span></a>
                    <a href="<?= e(url('/wholesale')) ?>"><?= e(t('Wholesale process')) ?> <span aria-hidden="true">↗</span></a>
                    <a href="<?= e(url('/contact')) ?>"><?= e(t('Contact')) ?> <span aria-hidden="true">↗</span></a>
                </nav>

                <address>
                    <p class="footer-label"><?= e(t('Direct contact')) ?></p>
                    <span><?= e(t($config['site']['address'])) ?></span>
                    <a href="tel:<?= e($config['site']['phone_link']) ?>"><?= e($config['site']['phone_display']) ?> <span aria-hidden="true">↗</span></a>
                    <a href="mailto:<?= e($config['site']['email']) ?>"><?= e($config['site']['email']) ?> <span aria-hidden="true">↗</span></a>
                    <a href="<?= e($config['site']['instagram']) ?>" rel="noopener noreferrer" target="_blank">Instagram <span aria-hidden="true">↗</span></a>
                </address>
            </div>
        </div>

        <a class="footer-wordmark footer-reveal" href="<?= e(url('/')) ?>" aria-label="Raspina Clothing home">
            <span aria-hidden="true">R</span><span aria-hidden="true">A</span><span aria-hidden="true">S</span><span aria-hidden="true">P</span><span aria-hidden="true">I</span><span aria-hidden="true">N</span><span aria-hidden="true">A</span>
        </a>

        <div class="footer-base footer-reveal">
            <span>© <?= date('Y') ?> Raspina Clothing</span>
            <span><?= e(t('Designed in Tehran / Prepared for the world')) ?></span>
            <nav class="footer-legal" aria-label="Legal navigation">
                <a href="<?= e(url('/privacy')) ?>"><?= e(t('Privacy')) ?></a>
                <a href="<?= e(url('/terms')) ?>"><?= e(t('Terms')) ?></a>
            </nav>
            <button class="footer-back-top" type="button" data-back-to-top aria-label="Back to the top of this page">
                <span><?= e(t('Back to top')) ?></span><span aria-hidden="true">↑</span>
            </button>
        </div>
    </div>
</footer>

<script src="<?= e($siteScript) ?>" defer></script>
</body>
</html>
