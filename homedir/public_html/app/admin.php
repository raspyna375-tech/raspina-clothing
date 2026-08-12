<?php
declare(strict_types=1);

function admin_handle_request(string $path, string $method, array $config, CatalogRepository $catalog): bool
{
    if ($path !== '/admin' && strpos($path, '/admin/') !== 0) {
        return false;
    }

    $repository = new AdminRepository($config);
    $route = trim($path, '/');
    $segments = $route === '' ? array() : explode('/', $route);
    $section = isset($segments[1]) ? $segments[1] : 'dashboard';

    if ($section === 'login') {
        admin_login($repository, $config, $method);
        return true;
    }
    if ($section === 'setup') {
        admin_setup($repository, $config, $method);
        return true;
    }
    if ($section === 'logout') {
        admin_logout($repository, $config, $method);
        return true;
    }

    $user = admin_authenticated_user($repository);
    if ($user === null) {
        $next = $path === '/admin' ? '/admin' : $path;
        redirect_to(admin_url('/login?next=' . rawurlencode($next)), 303);
    }

    if ($section === 'products') {
        if (isset($segments[2]) && ($segments[2] === 'new' || $segments[2] === 'edit')) {
            $id = isset($segments[3]) ? positive_int($segments[3]) : 0;
            admin_require_permission($repository, $user, $id > 0 ? 'products.update' : 'products.create');
            admin_product_form($repository, $config, $method, $id, $user);
        } elseif (isset($_POST['admin_action']) && $_POST['admin_action'] === 'delete_product') {
            admin_require_permission($repository, $user, 'products.delete');
            admin_delete_product($repository, $config, $method, $user);
        } else {
            admin_require_permission($repository, $user, 'products.view');
            admin_products($repository, $config, $method);
        }
        return true;
    }
    if ($section === 'categories') {
        admin_require_permission($repository, $user, 'categories.manage');
        if (isset($segments[2]) && ($segments[2] === 'new' || $segments[2] === 'edit')) {
            $id = isset($segments[3]) ? positive_int($segments[3]) : 0;
            admin_category_form($repository, $config, $method, $id, $user);
        } elseif (isset($_POST['admin_action']) && $_POST['admin_action'] === 'delete_category') {
            admin_delete_category($repository, $config, $method, $user);
        } else {
            admin_categories($repository, $config, $method);
        }
        return true;
    }
    if ($section === 'messages') {
        if (isset($segments[2]) && $segments[2] === 'view') {
            admin_require_permission($repository, $user, 'messages.view');
            $id = isset($segments[3]) ? positive_int($segments[3]) : 0;
            admin_message_view($repository, $config, $method, $id);
        } elseif (isset($_POST['admin_action']) && $_POST['admin_action'] === 'delete_message') {
            admin_require_permission($repository, $user, 'messages.delete');
            admin_delete_message($repository, $config, $method, $user);
        } else {
            admin_require_permission($repository, $user, 'messages.view');
            admin_messages($repository, $config, $method);
        }
        return true;
    }
    if ($section === 'settings') {
        admin_require_permission($repository, $user, 'settings.manage');
        admin_settings($repository, $config, $method, $user);
        return true;
    }
    if ($section === 'users') {
        if (isset($_POST['admin_action']) && $_POST['admin_action'] === 'deactivate_user') {
            admin_require_permission($repository, $user, 'users.deactivate');
            admin_deactivate_user($repository, $config, $method, $user);
        } elseif (isset($segments[2]) && ($segments[2] === 'new' || $segments[2] === 'edit')) {
            $id = isset($segments[3]) ? positive_int($segments[3]) : 0;
            admin_require_permission($repository, $user, $id > 0 ? 'users.update' : 'users.create');
            admin_user_form($repository, $config, $method, $id, $user);
        } else {
            admin_require_permission($repository, $user, 'users.view');
            admin_users($repository, $config, $method, $user);
        }
        return true;
    }
    if ($section === 'roles') {
        admin_require_permission($repository, $user, 'roles.manage');
        if (isset($_POST['admin_action']) && $_POST['admin_action'] === 'delete_role') {
            admin_delete_role($repository, $config, $method, $user);
        } elseif (isset($segments[2]) && ($segments[2] === 'new' || $segments[2] === 'edit')) {
            $id = isset($segments[3]) ? positive_int($segments[3]) : 0;
            admin_role_form($repository, $config, $method, $id, $user);
        } else {
            admin_roles($repository, $config, $method, $user);
        }
        return true;
    }
    if ($section === 'dashboard' || $section === 'index') {
        admin_require_permission($repository, $user, 'dashboard.view');
        admin_dashboard($repository, $config, $catalog, $user);
        return true;
    }

    http_response_code(404);
    admin_render('dashboard', array(
        'admin_user' => $user,
        'admin_repository' => $repository,
        'page_title' => 'Admin route not found',
        'admin_error' => 'The requested admin route could not be found.',
        'stats' => array('products' => 0, 'categories' => 0, 'messages' => 0, 'featured' => 0, 'latest_messages' => array()),
    ));
    return true;
}

function admin_login(AdminRepository $repository, array $config, string $method): void
{
    if ($method === 'POST') {
        $errors = array();
        $email = strtolower(clean_form_value($_POST['email'] ?? '', 190));
        $password = (string) ($_POST['password'] ?? '');
        $next = admin_safe_next($_POST['next'] ?? ($_GET['next'] ?? ''));
        if (!admin_verify_csrf()) {
            $errors[] = 'Your session expired. Reload this page and try again.';
        } elseif (!$repository->isReady()) {
            $errors[] = $repository->statusMessage();
        } elseif (!rate_limit_allowed('admin-login', $config)) {
            $errors[] = 'Too many sign-in attempts. Please wait a few minutes and try again.';
        } else {
            $user = $repository->findAdminByEmail($email);
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '' || !$user || empty($user['is_active']) || !password_verify($password, (string) $user['password_hash'])) {
                $errors[] = 'The email or password is not correct.';
            } else {
                session_regenerate_id(true);
                $_SESSION['admin_user'] = array('id' => (int) $user['id'], 'email' => (string) $user['email'], 'name' => (string) $user['name'], 'role' => (string) $user['role']);
                $_SESSION['admin_last_seen'] = time();
                $repository->touchLastLogin((int) $user['id']);
                $repository->audit((int) $user['id'], 'login', 'admin');
                redirect_to(url($next), 303);
            }
        }
        admin_render('login', array('page_title' => 'Admin sign in', 'errors' => $errors, 'email' => $email, 'next' => $next, 'admin_repository' => $repository));
        return;
    }
    if ($method !== 'GET' && $method !== 'HEAD') {
        admin_method_not_allowed('GET, HEAD, POST');
    }
    if (admin_authenticated_user($repository) !== null) {
        redirect_to(admin_url('/'), 303);
    }
    admin_render('login', array(
        'page_title' => 'Admin sign in',
        'errors' => array(),
        'email' => '',
        'next' => admin_safe_next($_GET['next'] ?? ''),
        'admin_repository' => $repository,
    ));
}

function admin_setup(AdminRepository $repository, array $config, string $method): void
{
    $setupKey = trim((string) ($config['admin']['setup_key'] ?? ''));
    $locked = $repository->isReady() && $repository->adminUserCount() > 0;
    if ($method === 'POST' && !$locked) {
        $errors = array();
        $name = clean_form_value($_POST['name'] ?? '', 120);
        $email = strtolower(clean_form_value($_POST['email'] ?? '', 190));
        $password = (string) ($_POST['password'] ?? '');
        $confirmation = (string) ($_POST['password_confirmation'] ?? '');
        $providedKey = trim((string) ($_POST['setup_key'] ?? ''));
        if (!admin_verify_csrf()) {
            $errors[] = 'Your session expired. Reload this page and try again.';
        } elseif (!$repository->isReady()) {
            $errors[] = $repository->statusMessage();
        } elseif ($setupKey === '') {
            $errors[] = 'First-admin setup is disabled until admin.setup_key is configured in app/config.local.php.';
        } elseif (!rate_limit_allowed('admin-setup', $config)) {
            $errors[] = 'Too many setup attempts. Please wait and try again.';
        } elseif (!hash_equals($setupKey, $providedKey)) {
            $errors[] = 'The setup key is not correct.';
        }
        if ($name === '') {
            $errors[] = 'Enter the administrator name.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Enter a valid administrator email.';
        }
        if (strlen($password) < 12) {
            $errors[] = 'Use a password with at least 12 characters.';
        }
        if (!hash_equals($password, $confirmation)) {
            $errors[] = 'The password confirmation does not match.';
        }
        if (!$errors) {
            try {
                $id = $repository->createFirstAdmin($email, $name, password_hash($password, PASSWORD_DEFAULT));
                session_regenerate_id(true);
                $_SESSION['admin_user'] = array('id' => $id, 'email' => $email, 'name' => $name, 'role' => 'owner');
                $_SESSION['admin_last_seen'] = time();
                $repository->audit($id, 'first_setup', 'admin');
                redirect_to(admin_url('/'), 303);
            } catch (Throwable $exception) {
                error_log('Admin setup failed: ' . get_class($exception));
                $errors[] = 'The first administrator could not be created. Confirm that setup has not already been completed.';
            }
        }
        admin_render('setup', array('page_title' => 'First admin setup', 'errors' => $errors, 'locked' => false, 'setup_enabled' => $setupKey !== '', 'admin_repository' => $repository, 'form' => array('name' => $name, 'email' => $email)));
        return;
    }
    if ($method !== 'GET' && $method !== 'HEAD') {
        admin_method_not_allowed('GET, HEAD, POST');
    }
    admin_render('setup', array('page_title' => 'First admin setup', 'errors' => array(), 'locked' => $locked, 'setup_enabled' => $setupKey !== '', 'admin_repository' => $repository, 'form' => array('name' => '', 'email' => '')));
}

