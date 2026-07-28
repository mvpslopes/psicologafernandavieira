<?php
require __DIR__ . '/includes/auth.php';
fv_logout();
header('Location: ' . fv_admin_url('index.php'));
exit;
