<?php
/**
 * Cliente GA4 Data API via service account (JWT RS256 + REST).
 */
function fv_service_account_path(array $config): ?string
{
    $primary = $config['service_account_path'];
    if (is_file($primary)) {
        return $primary;
    }
    $fallback = $config['service_account_fallback'] ?? '';
    if ($fallback && is_file($fallback)) {
        return $fallback;
    }
    return null;
}

function fv_base64url(string $data): string
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function fv_curl_set_common(array &$opts): void
{
    $ca = dirname(__DIR__, 2) . '/cacert.pem';
    if (is_file($ca)) {
        $opts[CURLOPT_CAINFO] = $ca;
    }
}

function fv_google_access_token(array $config): string
{
    static $cached = null;
    static $expires = 0;
    if ($cached && time() < $expires - 60) {
        return $cached;
    }

    $path = fv_service_account_path($config);
    if (!$path) {
        throw new RuntimeException('Service account não encontrada. Coloque o JSON em admin/data/service-account.json');
    }

    $sa = json_decode((string) file_get_contents($path), true);
    if (!$sa || empty($sa['private_key']) || empty($sa['client_email'])) {
        throw new RuntimeException('Service account inválida.');
    }

    $now = time();
    $header = fv_base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $claim = fv_base64url(json_encode([
        'iss' => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/analytics.readonly',
        'aud' => 'https://oauth2.googleapis.com/token',
        'iat' => $now,
        'exp' => $now + 3600,
    ]));

    $unsigned = $header . '.' . $claim;
    $key = openssl_pkey_get_private($sa['private_key']);
    if (!$key) {
        throw new RuntimeException('Não foi possível ler a chave privada da service account.');
    }
    $signature = '';
    if (!openssl_sign($unsigned, $signature, $key, OPENSSL_ALGO_SHA256)) {
        throw new RuntimeException('Falha ao assinar JWT.');
    }
    $jwt = $unsigned . '.' . fv_base64url($signature);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    $opts = [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
            'assertion' => $jwt,
        ]),
        CURLOPT_TIMEOUT => 20,
    ];
    fv_curl_set_common($opts);
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $resp = json_decode((string) $raw, true);
    if ($code >= 400 || empty($resp['access_token'])) {
        $msg = $resp['error_description'] ?? $resp['error'] ?? 'Erro ao obter token Google';
        throw new RuntimeException($msg);
    }

    $cached = $resp['access_token'];
    $expires = time() + (int) ($resp['expires_in'] ?? 3600);
    return $cached;
}

function fv_ga_request(array $config, string $method, string $url, ?array $body = null): array
{
    $token = fv_google_access_token($config);
    $ch = curl_init($url);
    $headers = [
        'Authorization: Bearer ' . $token,
        'Content-Type: application/json',
    ];
    $opts = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CUSTOMREQUEST => $method,
    ];
    if ($body !== null) {
        $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    fv_curl_set_common($opts);
    curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $resp = json_decode((string) $raw, true);
    if ($code >= 400) {
        $msg = $resp['error']['message'] ?? ('HTTP ' . $code);
        throw new RuntimeException($msg);
    }
    return is_array($resp) ? $resp : [];
}