function admin_logout(AdminRepository $repository, array $config, string $method): void
{
    $user = admin_authenticated_user($repository);
    if ($method !== 'POST') {
        admin_method_not_allowed('POST');
    }
    if (!admin_verify_csrf()) {
        admin_render('login', array('page_title' => 'Admin sign in', 'errors' => array('Your session expired. Reload this page and try again.'), 'email' => '', 'next' => '/admin', 'admin_repository' => $repository));
        return;
    }
    if ($user !== null) {
        $repository->audit((int) $user['id'], 'logout', 'admin');
    }
    unset($_SESSION['admin_user'], $_SESSION['admin_last_seen']);
    session_regenerate_id(true);
    redirect_to(admin_url('/login'), 303);
}

function admin_dashboard(AdminRepository $repository, array $config, CatalogRepository $catalog, array $user): void
{
    $stats = array('products' => 0, 'categories' => 0, 'messages' => 0, 'featured' => 0, 'latest_messages' => array());
    $adminError = '';
    if ($repository->isReady()) {
        try {
            $stats = $repository->dashboardStats();
        } catch (Throwable $exception) {
            error_log('Admin dashboard failed: ' . get_class($exception));
            $adminError = 'The dashboard data could not be loaded. Check the database connection and schema.';
        }
    } else {
        $adminError = $repository->statusMessage();
    }
    admin_render('dashboard', array('page_title' => 'Operations overview', 'admin_user' => $user, 'admin_repository' => $repository, 'stats' => $stats, 'admin_error' => $adminError, 'catalog_mode' => $catalog->mode()));
}

function admin_products(AdminRepository $repository, array $config, string $method): void
{
    if ($method !== 'GET' && $method !== 'HEAD') {
        admin_method_not_allowed('GET, HEAD');
    }
    $query = clean_query($_GET['q'] ?? '');
    $page = positive_int($_GET['page'] ?? 1);
    $data = array('items' => array(), 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => 20);
    $adminError = $repository->isReady() ? '' : $repository->statusMessage();
    if ($repository->isReady()) {
        try {
            $data = $repository->listProducts($query, $page);
        } catch (Throwable $exception) {
            error_log('Admin product list failed: ' . get_class($exception));
            $adminError = 'Products could not be loaded from MySQL.';
        }
    }
    admin_render('products', array('page_title' => 'Products', 'admin_repository' => $repository, 'products' => $data, 'query' => $query, 'admin_error' => $adminError));
}

function admin_product_form(AdminRepository $repository, array $config, string $method, int $id, array $user): void
{
    if (!$repository->isReady()) {
        admin_render('product-form', array('page_title' => $id > 0 ? 'Edit product' : 'New product', 'admin_repository' => $repository, 'product' => null, 'categories' => array(), 'form' => array(), 'errors' => array($repository->statusMessage()), 'read_only' => true));
        return;
    }
    $product = null;
    if ($id > 0) {
        try {
            $product = $repository->getProduct($id);
        } catch (Throwable $exception) {
            error_log('Admin product read failed: ' . get_class($exception));
        }
        if ($product === null) {
            admin_set_flash('error', 'The requested product was not found.');
            redirect_to(admin_url('/products'), 303);
        }
    }
    $form = admin_product_form_values($product);
    $errors = array();
    $uploadedRecords = array();
    if ($method === 'POST') {
        if (!admin_verify_csrf()) {
            $errors[] = 'Your session expired. Reload this page and try again.';
        } else {
            list($form, $errors) = admin_product_payload($_POST, $id);
            if (!empty($_FILES['product_images'])) {
                admin_require_permission($repository, $user, 'products.upload');
                list($uploadedRecords, $uploadErrors) = admin_process_product_uploads($_FILES['product_images'], (int) $user['id'], clean_form_value($_POST['upload_alt'] ?? '', 255));
                $errors = array_merge($errors, $uploadErrors);
                $form['image_records'] = array_merge((array) ($form['image_records'] ?? array()), $uploadedRecords);
                $form['images'] = array_map(function (array $image): string { return (string) ($image['path'] ?? ''); }, (array) $form['image_records']);
            }
            if (!$errors) {
                try {
                    $savedId = $repository->saveProduct($form);
                    $repository->audit((int) $user['id'], $id > 0 ? 'update' : 'create', 'product', $savedId, admin_ip_hash());
                    admin_set_flash('success', $id > 0 ? 'Product updated successfully.' : 'Product created successfully.');
                    redirect_to(admin_url('/products'), 303);
                } catch (Throwable $exception) {
                    error_log('Admin product save failed: ' . get_class($exception));
                    foreach ($uploadedRecords as $uploaded) {
                        $absolute = APP_ROOT . (string) ($uploaded['path'] ?? '');
                        if (is_file($absolute)) {
                            @unlink($absolute);
                        }
                    }
                    $errors[] = 'The product could not be saved. Check that the slug and SKU are unique and try again.';
                }
            }
            if ($errors && $uploadedRecords) {
                foreach ($uploadedRecords as $uploaded) {
                    $absolute = APP_ROOT . (string) ($uploaded['path'] ?? '');
                    if (is_file($absolute)) {
                        @unlink($absolute);
                    }
                }
            }
        }
    } elseif ($method !== 'GET' && $method !== 'HEAD') {
        admin_method_not_allowed('GET, HEAD, POST');
    }
    try {
        $categories = $repository->listCategories();
    } catch (Throwable $exception) {
        $categories = array();
        $errors[] = 'Categories could not be loaded.';
    }
    admin_render('product-form', array('page_title' => $id > 0 ? 'Edit product' : 'New product', 'admin_repository' => $repository, 'product' => $product, 'categories' => $categories, 'form' => $form, 'errors' => $errors, 'read_only' => false, 'can_upload' => $repository->hasPermission((int) $user['id'], 'products.upload')));
}

function admin_delete_product(AdminRepository $repository, array $config, string $method, array $user): void
{
    if ($method !== 'POST') {
        admin_method_not_allowed('POST');
    }
    if (!admin_verify_csrf()) {
        admin_set_flash('error', 'Your session expired. Reload the page and try again.');
        redirect_to(admin_url('/products'), 303);
    }
    $id = positive_int($_POST['id'] ?? 0);
    if (!$repository->isReady() || $id < 1) {
        admin_set_flash('error', $repository->isReady() ? 'The product selection is not valid.' : $repository->statusMessage());
        redirect_to(admin_url('/products'), 303);
    }
    try {
        $repository->deleteProduct($id);
        $repository->audit((int) $user['id'], 'delete', 'product', $id, admin_ip_hash());
        admin_set_flash('success', 'Product deleted.');
    } catch (Throwable $exception) {
        error_log('Admin product delete failed: ' . get_class($exception));
        admin_set_flash('error', 'The product could not be deleted.');
    }
    redirect_to(admin_url('/products'), 303);
}

function admin_categories(AdminRepository $repository, array $config, string $method): void
{
    if ($method !== 'GET' && $method !== 'HEAD') {
        admin_method_not_allowed('GET, HEAD');
    }
    $query = clean_query($_GET['q'] ?? '');
    $categories = array();
    $adminError = $repository->isReady() ? '' : $repository->statusMessage();
    if ($repository->isReady()) {
        try {
            $categories = $repository->listCategories($query);
        } catch (Throwable $exception) {
            error_log('Admin category list failed: ' . get_class($exception));
            $adminError = 'Categories could not be loaded from MySQL.';
        }
    }
    admin_render('categories', array('page_title' => 'Categories', 'admin_repository' => $repository, 'categories' => $categories, 'query' => $query, 'admin_error' => $adminError, 'editing' => null, 'form' => array(), 'errors' => array()));
}

