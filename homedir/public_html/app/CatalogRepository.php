<?php
declare(strict_types=1);

final class CatalogRepository
{
    private $config;
    private $jsonPath;
    private $products = array();
    private $categories = array();
    private $productMap = array();
    private $categoryMap = array();
    private $pdo = null;
    private $mode = 'json';

    public function __construct(array $config, string $jsonPath)
    {
        $this->config = $config;
        $this->jsonPath = $jsonPath;
        if (!empty($config['database']['enabled'])) {
            try {
                $this->connectDatabase();
                $this->loadDatabase();
                if ($this->products) {
                    $this->mode = 'mysql';
                    return;
                }
            } catch (Throwable $exception) {
                error_log('Catalogue database unavailable; JSON fallback selected. ' . safe_error_summary($exception));
                $this->pdo = null;
            }
        }
        $this->loadJson();
    }

    public function mode(): string
    {
        return $this->mode;
    }

    public function allProducts(): array
    {
        return $this->products;
    }

    public function categories(): array
    {
        return $this->categories;
    }

    public function productBySlug(string $slug): ?array
    {
        return isset($this->productMap[$slug]) ? $this->productMap[$slug] : null;
    }

    public function categoryBySlug(string $slug): ?array
    {
        return isset($this->categoryMap[$slug]) ? $this->categoryMap[$slug] : null;
    }

    public function queryProducts(array $filters, int $page, int $perPage): array
    {
        $query = lower_text(trim((string) ($filters['q'] ?? '')));
        $category = (string) ($filters['category'] ?? '');
        $stock = (string) ($filters['stock'] ?? '');
        $filtered = array_values(array_filter($this->products, function (array $product) use ($query, $category, $stock): bool {
            if ($stock !== '' && $product['stock_status'] !== $stock) {
                return false;
            }
            if ($category !== '') {
                $matchesCategory = false;
                foreach ($product['categories'] as $item) {
                    if ($item['slug'] === $category) {
                        $matchesCategory = true;
                        break;
                    }
                }
                if (!$matchesCategory) {
                    return false;
                }
            }
            if ($query !== '') {
                $categoryNames = array_map(function (array $item): string { return (string) $item['name']; }, $product['categories']);
                $haystack = lower_text(implode(' ', array(
                    (string) $product['name'],
                    (string) $product['sku'],
                    clean_product_copy((string) $product['description']),
                    implode(' ', $categoryNames),
                )));
                if (strpos($haystack, $query) === false) {
                    return false;
                }
            }
            return true;
        }));

        $sort = (string) ($filters['sort'] ?? 'latest');
        usort($filtered, function (array $a, array $b) use ($sort): int {
            if ($sort === 'az' || $sort === 'za') {
                $comparison = strcasecmp((string) $a['name'], (string) $b['name']);
                return $sort === 'za' ? -$comparison : $comparison;
            }
            if ($sort === 'sku') {
                return strnatcasecmp((string) $a['sku'], (string) $b['sku']);
            }
            if ($sort === 'price-low' || $sort === 'price-high') {
                $missingPrice = $sort === 'price-high' ? -INF : INF;
                $aPrice = is_numeric($a['price']) ? (float) $a['price'] : $missingPrice;
                $bPrice = is_numeric($b['price']) ? (float) $b['price'] : $missingPrice;
                $comparison = $aPrice <=> $bPrice;
                return $sort === 'price-high' ? -$comparison : $comparison;
            }
            return strcmp((string) $b['created_at'], (string) $a['created_at']);
        });

        $total = count($filtered);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        return array(
            'items' => array_slice($filtered, ($page - 1) * $perPage, $perPage),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'per_page' => $perPage,
        );
    }

    public function featuredProducts(int $limit): array
    {
        $featured = array_values(array_filter($this->products, function (array $product): bool {
            return !empty($product['featured']) && !empty($product['images']);
        }));
        $pool = count($featured) >= $limit ? $featured : array_values(array_filter($this->products, function (array $product): bool {
            return !empty($product['images']);
        }));
        usort($pool, function (array $a, array $b): int {
            return strcmp((string) $b['created_at'], (string) $a['created_at']);
        });
        return array_slice($pool, 0, $limit);
    }