function fv_resolve_property_id(array $config): string
{
    if (!empty($config['property_id'])) {
        return preg_replace('/\D+/', '', $config['property_id']);
    }

    $cacheFile = $config['cache_dir'] . '/property_id.txt';
    if (is_file($cacheFile)) {
        $cached = trim((string) file_get_contents($cacheFile));
        if ($cached !== '') {
            return $cached;
        }
    }

    // Tenta descobrir a propriedade pelo Measurement ID (requer Analytics Admin API ativada)
    try {
        $summaries = fv_ga_request(
            $config,
            'GET',
            'https://analyticsadmin.googleapis.com/v1beta/accountSummaries'
        );
    } catch (Throwable $e) {
        $msg = $e->getMessage();
        if (stripos($msg, 'analyticsadmin') !== false || stripos($msg, 'has not been used') !== false || stripos($msg, 'disabled') !== false) {
            throw new RuntimeException(
                'A Google Analytics Admin API está desativada neste projeto. ' .
                'Ative em: https://console.developers.google.com/apis/api/analyticsadmin.googleapis.com/overview?project=903411453192 ' .
                'OU informe o Property ID numérico em admin/includes/config.php (campo property_id). ' .
                'Para achar: GA4 → Admin → Detalhes da propriedade → PROPERTY ID.'
            );
        }
        throw $e;
    }

    $measurement = strtoupper($config['measurement_id']);
    foreach ($summaries['accountSummaries'] ?? [] as $account) {
        foreach ($account['propertySummaries'] ?? [] as $property) {
            $propName = $property['property'] ?? ''; // properties/123
            $propId = str_replace('properties/', '', $propName);
            if ($propId === '') {
                continue;
            }
            try {
                $streams = fv_ga_request(
                    $config,
                    'GET',
                    'https://analyticsadmin.googleapis.com/v1beta/properties/' . $propId . '/dataStreams'
                );
                foreach ($streams['dataStreams'] ?? [] as $stream) {
                    $mid = $stream['webStreamData']['measurementId'] ?? '';
                    if (strtoupper($mid) === $measurement) {
                        if (!is_dir($config['cache_dir'])) {
                            mkdir($config['cache_dir'], 0750, true);
                        }
                        file_put_contents($cacheFile, $propId);
                        return $propId;
                    }
                }
            } catch (Throwable $e) {
                // tenta próxima propriedade
            }
        }
    }

    throw new RuntimeException(
        'Não foi possível descobrir o Property ID do GA4. Defina GA4_PROPERTY_ID ou property_id em config.php e conceda acesso à service account na propriedade.'
    );
}

function fv_date_range(string $period): array
{
    $end = new DateTimeImmutable('today');
    switch ($period) {
        case 'today':
            $start = $end;
            break;
        case '7d':
            $start = $end->modify('-6 days');
            break;
        case '90d':
            $start = $end->modify('-89 days');
            break;
        case 'all':
            $start = new DateTimeImmutable('2024-01-01');
            break;
        case '30d':
        default:
            $start = $end->modify('-29 days');
            break;
    }
    return [
        'startDate' => $start->format('Y-m-d'),
        'endDate' => $end->format('Y-m-d'),
    ];
}

function fv_rows(array $report): array
{
    $out = [];
    foreach ($report['rows'] ?? [] as $row) {
        $dims = [];
        foreach ($row['dimensionValues'] ?? [] as $i => $d) {
            $dims[] = $d['value'] ?? '';
        }
        $mets = [];
        foreach ($row['metricValues'] ?? [] as $m) {
            $mets[] = $m['value'] ?? '0';
        }
        $out[] = ['dims' => $dims, 'mets' => $mets];
    }
    return $out;
}

function fv_metric_total(array $report, int $index = 0): float
{
    $total = 0.0;
    foreach (fv_rows($report) as $row) {
        $total += (float) ($row['mets'][$index] ?? 0);
    }
    // Prefer totals block when available
    if (!empty($report['totals'][0]['metricValues'][$index]['value'])) {
        return (float) $report['totals'][0]['metricValues'][$index]['value'];
    }
    return $total;
}

function fv_run_report(array $config, string $propertyId, array $body): array
{
    return fv_ga_request(
        $config,
        'POST',
        'https://analyticsdata.googleapis.com/v1beta/properties/' . $propertyId . ':runReport',
        $body
    );
}

function fv_run_realtime(array $config, string $propertyId, array $body): array
{
    return fv_ga_request(
        $config,
        'POST',
        'https://analyticsdata.googleapis.com/v1beta/properties/' . $propertyId . ':runRealtimeReport',
        $body
    );
}

/**
 * Dados ao vivo (últimos ~30 min). Útil enquanto o relatório padrão ainda processa.
 */
