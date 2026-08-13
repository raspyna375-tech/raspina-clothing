<?php
declare(strict_types=1);

final class AdminRepository
{
    private $config;
    private $pdo = null;
    private $prefix = 'rc_';
    private $status = 'disabled';

    public function __construct(array $config)
    {
        $this->config = $config;
        $configuredPrefix = (string) ($config['database']['prefix'] ?? 'rc_');
        $this->prefix = preg_match('/^[a-zA-Z0-9_]+$/', $configuredPrefix) ? $configuredPrefix : 'rc_';
        if (empty($config['database']['enabled'])) {
            return;
        }
        try {
            $db = $config['database'];
            $dsn = 'mysql:host=' . (string) ($db['host'] ?? 'localhost') . ';port=' . (string) ($db['port'] ?? '3306') . ';dbname=' . (string) ($db['name'] ?? '') . ';charset=utf8mb4';
            $this->pdo = new PDO($dsn, (string) ($db['user'] ?? ''), (string) ($db['password'] ?? ''), array(
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ));
            $this->status = $this->hasSchema() ? 'ready' : 'schema_missing';
        } catch (Throwable $exception) {
            $this->pdo = null;
            $this->status = 'unavailable';
            error_log('Admin database unavailable. ' . safe_error_summary($exception));
        }
    }

    public function status(): string
    {
        return $this->status;
    }

    public function isReady(): bool
    {
        return $this->status === 'ready' && $this->pdo instanceof PDO;
    }

    public function statusMessage(): string
    {
        if ($this->status === 'disabled') {
            return 'Database mode is disabled. Enable database.enabled in app/config.local.php to use the admin panel.';
        }
        if ($this->status === 'schema_missing') {
            return 'The enhanced admin schema is not installed. Import database/admin-schema.sql, then database/migration-20260804-product-team-access.sql, and reload this page.';
        }
        if ($this->status === 'unavailable') {
            return 'The MySQL connection is unavailable. Check the private database settings and try again.';
        }
        return 'MySQL catalogue and admin storage are ready.';
    }

    public function adminUserCount(): int
    {
        if (!$this->isReady()) {
            return 0;
        }
        return (int) $this->pdo->query('SELECT COUNT(*) FROM ' . $this->table('admin_users'))->fetchColumn();
    }

