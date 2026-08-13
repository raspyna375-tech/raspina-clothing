<?php
declare(strict_types=1);

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function safe_slug($value): string
{
    $value = strtolower(trim((string) $value));
    return preg_match('/^[a-z0-9][a-z0-9-]{0,119}$/', $value) ? $value : '';
}

function clean_query($value): string
{
    $value = trim(strip_tags((string) $value));
    $value = preg_replace('/\s+/u', ' ', $value) ?: '';
    return string_limit($value, 80);
}

function string_limit(string $value, int $limit): string
{
    if (function_exists('mb_substr')) {
        return (string) mb_substr($value, 0, $limit, 'UTF-8');
    }
    return substr($value, 0, $limit);
}

function safe_error_summary(Throwable $exception): string
{
    $message = preg_replace('/(?:password|passwd|pwd)\s*[=:]\s*[^\s,;]+/i', '$1=[redacted]', (string) $exception->getMessage()) ?: '';
    return string_limit($message, 500);
}

function lower_text(string $value): string
{
    return function_exists('mb_strtolower') ? (string) mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function url(string $path = ''): string
{
    global $config;
    $base = rtrim((string) ($config['site']['base_path'] ?? ''), '/');
    if ($path === '') {
        return $base === '' ? '/' : $base . '/';
    }
    if (preg_match('#^(?:https?:)?//#i', $path)) {
        return $path;
    }
    return $base . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url('/assets/' . ltrim($path, '/'));
}

function site_setting(string $key, $fallback = ''): string
{
    global $site_settings, $config;
    if (isset($site_settings) && is_array($site_settings) && array_key_exists($key, $site_settings)) {
        $value = trim((string) $site_settings[$key]);
        if ($value !== '') {
            return $value;
        }
    }
    $parts = explode('.', $key, 2);
    if (count($parts) === 2 && isset($config[$parts[0]][$parts[1]]) && is_scalar($config[$parts[0]][$parts[1]])) {
        return (string) $config[$parts[0]][$parts[1]];
    }
    return (string) $fallback;
}

function load_site_settings(array $config): array
{
    if (empty($config['database']['enabled'])) {
        return array();
    }
    $db = isset($config['database']) && is_array($config['database']) ? $config['database'] : array();
    $prefix = (string) ($db['prefix'] ?? 'rc_');
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $prefix)) {
        $prefix = 'rc_';
    }
    try {
        $dsn = 'mysql:host=' . (string) ($db['host'] ?? 'localhost') . ';port=' . (string) ($db['port'] ?? '3306') . ';dbname=' . (string) ($db['name'] ?? '') . ';charset=utf8mb4';
        $pdo = new PDO($dsn, (string) ($db['user'] ?? ''), (string) ($db['password'] ?? ''), array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ));
        $statement = $pdo->query("SELECT meta_key, meta_value FROM {$prefix}meta");
        $settings = array();
        foreach ($statement->fetchAll() as $row) {
            $settings[(string) $row['meta_key']] = (string) $row['meta_value'];
        }
        return $settings;
    } catch (Throwable $exception) {
        error_log('Site settings unavailable; configured fallbacks selected. ' . safe_error_summary($exception));
        return array();
    }
}

function request_path(array $config): string
{
    $uri = isset($_SERVER['REQUEST_URI']) ? (string) $_SERVER['REQUEST_URI'] : '/';
    $path = (string) parse_url($uri, PHP_URL_PATH);
    $path = rawurldecode($path);
    $base = rtrim((string) ($config['site']['base_path'] ?? ''), '/');
    if ($base !== '' && strpos($path, $base) === 0) {
        $path = substr($path, strlen($base));
    }
    $path = '/' . trim($path, '/');
    return $path === '//' ? '/' : $path;
}

function canonical_url(string $path): string
{
    global $config;
    $origin = rtrim((string) ($config['site']['url'] ?? ''), '/');
    if ($origin === '') {
        $host = preg_replace('/[^a-z0-9.:-]/i', '', (string) ($_SERVER['HTTP_HOST'] ?? 'raspinaclothing.com'));
        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $origin = $scheme . '://' . $host;
    }
    return $origin . url($path);
}

function redirect_to(string $location, int $status = 302): void
{
    header('Location: ' . $location, true, $status);
    exit;
}

function positive_int($value): int
{
    $number = filter_var($value, FILTER_VALIDATE_INT, array('options' => array('min_range' => 1)));
    return $number === false ? 1 : min((int) $number, 10000);
}

