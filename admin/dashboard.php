<?php
require __DIR__ . '/includes/auth.php';
fv_require_login();
$user = fv_current_user();
$activeNav = 'dashboard';
$period = $_GET['period'] ?? '30d';
$allowed = ['today' => 'Hoje', '7d' => '7 dias', '30d' => '30 dias', '90d' => '90 dias', 'all' => 'Todo período'];
if (!isset($allowed[$period])) {
    $period = '30d';
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Estatísticas | Fernanda Vieira</title>
<link rel="icon" type="image/png" href="<?= htmlspecialchars(fv_site_url('images/favicon-32.png'), ENT_QUOTES, 'UTF-8') ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600&family=Lora:wght@400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= htmlspecialchars(fv_admin_url('assets/admin.css', true), ENT_QUOTES, 'UTF-8') ?>">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js" defer></script>
<script src="<?= htmlspecialchars(fv_admin_url('assets/admin.js', true), ENT_QUOTES, 'UTF-8') ?>" defer></script>
</head>
<body class="has-sidebar">
<?php require __DIR__ . '/includes/sidebar.php'; ?>

  <div class="admin-content">
    <main class="admin-main" id="dashboard" data-period="<?= htmlspecialchars($period, ENT_QUOTES, 'UTF-8') ?>" data-api-base="<?= htmlspecialchars(fv_admin_url('api/stats.php'), ENT_QUOTES, 'UTF-8') ?>">
      <div class="dash-head">
        <div>
          <p class="eyebrow">Estatísticas</p>
          <h1>Bem-vindo de volta, <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></h1>
        </div>
        <div class="period-switch" role="tablist" aria-label="Período">
          <?php foreach ($allowed as $key => $label): ?>
            <a class="period-btn <?= $key === $period ? 'is-active' : '' ?>" href="?period=<?= urlencode($key) ?>" data-period="<?= htmlspecialchars($key, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></a>
          <?php endforeach; ?>
        </div>
      </div>

      <div id="dash-status" class="dash-status">Carregando dados do Google Analytics…</div>

      <section class="kpi-grid" id="kpi-grid" hidden></section>

      <section class="charts-grid">
        <article class="panel">
          <h2>Horários de Pico</h2>
          <div class="chart-wrap"><canvas id="chart-hours"></canvas></div>
        </article>
        <article class="panel">
          <h2>Atividade por Dia da Semana</h2>
          <div class="chart-wrap"><canvas id="chart-weekdays"></canvas></div>
        </article>
        <article class="panel panel-wide">
          <h2>Visitantes ao Longo do Tempo</h2>
          <div class="chart-wrap tall"><canvas id="chart-timeline"></canvas></div>
        </article>
      </section>

      <section class="split-grid">
        <article class="panel">
          <h2>Páginas Mais Visitadas</h2>
          <div id="top-pages" class="list-stack"></div>
        </article>
        <article class="panel">
          <h2>Dispositivos</h2>
          <div class="chart-wrap short"><canvas id="chart-devices"></canvas></div>
          <div id="devices-list" class="list-stack compact"></div>
        </article>
      </section>

      <section class="triple-grid">
        <article class="panel">
          <h2>Navegadores</h2>
          <div id="browsers-list" class="list-stack compact"></div>
        </article>
        <article class="panel">
          <h2>Sistemas Operacionais</h2>
          <div id="os-list" class="list-stack compact"></div>
        </article>
        <article class="panel">
          <h2>Origem do Tráfego</h2>
          <div id="sources-list" class="list-stack compact"></div>
        </article>
      </section>

      <section class="split-grid">
        <article class="panel">
          <h2>Páginas de Entrada</h2>
          <div id="landings-list" class="list-stack compact"></div>
        </article>
        <article class="panel">
          <h2>Páginas Mais Acessadas (saída / rejeição)</h2>
          <div id="exits-list" class="list-stack compact"></div>
        </article>
      </section>

      <section class="split-grid">
        <article class="panel">
          <h2>Acessos por País</h2>
          <div id="countries-list" class="list-stack compact"></div>
        </article>
        <article class="panel">
          <h2>Acessos por Cidade</h2>
          <div id="cities-list" class="list-stack compact"></div>
        </article>
      </section>

      <p class="footnote" id="dash-notes"></p>
    </main>
  </div>
</body>
</html>