    public function findAdminByEmail(string $email): ?array
    {
        if (!$this->isReady()) {
            return null;
        }
        $statement = $this->pdo->prepare('SELECT u.id, u.email, u.name, u.title, u.password_hash, u.role, u.role_id, u.is_active, u.last_login_at, u.created_at, r.slug AS role_slug, r.title AS role_title FROM ' . $this->table('admin_users') . ' u LEFT JOIN ' . $this->table('admin_roles') . ' r ON r.id = u.role_id WHERE LOWER(u.email) = LOWER(:email) LIMIT 1');
        $statement->execute(array(':email' => $email));
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function findAdminById(int $id): ?array
    {
        if (!$this->isReady() || $id < 1) {
            return null;
        }
        $statement = $this->pdo->prepare('SELECT u.id, u.email, u.name, u.title, u.role, u.role_id, u.is_active, u.last_login_at, u.created_at, r.slug AS role_slug, r.title AS role_title FROM ' . $this->table('admin_users') . ' u LEFT JOIN ' . $this->table('admin_roles') . ' r ON r.id = u.role_id WHERE u.id = :id LIMIT 1');
        $statement->execute(array(':id' => $id));
        $row = $statement->fetch();
        if ($row) {
            $row['permissions'] = $this->permissionCodesForUser($id);
            $row['is_owner'] = (($row['role_slug'] ?? '') === 'owner' || (string) ($row['role'] ?? '') === 'owner') ? 1 : 0;
        }
        return $row ?: null;
    }

    public function createFirstAdmin(string $email, string $name, string $passwordHash): int
    {
        $this->requireReady();
        $this->pdo->beginTransaction();
        try {
            $existing = $this->pdo->query('SELECT id FROM ' . $this->table('admin_users') . ' ORDER BY id ASC LIMIT 1 FOR UPDATE')->fetch();
            if ($existing) {
                throw new RuntimeException('Admin setup has already been completed.');
            }
            $statement = $this->pdo->prepare('INSERT INTO ' . $this->table('admin_users') . ' (email, name, title, password_hash, role, role_id, is_active, password_changed_at, created_at, updated_at) VALUES (:email, :name, :title, :password_hash, :role, (SELECT id FROM ' . $this->table('admin_roles') . ' WHERE slug = \'owner\' LIMIT 1), 1, NOW(), NOW(), NOW())');
            $statement->execute(array(':email' => $email, ':name' => $name, ':title' => 'Owner', ':password_hash' => $passwordHash, ':role' => 'owner'));
            $id = (int) $this->pdo->lastInsertId();
            $this->pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function touchLastLogin(int $id): void
    {
        if (!$this->isReady() || $id < 1) {
            return;
        }
        $statement = $this->pdo->prepare('UPDATE ' . $this->table('admin_users') . ' SET last_login_at = NOW(), updated_at = NOW() WHERE id = :id');
        $statement->execute(array(':id' => $id));
    }

    public function dashboardStats(): array
    {
        $this->requireReady();
        $stats = array(
            'products' => $this->countTable('products'),
            'categories' => $this->countTable('categories'),
            'messages' => $this->countTable('messages'),
            'featured' => $this->countTable('products', 'featured = 1'),
            'latest_messages' => array(),
        );
        $stats['latest_messages'] = $this->pdo->query('SELECT id, message_type, name, business_name, email, country, created_at FROM ' . $this->table('messages') . ' ORDER BY created_at DESC, id DESC LIMIT 6')->fetchAll();
        return $stats;
    }

    public function listProducts(string $query, int $page, int $perPage = 20): array
    {
        $this->requireReady();
        $query = clean_query($query);
        $where = '';
        $params = array();
        if ($query !== '') {
            $where = ' WHERE (p.name LIKE :query OR p.slug LIKE :query OR p.sku LIKE :query)';
            $params[':query'] = '%' . $query . '%';
        }
        $countStatement = $this->pdo->prepare('SELECT COUNT(*) FROM ' . $this->table('products') . ' p' . $where);
        $countStatement->execute($params);
        $total = (int) $countStatement->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT p.id, p.name, p.slug, p.sku, p.stock_status, p.stock_quantity, p.featured, p.reference_price, p.currency, p.updated_at, GROUP_CONCAT(DISTINCT c.name ORDER BY c.name SEPARATOR ", ") AS category_names FROM ' . $this->table('products') . ' p LEFT JOIN ' . $this->table('product_categories') . ' pc ON pc.product_id = p.id LEFT JOIN ' . $this->table('categories') . ' c ON c.id = pc.category_id' . $where . ' GROUP BY p.id ORDER BY p.updated_at DESC, p.id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return array('items' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage);
    }

    public function getProduct(int $id): ?array
    {
        $this->requireReady();
        $statement = $this->pdo->prepare('SELECT * FROM ' . $this->table('products') . ' WHERE id = :id LIMIT 1');
        $statement->execute(array(':id' => $id));
        $product = $statement->fetch();
        if (!$product) {
            return null;
        }
        $product['category_ids'] = array_map('intval', $this->columnValues('SELECT category_id FROM ' . $this->table('product_categories') . ' WHERE product_id = :id ORDER BY category_id', ':id', $id));
        $product['images'] = $this->pdo->prepare('SELECT id, path, original_name, stored_name, mime_type, file_size, width, height, alt_text, is_primary, uploaded_by, created_at FROM ' . $this->table('product_images') . ' WHERE product_id = :id ORDER BY sort_order, id');
        $product['images']->execute(array(':id' => $id));
        $product['images'] = $product['images']->fetchAll();
        $attributeStatement = $this->pdo->prepare('SELECT attribute_name, attribute_value FROM ' . $this->table('product_attributes') . ' WHERE product_id = :id ORDER BY sort_order, id');
        $attributeStatement->execute(array(':id' => $id));
        $product['attributes'] = $attributeStatement->fetchAll();
        $tagStatement = $this->pdo->prepare('SELECT tag_name FROM ' . $this->table('product_tags') . ' WHERE product_id = :id ORDER BY sort_order, id');
        $tagStatement->execute(array(':id' => $id));
        $product['tags'] = array_map(function (array $row): string { return (string) $row['tag_name']; }, $tagStatement->fetchAll());
        $variantStatement = $this->pdo->prepare('SELECT id, variant_sku, size, color, stock_status, stock_quantity, price_override, is_active, sort_order FROM ' . $this->table('product_variants') . ' WHERE product_id = :id ORDER BY sort_order, id');
        $variantStatement->execute(array(':id' => $id));
        $product['variants'] = $variantStatement->fetchAll();
        return $product;
    }

    public function saveProduct(array $data): int
    {
        $this->requireReady();
        $this->pdo->beginTransaction();
        try {
            $fields = array(
                'slug' => $data['slug'], 'name' => $data['name'], 'summary' => $data['summary'], 'description' => $data['description'],
                'sku' => $data['sku'], 'product_type' => $data['product_type'], 'reference_price' => $data['reference_price'],
                'regular_price' => $data['regular_price'], 'sale_price' => $data['sale_price'], 'currency' => $data['currency'],
                'stock_status' => $data['stock_status'], 'stock_quantity' => $data['stock_quantity'], 'featured' => $data['featured'],
                'seo_title' => $data['seo_title'], 'seo_description' => $data['seo_description'],
                'brand' => $data['brand'], 'garment_type' => $data['garment_type'], 'collection_name' => $data['collection_name'],
                'season' => $data['season'], 'fabric_composition' => $data['fabric_composition'], 'fabric_weight' => $data['fabric_weight'],
                'color' => $data['color'], 'color_family' => $data['color_family'], 'pattern' => $data['pattern'], 'fit' => $data['fit'],
                'silhouette' => $data['silhouette'], 'neckline' => $data['neckline'], 'sleeve_length' => $data['sleeve_length'],
                'garment_length' => $data['garment_length'], 'closure' => $data['closure'], 'lining' => $data['lining'], 'stretch' => $data['stretch'],
                'care_instructions' => $data['care_instructions'], 'origin_country' => $data['origin_country'], 'size_range' => $data['size_range'],
                'customizable_size' => $data['customizable_size'], 'customizable_fabric' => $data['customizable_fabric'],
                'minimum_order_quantity' => $data['minimum_order_quantity'], 'lead_time' => $data['lead_time'], 'wholesale_notes' => $data['wholesale_notes'],
                'publish_status' => $data['publish_status'], 'available_from' => $data['available_from'], 'availability_note' => $data['availability_note'],
            );
            $id = (int) ($data['id'] ?? 0);
            if ($id > 0) {
                $set = array();
                foreach (array_keys($fields) as $field) {
                    $set[] = $field . ' = :' . $field;
                }
                $statement = $this->pdo->prepare('UPDATE ' . $this->table('products') . ' SET ' . implode(', ', $set) . ', updated_at = NOW() WHERE id = :id');
                $params = array(':id' => $id);
                foreach ($fields as $field => $value) {
                    $params[':' . $field] = $value;
                }
                $statement->execute($params);
            } else {
                $columns = implode(', ', array_keys($fields));
                $placeholders = ':' . implode(', :', array_keys($fields));
                $statement = $this->pdo->prepare('INSERT INTO ' . $this->table('products') . ' (' . $columns . ', created_at, updated_at) VALUES (' . $placeholders . ', NOW(), NOW())');
                $params = array();
                foreach ($fields as $field => $value) {
                    $params[':' . $field] = $value;
                }
                $statement->execute($params);
                $id = (int) $this->pdo->lastInsertId();
            }
            $this->replaceProductRelations($id, $data);
            $this->pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function deleteProduct(int $id): void
    {
        $this->requireReady();
        $statement = $this->pdo->prepare('DELETE FROM ' . $this->table('products') . ' WHERE id = :id');
        $statement->execute(array(':id' => $id));
    }

    public function listCategories(string $query = ''): array
    {
        $this->requireReady();
        $query = clean_query($query);
        $where = '';
        $params = array();
        if ($query !== '') {
            $where = ' WHERE name LIKE :query OR slug LIKE :query';
            $params[':query'] = '%' . $query . '%';
        }
        $statement = $this->pdo->prepare('SELECT id, name, slug, description, parent_slug, product_count, created_at, updated_at FROM ' . $this->table('categories') . $where . ' ORDER BY product_count DESC, name ASC');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function listAuditLogs(int $page, int $perPage = 25): array
    {
        $this->requireReady();
        $countStatement = $this->pdo->query('SELECT COUNT(*) FROM ' . $this->table('admin_audit'));
        $total = (int) $countStatement->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT a.id, a.admin_user_id, a.action, a.entity_type, a.entity_id, a.ip_hash, a.metadata_json, a.created_at, u.name AS user_name, u.email AS user_email FROM ' . $this->table('admin_audit') . ' a LEFT JOIN ' . $this->table('admin_users') . ' u ON u.id = a.admin_user_id ORDER BY a.created_at DESC, a.id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset;
        $statement = $this->pdo->query($sql);
        return array('items' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage);
    }

    public function getCategory(int $id): ?array
    {
        $this->requireReady();
        $statement = $this->pdo->prepare('SELECT id, name, slug, description, parent_slug, product_count FROM ' . $this->table('categories') . ' WHERE id = :id LIMIT 1');
        $statement->execute(array(':id' => $id));
        $row = $statement->fetch();
        return $row ?: null;
    }

    public function saveCategory(array $data): int
    {
        $this->requireReady();
        $id = (int) ($data['id'] ?? 0);
        if ($id > 0) {
            $statement = $this->pdo->prepare('UPDATE ' . $this->table('categories') . ' SET name = :name, slug = :slug, description = :description, parent_slug = :parent_slug, updated_at = NOW() WHERE id = :id');
            $statement->execute(array(':name' => $data['name'], ':slug' => $data['slug'], ':description' => $data['description'], ':parent_slug' => $data['parent_slug'] ?: null, ':id' => $id));
            return $id;
        }
        $statement = $this->pdo->prepare('INSERT INTO ' . $this->table('categories') . ' (name, slug, description, parent_slug, product_count, created_at, updated_at) VALUES (:name, :slug, :description, :parent_slug, 0, NOW(), NOW())');
        $statement->execute(array(':name' => $data['name'], ':slug' => $data['slug'], ':description' => $data['description'], ':parent_slug' => $data['parent_slug'] ?: null));
        return (int) $this->pdo->lastInsertId();
    }

    public function deleteCategory(int $id): array
    {
        $this->requireReady();
        $statement = $this->pdo->prepare('SELECT COUNT(*) FROM ' . $this->table('product_categories') . ' WHERE category_id = :id');
        $statement->execute(array(':id' => $id));
        $assigned = (int) $statement->fetchColumn();
        if ($assigned > 0) {
            return array('ok' => false, 'assigned' => $assigned);
        }
        $delete = $this->pdo->prepare('DELETE FROM ' . $this->table('categories') . ' WHERE id = :id');
        $delete->execute(array(':id' => $id));
        return array('ok' => true, 'assigned' => 0);
    }

    public function listMessages(string $query, string $type, int $page, int $perPage = 20): array
    {
        $this->requireReady();
        $query = clean_query($query);
        $where = array();
        $params = array();
        if ($query !== '') {
            $where[] = '(name LIKE :query OR business_name LIKE :query OR email LIKE :query OR country LIKE :query)';
            $params[':query'] = '%' . $query . '%';
        }
        if (in_array($type, array('contact', 'wholesale'), true)) {
            $where[] = 'message_type = :type';
            $params[':type'] = $type;
        }
        $condition = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $count = $this->pdo->prepare('SELECT COUNT(*) FROM ' . $this->table('messages') . $condition);
        $count->execute($params);
        $total = (int) $count->fetchColumn();
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);
        $offset = ($page - 1) * $perPage;
        $statement = $this->pdo->prepare('SELECT id, message_type, name, business_name, email, phone, country, created_at FROM ' . $this->table('messages') . $condition . ' ORDER BY created_at DESC, id DESC LIMIT ' . (int) $perPage . ' OFFSET ' . (int) $offset);
        $statement->execute($params);
        return array('items' => $statement->fetchAll(), 'total' => $total, 'page' => $page, 'pages' => $pages, 'per_page' => $perPage);
    }

    public function getMessage(int $id): ?array
    {
        $this->requireReady();
        $statement = $this->pdo->prepare('SELECT id, message_type, name, business_name, email, phone, country, message, products_json, created_at FROM ' . $this->table('messages') . ' WHERE id = :id LIMIT 1');
        $statement->execute(array(':id' => $id));
        $row = $statement->fetch();
        if (!$row) {
            return null;
        }
        $decoded = json_decode((string) $row['products_json'], true);
        $row['products_list'] = is_array($decoded) ? $decoded : array();
        return $row;
    }

    public function deleteMessage(int $id): void
    {
        $this->requireReady();
        $statement = $this->pdo->prepare('DELETE FROM ' . $this->table('messages') . ' WHERE id = :id');
        $statement->execute(array(':id' => $id));
    }

    public function getMeta(): array
    {
        $this->requireReady();
        $rows = $this->pdo->query('SELECT meta_key, meta_value FROM ' . $this->table('meta') . ' ORDER BY meta_key')->fetchAll();
        $meta = array();
        foreach ($rows as $row) {
            $meta[(string) $row['meta_key']] = (string) $row['meta_value'];
        }
        return $meta;
    }

    public function saveMeta(array $settings): void
    {
        $this->requireReady();
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare('INSERT INTO ' . $this->table('meta') . ' (meta_key, meta_value, updated_at) VALUES (:meta_key, :meta_value, NOW()) ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value), updated_at = NOW()');
            foreach ($settings as $key => $value) {
                $statement->execute(array(':meta_key' => $key, ':meta_value' => $value));
            }
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function audit(?int $adminUserId, string $action, string $entityType = 'system', ?int $entityId = null, string $ipHash = '', array $metadata = array()): void
    {
        if (!$this->isReady()) {
            return;
        }
        $statement = $this->pdo->prepare('INSERT INTO ' . $this->table('admin_audit') . ' (admin_user_id, action, entity_type, entity_id, ip_hash, metadata_json, created_at) VALUES (:admin_user_id, :action, :entity_type, :entity_id, :ip_hash, :metadata_json, NOW())');
        $statement->execute(array(':admin_user_id' => $adminUserId, ':action' => string_limit($action, 100), ':entity_type' => string_limit($entityType, 50), ':entity_id' => $entityId, ':ip_hash' => $ipHash, ':metadata_json' => $metadata ? json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null));
    }

    public function hasPermission(int $userId, string $permission): bool
    {
        $this->requireReady();
        $user = $this->findAdminById($userId);
        if (!$user || empty($user['is_active'])) {
            return false;
        }
        if (!empty($user['is_owner'])) {
            return true;
        }
        $override = $this->pdo->prepare('SELECT up.effect FROM ' . $this->table('admin_user_permissions') . ' up JOIN ' . $this->table('admin_permissions') . ' p ON p.id = up.permission_id WHERE up.user_id = :user_id AND p.code = :code LIMIT 1');
        $override->execute(array(':user_id' => $userId, ':code' => $permission));
        $effect = $override->fetchColumn();
        if ($effect === 'allow') {
            return true;
        }
        if ($effect === 'deny') {
            return false;
        }
        $role = $this->pdo->prepare('SELECT 1 FROM ' . $this->table('admin_role_permissions') . ' rp JOIN ' . $this->table('admin_permissions') . ' p ON p.id = rp.permission_id JOIN ' . $this->table('admin_users') . ' u ON u.role_id = rp.role_id WHERE u.id = :user_id AND p.code = :code LIMIT 1');
        $role->execute(array(':user_id' => $userId, ':code' => $permission));
        return (bool) $role->fetchColumn();
    }

    public function permissionCodesForUser(int $userId): array
    {
        if (!$this->isReady() || $userId < 1) {
            return array();
        }
        $statement = $this->pdo->prepare('SELECT p.code, COALESCE(up.effect, CASE WHEN rp.permission_id IS NULL THEN \'deny\' ELSE \'allow\' END) AS effect FROM ' . $this->table('admin_permissions') . ' p LEFT JOIN ' . $this->table('admin_role_permissions') . ' rp ON rp.permission_id = p.id AND rp.role_id = (SELECT role_id FROM ' . $this->table('admin_users') . ' WHERE id = :user_id_role) LEFT JOIN ' . $this->table('admin_user_permissions') . ' up ON up.permission_id = p.id AND up.user_id = :user_id_override ORDER BY p.code');
        $statement->execute(array(':user_id_role' => $userId, ':user_id_override' => $userId));
        $codes = array();
        $owner = $this->pdo->prepare('SELECT 1 FROM ' . $this->table('admin_users') . ' WHERE id = :id AND (role = \'owner\' OR role_id = (SELECT id FROM ' . $this->table('admin_roles') . ' WHERE slug = \'owner\')) LIMIT 1');
        $owner->execute(array(':id' => $userId));
        $isOwner = (bool) $owner->fetchColumn();
        foreach ($statement->fetchAll() as $row) {
            if ($isOwner || $row['effect'] === 'allow') {
                $codes[] = (string) $row['code'];
            }
        }
        return $codes;
    }

    public function listPermissions(): array
    {
        $this->requireReady();
        return $this->pdo->query('SELECT id, code, title, module, action FROM ' . $this->table('admin_permissions') . ' ORDER BY module, action, code')->fetchAll();
    }

    public function listUsers(string $query = ''): array
    {
        $this->requireReady();
        $query = clean_query($query);
        $where = '';
        $params = array();
        if ($query !== '') {
            $where = ' WHERE u.name LIKE :query OR u.email LIKE :query OR u.title LIKE :query OR r.title LIKE :query';
            $params[':query'] = '%' . $query . '%';
        }
        $statement = $this->pdo->prepare('SELECT u.id, u.name, u.email, u.title, u.role, u.role_id, u.is_active, u.last_login_at, u.created_at, r.slug AS role_slug, r.title AS role_title FROM ' . $this->table('admin_users') . ' u LEFT JOIN ' . $this->table('admin_roles') . ' r ON r.id = u.role_id' . $where . ' ORDER BY u.is_active DESC, u.name ASC');
        $statement->execute($params);
        return $statement->fetchAll();
    }

    public function getAdminUser(int $id): ?array
    {
        $row = $this->findAdminById($id);
        if (!$row) {
            return null;
        }
        $overrides = $this->pdo->prepare('SELECT p.code, up.effect FROM ' . $this->table('admin_user_permissions') . ' up JOIN ' . $this->table('admin_permissions') . ' p ON p.id = up.permission_id WHERE up.user_id = :user_id ORDER BY p.code');
        $overrides->execute(array(':user_id' => $id));
        $row['overrides'] = array();
        foreach ($overrides->fetchAll() as $override) {
            $row['overrides'][(string) $override['code']] = (string) $override['effect'];
        }
        return $row;
    }

    public function listRoles(): array
    {
        $this->requireReady();
        return $this->pdo->query('SELECT r.id, r.slug, r.title, r.description, r.is_system, r.is_active, r.created_at, r.updated_at, COUNT(DISTINCT u.id) AS user_count, COUNT(DISTINCT rp.permission_id) AS permission_count FROM ' . $this->table('admin_roles') . ' r LEFT JOIN ' . $this->table('admin_users') . ' u ON u.role_id = r.id LEFT JOIN ' . $this->table('admin_role_permissions') . ' rp ON rp.role_id = r.id GROUP BY r.id ORDER BY r.is_system DESC, r.title ASC')->fetchAll();
    }

    public function getRole(int $id): ?array
    {
        $this->requireReady();
        $statement = $this->pdo->prepare('SELECT id, slug, title, description, is_system, is_active FROM ' . $this->table('admin_roles') . ' WHERE id = :id LIMIT 1');
        $statement->execute(array(':id' => $id));
        $role = $statement->fetch();
        if (!$role) {
            return null;
        }
        $permissions = $this->pdo->prepare('SELECT p.code FROM ' . $this->table('admin_role_permissions') . ' rp JOIN ' . $this->table('admin_permissions') . ' p ON p.id = rp.permission_id WHERE rp.role_id = :role_id');
        $permissions->execute(array(':role_id' => $id));
        $role['permissions'] = array_map('strval', $permissions->fetchAll(PDO::FETCH_COLUMN));
        return $role;
    }

    public function saveRole(array $data): int
    {
        $this->requireReady();
        $id = (int) ($data['id'] ?? 0);
        $this->pdo->beginTransaction();
        try {
            if ($id > 0) {
                $statement = $this->pdo->prepare('UPDATE ' . $this->table('admin_roles') . ' SET title = :title, description = :description, is_active = :is_active, updated_at = NOW() WHERE id = :id AND is_system = 0');
                $statement->execute(array(':title' => $data['title'], ':description' => $data['description'], ':is_active' => !empty($data['is_active']) ? 1 : 0, ':id' => $id));
                if ($statement->rowCount() === 0 && !$this->getRole($id)) {
                    throw new RuntimeException('Role not found.');
                }
            } else {
                $statement = $this->pdo->prepare('INSERT INTO ' . $this->table('admin_roles') . ' (slug, title, description, is_system, is_active, created_at, updated_at) VALUES (:slug, :title, :description, 0, :is_active, NOW(), NOW())');
                $statement->execute(array(':slug' => $data['slug'], ':title' => $data['title'], ':description' => $data['description'], ':is_active' => !empty($data['is_active']) ? 1 : 0));
                $id = (int) $this->pdo->lastInsertId();
            }
            $this->pdo->prepare('DELETE FROM ' . $this->table('admin_role_permissions') . ' WHERE role_id = :role_id')->execute(array(':role_id' => $id));
            $permission = $this->pdo->prepare('INSERT INTO ' . $this->table('admin_role_permissions') . ' (role_id, permission_id) SELECT :role_id, id FROM ' . $this->table('admin_permissions') . ' WHERE code = :code');
            foreach (array_unique((array) ($data['permissions'] ?? array())) as $code) {
                $permission->execute(array(':role_id' => $id, ':code' => $code));
            }
            $this->pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function deleteRole(int $id): bool
    {
        $this->requireReady();
        $role = $this->getRole($id);
        if (!$role || !empty($role['is_system'])) {
            return false;
        }
        $assigned = $this->pdo->prepare('SELECT COUNT(*) FROM ' . $this->table('admin_users') . ' WHERE role_id = :role_id');
        $assigned->execute(array(':role_id' => $id));
        if ((int) $assigned->fetchColumn() > 0) {
            return false;
        }
        $statement = $this->pdo->prepare('DELETE FROM ' . $this->table('admin_roles') . ' WHERE id = :id AND is_system = 0');
        $statement->execute(array(':id' => $id));
        return $statement->rowCount() > 0;
    }

    public function saveAdminUser(array $data): int
    {
        $this->requireReady();
        $id = (int) ($data['id'] ?? 0);
        $roleId = (int) ($data['role_id'] ?? 0);
        $role = $this->getRole($roleId);
        if (!$role || empty($role['is_active'])) {
            throw new RuntimeException('The selected role is unavailable.');
        }
        $this->pdo->beginTransaction();
        try {
            $current = $id > 0 ? $this->getAdminUserForUpdate($id) : null;
            if ($id > 0 && !$current) {
                throw new RuntimeException('User not found.');
            }
            $currentIsOwner = $current && (($current['role_slug'] ?? '') === 'owner' || (string) ($current['role'] ?? '') === 'owner');
            $nextIsOwner = ((string) ($role['slug'] ?? '') === 'owner');
            $nextActive = !empty($data['is_active']);
            if ($currentIsOwner && (!$nextIsOwner || !$nextActive) && $this->countActiveOwners($id) < 1) {
                throw new RuntimeException('owner_invariant');
            }
            if ($id > 0) {
                $fields = array('email' => $data['email'], 'name' => $data['name'], 'title' => $data['title'], 'role' => $role['slug'], 'role_id' => $roleId, 'is_active' => $nextActive ? 1 : 0);
                $params = array(':id' => $id);
                $sets = array();
                foreach ($fields as $field => $value) {
                    $sets[] = $field . ' = :' . $field;
                    $params[':' . $field] = $value;
                }
                if (!empty($data['password_hash'])) {
                    $sets[] = 'password_hash = :password_hash';
                    $sets[] = 'password_changed_at = NOW()';
                    $params[':password_hash'] = $data['password_hash'];
                }
                $sets[] = 'updated_at = NOW()';
                $statement = $this->pdo->prepare('UPDATE ' . $this->table('admin_users') . ' SET ' . implode(', ', $sets) . ' WHERE id = :id');
                $statement->execute($params);
            } else {
                if (empty($data['password_hash'])) {
                    throw new RuntimeException('password_required');
                }
                $statement = $this->pdo->prepare('INSERT INTO ' . $this->table('admin_users') . ' (email, name, title, password_hash, role, role_id, is_active, created_by, password_changed_at, created_at, updated_at) VALUES (:email, :name, :title, :password_hash, :role, :role_id, :is_active, :created_by, NOW(), NOW(), NOW())');
                $statement->execute(array(':email' => $data['email'], ':name' => $data['name'], ':title' => $data['title'], ':password_hash' => $data['password_hash'], ':role' => $role['slug'], ':role_id' => $roleId, ':is_active' => $nextActive ? 1 : 0, ':created_by' => !empty($data['created_by']) ? (int) $data['created_by'] : null));
                $id = (int) $this->pdo->lastInsertId();
            }
            $this->replaceUserPermissions($id, (array) ($data['overrides'] ?? array()));
            $this->pdo->commit();
            return $id;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function deactivateAdminUser(int $id, bool $active): void
    {
        $this->requireReady();
        $this->pdo->beginTransaction();
        try {
            $current = $this->getAdminUserForUpdate($id);
            if (!$current) {
                throw new RuntimeException('User not found.');
            }
            $isOwner = (($current['role_slug'] ?? '') === 'owner' || (string) ($current['role'] ?? '') === 'owner');
            if ($isOwner && !$active && $this->countActiveOwners($id) < 1) {
                throw new RuntimeException('owner_invariant');
            }
            $statement = $this->pdo->prepare('UPDATE ' . $this->table('admin_users') . ' SET is_active = :is_active, updated_at = NOW() WHERE id = :id');
            $statement->execute(array(':is_active' => $active ? 1 : 0, ':id' => $id));
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    private function getAdminUserForUpdate(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT u.id, u.email, u.name, u.title, u.role, u.role_id, u.is_active, r.slug AS role_slug FROM ' . $this->table('admin_users') . ' u LEFT JOIN ' . $this->table('admin_roles') . ' r ON r.id = u.role_id WHERE u.id = :id LIMIT 1 FOR UPDATE');
        $statement->execute(array(':id' => $id));
        $row = $statement->fetch();
        return $row ?: null;
    }

    private function countActiveOwners(?int $excludeId = null): int
    {
        $sql = 'SELECT COUNT(*) FROM ' . $this->table('admin_users') . ' u LEFT JOIN ' . $this->table('admin_roles') . ' r ON r.id = u.role_id WHERE u.is_active = 1 AND (r.slug = \'owner\' OR u.role = \'owner\')';
        $params = array();
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND u.id <> :exclude_id';
            $params[':exclude_id'] = $excludeId;
        }
        $statement = $this->pdo->prepare($sql);
        $statement->execute($params);
        return (int) $statement->fetchColumn();
    }

    private function replaceUserPermissions(int $userId, array $overrides): void
    {
        $this->pdo->prepare('DELETE FROM ' . $this->table('admin_user_permissions') . ' WHERE user_id = :user_id')->execute(array(':user_id' => $userId));
        $statement = $this->pdo->prepare('INSERT INTO ' . $this->table('admin_user_permissions') . ' (user_id, permission_id, effect, created_at, updated_at) SELECT :user_id, id, :effect, NOW(), NOW() FROM ' . $this->table('admin_permissions') . ' WHERE code = :code');
        foreach ($overrides as $code => $effect) {
            if (!in_array($effect, array('allow', 'deny'), true) || !preg_match('/^[a-z0-9_.-]{3,100}$/', (string) $code)) {
                continue;
            }
            $statement->execute(array(':user_id' => $userId, ':effect' => $effect, ':code' => $code));
        }
    }

    private function requireReady(): void
    {
        if (!$this->isReady()) {
            throw new RuntimeException('The admin database is not ready.');
        }
    }

    private function hasSchema(): bool
    {
        $required = array('admin_users', 'admin_audit', 'admin_roles', 'admin_permissions', 'admin_role_permissions', 'admin_user_permissions', 'product_variants');
        $quoted = array_map(function (string $name): string { return "'" . $this->table($name) . "'"; }, $required);
        $statement = $this->pdo->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name IN (" . implode(',', $quoted) . ")");
        return (int) $statement->fetchColumn() === count($required);
    }

    private function countTable(string $name, string $where = ''): int
    {
        return (int) $this->pdo->query('SELECT COUNT(*) FROM ' . $this->table($name) . ($where !== '' ? ' WHERE ' . $where : ''))->fetchColumn();
    }

    private function columnValues(string $sql, string $key, int $value): array
    {
        $statement = $this->pdo->prepare($sql);
        $statement->execute(array($key => $value));
        $rows = $statement->fetchAll(PDO::FETCH_COLUMN);
        return is_array($rows) ? $rows : array();
    }

    private function replaceProductRelations(int $productId, array $data): void
    {
        foreach (array('product_categories', 'product_images', 'product_attributes', 'product_tags', 'product_variants') as $relation) {
            $delete = $this->pdo->prepare('DELETE FROM ' . $this->table($relation) . ' WHERE product_id = :product_id');
            $delete->execute(array(':product_id' => $productId));
        }
        $categoryStatement = $this->pdo->prepare('INSERT INTO ' . $this->table('product_categories') . ' (product_id, category_id) VALUES (:product_id, :category_id)');
        foreach (array_unique(array_map('intval', $data['category_ids'])) as $categoryId) {
            if ($categoryId > 0) {
                $categoryStatement->execute(array(':product_id' => $productId, ':category_id' => $categoryId));
            }
        }
        $imageStatement = $this->pdo->prepare('INSERT INTO ' . $this->table('product_images') . ' (product_id, path, original_name, stored_name, mime_type, file_size, width, height, alt_text, is_primary, uploaded_by, created_at, sort_order) VALUES (:product_id, :path, :original_name, :stored_name, :mime_type, :file_size, :width, :height, :alt_text, :is_primary, :uploaded_by, NOW(), :sort_order)');
        $imageRecords = !empty($data['image_records']) ? (array) $data['image_records'] : array_map(function ($path): array { return array('path' => (string) $path); }, (array) ($data['images'] ?? array()));
        $primaryPath = (string) ($data['primary_image_path'] ?? '');
        foreach (array_values($imageRecords) as $index => $image) {
            if (!is_array($image) || trim((string) ($image['path'] ?? '')) === '') {
                continue;
            }
            $path = (string) $image['path'];
            $imageStatement->execute(array(
                ':product_id' => $productId,
                ':path' => $path,
                ':original_name' => string_limit((string) ($image['original_name'] ?? ''), 255),
                ':stored_name' => string_limit((string) ($image['stored_name'] ?? ''), 255),
                ':mime_type' => string_limit((string) ($image['mime_type'] ?? ''), 80),
                ':file_size' => !empty($image['file_size']) ? (int) $image['file_size'] : null,
                ':width' => !empty($image['width']) ? (int) $image['width'] : null,
                ':height' => !empty($image['height']) ? (int) $image['height'] : null,
                ':alt_text' => string_limit((string) ($image['alt_text'] ?? ''), 255),
                ':is_primary' => ($primaryPath !== '' && $primaryPath === $path) || ($primaryPath === '' && $index === 0) ? 1 : 0,
                ':uploaded_by' => !empty($image['uploaded_by']) ? (int) $image['uploaded_by'] : null,
                ':sort_order' => $index,
            ));
        }
        $attributeStatement = $this->pdo->prepare('INSERT INTO ' . $this->table('product_attributes') . ' (product_id, attribute_name, attribute_value, sort_order) VALUES (:product_id, :attribute_name, :attribute_value, :sort_order)');
        foreach (array_values($data['attributes']) as $index => $attribute) {
            $attributeStatement->execute(array(':product_id' => $productId, ':attribute_name' => $attribute['name'], ':attribute_value' => $attribute['value'], ':sort_order' => $index));
        }
        $tagStatement = $this->pdo->prepare('INSERT INTO ' . $this->table('product_tags') . ' (product_id, tag_name, sort_order) VALUES (:product_id, :tag_name, :sort_order)');
        foreach (array_values($data['tags']) as $index => $tag) {
            $tagStatement->execute(array(':product_id' => $productId, ':tag_name' => $tag, ':sort_order' => $index));
        }
        $variantStatement = $this->pdo->prepare('INSERT INTO ' . $this->table('product_variants') . ' (product_id, variant_sku, size, color, stock_status, stock_quantity, price_override, is_active, sort_order) VALUES (:product_id, :variant_sku, :size, :color, :stock_status, :stock_quantity, :price_override, :is_active, :sort_order)');
        foreach (array_values((array) ($data['variants'] ?? array())) as $index => $variant) {
            if (!is_array($variant) || ((string) ($variant['variant_sku'] ?? '') === '' && (string) ($variant['size'] ?? '') === '' && (string) ($variant['color'] ?? '') === '')) {
                continue;
            }
            $variantStatement->execute(array(
                ':product_id' => $productId,
                ':variant_sku' => string_limit((string) ($variant['variant_sku'] ?? ''), 96),
                ':size' => string_limit((string) ($variant['size'] ?? ''), 80),
                ':color' => string_limit((string) ($variant['color'] ?? ''), 120),
                ':stock_status' => in_array($variant['stock_status'] ?? '', array('instock', 'onbackorder', 'outofstock'), true) ? $variant['stock_status'] : 'outofstock',
                ':stock_quantity' => ($variant['stock_quantity'] ?? '') === '' ? null : (int) $variant['stock_quantity'],
                ':price_override' => ($variant['price_override'] ?? '') === '' ? null : $variant['price_override'],
                ':is_active' => !empty($variant['is_active']) ? 1 : 0,
                ':sort_order' => $index,
            ));
        }
        $this->pdo->exec('UPDATE ' . $this->table('categories') . ' c SET product_count = (SELECT COUNT(*) FROM ' . $this->table('product_categories') . ' pc WHERE pc.category_id = c.id)');
    }

    private function table(string $name): string
    {
        return $this->prefix . $name;
    }
}