function shop_filters(): array
{
    $sort = (string) ($_GET['sort'] ?? 'latest');
    if (!in_array($sort, array('latest', 'az', 'za', 'sku', 'price-low', 'price-high'), true)) {
        $sort = 'latest';
    }
    $stock = (string) ($_GET['stock'] ?? '');
    if (!in_array($stock, array('', 'instock', 'onbackorder', 'outofstock'), true)) {
        $stock = '';
    }
    return array(
        'q' => clean_query($_GET['q'] ?? ''),
        'category' => safe_slug($_GET['category'] ?? ''),
        'stock' => $stock,
        'sort' => $sort,
    );
}

function product_image(array $product, int $index = 0): string
{
    if (!empty($product['images'][$index])) {
        return url((string) $product['images'][$index]);
    }
    return '';
}

function product_category(array $product): array
{
    if (!empty($product['categories'][0]) && is_array($product['categories'][0])) {
        return $product['categories'][0];
    }
    return array('name' => 'Raspina collection', 'slug' => '');
}

function clean_product_copy(string $value): string
{
    $value = html_entity_decode(strip_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $value = preg_replace('/Phone\s*number\s*:\s*\+?[0-9\s-]+/iu', '', $value) ?: $value;
    $value = preg_replace('/Admin[^:]{0,30}:\s*\+?[0-9\s-]+/iu', '', $value) ?: $value;
    $value = preg_replace('/\(?\s*Costumizable[^)]*\)?/iu', '', $value) ?: $value;
    return trim(preg_replace('/\s+/u', ' ', $value) ?: '');
}

function product_meta_description(array $product): string
{
    $copy = clean_product_copy((string) ($product['summary'] ?: $product['description']));
    if ($copy === '') {
        $copy = 'Discover ' . $product['name'] . ' by Raspina Clothing and request a current wholesale quotation.';
    }
    return string_limit($copy, 155);
}

function money_reference($value, string $currency): string
{
    if ($value === null || $value === '' || !is_numeric($value) || (float) $value <= 0) {
        return '';
    }
    $code = strtoupper($currency ?: 'IRR');
    return number_format((float) $value, 0) . ' ' . $code;
}

function pagination_url(int $page): string
{
    $query = $_GET;
    if ($page <= 1) {
        unset($query['page']);
    } else {
        $query['page'] = $page;
    }
    $suffix = $query ? '?' . http_build_query($query) : '';
    global $config;
    return url(request_path($config) . $suffix);
}

function render_page(string $view, array $data = array()): void
{
    global $config, $catalog;
    $file = APP_DIR . '/templates/pages/' . basename($view) . '.php';
    if (!is_file($file)) {
        throw new RuntimeException('Template is unavailable.');
    }
    extract($data, EXTR_SKIP);
    ob_start();
    require $file;
    $content = (string) ob_get_clean();
    require APP_DIR . '/templates/layout.php';
}

function render_product_card(array $product): void
{
    require APP_DIR . '/templates/components/product-card.php';
}

function active_nav(string $path): string
{
    global $config;
    $current = request_path($config);
    return ($current === $path || ($path !== '/' && strpos($current, $path . '/') === 0)) ? ' aria-current="page"' : '';
}

function json_for_html($value): string
{
    return (string) json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
}

function render_sitemap(CatalogRepository $catalog, array $config): void
{
    header('Content-Type: application/xml; charset=UTF-8');
    $paths = array('/', '/shop', '/catalog', '/about-us', '/contact', '/wholesale', '/privacy', '/terms');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    foreach ($paths as $path) {
        echo '<url><loc>' . e(canonical_url($path)) . '</loc></url>';
    }
    foreach ($catalog->categories() as $category) {
        echo '<url><loc>' . e(canonical_url('/collection/' . $category['slug'])) . '</loc></url>';
    }
    foreach ($catalog->allProducts() as $product) {
        echo '<url><loc>' . e(canonical_url('/product/' . $product['slug'])) . '</loc></url>';
    }
    echo '</urlset>';
}

function get_current_lang(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    if (isset($_GET['lang'])) {
        $lang = strtolower((string) $_GET['lang']);
        if (in_array($lang, array('en', 'ru', 'ar'), true)) {
            $_SESSION['lang'] = $lang;
            return $lang;
        }
    }
    if (isset($_SESSION['lang'])) {
        return (string) $_SESSION['lang'];
    }
    return 'en';
}

function lang_url(string $lang): string
{
    $query = $_GET;
    $query['lang'] = $lang;
    return '?' . http_build_query($query);
}

function t(string $key, string $default = ''): string
{
    static $translations = null;
    if ($translations === null) {
        $file = __DIR__ . '/translations.php';
        if (is_file($file)) {
            $translations = require $file;
        } else {
            $translations = array();
        }
    }
    $lang = get_current_lang();
    if ($lang === 'en') {
        return $default !== '' ? $default : $key;
    }
    if (isset($translations[$lang][$key])) {
        return $translations[$lang][$key];
    }
    return $default !== '' ? $default : $key;
}