function admin_category_form(AdminRepository $repository, array $config, string $method, int $id, array $user): void
{
    if (!$repository->isReady()) {
        admin_render('categories', array('page_title' => $id > 0 ? 'Edit category' : 'New category', 'admin_repository' => $repository, 'categories' => array(), 'query' => '', 'admin_error' => $repository->statusMessage(), 'editing' => null, 'form' => array(), 'errors' => array($repository->statusMessage())));
        return;
    }
    $editing = $id > 0 ? $repository->getCategory($id) : null;
    if ($id > 0 && $editing === null) {
        admin_set_flash('error', 'The requested category was not found.');
        redirect_to(admin_url('/categories'), 303);
    }
    $form = array('id' => $id, 'name' => $editing['name'] ?? '', 'slug' => $editing['slug'] ?? '', 'description' => $editing['description'] ?? '', 'parent_slug' => $editing['parent_slug'] ?? '');
    $errors = array();
    if ($method === 'POST') {
        if (!admin_verify_csrf()) {
            $errors[] = 'Your session expired. Reload this page and try again.';
        } else {
            list($form, $errors) = admin_category_payload($_POST, $id);
            if (!$errors) {
                try {
                    $savedId = $repository->saveCategory($form);
                    $repository->audit((int) $user['id'], $id > 0 ? 'update' : 'create', 'category', $savedId, admin_ip_hash());
                    admin_set_flash('success', $id > 0 ? 'Category updated successfully.' : 'Category created successfully.');
                    redirect_to(admin_url('/categories'), 303);
                } catch (Throwable $exception) {
                    error_log('Admin category save failed: ' . get_class($exception));
                    $errors[] = 'The category could not be saved. Confirm that the slug is unique.';
                }
            }
        }
    } elseif ($method !== 'GET' && $method !== 'HEAD') {
        admin_method_not_allowed('GET, HEAD, POST');
    }
    $categories = $repository->listCategories();
    admin_render('categories', array('page_title' => $id > 0 ? 'Edit category' : 'New category', 'admin_repository' => $repository, 'categories' => $categories, 'query' => '', 'admin_error' => '', 'editing' => $editing, 'form' => $form, 'errors' => $errors));
}

function admin_delete_category(AdminRepository $repository, array $config, string $method, array $user): void
{
    if ($method !== 'POST') {
        admin_method_not_allowed('POST');
    }
    if (!admin_verify_csrf()) {
        admin_set_flash('error', 'Your session expired. Reload the page and try again.');
        redirect_to(admin_url('/categories'), 303);
    }
    $id = positive_int($_POST['id'] ?? 0);
    if (!$repository->isReady() || $id < 1) {
        admin_set_flash('error', $repository->isReady() ? 'The category selection is not valid.' : $repository->statusMessage());
        redirect_to(admin_url('/categories'), 303);
    }
    try {
        $result = $repository->deleteCategory($id);
        if (!$result['ok']) {
            admin_set_flash('error', 'This category still has ' . (int) $result['assigned'] . ' assigned product(s). Move them first, then delete the category.');
        } else {
            $repository->audit((int) $user['id'], 'delete', 'category', $id, admin_ip_hash());
            admin_set_flash('success', 'Category deleted.');
        }
    } catch (Throwable $exception) {
        error_log('Admin category delete failed: ' . get_class($exception));
        admin_set_flash('error', 'The category could not be deleted.');
    }
    redirect_to(admin_url('/categories'), 303);
}

function admin_messages(AdminRepository $repository, array $config, string $method): void
{
    if ($method !== 'GET' && $method !== 'HEAD') {
        admin_method_not_allowed('GET, HEAD');
    }
    $query = clean_query($_GET['q'] ?? '');
    $type = (string) ($_GET['type'] ?? '');
    $data = array('items' => array(), 'total' => 0, 'page' => 1, 'pages' => 1, 'per_page' => 20);
    $adminError = $repository->isReady() ? '' : $repository->statusMessage();
    if ($repository->isReady()) {
        try {
            $data = $repository->listMessages($query, $type, positive_int($_GET['page'] ?? 1));
        } catch (Throwable $exception) {
            error_log('Admin message list failed: ' . get_class($exception));
            $adminError = 'Messages could not be loaded from MySQL.';
        }
    }
    admin_render('messages', array('page_title' => 'Enquiries', 'admin_repository' => $repository, 'messages' => $data, 'query' => $query, 'type' => $type, 'admin_error' => $adminError));
}

function admin_message_view(AdminRepository $repository, array $config, string $method, int $id): void
{
    if ($method !== 'GET' && $method !== 'HEAD') {
        admin_method_not_allowed('GET, HEAD');
    }
    $message = $repository->isReady() && $id > 0 ? $repository->getMessage($id) : null;
    if ($message === null) {
        admin_set_flash('error', $repository->isReady() ? 'The enquiry was not found.' : $repository->statusMessage());
        redirect_to(admin_url('/messages'), 303);
    }
    admin_render('message-view', array('page_title' => 'Enquiry from ' . $message['name'], 'admin_repository' => $repository, 'message' => $message));
}

function admin_delete_message(AdminRepository $repository, array $config, string $method, array $user): void
{
    if ($method !== 'POST') {
        admin_method_not_allowed('POST');
    }
    if (!admin_verify_csrf()) {
        admin_set_flash('error', 'Your session expired. Reload the page and try again.');
        redirect_to(admin_url('/messages'), 303);
    }
    $id = positive_int($_POST['id'] ?? 0);
    if (!$repository->isReady() || $id < 1) {
        admin_set_flash('error', $repository->isReady() ? 'The enquiry selection is not valid.' : $repository->statusMessage());
        redirect_to(admin_url('/messages'), 303);
    }
    try {
        $repository->deleteMessage($id);
        $repository->audit((int) $user['id'], 'delete', 'message', $id, admin_ip_hash());
        admin_set_flash('success', 'Enquiry deleted.');
    } catch (Throwable $exception) {
        error_log('Admin message delete failed: ' . get_class($exception));
        admin_set_flash('error', 'The enquiry could not be deleted.');
    }
    redirect_to(admin_url('/messages'), 303);
}

function admin_users(AdminRepository $repository, array $config, string $method, array $user): void
{
    if ($method !== 'GET' && $method !== 'HEAD') {
        admin_method_not_allowed('GET, HEAD');
    }
    $query = clean_query($_GET['q'] ?? '');
    $users = array();
    $adminError = '';
    try {
        $users = $repository->listUsers($query);
    } catch (Throwable $exception) {
        error_log('Admin user list failed: ' . get_class($exception));
        $adminError = 'Team members could not be loaded from MySQL.';
    }
    admin_render('users', array(
        'page_title' => 'Team access',
        'admin_repository' => $repository,
        'admin_user' => $user,
        'users' => $users,
        'query' => $query,
        'admin_error' => $adminError,
    ));
}

