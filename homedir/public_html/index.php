<?php
declare(strict_types=1);

require __DIR__ . '/app/bootstrap.php';
require APP_DIR . '/AdminRepository.php';
require APP_DIR . '/admin.php';

$path = request_path($config);
$method = strtoupper(isset($_SERVER['REQUEST_METHOD']) ? (string) $_SERVER['REQUEST_METHOD'] : 'GET');

if (admin_handle_request($path, $method, $config, $catalog)) {
    exit;
}

if ($method === 'POST' && ($path === '/contact' || $path === '/wholesale')) {
    $type = $path === '/wholesale' ? 'wholesale' : 'contact';
    $result = handle_public_form($type, $catalog, $config);
    set_form_flash($result);
    redirect_to(url($path . ($result['ok'] ? '?sent=1' : '?error=1')), 303);
}

if ($method !== 'GET' && $method !== 'HEAD') {
    http_response_code(405);
    header('Allow: GET, HEAD, POST');
    render_page('404', array(
        'page_title' => 'Method not allowed',
        'meta_description' => 'The requested method is not available.',
        'canonical_path' => '/404',
    ));
    exit;
}

if ($path === '/sitemap.xml') {
    render_sitemap($catalog, $config);
    exit;
}

$route = trim($path, '/');
$segments = $route === '' ? array() : explode('/', $route);
$common = array(
    'canonical_path' => $path,
    'form_flash' => consume_form_flash(),
);

if ($path === '/') {
    render_page('home', array_merge($common, array(
        'page_title' => 'Women’s Clothing Wholesale',
        'meta_description' => 'Discover Raspina Clothing collections for boutiques, distributors and international wholesale buyers.',
        'featured_products' => $catalog->featuredProducts(8),
        'hero_products' => $catalog->featuredProducts(12),
        'home_collections' => $catalog->homeCollections(),
        'body_class' => 'is-home',
    )));
    exit;
}

if ($path === '/shop') {
    $filters = shop_filters();
    $results = $catalog->queryProducts($filters, positive_int($_GET['page'] ?? 1), 16);
    render_page('shop', array_merge($common, array(
        'page_title' => 'Shop the wholesale collection',
        'meta_description' => 'Search and filter the complete Raspina Clothing wholesale collection.',
        'results' => $results,
        'filters' => $filters,
        'categories' => $catalog->categories(),
        'archive_title' => 'The complete collection',
        'archive_kicker' => 'Wholesale catalogue',
        'body_class' => 'is-archive',
    )));
    exit;
}

if (count($segments) === 2 && $segments[0] === 'product-category') {
    redirect_to(url('/collection/' . safe_slug($segments[1])), 301);
}

if (count($segments) === 2 && $segments[0] === 'collection') {
    $slug = safe_slug($segments[1]);
    $category = $catalog->categoryBySlug($slug);
    if ($category !== null) {
        $filters = shop_filters();
        $filters['category'] = $slug;
        $results = $catalog->queryProducts($filters, positive_int($_GET['page'] ?? 1), 16);
        render_page('shop', array_merge($common, array(
            'page_title' => $category['name'] . ' collection',
            'meta_description' => 'Browse ' . $category['name'] . ' by Raspina Clothing for wholesale enquiries.',
            'canonical_path' => '/collection/' . $slug,
            'results' => $results,
            'filters' => $filters,
            'categories' => $catalog->categories(),
            'archive_title' => $category['name'],
            'archive_kicker' => 'Collection ' . str_pad((string) $category['product_count'], 2, '0', STR_PAD_LEFT),
            'current_category' => $category,
            'body_class' => 'is-archive',
        )));
        exit;
    }
}

if (count($segments) === 2 && $segments[0] === 'product') {
    $slug = safe_slug($segments[1]);
    $product = $catalog->productBySlug($slug);
    if ($product !== null) {
        render_page('product', array_merge($common, array(
            'page_title' => $product['name'],
            'meta_description' => product_meta_description($product),
            'canonical_path' => '/product/' . $product['slug'],
            'product' => $product,
            'related_products' => $catalog->relatedProducts($product, 4),
            'adjacent_products' => $catalog->adjacentProducts($product['slug']),
            'body_class' => 'is-product',
        )));
        exit;
    }
}

if ($path === '/search') {
    $query = clean_query($_GET['q'] ?? '');
    $filters = array('q' => $query, 'category' => '', 'stock' => '', 'sort' => 'latest');
    $results = $catalog->queryProducts($filters, positive_int($_GET['page'] ?? 1), 16);
    render_page('search', array_merge($common, array(
        'page_title' => $query === '' ? 'Search' : 'Search results for “' . $query . '”',
        'meta_description' => 'Search the Raspina Clothing wholesale catalogue.',
        'canonical_path' => '/search',
        'query' => $query,
        'results' => $results,
        'body_class' => 'is-search',
    )));
    exit;
}

$simplePages = array(
    '/catalog' => array('catalog', 'Catalog', 'Explore Raspina Clothing editorial catalogues and wholesale collection highlights.'),
    '/about-us' => array('about', 'About Raspina', 'Learn about Raspina Clothing, our design approach and wholesale production in Tehran.'),
    '/contact' => array('contact', 'Contact', 'Contact Raspina Clothing for collection, distribution and wholesale enquiries.'),
    '/wholesale' => array('wholesale', 'Wholesale enquiries', 'Start a wholesale conversation with Raspina Clothing.'),
    '/wishlist' => array('wishlist', 'Your inquiry shortlist', 'Review garments saved for your Raspina wholesale enquiry.'),
    '/privacy' => array('privacy', 'Privacy notice', 'A concise privacy notice for Raspina Clothing website visitors.'),
    '/terms' => array('terms', 'Terms of use', 'Website terms for the Raspina Clothing catalogue.'),
);

if (isset($simplePages[$path])) {
    $definition = $simplePages[$path];
    $data = array_merge($common, array(
        'page_title' => $definition[1],
        'meta_description' => $definition[2],
        'body_class' => 'is-' . $definition[0],
    ));
    if ($path === '/catalog') {
        $data['catalog_products'] = $catalog->featuredProducts(12);
    }
    if ($path === '/wishlist') {
        $data['wishlist_catalog'] = $catalog->wishlistIndex();
    }
    if ($path === '/wholesale') {
        $requestedSlug = safe_slug($_GET['product'] ?? '');
        $data['requested_product'] = $requestedSlug !== '' ? $catalog->productBySlug($requestedSlug) : null;
    }
    render_page($definition[0], $data);
    exit;
}

http_response_code(404);
render_page('404', array_merge($common, array(
    'page_title' => 'Page not found',
    'meta_description' => 'The requested page could not be found.',
    'canonical_path' => '/404',
    'body_class' => 'is-404',
)));
