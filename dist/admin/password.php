<?php
require __DIR__ . '/includes/auth.php';
fv_require_login();
$user = fv_current_user();
$activeNav = 'password';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if ($new !== $confirm) {
        $error = 'A confirmação não confere com a nova senha.';
    } else {
        $result = fv_change_password($user['username'], $current, $new);
        if ($result['ok']) {
            $message = 'Senha alterada com sucesso.';
        } else {
            $error = $result['error'] ?? 'Não foi possível alterar a senha.';
        }
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Trocar senha | Painel</title>
<link rel="icon" type="image/png" href="<?= htmlspecialchars(fv_site_url('images/favicon-32.png'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Lora:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= htmlspecialchars(fv_admin_url('assets/admin.css', true), ENT_QUOTES, 'UTF-8') ?>">
<script src="<?= htmlspecialchars(fv_admin_url('assets/admin.js', true), ENT_QUOTES, 'UTF-8') ?>" defer></script>
</head>
<body class="has-sidebar">
<?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="admin-content">
    <main class="admin-main narrow">
      <p class="eyebrow">Conta</p>
      <h1>Trocar senha</h1>
      <p class="muted">Olá, <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>. Defina uma senha forte com pelo menos 8 caracteres.</p>

      <?php if ($message): ?><div class="alert alert-ok"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
      <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

      <form method="post" class="panel-form panel-card">
        <label>
          <span>Senha atual</span>
          <input type="password" name="current_password" required autocomplete="current-password">
        </label>
        <label>
          <span>Nova senha</span>
          <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
        </label>
        <label>
          <span>Confirmar nova senha</span>
          <input type="password" name="confirm_password" required minlength="8" autocomplete="new-password">
        </label>
        <button type="submit" class="btn btn-primary">Salvar nova senha</button>
      </form>
    </main>
  </div>
</body>
</html>