function admin_user_form(AdminRepository $repository, array $config, string $method, int $id, array $user): void
{
    $roles = array();
    $permissions = array();
    $editing = null;
    $errors = array();
    try {
        $roles = $repository->listRoles();
        $permissions = $repository->listPermissions();
        if ($id > 0) {
            $editing = $repository->getAdminUser($id);
            if ($editing === null) {
                admin_set_flash('error', 'The requested team member was not found.');
                redirect_to(admin_url('/users'), 303);
            }
        }
    } catch (Throwable $exception) {
        error_log('Admin user form read failed: ' . get_class($exception));
        admin_render('user-form', array('page_title' => $id > 0 ? 'Edit team member' : 'Add team member', 'admin_repository' => $repository, 'admin_user' => $user, 'roles' => array(), 'permissions' => array(), 'form' => array(), 'errors' => array('The team access schema is not available. Import the product-team migration, then reload.'), 'editing' => $editing, 'read_only' => true));
        return;
    }

    $form = admin_user_form_values($editing);
    if ($method === 'POST') {
        if (!admin_verify_csrf()) {
            $errors[] = 'Your session expired. Reload this page and try again.';
        } else {
            list($form, $payloadErrors) = admin_user_payload($_POST, $id, $permissions);
            $errors = array_merge($errors, $payloadErrors);
            $selectedRole = null;
            if ((int) ($form['role_id'] ?? 0) > 0) {
                foreach ($roles as $role) {
                    if ((int) $role['id'] === (int) $form['role_id']) {
                        $selectedRole = $role;
                        break;
                    }
                }
            }
            if ($selectedRole === null) {
                $errors[] = 'Select an active role for this team member.';
            } elseif (empty($selectedRole['is_active'])) {
                $errors[] = 'The selected role is archived. Choose an active role.';
            }
            if (!empty($selectedRole['slug']) && $selectedRole['slug'] === 'owner' && empty($user['is_owner'])) {
                $errors[] = 'Only an owner can grant the Owner role.';
            }
            if (empty($user['is_owner']) && isset($form['overrides']['roles.manage']) && $form['overrides']['roles.manage'] === 'allow') {
                $errors[] = 'Only an owner can grant role-management access.';
            }
            $password = (string) ($_POST['password'] ?? '');
            $confirmation = (string) ($_POST['password_confirmation'] ?? '');
            if ($id < 1 && strlen($password) < 12) {
                $errors[] = 'Use a password with at least 12 characters for a new team member.';
            } elseif ($password !== '' && strlen($password) < 12) {
                $errors[] = 'A replacement password must contain at least 12 characters.';
            } elseif ($password !== '' && !hash_equals($password, $confirmation)) {
                $errors[] = 'The password confirmation does not match.';
            }
            if ($password !== '' && strlen($password) >= 12 && hash_equals($password, $confirmation)) {
                $form['password_hash'] = password_hash($password, PASSWORD_DEFAULT);
            }
            if (!$errors) {
                try {
                    $form['created_by'] = (int) $user['id'];
                    $savedId = $repository->saveAdminUser($form);
                    $repository->audit((int) $user['id'], $id > 0 ? 'update' : 'create', 'admin_user', $savedId, admin_ip_hash(), array('role_id' => (int) $form['role_id'], 'override_count' => count((array) ($form['overrides'] ?? array()))));
                    admin_set_flash('success', $id > 0 ? 'Team member updated successfully.' : 'Team member created successfully.');
                    redirect_to(admin_url('/users'), 303);
                } catch (Throwable $exception) {
                    error_log('Admin user save failed: ' . get_class($exception));
                    $message = $exception->getMessage();
                    if ($message === 'owner_invariant') {
                        $errors[] = 'Keep at least one active Owner before changing this account.';
                    } elseif ($message === 'password_required') {
                        $errors[] = 'A password is required for a new team member.';
                    } else {
                        $errors[] = 'The team member could not be saved. Confirm the email is unique and try again.';
                    }
                }
            }
        }
    } elseif ($method !== 'GET' && $method !== 'HEAD') {
        admin_method_not_allowed('GET, HEAD, POST');
    }

    admin_render('user-form', array(
        'page_title' => $id > 0 ? 'Edit team member' : 'Add team member',
        'admin_repository' => $repository,
        'admin_user' => $user,
        'roles' => $roles,
        'permissions' => $permissions,
        'form' => $form,
        'errors' => $errors,
        'editing' => $editing,
        'read_only' => false,
    ));
}

function admin_deactivate_user(AdminRepository $repository, array $config, string $method, array $user): void
{
    if ($method !== 'POST') {
        admin_method_not_allowed('POST');
    }
    if (!admin_verify_csrf()) {
        admin_set_flash('error', 'Your session expired. Reload the page and try again.');
        redirect_to(admin_url('/users'), 303);
    }
    $id = positive_int($_POST['id'] ?? 0);
    $active = !empty($_POST['active']);
    if ($id < 1) {
        admin_set_flash('error', 'The team member selection is not valid.');
        redirect_to(admin_url('/users'), 303);
    }
    if ($id === (int) $user['id'] && !$active) {
        admin_set_flash('error', 'You cannot deactivate your own account from this screen.');
        redirect_to(admin_url('/users'), 303);
    }
    try {
        $repository->deactivateAdminUser($id, $active);
        $repository->audit((int) $user['id'], $active ? 'activate' : 'deactivate', 'admin_user', $id, admin_ip_hash());
        admin_set_flash('success', $active ? 'Team member activated.' : 'Team member deactivated.');
    } catch (Throwable $exception) {
        error_log('Admin user status change failed: ' . get_class($exception));
        admin_set_flash('error', $exception->getMessage() === 'owner_invariant' ? 'Keep at least one active Owner.' : 'The team member status could not be changed.');
    }
    redirect_to(admin_url('/users'), 303);
}

function admin_roles(AdminRepository $repository, array $config, string $method, array $user): void
{
    if ($method !== 'GET' && $method !== 'HEAD') {
        admin_method_not_allowed('GET, HEAD');
    }
    $roles = array();
    $adminError = '';
    try {
        $roles = $repository->listRoles();
    } catch (Throwable $exception) {
        error_log('Admin role list failed: ' . get_class($exception));
        $adminError = 'Roles could not be loaded from MySQL.';
    }
    admin_render('roles', array('page_title' => 'Roles & permissions', 'admin_repository' => $repository, 'admin_user' => $user, 'roles' => $roles, 'admin_error' => $adminError));
}

function admin_role_form(AdminRepository $repository, array $config, string $method, int $id, array $user): void
{
    $role = null;
    $permissions = array();
    $errors = array();
    try {
        $permissions = $repository->listPermissions();
        if ($id > 0) {
            $role = $repository->getRole($id);
            if ($role === null) {
                admin_set_flash('error', 'The requested role was not found.');
                redirect_to(admin_url('/roles'), 303);
            }
        }
    } catch (Throwable $exception) {
        error_log('Admin role form read failed: ' . get_class($exception));
        admin_render('role-form', array('page_title' => $id > 0 ? 'Edit role' : 'New role', 'admin_repository' => $repository, 'admin_user' => $user, 'permissions' => array(), 'form' => array(), 'errors' => array('The role schema is not available. Import the product-team migration, then reload.'), 'read_only' => true));
        return;
    }
    $form = admin_role_form_values($role);
    if ($method === 'POST') {
        if (!admin_verify_csrf()) {
            $errors[] = 'Your session expired. Reload this page and try again.';
        } else {
            list($form, $payloadErrors) = admin_role_payload($_POST, $id, $permissions);
            $errors = array_merge($errors, $payloadErrors);
            if (!empty($form['is_system']) && empty($user['is_owner'])) {
                $errors[] = 'Only an owner can edit a system role.';
            }
            if (empty($user['is_owner']) && in_array('roles.manage', (array) ($form['permissions'] ?? array()), true)) {
                $errors[] = 'Only an owner can grant role-management access.';
            }
            if (!$errors) {
                try {
                    $savedId = $repository->saveRole($form);
                    $repository->audit((int) $user['id'], $id > 0 ? 'update' : 'create', 'admin_role', $savedId, admin_ip_hash(), array('permission_count' => count((array) ($form['permissions'] ?? array()))));
                    admin_set_flash('success', $id > 0 ? 'Role updated successfully.' : 'Role created successfully.');
                    redirect_to(admin_url('/roles'), 303);
                } catch (Throwable $exception) {
                    error_log('Admin role save failed: ' . get_class($exception));
                    $errors[] = 'The role could not be saved. Confirm that its slug is unique.';
                }
            }
        }
    } elseif ($method !== 'GET' && $method !== 'HEAD') {
        admin_method_not_allowed('GET, HEAD, POST');
    }
    admin_render('role-form', array('page_title' => $id > 0 ? 'Edit role' : 'New role', 'admin_repository' => $repository, 'admin_user' => $user, 'permissions' => $permissions, 'form' => $form, 'errors' => $errors, 'read_only' => false));
}

function admin_delete_role(AdminRepository $repository, array $config, string $method, array $user): void
{
    if ($method !== 'POST') {
        admin_method_not_allowed('POST');
    }
    if (!admin_verify_csrf()) {
        admin_set_flash('error', 'Your session expired. Reload the page and try again.');
        redirect_to(admin_url('/roles'), 303);
    }
    $id = positive_int($_POST['id'] ?? 0);
    try {
        if (!$repository->deleteRole($id)) {
            throw new RuntimeException('role_delete_blocked');
        }
        $repository->audit((int) $user['id'], 'delete', 'admin_role', $id, admin_ip_hash());
        admin_set_flash('success', 'Custom role deleted.');
    } catch (Throwable $exception) {
        error_log('Admin role delete failed: ' . get_class($exception));
        admin_set_flash('error', 'This role is protected, system-owned or still assigned to a team member.');
    }
    redirect_to(admin_url('/roles'), 303);
}

