<?php
$config = require __DIR__ . '/config.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name($config['session_name']);
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
    ]);
}

function fv_users_load(): array
{
    global $config;
    $path = $config['users_file'];
    if (!is_file($path)) {
        return ['users' => []];
    }
    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? $data : ['users' => []];
}

function fv_users_save(array $data): bool
{
    global $config;
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    return file_put_contents($config['users_file'], $json, LOCK_EX) !== false;
}

function fv_find_user(string $username): ?array
{
    $data = fv_users_load();
    foreach ($data['users'] as $user) {
        if (strcasecmp($user['username'], $username) === 0) {
            return $user;
        }
    }
    return null;
}

function fv_attempt_login(string $username, string $password): bool
{
    $user = fv_find_user($username);
    if (!$user || empty($user['password_hash'])) {
        return false;
    }
    if (!password_verify($password, $user['password_hash'])) {
        return false;
    }
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $user['id'],
        'name' => $user['name'],
        'username' => $user['username'],
        'role' => $user['role'] ?? 'admin',
    ];
    return true;
}

function fv_require_login(): void
{
    if (empty($_SESSION['user'])) {
        header('Location: ' . fv_admin_url('index.php'));
        exit;
    }
}

function fv_current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/** Base URL do painel, ex: /admin */
function fv_admin_base(): string
{
    global $config;
    $base = $config['admin_base'] ?? '/admin';
    return rtrim((string) $base, '/') ?: '/admin';
}

/** URL absoluta no painel, ex: /admin/assets/admin.css?v=... */
function fv_admin_url(string $path = '', bool $versioned = false): string
{
    global $config;
    $url = fv_admin_base() . '/' . ltrim($path, '/');
    if ($versioned) {
        $v = rawurlencode((string) ($config['asset_version'] ?? '1'));
        $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . $v;
    }
    return $url;
}

/** URL na raiz do site, ex: /images/logo.png */
function fv_site_url(string $path = ''): string
{
    return '/' . ltrim($path, '/');
}

function fv_logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function fv_change_password(string $username, string $current, string $new): array
{
    $data = fv_users_load();
    foreach ($data['users'] as &$user) {
        if (strcasecmp($user['username'], $username) !== 0) {
            continue;
        }
        if (!password_verify($current, $user['password_hash'])) {
            return ['ok' => false, 'error' => 'Senha atual incorreta.'];
        }
        if (strlen($new) < 8) {
            return ['ok' => false, 'error' => 'A nova senha deve ter pelo menos 8 caracteres.'];
        }
        $user['password_hash'] = password_hash($new, PASSWORD_DEFAULT);
        if (!fv_users_save($data)) {
            return ['ok' => false, 'error' => 'Não foi possível salvar a nova senha.'];
        }
        return ['ok' => true];
    }
    return ['ok' => false, 'error' => 'Usuário não encontrado.'];
}
