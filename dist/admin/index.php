<?php
require __DIR__ . '/includes/auth.php';

if (fv_current_user()) {
    header('Location: ' . fv_admin_url('dashboard.php'));
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($username === '' || $password === '') {
        $error = 'Informe usuário e senha.';
    } elseif (fv_attempt_login($username, $password)) {
        header('Location: ' . fv_admin_url('dashboard.php'));
        exit;
    } else {
        $error = 'Usuário ou senha inválidos.';
        usleep(400000);
    }
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Acesso interno | Fernanda Vieira Psicologia</title>
<link rel="icon" type="image/png" href="<?= htmlspecialchars(fv_site_url('images/favicon-32.png'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Lora:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= htmlspecialchars(fv_admin_url('assets/admin.css', true), ENT_QUOTES, 'UTF-8') ?>">
</head>
<body class="login-page">
  <div class="login-shell">
    <div class="login-card">
      <img src="<?= htmlspecialchars(fv_site_url('images/logo-footer.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Fernanda Vieira Psicologia" class="login-logo">
      <p class="login-eyebrow">Área interna</p>
      <h1>Estatísticas do site</h1>
      <p class="login-lead">Acompanhe visitas, origem do tráfego e engajamento com a identidade do consultório.</p>

      <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
      <?php endif; ?>

      <form method="post" class="login-form" autocomplete="username">
        <label>
          <span>Usuário</span>
          <input type="text" name="username" required autofocus value="<?= htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </label>
        <label>
          <span>Senha</span>
          <input type="password" name="password" required autocomplete="current-password">
        </label>
        <button type="submit" class="btn btn-primary">Entrar</button>
      </form>
    </div>
  </div>
</body>
</html>