function admin_settings(AdminRepository $repository, array $config, string $method, array $user): void
{
    $keys = admin_setting_keys();
    if (!$repository->isReady()) {
        admin_render('settings', array('page_title' => 'Site settings', 'admin_repository' => $repository, 'settings' => array(), 'errors' => array($repository->statusMessage()), 'admin_error' => $repository->statusMessage(), 'setting_keys' => $keys));
        return;
    }
    $settings = $repository->getMeta();
    $form = array();
    foreach ($keys as $key => $definition) {
        $form[$key] = isset($settings[$key]) ? $settings[$key] : $definition['fallback'];
    }
    $errors = array();
    if ($method === 'POST') {
        if (!admin_verify_csrf()) {
            $errors[] = 'Your session expired. Reload this page and try again.';
        } else {
            foreach ($keys as $key => $definition) {
                $form[$key] = clean_form_value($_POST['settings'][$key] ?? '', (int) $definition['max']);
            }
            if (!filter_var($form['site.email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Enter a valid site email address.';
            }
            if ($form['site.instagram'] !== '' && (!filter_var($form['site.instagram'], FILTER_VALIDATE_URL) || !preg_match('#^https://#i', $form['site.instagram']))) {
                $errors[] = 'Instagram must be an HTTPS URL.';
            }
            if (!$errors) {
                try {
                    $repository->saveMeta($form);
                    $repository->audit((int) $user['id'], 'update', 'settings', null, admin_ip_hash());
                    admin_set_flash('success', 'Site settings saved.');
                    redirect_to(admin_url('/settings'), 303);
                } catch (Throwable $exception) {
                    error_log('Admin settings save failed: ' . get_class($exception));
                    $errors[] = 'Settings could not be saved.';
                }
            }
        }
    } elseif ($method !== 'GET' && $method !== 'HEAD') {
        admin_method_not_allowed('GET, HEAD, POST');
    }
    admin_render('settings', array('page_title' => 'Site settings', 'admin_repository' => $repository, 'settings' => $form, 'errors' => $errors, 'admin_error' => '', 'setting_keys' => $keys));
}

function admin_setting_keys(): array
{
    return array(
        'site.name' => array('label' => 'Site name', 'group' => 'Site identity', 'type' => 'text', 'max' => 120, 'fallback' => 'Raspina Clothing'),
        'site.email' => array('label' => 'Contact email', 'group' => 'Site identity', 'type' => 'email', 'max' => 190, 'fallback' => 'info@raspinaclothing.com'),
        'site.phone_display' => array('label' => 'Studio phone', 'group' => 'Site identity', 'type' => 'text', 'max' => 60, 'fallback' => '+98 21 8880 0890'),
        'site.phone_link' => array('label' => 'Studio phone link', 'group' => 'Site identity', 'type' => 'text', 'max' => 60, 'fallback' => '+982188800890'),
        'site.order_mobile_display' => array('label' => 'Order mobile display', 'group' => 'Site identity', 'type' => 'text', 'max' => 60, 'fallback' => '+98 999 301 9987'),
        'site.order_mobile_link' => array('label' => 'Order mobile link', 'group' => 'Site identity', 'type' => 'text', 'max' => 60, 'fallback' => '+989993019987'),
        'site.instagram' => array('label' => 'Instagram URL', 'group' => 'Site identity', 'type' => 'url', 'max' => 255, 'fallback' => 'https://www.instagram.com/raspina.clothing/'),
        'site.address' => array('label' => 'Studio address', 'group' => 'Site identity', 'type' => 'textarea', 'max' => 500, 'fallback' => 'No. 22, Ground Floor, Shahamati Alley, Valiasr Sq., Tehran, Iran'),
        'home.announcement' => array('label' => 'Announcement strip', 'group' => 'Homepage', 'type' => 'text', 'max' => 180, 'fallback' => "International women's clothing wholesale"),
        'home.hero_kicker' => array('label' => 'Hero kicker', 'group' => 'Homepage', 'type' => 'text', 'max' => 160, 'fallback' => "Wholesale Women's Fashion"),
        'home.hero_title' => array('label' => 'Hero title', 'group' => 'Homepage', 'type' => 'textarea', 'max' => 220, 'fallback' => 'Designed for boutiques that move with style.'),
        'home.hero_description' => array('label' => 'Hero description', 'group' => 'Homepage', 'type' => 'textarea', 'max' => 600, 'fallback' => "Explore Raspina's real product collections through an immersive motion gallery. Premium women's apparel, refined silhouettes, and wholesale-ready pieces crafted for modern fashion retailers."),
        'home.hero_footer' => array('label' => 'Hero footer hint', 'group' => 'Homepage', 'type' => 'text', 'max' => 180, 'fallback' => 'Move pointer or scroll to explore real Raspina products'),
        'home.arrivals_kicker' => array('label' => 'Arrivals kicker', 'group' => 'Homepage', 'type' => 'text', 'max' => 160, 'fallback' => 'New in the archive'),
        'home.arrivals_title' => array('label' => 'Arrivals title', 'group' => 'Homepage', 'type' => 'text', 'max' => 180, 'fallback' => 'Recent arrivals'),
        'home.arrivals_link' => array('label' => 'Arrivals link label', 'group' => 'Homepage', 'type' => 'text', 'max' => 120, 'fallback' => 'View all pieces'),
        'home.collections_kicker' => array('label' => 'Collections kicker', 'group' => 'Homepage', 'type' => 'text', 'max' => 160, 'fallback' => 'Index'),
        'home.collections_title' => array('label' => 'Collections title', 'group' => 'Homepage', 'type' => 'textarea', 'max' => 220, 'fallback' => "A wardrobe,\nedited by chapter."),
        'home.collections_description' => array('label' => 'Collections description', 'group' => 'Homepage', 'type' => 'textarea', 'max' => 500, 'fallback' => 'From fluid blouses to considered tailoring, each chapter is designed to work as a coherent boutique selection.'),
        'home.wholesale_kicker' => array('label' => 'Wholesale band kicker', 'group' => 'Homepage', 'type' => 'text', 'max' => 160, 'fallback' => 'Built for wholesale'),
        'home.wholesale_title' => array('label' => 'Wholesale band title', 'group' => 'Homepage', 'type' => 'textarea', 'max' => 220, 'fallback' => "From our studio\nto your store."),
        'home.wholesale_button' => array('label' => 'Wholesale band button', 'group' => 'Homepage', 'type' => 'text', 'max' => 140, 'fallback' => 'How wholesale works'),
        'home.catalog_kicker' => array('label' => 'Catalog kicker', 'group' => 'Homepage', 'type' => 'text', 'max' => 160, 'fallback' => 'The printed edit'),
        'home.catalog_title' => array('label' => 'Catalog title', 'group' => 'Homepage', 'type' => 'textarea', 'max' => 180, 'fallback' => "Catalog\nNo. 23"),
        'home.catalog_label' => array('label' => 'Catalog archive label', 'group' => 'Homepage', 'type' => 'text', 'max' => 120, 'fallback' => 'Archive / 2023'),
        'home.catalog_description' => array('label' => 'Catalog description', 'group' => 'Homepage', 'type' => 'textarea', 'max' => 500, 'fallback' => 'A tactile overview of silhouettes, styling and collection stories from the Raspina archive.'),
        'home.catalog_button' => array('label' => 'Catalog button', 'group' => 'Homepage', 'type' => 'text', 'max' => 120, 'fallback' => 'Download PDF'),
        'home.close_kicker' => array('label' => 'Closing kicker', 'group' => 'Homepage', 'type' => 'text', 'max' => 160, 'fallback' => 'Keep in touch'),
        'home.close_title' => array('label' => 'Closing title', 'group' => 'Homepage', 'type' => 'textarea', 'max' => 220, 'fallback' => "For appointments, distribution\nand collection enquiries."),
        'footer.cta_kicker' => array('label' => 'Footer CTA kicker', 'group' => 'Footer', 'type' => 'text', 'max' => 160, 'fallback' => 'Your next edit starts here'),
        'footer.cta_title' => array('label' => 'Footer CTA title', 'group' => 'Footer', 'type' => 'textarea', 'max' => 220, 'fallback' => "Let's shape your next collection."),
        'footer.cta_description' => array('label' => 'Footer CTA description', 'group' => 'Footer', 'type' => 'textarea', 'max' => 500, 'fallback' => "Tell us what your market needs. We'll help you build a focused Raspina selection for your boutique, distribution network or retail concept."),
        'footer.provenance' => array('label' => 'Footer provenance', 'group' => 'Footer', 'type' => 'textarea', 'max' => 500, 'fallback' => "Women's fashion shaped in Tehran and prepared for independent boutiques and international wholesale partners."),
        'page.about_intro' => array('label' => 'About introduction', 'group' => 'Page copy', 'type' => 'textarea', 'max' => 600, 'fallback' => 'Raspina is a women’s clothing studio and wholesale partner built around considered silhouettes, adaptable production and long-term relationships.'),
        'page.contact_intro' => array('label' => 'Contact introduction', 'group' => 'Page copy', 'type' => 'textarea', 'max' => 600, 'fallback' => 'For wholesale, distribution, appointments or collection questions, send a note to the studio.'),
        'page.wholesale_intro' => array('label' => 'Wholesale introduction', 'group' => 'Page copy', 'type' => 'textarea', 'max' => 600, 'fallback' => 'A direct enquiry process for boutique owners, distributors and apparel buyers.'),
    );
}

function admin_role_form_values(?array $role): array
{
    return $role ? array('id' => (int) $role['id'], 'slug' => (string) $role['slug'], 'title' => (string) $role['title'], 'description' => (string) ($role['description'] ?? ''), 'is_system' => !empty($role['is_system']) ? 1 : 0, 'is_active' => !empty($role['is_active']) ? 1 : 0, 'permissions' => (array) ($role['permissions'] ?? array())) : array('id' => 0, 'slug' => '', 'title' => '', 'description' => '', 'is_system' => 0, 'is_active' => 1, 'permissions' => array());
}

function admin_role_payload(array $source, int $id, array $permissions): array
{
    $allowed = array_column($permissions, 'code');
    $selected = array_values(array_intersect($allowed, array_map('strval', (array) ($source['permissions'] ?? array()))));
    $title = clean_form_value($source['title'] ?? '', 120);
    $slug = safe_slug($source['slug'] ?? '');
    $errors = array();
    if ($title === '') { $errors[] = 'Role title is required.'; }
    if ($slug === '') { $errors[] = 'Enter a URL-safe role slug.'; }
    return array(array('id' => $id, 'slug' => $slug, 'title' => $title, 'description' => clean_form_value($source['description'] ?? '', 1000), 'is_active' => !empty($source['is_active']) ? 1 : 0, 'permissions' => $selected), $errors);
}

function admin_user_form_values(?array $user): array
{
    return $user ? array('id' => (int) $user['id'], 'name' => (string) $user['name'], 'email' => (string) $user['email'], 'title' => (string) ($user['title'] ?? ''), 'role_id' => (int) ($user['role_id'] ?? 0), 'is_active' => !empty($user['is_active']) ? 1 : 0, 'overrides' => (array) ($user['overrides'] ?? array())) : array('id' => 0, 'name' => '', 'email' => '', 'title' => '', 'role_id' => 0, 'is_active' => 1, 'overrides' => array());
}

function admin_user_payload(array $source, int $id, array $permissions): array
{
    $allowed = array_column($permissions, 'code');
    $overrides = array();
    foreach ((array) ($source['overrides'] ?? array()) as $code => $effect) { if (in_array((string) $code, $allowed, true) && in_array($effect, array('allow', 'deny'), true)) { $overrides[(string) $code] = $effect; } }
    $email = strtolower(clean_form_value($source['email'] ?? '', 190));
    $name = clean_form_value($source['name'] ?? '', 120);
    $errors = array();
    if ($name === '') { $errors[] = 'Name is required.'; }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'Enter a valid email address.'; }
    return array(array('id' => $id, 'name' => $name, 'email' => $email, 'title' => clean_form_value($source['title'] ?? '', 120), 'role_id' => positive_int($source['role_id'] ?? 0), 'is_active' => !empty($source['is_active']) ? 1 : 0, 'overrides' => $overrides), $errors);
}

function admin_product_payload(array $source, int $id): array
{
    $name = clean_form_value($source['name'] ?? '', 255);
    $slug = safe_slug($source['slug'] ?? '');
    if ($slug === '') {
        $slug = admin_slugify($name);
    }
    $errors = array();
    if ($name === '') {
        $errors[] = 'Product name is required.';
    }
    if ($slug === '') {
        $errors[] = 'Enter a URL-safe slug using lowercase letters, numbers and hyphens.';
    }
    $stockStatus = (string) ($source['stock_status'] ?? 'outofstock');
    if (!in_array($stockStatus, array('instock', 'onbackorder', 'outofstock'), true)) {
        $stockStatus = 'outofstock';
    }
    $currency = strtoupper(clean_form_value($source['currency'] ?? 'USD', 3));
    if (!preg_match('/^[A-Z]{3}$/', $currency)) {
        $errors[] = 'Currency must be a three-letter code.';
        $currency = 'USD';
    }
    $imageRecords = array();
    $removeImages = array();
    foreach ((array) ($source['remove_images'] ?? array()) as $path) {
        $cleanPath = admin_image_path((string) $path);
        if ($cleanPath !== '') {
            $removeImages[$cleanPath] = true;
        }
    }
    $altMap = array();
    foreach ((array) ($source['image_alt'] ?? array()) as $encodedPath => $alt) {
        $decodedPath = admin_image_path(rawurldecode((string) $encodedPath));
        if ($decodedPath !== '') {
            $altMap[$decodedPath] = clean_form_value($alt, 255);
        }
    }
    foreach ((array) ($source['keep_images'] ?? array()) as $path) {
        $cleanPath = admin_image_path((string) $path);
        if ($cleanPath !== '' && empty($removeImages[$cleanPath])) {
            $imageRecords[] = array('path' => $cleanPath, 'alt_text' => $altMap[$cleanPath] ?? '');
        }
    }
    foreach (preg_split('/\r\n|\r|\n/', (string) ($source['images'] ?? '')) as $path) {
        $path = trim($path);
        if ($path === '') {
            continue;
        }
        $cleanPath = admin_image_path($path);
        if ($cleanPath === '') {
            $errors[] = 'Every manually entered image path must be a local path beginning with /assets/.';
        } elseif (!isset($removeImages[$cleanPath])) {
            $imageRecords[] = array('path' => $cleanPath, 'alt_text' => $altMap[$cleanPath] ?? '');
        }
    }
    $attributes = array();
    foreach (preg_split('/\r\n|\r|\n/', (string) ($source['attributes'] ?? '')) as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = explode(':', $line, 2);
        if (count($parts) !== 2 || trim($parts[0]) === '' || trim($parts[1]) === '') {
            $errors[] = 'Attributes must use one “Name: Value” pair per line.';
            continue;
        }
        $attributes[] = array('name' => clean_form_value($parts[0], 191), 'value' => clean_form_value($parts[1], 500));
    }
    $tags = array();
    foreach (preg_split('/[,\r\n]+/', (string) ($source['tags'] ?? '')) as $tag) {
        $tag = clean_form_value($tag, 191);
        if ($tag !== '') {
            $tags[] = $tag;
        }
    }
    $categoryIds = array();
    foreach ((array) ($source['categories'] ?? array()) as $categoryId) {
        $categoryId = positive_int($categoryId);
        if ($categoryId > 0) {
            $categoryIds[] = $categoryId;
        }
    }
    $apparel = array(
        'brand' => array('max' => 120), 'garment_type' => array('max' => 120), 'collection_name' => array('max' => 160), 'season' => array('max' => 80),
        'fabric_composition' => array('max' => 255), 'fabric_weight' => array('max' => 80), 'color' => array('max' => 120), 'color_family' => array('max' => 100),
        'pattern' => array('max' => 120), 'fit' => array('max' => 100), 'silhouette' => array('max' => 120), 'neckline' => array('max' => 100),
        'sleeve_length' => array('max' => 100), 'garment_length' => array('max' => 100), 'closure' => array('max' => 100), 'lining' => array('max' => 100),
        'stretch' => array('max' => 80), 'care_instructions' => array('max' => 2000), 'origin_country' => array('max' => 100), 'size_range' => array('max' => 160),
        'lead_time' => array('max' => 120), 'wholesale_notes' => array('max' => 3000), 'availability_note' => array('max' => 255),
    );
    $product = array(
        'id' => $id,
        'name' => $name,
        'slug' => $slug,
        'sku' => clean_form_value($source['sku'] ?? '', 96),
        'summary' => clean_form_value($source['summary'] ?? '', 5000),
        'description' => clean_form_value($source['description'] ?? '', 50000),
        'product_type' => preg_match('/^[a-z0-9_-]{1,50}$/i', (string) ($source['product_type'] ?? 'simple')) ? strtolower((string) $source['product_type']) : 'simple',
        'reference_price' => admin_decimal($source['reference_price'] ?? ''),
        'regular_price' => admin_decimal($source['regular_price'] ?? ''),
        'sale_price' => admin_decimal($source['sale_price'] ?? ''),
        'currency' => $currency,
        'stock_status' => $stockStatus,
        'stock_quantity' => admin_integer_or_null($source['stock_quantity'] ?? ''),
        'featured' => !empty($source['featured']) ? 1 : 0,
        'seo_title' => clean_form_value($source['seo_title'] ?? '', 255),
        'seo_description' => clean_form_value($source['seo_description'] ?? '', 500),
        'brand' => clean_form_value($source['brand'] ?? 'Raspina', 120),
        'garment_type' => clean_form_value($source['garment_type'] ?? '', 120),
        'collection_name' => clean_form_value($source['collection_name'] ?? '', 160),
        'season' => clean_form_value($source['season'] ?? '', 80),
        'fabric_composition' => clean_form_value($source['fabric_composition'] ?? '', 255),
        'fabric_weight' => clean_form_value($source['fabric_weight'] ?? '', 80),
        'color' => clean_form_value($source['color'] ?? '', 120),
        'color_family' => clean_form_value($source['color_family'] ?? '', 100),
        'pattern' => clean_form_value($source['pattern'] ?? '', 120),
        'fit' => clean_form_value($source['fit'] ?? '', 100),
        'silhouette' => clean_form_value($source['silhouette'] ?? '', 120),
        'neckline' => clean_form_value($source['neckline'] ?? '', 100),
        'sleeve_length' => clean_form_value($source['sleeve_length'] ?? '', 100),
        'garment_length' => clean_form_value($source['garment_length'] ?? '', 100),
        'closure' => clean_form_value($source['closure'] ?? '', 100),
        'lining' => clean_form_value($source['lining'] ?? '', 100),
        'stretch' => clean_form_value($source['stretch'] ?? '', 80),
        'care_instructions' => clean_form_value($source['care_instructions'] ?? '', 2000),
        'origin_country' => clean_form_value($source['origin_country'] ?? '', 100),
        'size_range' => clean_form_value($source['size_range'] ?? '', 160),
        'customizable_size' => !empty($source['customizable_size']) ? 1 : 0,
        'customizable_fabric' => !empty($source['customizable_fabric']) ? 1 : 0,
        'minimum_order_quantity' => admin_integer_or_null($source['minimum_order_quantity'] ?? ''),
        'lead_time' => clean_form_value($source['lead_time'] ?? '', 120),
        'wholesale_notes' => clean_form_value($source['wholesale_notes'] ?? '', 3000),
        'publish_status' => in_array((string) ($source['publish_status'] ?? 'published'), array('draft', 'published', 'archived'), true) ? (string) $source['publish_status'] : 'draft',
        'available_from' => admin_date_or_null($source['available_from'] ?? ''),
        'availability_note' => clean_form_value($source['availability_note'] ?? '', 255),
        'category_ids' => array_values(array_unique($categoryIds)),
        'image_records' => array_values(array_reduce($imageRecords, function (array $carry, array $image): array { $key = (string) ($image['path'] ?? ''); if ($key !== '' && !isset($carry[$key])) { $carry[$key] = $image; } return $carry; }, array())),
        'attributes' => $attributes,
        'tags' => array_values(array_unique($tags)),
        'variants' => admin_variant_payload($source['variants'] ?? array()),
        'primary_image_path' => admin_image_path((string) ($source['primary_image_path'] ?? '')),
    );
    $product['images'] = array_map(function (array $image): string { return (string) $image['path']; }, $product['image_records']);
    if ($product['primary_image_path'] === '' && !empty($product['images'])) {
        $product['primary_image_path'] = (string) $product['images'][0];
    }
    if ($product['available_from'] === false) {
        $product['available_from'] = null;
        $errors[] = 'Available-from must use the YYYY-MM-DD format.';
    }
    if ($product['description'] === '') {
        $product['description'] = $product['summary'];
    }
    return array($product, $errors);
}

function admin_category_payload(array $source, int $id): array
{
    $name = clean_form_value($source['name'] ?? '', 191);
    $slug = safe_slug($source['slug'] ?? '');
    if ($slug === '') {
        $slug = admin_slugify($name);
    }
    $parent = safe_slug($source['parent_slug'] ?? '');
    $errors = array();
    if ($name === '') {
        $errors[] = 'Category name is required.';
    }
    if ($slug === '') {
        $errors[] = 'Enter a URL-safe slug using lowercase letters, numbers and hyphens.';
    }
    return array(array('id' => $id, 'name' => $name, 'slug' => $slug, 'description' => clean_form_value($source['description'] ?? '', 5000), 'parent_slug' => $parent), $errors);
}

function admin_product_form_values(?array $product): array
{
    if (!$product) {
        return array('id' => 0, 'name' => '', 'slug' => '', 'sku' => '', 'summary' => '', 'description' => '', 'product_type' => 'simple', 'reference_price' => '', 'regular_price' => '', 'sale_price' => '', 'currency' => 'USD', 'stock_status' => 'outofstock', 'stock_quantity' => '', 'featured' => 0, 'seo_title' => '', 'seo_description' => '', 'brand' => 'Raspina', 'garment_type' => '', 'collection_name' => '', 'season' => '', 'fabric_composition' => '', 'fabric_weight' => '', 'color' => '', 'color_family' => '', 'pattern' => '', 'fit' => '', 'silhouette' => '', 'neckline' => '', 'sleeve_length' => '', 'garment_length' => '', 'closure' => '', 'lining' => '', 'stretch' => '', 'care_instructions' => '', 'origin_country' => '', 'size_range' => '', 'customizable_size' => 0, 'customizable_fabric' => 0, 'minimum_order_quantity' => '', 'lead_time' => '', 'wholesale_notes' => '', 'publish_status' => 'draft', 'available_from' => '', 'availability_note' => '', 'category_ids' => array(), 'images' => array(), 'image_records' => array(), 'attributes' => array(), 'tags' => array(), 'variants' => array(), 'primary_image_path' => '');
    }
    $attributeLines = array_map(function (array $attribute): string { return $attribute['attribute_name'] . ': ' . $attribute['attribute_value']; }, (array) ($product['attributes'] ?? array()));
    $imageRecords = array();
    foreach ((array) ($product['images'] ?? array()) as $image) {
        $imageRecords[] = is_array($image) ? $image : array('path' => (string) $image);
    }
    $values = array('id' => (int) $product['id'], 'name' => (string) $product['name'], 'slug' => (string) $product['slug'], 'sku' => (string) $product['sku'], 'summary' => (string) $product['summary'], 'description' => (string) $product['description'], 'product_type' => (string) $product['product_type'], 'reference_price' => (string) ($product['reference_price'] ?? ''), 'regular_price' => (string) ($product['regular_price'] ?? ''), 'sale_price' => (string) ($product['sale_price'] ?? ''), 'currency' => (string) $product['currency'], 'stock_status' => (string) $product['stock_status'], 'stock_quantity' => (string) ($product['stock_quantity'] ?? ''), 'featured' => !empty($product['featured']) ? 1 : 0, 'seo_title' => (string) $product['seo_title'], 'seo_description' => (string) $product['seo_description'], 'category_ids' => (array) ($product['category_ids'] ?? array()), 'images' => array_map(function (array $image): string { return (string) ($image['path'] ?? ''); }, $imageRecords), 'image_records' => $imageRecords, 'attributes' => $attributeLines, 'tags' => (array) ($product['tags'] ?? array()), 'variants' => (array) ($product['variants'] ?? array()), 'primary_image_path' => '');
    foreach (array('brand', 'garment_type', 'collection_name', 'season', 'fabric_composition', 'fabric_weight', 'color', 'color_family', 'pattern', 'fit', 'silhouette', 'neckline', 'sleeve_length', 'garment_length', 'closure', 'lining', 'stretch', 'care_instructions', 'origin_country', 'size_range', 'lead_time', 'wholesale_notes', 'availability_note', 'publish_status', 'available_from') as $field) {
        $values[$field] = (string) ($product[$field] ?? $values[$field] ?? '');
    }
    foreach (array('customizable_size', 'customizable_fabric') as $field) {
        $values[$field] = !empty($product[$field]) ? 1 : 0;
    }
    $values['minimum_order_quantity'] = (string) ($product['minimum_order_quantity'] ?? '');
    foreach ($imageRecords as $image) {
        if (!empty($image['is_primary'])) {
            $values['primary_image_path'] = (string) ($image['path'] ?? '');
            break;
        }
    }
    if ($values['primary_image_path'] === '' && !empty($values['images'])) {
        $values['primary_image_path'] = (string) $values['images'][0];
    }
    return $values;
}

function admin_slugify(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?: '';
    return safe_slug(trim($value, '-'));
}

function admin_decimal($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    return preg_match('/^\d{1,14}(?:\.\d{1,4})?$/', $value) ? $value : null;
}

function admin_integer_or_null($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    return preg_match('/^\d{1,9}$/', $value) ? (int) $value : null;
}

function admin_date_or_null($value)
{
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }
    $date = DateTime::createFromFormat('!Y-m-d', $value);
    return $date && $date->format('Y-m-d') === $value ? $value : false;
}

