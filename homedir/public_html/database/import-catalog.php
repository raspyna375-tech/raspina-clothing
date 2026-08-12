<?php
declare(strict_types=1);

/*
 * One-time Raspina catalogue importer.
 *
 * CLI only. It reads app/config.php and storage/catalog.json, creates the
 * prefixed schema, and imports catalogue rows in one transaction.
 *
 * Usage:
 *   php database/import-catalog.php --dry-run
 *   php database/import-catalog.php --confirm
 *   php database/import-catalog.php --confirm --force
 */

if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
    http_response_code(404);
    exit;
}

const RASPINA_DEFAULT_PREFIX = 'rc_';
const RASPINA_IMPORT_META_KEY = 'catalog_import';

function importerOut(string $message): void
{
    fwrite(STDOUT, $message . PHP_EOL);
}

function importerError(string $message, int $code = 1): void
{
    fwrite(STDERR, 'Error: ' . $message . PHP_EOL);
    exit($code);
}

function importerLimit($value, int $limit): string
{
    $value = (string) $value;
    if (function_exists('mb_substr')) {
        return (string) mb_substr($value, 0, $limit, 'UTF-8');
    }
    return substr($value, 0, $limit);
}

function importerSlug($value): string
{
    $slug = strtolower(trim((string) $value));
    return preg_match('/^[a-z0-9][a-z0-9-]{0,119}$/', $slug) ? $slug : '';
}

function importerDecimal($value)
{
    if ($value === null || $value === '' || !is_numeric($value) || (float) $value <= 0) {
        return null;
    }
    return number_format((float) $value, 4, '.', '');
}

function importerDate($value): ?string
{
    $value = trim((string) $value);
    if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value)) {
        return null;
    }
    $date = DateTime::createFromFormat('Y-m-d H:i:s', $value);
    return $date instanceof DateTime && $date->format('Y-m-d H:i:s') === $value ? $value : null;
}

function importerImagePath($value): string
{
    $path = trim((string) $value);
    if (strlen($path) > 500 || strpos($path, '..') !== false || strpos($path, '/assets/images/') !== 0) {
        return '';
    }
    return $path;
}

function importerPrefix(array $config): string
{
    $prefix = (string) ($config['database']['prefix'] ?? RASPINA_DEFAULT_PREFIX);
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]{0,31}$/', $prefix)) {
        importerError('The configured table prefix is invalid. Use letters, numbers and underscores only.');
    }
    return $prefix;
}

function importerSchemaStatements(string $schemaPath, string $prefix): array
{
    $schema = @file_get_contents($schemaPath);
    if ($schema === false || trim($schema) === '') {
        throw new RuntimeException('database/schema.sql is unavailable.');
    }
    $schema = preg_replace('/^\s*--.*$/m', '', $schema) ?: $schema;
    if ($prefix !== RASPINA_DEFAULT_PREFIX) {
        $schema = str_replace('`' . RASPINA_DEFAULT_PREFIX, '`' . $prefix, $schema);
    }
    $statements = preg_split('/;\s*(?:\r\n|\r|\n|$)/', $schema) ?: array();
    return array_values(array_filter(array_map('trim', $statements), function (string $statement): bool {
        return $statement !== '';
    }));
}

function importerLoadCatalog(string $path): array
{
    $raw = @file_get_contents($path);
    if ($raw === false) {
        throw new RuntimeException('storage/catalog.json could not be read.');
    }
    $catalog = json_decode($raw, true);
    if (!is_array($catalog) || !isset($catalog['products'], $catalog['categories']) || !is_array($catalog['products']) || !is_array($catalog['categories'])) {
        throw new RuntimeException('storage/catalog.json is not a valid Raspina catalogue.');
    }
    if (count($catalog['products']) === 0 || count($catalog['categories']) === 0) {
        throw new RuntimeException('The catalogue is empty; import was stopped.');
    }
    foreach ($catalog['products'] as $index => $product) {
        if (!is_array($product) || importerSlug($product['slug'] ?? '') === '' || trim((string) ($product['name'] ?? '')) === '') {
            throw new RuntimeException('Invalid product at catalogue position ' . ($index + 1) . '.');
        }
    }
    foreach ($catalog['categories'] as $index => $category) {
        if (!is_array($category) || importerSlug($category['slug'] ?? '') === '' || trim((string) ($category['name'] ?? '')) === '') {
            throw new RuntimeException('Invalid category at catalogue position ' . ($index + 1) . '.');
        }
    }
    return $catalog;
}

