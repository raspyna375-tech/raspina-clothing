<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return (string) $_SESSION['csrf_token'];
}

function set_form_flash(array $result): void
{
    $_SESSION['form_flash'] = $result;
}

function consume_form_flash(): ?array
{
    if (empty($_SESSION['form_flash']) || !is_array($_SESSION['form_flash'])) {
        return null;
    }
    $flash = $_SESSION['form_flash'];
    unset($_SESSION['form_flash']);
    return $flash;
}

function clean_form_value($value, int $max = 300): string
{
    $value = trim(strip_tags((string) $value));
    $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?: '';
    return string_limit($value, $max);
}

function client_ip(): string
{
    $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
}

function rate_limit_allowed(string $scope, array $config): bool
{
    $window = max(60, (int) ($config['security']['rate_limit_window'] ?? 600));
    $limit = max(1, (int) ($config['security']['rate_limit_count'] ?? 5));
    $key = hash('sha256', $scope . '|' . client_ip());
    $path = APP_ROOT . '/storage/rate-limit/' . $key . '.json';
    $now = time();
    $handle = @fopen($path, 'c+');
    if ($handle === false) {
        $sessionKey = 'rate_limit_' . $key;
        $events = isset($_SESSION[$sessionKey]) && is_array($_SESSION[$sessionKey]) ? $_SESSION[$sessionKey] : array();
        $events = array_values(array_filter($events, function ($stamp) use ($now, $window): bool {
            return is_int($stamp) && $stamp > ($now - $window);
        }));
        if (count($events) >= $limit) {
            $_SESSION[$sessionKey] = $events;
            return false;
        }
        $events[] = $now;
        $_SESSION[$sessionKey] = $events;
        return true;
    }
    $allowed = true;
    if (flock($handle, LOCK_EX)) {
        $raw = stream_get_contents($handle);
        $events = $raw ? json_decode($raw, true) : array();
        $events = is_array($events) ? array_values(array_filter($events, function ($stamp) use ($now, $window): bool {
            return is_int($stamp) && $stamp > ($now - $window);
        })) : array();
        if (count($events) >= $limit) {
            $allowed = false;
        } else {
            $events[] = $now;
            ftruncate($handle, 0);
            rewind($handle);
            fwrite($handle, (string) json_encode($events));
        }
        flock($handle, LOCK_UN);
    }
    fclose($handle);
    return $allowed;
}

function handle_public_form(string $type, CatalogRepository $catalog, array $config): array
{
    if (!empty($_POST['website'])) {
        return array('ok' => true, 'message' => 'Thank you. Your message has been received.', 'errors' => array());
    }

    $token = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
    if ($token === '' || !hash_equals(csrf_token(), $token)) {
        return array('ok' => false, 'message' => 'Your session expired. Please reload the page and try again.', 'errors' => array('csrf'));
    }

    if (!rate_limit_allowed($type, $config)) {
        return array('ok' => false, 'message' => 'Too many attempts. Please wait a few minutes and try again.', 'errors' => array('rate'));
    }

    $record = array(
        'type' => $type,
        'name' => clean_form_value($_POST['name'] ?? '', 100),
        'business_name' => clean_form_value($_POST['business_name'] ?? '', 120),
        'email' => clean_form_value($_POST['email'] ?? '', 190),
        'phone' => clean_form_value($_POST['phone'] ?? '', 60),
        'country' => clean_form_value($_POST['country'] ?? '', 100),
        'message' => clean_form_value($_POST['message'] ?? '', 3000),
        'products' => array(),
        'ip_hash' => hash('sha256', client_ip()),
        'created_at' => gmdate('Y-m-d H:i:s'),
    );

    $errors = array();
    if ($record['name'] === '') {
        $errors[] = 'name';
    }
    if (!filter_var($record['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'email';
    }
    if ($record['message'] === '' || strlen($record['message']) < 10) {
        $errors[] = 'message';
    }

    $slugs = explode(',', (string) ($_POST['product_slugs'] ?? ''));
    foreach (array_slice($slugs, 0, 30) as $slug) {
        $slug = safe_slug($slug);
        $product = $slug !== '' ? $catalog->productBySlug($slug) : null;
        if ($product !== null) {
            $record['products'][] = array('slug' => $product['slug'], 'name' => $product['name'], 'sku' => $product['sku']);
        }
    }

    if ($errors) {
        return array('ok' => false, 'message' => 'Please complete the required fields and use a valid email address.', 'errors' => $errors);
    }

    if (!$catalog->saveMessage($record)) {
        return array('ok' => false, 'message' => 'We could not save your message. Please contact us directly by email or WhatsApp.', 'errors' => array('storage'));
    }

    send_site_mail($record, $config);
    unset($_SESSION['csrf_token']);
    return array('ok' => true, 'message' => $type === 'wholesale' ? 'Your wholesale request has been received. We will contact you with the next steps.' : 'Thank you. Your message has been received.', 'errors' => array());
}

function send_site_mail(array $record, array $config): bool
{
    if (empty($config['mail']['enabled'])) {
        return false;
    }
    $recipient = filter_var($config['mail']['recipient'] ?? '', FILTER_VALIDATE_EMAIL);
    $from = filter_var($config['mail']['from'] ?? '', FILTER_VALIDATE_EMAIL);
    if (!$recipient || !$from) {
        return false;
    }
    $subject = '[Raspina website] ' . ($record['type'] === 'wholesale' ? 'Wholesale enquiry' : 'Contact message');
    $lines = array(
        'Name: ' . $record['name'],
        'Business: ' . $record['business_name'],
        'Email: ' . $record['email'],
        'Phone: ' . $record['phone'],
        'Country: ' . $record['country'],
        '',
        $record['message'],
    );
    if ($record['products']) {
        $lines[] = '';
        $lines[] = 'Shortlist:';
        foreach ($record['products'] as $product) {
            $lines[] = '- ' . $product['name'] . ' / SKU ' . $product['sku'];
        }
    }
    $headers = array(
        'From: Raspina Website <' . $from . '>',
        'Reply-To: ' . $record['email'],
        'Content-Type: text/plain; charset=UTF-8',
    );
    return @mail((string) $recipient, $subject, implode("\r\n", $lines), implode("\r\n", $headers));
}