function admin_variant_payload($source): array
{
    $variants = array();
    foreach ((array) $source as $variant) {
        if (!is_array($variant)) {
            continue;
        }
        $stockStatus = in_array((string) ($variant['stock_status'] ?? ''), array('instock', 'onbackorder', 'outofstock'), true) ? (string) $variant['stock_status'] : 'outofstock';
        $quantity = admin_integer_or_null($variant['stock_quantity'] ?? '');
        $price = admin_decimal($variant['price_override'] ?? '');
        $record = array('variant_sku' => clean_form_value($variant['variant_sku'] ?? '', 96), 'size' => clean_form_value($variant['size'] ?? '', 80), 'color' => clean_form_value($variant['color'] ?? '', 120), 'stock_status' => $stockStatus, 'stock_quantity' => $quantity === null ? '' : $quantity, 'price_override' => $price === null ? '' : $price, 'is_active' => !isset($variant['is_active']) || !empty($variant['is_active']) ? 1 : 0);
        if ($record['variant_sku'] !== '' || $record['size'] !== '' || $record['color'] !== '') {
            $variants[] = $record;
        }
    }
    return $variants;
}

function admin_image_path(string $path): string
{
    $path = rawurldecode(trim(str_replace('\\', '/', $path)));
    if ($path === '' || strpos($path, '..') !== false || preg_match('#^(?:https?:)?//#i', $path)) {
        return '';
    }
    if (strpos($path, '/assets/') !== 0) {
        $path = '/' . ltrim($path, '/');
    }
    return preg_match('#^/assets/[a-zA-Z0-9_./-]+$#', $path) ? $path : '';
}