function fv_fetch_realtime_snapshot(array $config, string $propertyId): array
{
    $overview = fv_run_realtime($config, $propertyId, [
        'metrics' => [
            ['name' => 'activeUsers'],
            ['name' => 'screenPageViews'],
            ['name' => 'eventCount'],
        ],
    ]);

    $byPage = fv_run_realtime($config, $propertyId, [
        'dimensions' => [['name' => 'unifiedScreenName']],
        'metrics' => [['name' => 'activeUsers'], ['name' => 'screenPageViews']],
        'orderBys' => [['metric' => ['metricName' => 'activeUsers'], 'desc' => true]],
        'limit' => 10,
    ]);

    $byDevice = fv_run_realtime($config, $propertyId, [
        'dimensions' => [['name' => 'deviceCategory']],
        'metrics' => [['name' => 'activeUsers']],
        'orderBys' => [['metric' => ['metricName' => 'activeUsers'], 'desc' => true]],
    ]);

    $byCountry = fv_run_realtime($config, $propertyId, [
        'dimensions' => [['name' => 'country']],
        'metrics' => [['name' => 'activeUsers']],
        'orderBys' => [['metric' => ['metricName' => 'activeUsers'], 'desc' => true]],
        'limit' => 8,
    ]);

    $byEvent = fv_run_realtime($config, $propertyId, [
        'dimensions' => [['name' => 'eventName']],
        'metrics' => [['name' => 'eventCount']],
        'orderBys' => [['metric' => ['metricName' => 'eventCount'], 'desc' => true]],
        'limit' => 12,
    ]);

    $activeUsers = (int) fv_metric_total($overview, 0);
    $pageviews = (int) fv_metric_total($overview, 1);
    $eventCount = (int) fv_metric_total($overview, 2);

    $waClicks = 0;
    foreach (fv_rows($byEvent) as $row) {
        if (($row['dims'][0] ?? '') === 'whatsapp_click') {
            $waClicks = (int) ($row['mets'][0] ?? 0);
            break;
        }
    }

    $deviceTotal = max(1, array_sum(array_map(fn($r) => (int) $r['mets'][0], fv_rows($byDevice))));

    return [
        'active_users' => $activeUsers,
        'pageviews' => $pageviews,
        'event_count' => $eventCount,
        'whatsapp_clicks' => $waClicks,
        'pages' => array_map(function ($row) {
            return [
                'path' => $row['dims'][0] ?: '/',
                'users' => (int) $row['mets'][0],
                'views' => (int) ($row['mets'][1] ?? 0),
            ];
        }, fv_rows($byPage)),
        'devices' => array_map(function ($row) use ($deviceTotal) {
            $count = (int) $row['mets'][0];
            return [
                'name' => $row['dims'][0],
                'value' => $count,
                'percent' => round($count / $deviceTotal * 100, 1),
            ];
        }, fv_rows($byDevice)),
        'countries' => array_map(function ($row) {
            return [
                'name' => $row['dims'][0],
                'sessions' => (int) $row['mets'][0],
                'views' => 0,
            ];
        }, fv_rows($byCountry)),
        'events' => array_map(function ($row) {
            return [
                'name' => $row['dims'][0],
                'value' => (int) $row['mets'][0],
            ];
        }, fv_rows($byEvent)),
    ];
}

