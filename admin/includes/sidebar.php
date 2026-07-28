<?php
/**
 * Sidebar compartilhada do painel.
 * Variáveis esperadas: $user (array), $activeNav (string: dashboard|password)
 */
$activeNav = $activeNav ?? 'dashboard';
$userName = htmlspecialchars($user['name'] ?? 'Fernanda', ENT_QUOTES, 'UTF-8');
$userUser = htmlspecialchars($user['username'] ?? 'fernanda', ENT_QUOTES, 'UTF-8');
?>
<aside class="admin-sidebar" id="admin-sidebar">
  <div class="sidebar-brand">
    <img src="<?= htmlspecialchars(fv_site_url('images/logo-footer.png'), ENT_QUOTES, 'UTF-8') ?>" alt="Fernanda Vieira Psicologia">
    <p>Área interna</p>
  </div>

  <nav class="sidebar-nav" aria-label="Menu principal">
    <p class="sidebar-label">Menu</p>
    <a href="<?= htmlspecialchars(fv_admin_url('dashboard.php'), ENT_QUOTES, 'UTF-8') ?>" class="<?= $activeNav === 'dashboard' ? 'is-active' : '' ?>">
      <span class="nav-ico" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 13h6V4H4v9zm10 7h6V4h-6v16zM4 20h6v-5H4v5zm10-9h6V4h-6v7z"/></svg>
      </span>
      Estatísticas
    </a>
    <a href="<?= htmlspecialchars(fv_admin_url('password.php'), ENT_QUOTES, 'UTF-8') ?>" class="<?= $activeNav === 'password' ? 'is-active' : '' ?>">
      <span class="nav-ico" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="4" y="10" width="16" height="10" rx="2"/><path d="M8 10V7a4 4 0 118 0v3"/></svg>
      </span>
      Trocar senha
    </a>
    <a href="<?= htmlspecialchars(fv_site_url('index.html'), ENT_QUOTES, 'UTF-8') ?>" target="_blank" rel="noopener">
      <span class="nav-ico" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 12l8-8 8 8"/><path d="M6 10.5V20h12v-9.5"/></svg>
      </span>
      Ver site
    </a>
  </nav>

  <div class="sidebar-footer">
    <div class="sidebar-user">
      <div class="avatar"><?= strtoupper(substr($userName, 0, 1)) ?></div>
      <div>
        <strong><?= $userName ?></strong>
        <span>@<?= $userUser ?></span>
      </div>
    </div>
    <a href="<?= htmlspecialchars(fv_admin_url('logout.php'), ENT_QUOTES, 'UTF-8') ?>" class="sidebar-logout">Sair</a>
  </div>
</aside>

<div class="sidebar-backdrop" id="sidebar-backdrop" hidden></div>

<header class="admin-mobile-bar">
  <button type="button" class="sidebar-toggle" id="sidebar-toggle" aria-label="Abrir menu" aria-expanded="false" aria-controls="admin-sidebar">
    <span></span><span></span><span></span>
  </button>
  <span class="mobile-title">Painel · Fernanda Vieira</span>
</header>