function importerConnect(array $config): PDO
{
    $database = $config['database'] ?? array();
    if (empty($database['enabled'])) {
        throw new RuntimeException('Database mode is disabled in app/config.php.');
    }
    foreach (array('host', 'port', 'name', 'user') as $required) {
        if (trim((string) ($database[$required] ?? '')) === '') {
            throw new RuntimeException('Database configuration is incomplete in app/config.php.');
        }
    }
    $dsn = 'mysql:host=' . $database['host'] . ';port=' . $database['port'] . ';dbname=' . $database['name'] . ';charset=utf8mb4';
    return new PDO($dsn, (string) $database['user'], (string) ($database['password'] ?? ''), array(
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ));
}

function importerAcquireLock(PDO $pdo, string $databaseName, string $prefix): string
{
    $name = 'raspina_import_' . substr(hash('sha256', $databaseName . '|' . $prefix), 0, 32);
    $statement = $pdo->prepare('SELECT GET_LOCK(:lock_name, 10)');
    $statement->execute(array(':lock_name' => $name));
    if ((int) $statement->fetchColumn() !== 1) {
        throw new RuntimeException('Another catalogue import is already running.');
    }
    return $name;
}

function importerReleaseLock(PDO $pdo, string $name): void
{
    if ($name === '') {
        return;
    }
    try {
        $statement = $pdo->prepare('SELECT RELEASE_LOCK(:lock_name)');
        $statement->execute(array(':lock_name' => $name));
    } catch (Throwable $exception) {
        /* The MySQL connection will release the lock when it closes. */
    }
}

function importerCreateSchema(PDO $pdo, string $schemaPath, string $prefix): void
{
    foreach (importerSchemaStatements($schemaPath, $prefix) as $statement) {
        $pdo->exec($statement);
    }
}

function importerAlreadyCompleted(PDO $pdo, string $prefix): bool
{
    $statement = $pdo->prepare("SELECT meta_value FROM `{$prefix}meta` WHERE meta_key = :meta_key LIMIT 1");
    $statement->execute(array(':meta_key' => RASPINA_IMPORT_META_KEY));
    return $statement->fetchColumn() !== false;
}

function importerProductCount(PDO $pdo, string $prefix): int
{
    return (int) $pdo->query("SELECT COUNT(*) FROM `{$prefix}products`")->fetchColumn();
}

function importerClearCatalog(PDO $pdo, string $prefix): void
{
    $pdo->exec("DELETE FROM `{$prefix}product_tags`");
    $pdo->exec("DELETE FROM `{$prefix}product_attributes`");
    $pdo->exec("DELETE FROM `{$prefix}product_images`");
    $pdo->exec("DELETE FROM `{$prefix}product_categories`");
    $pdo->exec("DELETE FROM `{$prefix}products`");
    $pdo->exec("DELETE FROM `{$prefix}categories`");
}