    public function homeCollections(): array
    {
        $preferred = array('collection-2026', 'dress', 'blouse', 'pant', 'chemise', 'coat');
        $collections = array();
        foreach ($preferred as $slug) {
            if (!isset($this->categoryMap[$slug])) {
                continue;
            }
            $category = $this->categoryMap[$slug];
            $sample = $this->queryProducts(array('q' => '', 'category' => $slug, 'stock' => '', 'sort' => 'latest'), 1, 1);
            $category['image'] = !empty($sample['items'][0]) ? product_image($sample['items'][0]) : '';
            $collections[] = $category;
        }
        return $collections;
    }

    public function relatedProducts(array $product, int $limit): array
    {
        $slugs = array_map(function (array $category): string { return (string) $category['slug']; }, $product['categories']);
        $related = array_values(array_filter($this->products, function (array $candidate) use ($product, $slugs): bool {
            if ($candidate['slug'] === $product['slug']) {
                return false;
            }
            foreach ($candidate['categories'] as $category) {
                if (in_array($category['slug'], $slugs, true)) {
                    return true;
                }
            }
            return false;
        }));
        return array_slice($related, 0, $limit);
    }

    public function adjacentProducts(string $slug): array
    {
        $index = array_search($slug, array_column($this->products, 'slug'), true);
        if ($index === false) {
            return array('previous' => null, 'next' => null);
        }
        return array(
            'previous' => $index > 0 ? $this->products[$index - 1] : null,
            'next' => $index < count($this->products) - 1 ? $this->products[$index + 1] : null,
        );
    }

    public function wishlistIndex(): array
    {
        return array_map(function (array $product): array {
            $category = product_category($product);
            return array(
                'slug' => $product['slug'],
                'name' => $product['name'],
                'sku' => $product['sku'],
                'image' => product_image($product),
                'url' => url('/product/' . $product['slug']),
                'category' => $category['name'],
                'stock_status' => $product['stock_status'],
            );
        }, $this->products);
    }

    public function saveMessage(array $record): bool
    {
        if ($this->mode === 'mysql' && $this->pdo instanceof PDO) {
            $prefix = $this->tablePrefix();
            $statement = $this->pdo->prepare("INSERT INTO {$prefix}messages (message_type, name, business_name, email, phone, country, message, products_json, ip_hash, created_at) VALUES (:type, :name, :business, :email, :phone, :country, :message, :products, :ip_hash, :created_at)");
            return $statement->execute(array(
                ':type' => $record['type'], ':name' => $record['name'], ':business' => $record['business_name'],
                ':email' => $record['email'], ':phone' => $record['phone'], ':country' => $record['country'],
                ':message' => $record['message'], ':products' => json_encode($record['products'], JSON_UNESCAPED_UNICODE),
                ':ip_hash' => $record['ip_hash'], ':created_at' => $record['created_at'],
            ));
        }
        $path = APP_ROOT . '/storage/logs/inquiries.ndjson';
        $line = json_encode($record, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
        return @file_put_contents($path, $line, FILE_APPEND | LOCK_EX) !== false;
    }

    private function loadJson(): void
    {
        $raw = @file_get_contents($this->jsonPath);
        $data = $raw !== false ? json_decode($raw, true) : null;
        if (!is_array($data) || empty($data['products']) || !isset($data['categories'])) {
            throw new RuntimeException('Catalogue data is unavailable.');
        }
        $this->categories = array_values($data['categories']);
        $this->products = array_values($data['products']);
        $this->buildMaps();
    }

    private function connectDatabase(): void
    {
        $db = $this->config['database'];
        $dsn = 'mysql:host=' . $db['host'] . ';port=' . $db['port'] . ';dbname=' . $db['name'] . ';charset=utf8mb4';
        $this->pdo = new PDO($dsn, $db['user'], $db['password'], array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ));
    }

