<?php
/**
 * Configuração do painel interno — Fernanda Vieira Psicologia
 * NÃO versionar senhas em texto puro. Service account fica em /admin/data/
 */
return [
    'site_name' => 'Fernanda Vieira Psicologia',
    // Caminho público do painel (evita CSS quebrado em /admin sem barra final)
    'admin_base' => '/admin',
    'asset_version' => '20260728b',
    'measurement_id' => 'G-QKSZY5SXN8',
    // ID numérico da propriedade GA4 (Admin > Configurações da propriedade).
    // Deixe vazio para tentar descobrir automaticamente via API.
    'property_id' => getenv('GA4_PROPERTY_ID') ?: '547353361',
    'service_account_path' => __DIR__ . '/../data/service-account.json',
    // Fallback local (arquivo na raiz do projeto, fora do dist público)
    'service_account_fallback' => dirname(__DIR__, 2) . '/graphic-ripsaw-461313-c3-03abc8b51f09.json',
    'users_file' => __DIR__ . '/../data/users.json',
    'cache_dir' => __DIR__ . '/../data/cache',
    'cache_ttl' => 300, // 5 minutos
    'session_name' => 'fv_admin_session',
];