function importerInsertCatalog(PDO $pdo, array $catalog, string $prefix, string $catalogPath): array
{
    $categoryInsert = $pdo->prepare("INSERT INTO `{$prefix}categories` (name, slug, description, parent_slug, product_count) VALUES (:name, :slug, :description, :parent_slug, :product_count)");
    $productInsert = $pdo->prepare("INSERT INTO `{$prefix}products` (source_id, slug, name, summary, description, sku, product_type, reference_price, regular_price, sale_price, currency, stock_status, stock_quantity, featured, created_at, updated_at, seo_title, seo_description) VALUES (:source_id, :slug, :name, :summary, :description, :sku, :product_type, :reference_price, :regular_price, :sale_price, :currency, :stock_status, :stock_quantity, :featured, :created_at, :updated_at, :seo_title, :seo_description)");
    $relationInsert = $pdo->prepare("INSERT INTO `{$prefix}product_categories` (product_id, category_id) VALUES (:product_id, :category_id)");
    $imageInsert = $pdo->prepare("INSERT INTO `{$prefix}product_images` (product_id, path, sort_order) VALUES (:product_id, :path, :sort_order)");
    $attributeInsert = $pdo->prepare("INSERT INTO `{$prefix}product_attributes` (product_id, attribute_name, attribute_value, sort_order) VALUES (:product_id, :attribute_name, :attribute_value, :sort_order)");
    $tagInsert = $pdo->prepare("INSERT INTO `{$prefix}product_tags` (product_id, tag_name, sort_order) VALUES (:product_id, :tag_name, :sort_order)");
    $metaInsert = $pdo->prepare("INSERT INTO `{$prefix}meta` (meta_key, meta_value) VALUES (:meta_key, :meta_value) ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = CURRENT_TIMESTAMP");

    $categoryIds = array();
    foreach ($catalog['categories'] as $category) {
        $slug = importerSlug($category['slug']);
        $categoryInsert->execute(array(
            ':name' => importerLimit(trim((string) $category['name']), 191),
            ':slug' => $slug,
            ':description' => trim((string) ($category['description'] ?? '')),
            ':parent_slug' => null,
            ':product_count' => max(0, (int) ($category['product_count'] ?? 0)),
        ));
        $categoryIds[$slug] = (int) $pdo->lastInsertId();
    }

    $counts = array('products' => 0, 'relations' => 0, 'images' => 0, 'attributes' => 0, 'tags' => 0, 'skipped_relations' => 0);
    foreach ($catalog['products'] as $product) {
        $currency = strtoupper(trim((string) ($product['currency'] ?? 'USD')));
        if (!preg_match('/^[A-Z]{3}$/', $currency)) {
            $currency = 'USD';
        }
        $stockStatus = (string) ($product['stock_status'] ?? 'outofstock');
        if (!in_array($stockStatus, array('instock', 'outofstock', 'onbackorder'), true)) {
            $stockStatus = 'outofstock';
        }
        $sourceId = isset($product['source_id']) && is_numeric($product['source_id']) && (int) $product['source_id'] > 0 ? (int) $product['source_id'] : null;
        $stockQuantity = isset($product['stock_quantity']) && is_numeric($product['stock_quantity']) ? (int) $product['stock_quantity'] : null;
        $productInsert->execute(array(
            ':source_id' => $sourceId,
            ':slug' => importerSlug($product['slug']),
            ':name' => importerLimit(trim((string) $product['name']), 255),
            ':summary' => trim((string) ($product['summary'] ?? '')),
            ':description' => trim((string) ($product['description'] ?? '')),
            ':sku' => importerLimit(trim((string) ($product['sku'] ?? '')), 96),
            ':product_type' => importerLimit(trim((string) ($product['type'] ?? 'simple')), 50),
            ':reference_price' => importerDecimal($product['price'] ?? null),
            ':regular_price' => importerDecimal($product['regular_price'] ?? null),
            ':sale_price' => importerDecimal($product['sale_price'] ?? null),
            ':currency' => $currency,
            ':stock_status' => $stockStatus,
            ':stock_quantity' => $stockQuantity,
            ':featured' => !empty($product['featured']) ? 1 : 0,
            ':created_at' => importerDate($product['created_at'] ?? null),
            ':updated_at' => importerDate($product['updated_at'] ?? null),
            ':seo_title' => importerLimit(trim((string) ($product['seo_title'] ?? '')), 255),
            ':seo_description' => importerLimit(trim((string) ($product['seo_description'] ?? '')), 500),
        ));
        $productId = (int) $pdo->lastInsertId();
        $counts['products']++;

        foreach ((array) ($product['categories'] ?? array()) as $category) {
            $slug = is_array($category) ? importerSlug($category['slug'] ?? '') : '';
            if ($slug === '' || !isset($categoryIds[$slug])) {
                $counts['skipped_relations']++;
                continue;
            }
            $relationInsert->execute(array(':product_id' => $productId, ':category_id' => $categoryIds[$slug]));
            $counts['relations']++;
        }

        foreach (array_slice((array) ($product['images'] ?? array()), 0, 50) as $order => $image) {
            $path = importerImagePath($image);
            if ($path === '') {
                continue;
            }
            $imageInsert->execute(array(':product_id' => $productId, ':path' => $path, ':sort_order' => $order));
            $counts['images']++;
        }

        $attributeOrder = 0;
        foreach ((array) ($product['attributes'] ?? array()) as $name => $values) {
            $name = importerLimit(trim((string) $name), 191);
            if ($name === '') {
                continue;
            }
            foreach (array_slice((array) $values, 0, 50) as $value) {
                $value = importerLimit(trim((string) $value), 500);
                if ($value === '') {
                    continue;
                }
                $attributeInsert->execute(array(':product_id' => $productId, ':attribute_name' => $name, ':attribute_value' => $value, ':sort_order' => $attributeOrder++));
                $counts['attributes']++;
            }
        }

        foreach (array_slice((array) ($product['tags'] ?? array()), 0, 100) as $order => $tag) {
            $tagName = is_array($tag) ? (string) ($tag['name'] ?? '') : (string) $tag;
            $tagName = importerLimit(trim($tagName), 191);
            if ($tagName === '') {
                continue;
            }
            $tagInsert->execute(array(':product_id' => $productId, ':tag_name' => $tagName, ':sort_order' => $order));
            $counts['tags']++;
        }
    }

    $metadata = array(
        'completed_at_utc' => gmdate('c'),
        'catalog_sha256' => hash_file('sha256', $catalogPath),
        'products' => $counts['products'],
        'categories' => count($categoryIds),
        'images' => $counts['images'],
    );
    $metaInsert->execute(array(
        ':meta_key' => RASPINA_IMPORT_META_KEY,
        ':meta_value' => json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
    ));
    return $counts;
}