function fv_fetch_dashboard(array $config, string $period): array
{
    $range = fv_date_range($period);
    $propertyId = fv_resolve_property_id($config);

    $cacheKey = $config['cache_dir'] . '/dash_' . $period . '_' . $range['startDate'] . '_' . $range['endDate'] . '.json';
    if (is_file($cacheKey) && (time() - filemtime($cacheKey)) < (int) $config['cache_ttl']) {
        $cached = json_decode((string) file_get_contents($cacheKey), true);
        // Não reutilizar cache vazio — prioriza realtime fresco até o histórico aparecer
        if (is_array($cached) && empty($cached['history_empty']) && empty($cached['using_realtime'])) {
            $cached['cached'] = true;
            return $cached;
        }
    }

    $dateRanges = [['startDate' => $range['startDate'], 'endDate' => $range['endDate']]];

    $overview = fv_run_report($config, $propertyId, [
        'dateRanges' => $dateRanges,
        'metrics' => [
            ['name' => 'totalUsers'],
            ['name' => 'sessions'],
            ['name' => 'screenPageViews'],
            ['name' => 'averageSessionDuration'],
            ['name' => 'bounceRate'],
            ['name' => 'sessionsPerUser'],
            ['name' => 'screenPageViewsPerSession'],
            ['name' => 'eventCount'],
            ['name' => 'engagedSessions'],
        ],
    ]);

    $byHour = fv_run_report($config, $propertyId, [
        'dateRanges' => $dateRanges,
        'dimensions' => [['name' => 'hour']],
        'metrics' => [['name' => 'sessions']],
        'orderBys' => [['dimension' => ['dimensionName' => 'hour']]],
        'limit' => 24,
    ]);

    $byWeekday = fv_run_report($config, $propertyId, [
        'dateRanges' => $dateRanges,
        'dimensions' => [['name' => 'dayOfWeekName']],
        'metrics' => [['name' => 'sessions']],
        'limit' => 7,
    ]);

    $byDate = fv_run_report($config, $propertyId, [
        'dateRanges' => $dateRanges,
        'dimensions' => [['name' => 'date']],
        'metrics' => [['name' => 'totalUsers'], ['name' => 'sessions'], ['name' => 'screenPageViews']],
        'orderBys' => [['dimension' => ['dimensionName' => 'date']]],
        'limit' => 120,
    ]);

    $topPages = fv_run_report($config, $propertyId, [
        'dateRanges' => $dateRanges,
        'dimensions' => [['name' => 'pagePath']],
        'metrics' => [
            ['name' => 'screenPageViews'],
            ['name' => 'averageSessionDuration'],
            ['name' => 'sessions'],
        ],
        'orderBys' => [['metric' => ['metricName' => 'screenPageViews'], 'desc' => true]],
        'limit' => 12,
    ]);

    $devices = fv_run_report($config, $propertyId, [
        'dateRanges' => $dateRanges,
        'dimensions' => [['name' => 'deviceCategory']],
        'metrics' => [['name' => 'totalUsers']],
        'orderBys' => [['metric' => ['metricName' => 'totalUsers'], 'desc' => true]],
    ]);

    $browsers = fv_run_report($config, $propertyId, [
        'dateRanges' => $dateRanges,
        'dimensions' => [['name' => 'browser']],
        'metrics' => [['name' => 'totalUsers']],
        'orderBys' => [['metric' => ['metricName' => 'totalUsers'], 'desc' => true]],
        'limit' => 8,
    ]);

    $os = fv_run_report($config, $propertyId, [
        'dateRanges' => $dateRanges,
        'dimensions' => [['name' => 'operatingSystem']],
        'metrics' => [['name' => 'totalUsers']],
        'orderBys' => [['metric' => ['metricName' => 'totalUsers'], 'desc' => true]],
        'limit' => 8,
    ]);

    $sources = fv_run_report($config, $propertyId, [
        'dateRanges' => $dateRanges,
        'dimensions' => [['name' => 'sessionDefaultChannelGroup']],
        'metrics' => [['name' => 'sessions']],
        'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
        'limit' => 10,
    ]);

    $countries = fv_run_report($config, $propertyId, [
        'dateRanges' => $dateRanges,
        'dimensions' => [['name' => 'country']],
        'metrics' => [['name' => 'sessions'], ['name' => 'screenPageViews']],
        'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
        'limit' => 10,
    ]);

    $cities = fv_run_report($config, $propertyId, [
        'dateRanges' => $dateRanges,
        'dimensions' => [['name' => 'city'], ['name' => 'country']],
        'metrics' => [['name' => 'sessions'], ['name' => 'screenPageViews']],
        'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
        'limit' => 20,
    ]);

    $landings = fv_run_report($config, $propertyId, [
        'dateRanges' => $dateRanges,
        'dimensions' => [['name' => 'landingPage']],
        'metrics' => [['name' => 'sessions']],
        'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
        'limit' => 10,
    ]);

    $exitPages = fv_run_report($config, $propertyId, [
        'dateRanges' => $dateRanges,
        'dimensions' => [['name' => 'pagePath']],
        'metrics' => [['name' => 'sessions'], ['name' => 'bounceRate']],
        'orderBys' => [['metric' => ['metricName' => 'sessions'], 'desc' => true]],
        'limit' => 10,
    ]);

    // Eventos de clique WhatsApp (se existirem)
    $waClicks = 0;
    try {
        $waReport = fv_run_report($config, $propertyId, [
            'dateRanges' => $dateRanges,
            'dimensions' => [['name' => 'eventName']],
            'metrics' => [['name' => 'eventCount']],
            'dimensionFilter' => [
                'filter' => [
                    'fieldName' => 'eventName',
                    'stringFilter' => ['matchType' => 'EXACT', 'value' => 'whatsapp_click'],
                ],
            ],
            'limit' => 1,
        ]);
        $waClicks = (int) fv_metric_total($waReport, 0);
    } catch (Throwable $e) {
        $waClicks = 0;
    }

    $users = (int) fv_metric_total($overview, 0);
    $sessions = (int) fv_metric_total($overview, 1);
    $views = (int) fv_metric_total($overview, 2);
    $avgSession = (float) fv_metric_total($overview, 3);
    $bounce = (float) fv_metric_total($overview, 4);
    $pagesPerSession = (float) fv_metric_total($overview, 6);
    $eventCount = (int) fv_metric_total($overview, 7);

    $clicks = $waClicks > 0 ? $waClicks : $eventCount;
    $conversion = $sessions > 0 ? round(($waClicks > 0 ? $waClicks : 0) / $sessions * 100, 1) : 0;
    // Se ainda não há evento whatsapp_click, mostra conversão 0 e cliques como eventos gerais com nota

    $hourMap = array_fill(0, 24, 0);
    foreach (fv_rows($byHour) as $row) {
        $h = (int) $row['dims'][0];
        if ($h >= 0 && $h < 24) {
            $hourMap[$h] = (int) $row['mets'][0];
        }
    }

    $weekdayOrder = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $weekdayLabels = [
        'Sunday' => 'Domingo',
        'Monday' => 'Segunda',
        'Tuesday' => 'Terça',
        'Wednesday' => 'Quarta',
        'Thursday' => 'Quinta',
        'Friday' => 'Sexta',
        'Saturday' => 'Sábado',
    ];
    $weekdayMap = array_fill_keys($weekdayOrder, 0);
    foreach (fv_rows($byWeekday) as $row) {
        $name = $row['dims'][0];
        if (isset($weekdayMap[$name])) {
            $weekdayMap[$name] = (int) $row['mets'][0];
        }
    }

    $timeline = [];
    foreach (fv_rows($byDate) as $row) {
        $raw = $row['dims'][0]; // YYYYMMDD
        $label = substr($raw, 6, 2) . '/' . substr($raw, 4, 2) . '/' . substr($raw, 0, 4);
        $timeline[] = [
            'date' => $label,
            'users' => (int) $row['mets'][0],
            'sessions' => (int) $row['mets'][1],
            'views' => (int) $row['mets'][2],
        ];
    }

    $mapList = function (array $report, callable $mapper): array {
        $items = [];
        foreach (fv_rows($report) as $row) {
            $items[] = $mapper($row);
        }
        return $items;
    };

    $deviceTotal = max(1, array_sum(array_map(fn($r) => (int) $r['mets'][0], fv_rows($devices))));

    $historyEmpty = ($users + $sessions + $views) === 0;
    $realtime = [
        'active_users' => 0,
        'pageviews' => 0,
        'event_count' => 0,
        'whatsapp_clicks' => 0,
        'pages' => [],
        'devices' => [],
        'countries' => [],
        'events' => [],
    ];
    try {
        $realtime = fv_fetch_realtime_snapshot($config, $propertyId);
    } catch (Throwable $e) {
        // Realtime é complementar; não bloqueia o dashboard.
    }

    $usingRealtime = $historyEmpty && (($realtime['active_users'] ?? 0) > 0 || ($realtime['pageviews'] ?? 0) > 0 || ($realtime['event_count'] ?? 0) > 0);

    $topPagesList = $mapList($topPages, function ($row) {
        return [
            'path' => $row['dims'][0] ?: '/',
            'views' => (int) $row['mets'][0],
            'avg_duration' => (int) round((float) $row['mets'][1]),
            'sessions' => (int) $row['mets'][2],
        ];
    });
    $devicesList = $mapList($devices, function ($row) use ($deviceTotal) {
        $count = (int) $row['mets'][0];
        return [
            'name' => $row['dims'][0],
            'value' => $count,
            'percent' => round($count / $deviceTotal * 100, 1),
        ];
    });
    $countriesList = $mapList($countries, fn($row) => [
        'name' => $row['dims'][0],
        'sessions' => (int) $row['mets'][0],
        'views' => (int) $row['mets'][1],
    ]);

    $kpis = [
        'unique_visitors' => $users,
        'sessions' => $sessions,
        'pageviews' => $views,
        'avg_views_per_session' => round($pagesPerSession, 1),
        'clicks' => $clicks,
        'whatsapp_clicks' => $waClicks,
        'avg_session_duration' => (int) round($avgSession),
        'bounce_rate' => round($bounce * 100, 1),
        'conversion_rate' => $conversion,
        'pages_per_session' => round($pagesPerSession, 1),
        'active_users_now' => (int) ($realtime['active_users'] ?? 0),
    ];

    if ($usingRealtime) {
        $kpis['unique_visitors'] = (int) $realtime['active_users'];
        $kpis['sessions'] = (int) $realtime['active_users'];
        $kpis['pageviews'] = (int) $realtime['pageviews'];
        $kpis['clicks'] = (int) $realtime['event_count'];
        $kpis['whatsapp_clicks'] = (int) $realtime['whatsapp_clicks'];
        $kpis['avg_views_per_session'] = $kpis['unique_visitors'] > 0
            ? round($kpis['pageviews'] / max(1, $kpis['unique_visitors']), 1)
            : 0;
        $kpis['pages_per_session'] = $kpis['avg_views_per_session'];
        $topPagesList = array_map(function ($p) {
            return [
                'path' => $p['path'],
                'views' => (int) $p['views'],
                'avg_duration' => 0,
                'sessions' => (int) $p['users'],
            ];
        }, $realtime['pages'] ?? []);
        $devicesList = $realtime['devices'] ?? [];
        $countriesList = $realtime['countries'] ?? [];
    }

    $notes = [
        'IP individual não está disponível na API do GA4 (privacidade do Google).',
        'Cliques de WhatsApp usam o evento whatsapp_click (já enviado pelo site).',
    ];
    if ($usingRealtime) {
        array_unshift(
            $notes,
            'Relatórios padrão ainda sem histórico processado. Exibindo dados em tempo real (últimos ~30 min). O GA4 costuma liberar o histórico em até 24–48h.'
        );
    } elseif ($historyEmpty) {
        array_unshift(
            $notes,
            'Ainda não há visitas processadas neste período. Confira o relatório em tempo real no GA4; o histórico pode levar até 24–48h após o início da coleta.'
        );
    }

    $payload = [
        'ok' => true,
        'cached' => false,
        'period' => $period,
        'range' => $range,
        'property_id' => $propertyId,
        'generated_at' => date('c'),
        'using_realtime' => $usingRealtime,
        'history_empty' => $historyEmpty,
        'realtime' => $realtime,
        'kpis' => $kpis,
        'peak_hours' => array_map(fn($h, $v) => ['hour' => sprintf('%02d:00', $h), 'value' => $v], array_keys($hourMap), $hourMap),
        'weekdays' => array_map(fn($k, $v) => ['day' => $weekdayLabels[$k] ?? $k, 'value' => $v], array_keys($weekdayMap), $weekdayMap),
        'timeline' => $timeline,
        'top_pages' => $topPagesList,
        'devices' => $devicesList,
        'browsers' => $mapList($browsers, fn($row) => ['name' => $row['dims'][0], 'value' => (int) $row['mets'][0]]),
        'os' => $mapList($os, fn($row) => ['name' => $row['dims'][0], 'value' => (int) $row['mets'][0]]),
        'sources' => $mapList($sources, fn($row) => ['name' => $row['dims'][0], 'value' => (int) $row['mets'][0]]),
        'countries' => $countriesList,
        'cities' => $mapList($cities, fn($row) => [
            'city' => $row['dims'][0],
            'country' => $row['dims'][1],
            'sessions' => (int) $row['mets'][0],
            'views' => (int) $row['mets'][1],
        ]),
        'landings' => $mapList($landings, fn($row) => [
            'path' => $row['dims'][0] ?: '/',
            'sessions' => (int) $row['mets'][0],
        ]),
        'exits' => $mapList($exitPages, fn($row) => [
            'path' => $row['dims'][0] ?: '/',
            'sessions' => (int) $row['mets'][0],
            'bounce_rate' => round(((float) $row['mets'][1]) * 100, 1),
        ]),
        'notes' => $notes,
    ];

    if (!is_dir($config['cache_dir'])) {
        mkdir($config['cache_dir'], 0750, true);
    }
    // Cache curto enquanto só há realtime (dados mudam rápido)
    if (!$usingRealtime) {
        file_put_contents($cacheKey, json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    return $payload;
}

function fv_format_duration(int $seconds): string
{
    if ($seconds <= 0) {
        return '0s';
    }
    $m = intdiv($seconds, 60);
    $s = $seconds % 60;
    if ($m <= 0) {
        return $s . 's';
    }
    return $m . 'm ' . str_pad((string) $s, 2, '0', STR_PAD_LEFT) . 's';
}