function admin_require_permission(AdminRepository $repository, array $user, string $permission): void
{
    if ($repository->hasPermission((int) ($user['id'] ?? 0), $permission)) {
        return;
    }
    http_response_code(403);
    admin_render('forbidden', array('page_title' => 'Access restricted', 'admin_repository' => $repository, 'admin_user' => $user, 'required_permission' => $permission));
    exit;
}

function admin_process_product_uploads(array $files, int $uploadedBy, string $altText): array
{
    $records = array();
    $errors = array();
    $maxBytes = 8 * 1024 * 1024;
    $maxFiles = 12;
    $names = isset($files['name']) && is_array($files['name']) ? $files['name'] : array($files['name'] ?? '');
    $tmpNames = isset($files['tmp_name']) && is_array($files['tmp_name']) ? $files['tmp_name'] : array($files['tmp_name'] ?? '');
    $sizes = isset($files['size']) && is_array($files['size']) ? $files['size'] : array($files['size'] ?? 0);
    $uploadErrors = isset($files['error']) && is_array($files['error']) ? $files['error'] : array($files['error'] ?? UPLOAD_ERR_NO_FILE);
    $count = min(count($names), $maxFiles);
    if (count($names) > $maxFiles) {
        $errors[] = 'You can upload up to 12 product images at a time.';
    }
    $finfo = function_exists('finfo_open') ? finfo_open(FILEINFO_MIME_TYPE) : false;
    $allowed = array('image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp');
    $destination = APP_ROOT . '/assets/uploads/products';
    if (!is_dir($destination) && !@mkdir($destination, 0750, true) && !is_dir($destination)) {
        return array(array(), array('The secure product upload directory could not be created.'));
    }
    for ($index = 0; $index < $count; $index++) {
        $error = (int) ($uploadErrors[$index] ?? UPLOAD_ERR_NO_FILE);
        if ($error === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        if ($error !== UPLOAD_ERR_OK) {
            $errors[] = 'One product image could not be uploaded.';
            continue;
        }
        $tmp = (string) ($tmpNames[$index] ?? '');
        $size = (int) ($sizes[$index] ?? 0);
        if ($tmp === '' || !is_uploaded_file($tmp) || $size < 1 || $size > $maxBytes) {
            $errors[] = 'Images must be valid uploads no larger than 8 MB.';
            continue;
        }
        $mime = $finfo ? (string) finfo_file($finfo, $tmp) : '';
        $dimensions = @getimagesize($tmp);
        if (!isset($allowed[$mime]) || !is_array($dimensions) || (int) ($dimensions[0] ?? 0) < 120 || (int) ($dimensions[1] ?? 0) < 120 || (int) ($dimensions[0] ?? 0) > 10000 || (int) ($dimensions[1] ?? 0) > 10000) {
            $errors[] = 'Only valid JPEG, PNG or WebP images between 120 and 10000 pixels are accepted.';
            continue;
        }
        try {
            $storedName = bin2hex(random_bytes(18)) . '.' . $allowed[$mime];
        } catch (Throwable $exception) {
            $errors[] = 'The server could not create a safe image name.';
            continue;
        }
        $absolute = $destination . '/' . $storedName;
        if (!@move_uploaded_file($tmp, $absolute)) {
            $errors[] = 'The server could not store one product image.';
            continue;
        }
        $records[] = array('path' => '/assets/uploads/products/' . $storedName, 'original_name' => clean_form_value($names[$index] ?? '', 255), 'stored_name' => $storedName, 'mime_type' => $mime, 'file_size' => $size, 'width' => (int) $dimensions[0], 'height' => (int) $dimensions[1], 'alt_text' => $altText, 'is_primary' => empty($records) ? 1 : 0, 'uploaded_by' => $uploadedBy);
    }
    if (is_resource($finfo)) {
        finfo_close($finfo);
    }
    return array($records, $errors);
}

function admin_authenticated_user(AdminRepository $repository): ?array
{
    if (empty($_SESSION['admin_user']['id']) || !$repository->isReady()) {
        return null;
    }
    $user = $repository->findAdminById((int) $_SESSION['admin_user']['id']);
    if (!$user || empty($user['is_active'])) {
        unset($_SESSION['admin_user'], $_SESSION['admin_last_seen']);
        return null;
    }
    return $user;
}

function admin_verify_csrf(): bool
{
    $token = (string) ($_POST['csrf'] ?? '');
    return $token !== '' && hash_equals(csrf_token(), $token);
}

function admin_url(string $path = '/'): string
{
    return url('/admin' . ($path === '/' ? '' : '/' . ltrim($path, '/')));
}

function admin_safe_next($value): string
{
    $value = (string) $value;
    return ($value === '/admin' || strpos($value, '/admin/') === 0) && strpos($value, '//') === false ? $value : '/admin';
}

function admin_set_flash(string $type, string $message): void
{
    $_SESSION['admin_flash'] = array('type' => $type === 'success' ? 'success' : 'error', 'message' => $message);
}

function admin_consume_flash(): ?array
{
    $flash = isset($_SESSION['admin_flash']) && is_array($_SESSION['admin_flash']) ? $_SESSION['admin_flash'] : null;
    unset($_SESSION['admin_flash']);
    return $flash;
}

function admin_ip_hash(): string
{
    return hash('sha256', client_ip());
}

function admin_render(string $view, array $data = array()): void
{
    global $config;
    $file = APP_DIR . '/templates/admin/' . basename($view) . '.php';
    if (!is_file($file)) {
        throw new RuntimeException('Admin template is unavailable.');
    }
    if (!isset($data['admin_user']) && isset($_SESSION['admin_user']) && is_array($_SESSION['admin_user'])) {
        $data['admin_user'] = $_SESSION['admin_user'];
    }
    $data['admin_flash'] = admin_consume_flash();
    $data['admin_css_version'] = (string) (@filemtime(APP_ROOT . '/assets/css/admin.css') ?: '20260803');
    $data['admin_js_version'] = (string) (@filemtime(APP_ROOT . '/assets/js/admin.js') ?: '20260803');
    extract($data, EXTR_SKIP);
    ob_start();
    require $file;
    $content = (string) ob_get_clean();
    require APP_DIR . '/templates/admin/layout.php';
}

function admin_method_not_allowed(string $allow): void
{
    http_response_code(405);
    header('Allow: ' . $allow);
    header('Content-Type: text/html; charset=UTF-8');
    echo '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Method not allowed</title><body style="font:18px Georgia,serif;padding:3rem;background:#f7f1e7;color:#17130f"><h1>Method not allowed</h1><p>Please return to the <a href="/admin">admin panel</a>.</p></body></html>';
    exit;
}