$options = getopt('', array('confirm', 'force', 'dry-run'));
$options = is_array($options) ? $options : array();
$dryRun = array_key_exists('dry-run', $options);
$confirmed = array_key_exists('confirm', $options);
$force = array_key_exists('force', $options);

if (!$dryRun && !$confirmed) {
    importerOut('No changes made. Import requires the explicit --confirm flag.');
    importerOut('Run --dry-run first, then: php database/import-catalog.php --confirm');
    exit(64);
}

$root = dirname(__DIR__);
$configPath = is_file($root . '/app/config.local.php') ? $root . '/app/config.local.php' : $root . '/app/config.php';
$catalogPath = $root . '/storage/catalog.json';
$schemaPath = __DIR__ . '/schema.sql';

if (!is_file($configPath)) {
    importerError('Application configuration is unavailable. Copy app/config.example.php to app/config.local.php first.');
}

try {
    $config = require $configPath;
    if (!is_array($config)) {
        throw new RuntimeException('The selected application configuration must return an array.');
    }
    $catalog = importerLoadCatalog($catalogPath);
    $prefix = importerPrefix($config);
    importerOut('Catalogue validation passed.');
    importerOut('Products: ' . count($catalog['products']));
    importerOut('Categories: ' . count($catalog['categories']));
    importerOut('Table prefix: ' . $prefix);

    $pdo = importerConnect($config);
    importerOut('Database connection passed.');

    if ($dryRun) {
        importerOut('Dry run complete. No tables or rows were changed.');
        exit(0);
    }

    $lockName = '';
    try {
        $lockName = importerAcquireLock($pdo, (string) $config['database']['name'], $prefix);
        importerCreateSchema($pdo, $schemaPath, $prefix);

        if (!$force && importerAlreadyCompleted($pdo, $prefix)) {
            throw new RuntimeException('This catalogue has already been imported. Use --force only when an intentional replacement is required.');
        }
        if (!$force && importerProductCount($pdo, $prefix) > 0) {
            throw new RuntimeException('The prefixed product table is not empty. No existing rows were changed.');
        }

        $pdo->beginTransaction();
        try {
            if ($force) {
                importerClearCatalog($pdo, $prefix);
            }
            $counts = importerInsertCatalog($pdo, $catalog, $prefix, $catalogPath);
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }

        importerOut('Import completed successfully.');
        importerOut('Products imported: ' . $counts['products']);
        importerOut('Product/category links: ' . $counts['relations']);
        importerOut('Images imported: ' . $counts['images']);
        importerOut('Attributes imported: ' . $counts['attributes']);
        importerOut('Tags imported: ' . $counts['tags']);
        if ($counts['skipped_relations'] > 0) {
            importerOut('Warning: category links skipped because their category was unavailable: ' . $counts['skipped_relations']);
        }
    } finally {
        importerReleaseLock($pdo, $lockName);
    }
} catch (PDOException $exception) {
    importerError('A database operation failed. Check the database name, user privileges, schema compatibility and app/config.php.');
} catch (Throwable $exception) {
    importerError($exception->getMessage());
}