    private function loadDatabase(): void
    {
        $prefix = $this->tablePrefix();
        $this->categories = $this->pdo->query("SELECT name, slug, description, parent_slug, product_count FROM {$prefix}categories ORDER BY product_count DESC, name ASC")->fetchAll();
        $rows = $this->pdo->query("SELECT * FROM {$prefix}products ORDER BY created_at DESC, id DESC")->fetchAll();
        $productsById = array();
        $apparelFields = array('brand', 'garment_type', 'collection_name', 'season', 'fabric_composition', 'fabric_weight', 'color', 'color_family', 'pattern', 'fit', 'silhouette', 'neckline', 'sleeve_length', 'garment_length', 'closure', 'lining', 'stretch', 'care_instructions', 'origin_country', 'size_range', 'customizable_size', 'customizable_fabric', 'minimum_order_quantity', 'lead_time', 'wholesale_notes', 'publish_status', 'available_from', 'availability_note');
        foreach ($rows as $row) {
            if (array_key_exists('publish_status', $row) && !in_array((string) $row['publish_status'], array('', 'published'), true)) {
                continue;
            }
            $id = (int) $row['id'];
            $productsById[$id] = array(
                'source_id' => (int) $row['source_id'], 'slug' => $row['slug'], 'name' => $row['name'],
                'summary' => $row['summary'], 'description' => $row['description'], 'sku' => $row['sku'],
                'type' => $row['product_type'], 'price' => $row['reference_price'], 'regular_price' => $row['regular_price'],
                'sale_price' => $row['sale_price'], 'currency' => $row['currency'], 'stock_status' => $row['stock_status'],
                'stock_quantity' => $row['stock_quantity'], 'featured' => (bool) $row['featured'], 'categories' => array(),
                'tags' => array(), 'attributes' => array(), 'images' => array(), 'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at'], 'seo_title' => $row['seo_title'], 'seo_description' => $row['seo_description'],
                'apparel_specs' => array(), 'variants' => array(),
            );
            foreach ($apparelFields as $field) {
                if (array_key_exists($field, $row)) {
                    $productsById[$id]['apparel_specs'][$field] = $row[$field];
                }
            }
        }
        foreach ($this->pdo->query("SELECT pc.product_id, c.name, c.slug FROM {$prefix}product_categories pc JOIN {$prefix}categories c ON c.id = pc.category_id ORDER BY c.product_count DESC") as $row) {
            if (isset($productsById[(int) $row['product_id']])) {
                $productsById[(int) $row['product_id']]['categories'][] = array('name' => $row['name'], 'slug' => $row['slug']);
            }
        }
        foreach ($this->pdo->query("SELECT product_id, path FROM {$prefix}product_images ORDER BY product_id, sort_order") as $row) {
            if (isset($productsById[(int) $row['product_id']])) {
                $productsById[(int) $row['product_id']]['images'][] = $row['path'];
            }
        }
        foreach ($this->pdo->query("SELECT product_id, attribute_name, attribute_value FROM {$prefix}product_attributes ORDER BY product_id, sort_order") as $row) {
            $id = (int) $row['product_id'];
            if (isset($productsById[$id])) {
                $productsById[$id]['attributes'][$row['attribute_name']][] = $row['attribute_value'];
            }
        }
        foreach ($this->pdo->query("SELECT product_id, tag_name FROM {$prefix}product_tags ORDER BY product_id, sort_order") as $row) {
            $id = (int) $row['product_id'];
            if (isset($productsById[$id])) {
                $productsById[$id]['tags'][] = $row['tag_name'];
            }
        }
        if ($this->tableExists($prefix . 'product_variants')) {
            foreach ($this->pdo->query("SELECT product_id, variant_sku, size, color, stock_status, stock_quantity, price_override, is_active FROM {$prefix}product_variants WHERE is_active = 1 ORDER BY product_id, sort_order, id") as $row) {
                $id = (int) $row['product_id'];
                if (isset($productsById[$id])) {
                    $productsById[$id]['variants'][] = $row;
                }
            }
        }
        $this->products = array_values($productsById);
        $this->buildMaps();
    }

    private function buildMaps(): void
    {
        $this->productMap = array();
        foreach ($this->products as $product) {
            $this->productMap[$product['slug']] = $product;
        }
        $this->categoryMap = array();
        foreach ($this->categories as $category) {
            $category['product_count'] = (int) $category['product_count'];
            $this->categoryMap[$category['slug']] = $category;
        }
    }

    private function tablePrefix(): string
    {
        $prefix = (string) ($this->config['database']['prefix'] ?? 'rc_');
        return preg_match('/^[a-zA-Z0-9_]+$/', $prefix) ? $prefix : 'rc_';
    }

    private function tableExists(string $table): bool
    {
        try {
            $statement = $this->pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :table');
            $statement->execute(array(':table' => $table));
            return (int) $statement->fetchColumn() > 0;
        } catch (Throwable $exception) {
            return false;
        }
    }
}
