<?php
header('Content-Type: application/json; charset=utf-8');

$endpoint = trim((string)($_GET['endpoint'] ?? ''));
$regionCode = trim((string)($_GET['region_code'] ?? ''));
$provinceCode = trim((string)($_GET['province_code'] ?? ''));
$cityCode = trim((string)($_GET['city_code'] ?? ''));

if ($endpoint === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing endpoint']);
    exit;
}

function respondError(string $message, int $status = 400): void {
    http_response_code($status);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

function getOfflineAddressData(): array {
    return [
        'regions' => [
            ['code' => '130000000', 'name' => 'NCR'],
            ['code' => '030000000', 'name' => 'Central Luzon'],
            ['code' => '040000000', 'name' => 'CALABARZON'],
            ['code' => '070000000', 'name' => 'Central Visayas'],
            ['code' => '110000000', 'name' => 'Davao Region']
        ],
        'provincesByRegion' => [
            '130000000' => [],
            '030000000' => [
                ['code' => '031400000', 'name' => 'Bulacan'],
                ['code' => '035400000', 'name' => 'Pampanga']
            ],
            '040000000' => [
                ['code' => '042100000', 'name' => 'Cavite'],
                ['code' => '043400000', 'name' => 'Laguna']
            ],
            '070000000' => [
                ['code' => '072200000', 'name' => 'Cebu'],
                ['code' => '074600000', 'name' => 'Bohol']
            ],
            '110000000' => [
                ['code' => '112400000', 'name' => 'Davao del Sur'],
                ['code' => '112300000', 'name' => 'Davao del Norte']
            ]
        ],
        'citiesByRegion' => [
            '130000000' => [
                ['code' => '133900000', 'name' => 'City of Manila'],
                ['code' => '137404000', 'name' => 'Quezon City'],
                ['code' => '137602000', 'name' => 'Makati City'],
                ['code' => '137605000', 'name' => 'Pasig City'],
                ['code' => '137607000', 'name' => 'Taguig City']
            ]
        ],
        'citiesByProvince' => [
            '031400000' => [
                ['code' => '031408000', 'name' => 'City of Malolos'],
                ['code' => '031420000', 'name' => 'Meycauayan City']
            ],
            '035400000' => [
                ['code' => '035416000', 'name' => 'City of San Fernando'],
                ['code' => '035409000', 'name' => 'Angeles City']
            ],
            '042100000' => [
                ['code' => '042108000', 'name' => 'Dasmarinas City'],
                ['code' => '042109000', 'name' => 'Imus City']
            ],
            '043400000' => [
                ['code' => '043410000', 'name' => 'City of Santa Rosa'],
                ['code' => '043403000', 'name' => 'City of Calamba']
            ],
            '072200000' => [
                ['code' => '072217000', 'name' => 'Cebu City'],
                ['code' => '072234000', 'name' => 'Mandaue City']
            ],
            '074600000' => [
                ['code' => '074601000', 'name' => 'Tagbilaran City'],
                ['code' => '074613000', 'name' => 'Dauis']
            ],
            '112400000' => [
                ['code' => '112402000', 'name' => 'Davao City'],
                ['code' => '112404000', 'name' => 'Digos City']
            ],
            '112300000' => [
                ['code' => '112314000', 'name' => 'Panabo City'],
                ['code' => '112315000', 'name' => 'Tagum City']
            ]
        ],
        'barangaysByCity' => [
            '133900000' => [
                ['code' => '133901001', 'name' => 'Barangay 1'],
                ['code' => '133901002', 'name' => 'Barangay 2'],
                ['code' => '133901003', 'name' => 'Barangay 3']
            ],
            '137404000' => [
                ['code' => '137404001', 'name' => 'Batasan Hills'],
                ['code' => '137404002', 'name' => 'Commonwealth'],
                ['code' => '137404003', 'name' => 'Holy Spirit']
            ],
            '137602000' => [
                ['code' => '137602001', 'name' => 'Bel-Air'],
                ['code' => '137602002', 'name' => 'Poblacion'],
                ['code' => '137602003', 'name' => 'San Lorenzo']
            ],
            '137605000' => [
                ['code' => '137605001', 'name' => 'Kapitolyo'],
                ['code' => '137605002', 'name' => 'Rosario'],
                ['code' => '137605003', 'name' => 'Ugong']
            ],
            '137607000' => [
                ['code' => '137607001', 'name' => 'Fort Bonifacio'],
                ['code' => '137607002', 'name' => 'Pinagsama'],
                ['code' => '137607003', 'name' => 'Western Bicutan']
            ]
        ]
    ];
}

function getOfflineResponse(string $endpoint, string $regionCode, string $provinceCode, string $cityCode): array {
    $offline = getOfflineAddressData();
    if ($endpoint === 'regions') {
        return $offline['regions'];
    }
    if ($endpoint === 'provinces') {
        return $offline['provincesByRegion'][$regionCode] ?? [];
    }
    if ($endpoint === 'cities') {
        if ($provinceCode !== '') {
            return $offline['citiesByProvince'][$provinceCode] ?? [];
        }
        return $offline['citiesByRegion'][$regionCode] ?? [];
    }
    if ($endpoint === 'barangays') {
        return $offline['barangaysByCity'][$cityCode] ?? [
            ['code' => $cityCode . '001', 'name' => 'Barangay 1'],
            ['code' => $cityCode . '002', 'name' => 'Barangay 2'],
            ['code' => $cityCode . '003', 'name' => 'Barangay 3']
        ];
    }
    return [];
}

function fetchRemoteJson(string $url): array {
    $errors = [];

    // Try cURL first (usually more reliable on Windows/XAMPP).
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: AndreaMysteryShop/1.0'
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $raw = curl_exec($ch);
        $curlErr = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Fallback for environments with missing CA bundle.
        if (($raw === false || $status >= 400) && ($curlErr !== '' || $status === 0)) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            $raw = curl_exec($ch);
            $curlErr = curl_error($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }

        curl_close($ch);

        if ($raw !== false && $status >= 200 && $status < 300) {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                return $decoded;
            }
            $errors[] = 'Invalid JSON from upstream';
        } else {
            $errors[] = $curlErr !== '' ? ('cURL: ' . $curlErr) : ('Upstream HTTP status ' . $status);
        }
    }

    // Fallback to file_get_contents for hosts without cURL.
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'timeout' => 12,
            'header' => "Accept: application/json\r\nUser-Agent: AndreaMysteryShop/1.0\r\n"
        ],
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false
        ]
    ]);

    $raw = @file_get_contents($url, false, $context);
    if ($raw !== false) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            return $decoded;
        }
        $errors[] = 'Invalid JSON from upstream (fopen)';
    } else {
        $errors[] = 'file_get_contents failed';
    }

    throw new RuntimeException(implode(' | ', $errors));
}

switch ($endpoint) {
    case 'regions':
        $remoteUrl = 'https://psgc.gitlab.io/api/regions/';
        break;
    case 'provinces':
        if ($regionCode === '') {
            respondError('Missing region_code');
        }
        $remoteUrl = 'https://psgc.gitlab.io/api/regions/' . rawurlencode($regionCode) . '/provinces/';
        break;
    case 'cities':
        if ($provinceCode !== '') {
            $remoteUrl = 'https://psgc.gitlab.io/api/provinces/' . rawurlencode($provinceCode) . '/cities-municipalities/';
            break;
        }
        if ($regionCode !== '') {
            $remoteUrl = 'https://psgc.gitlab.io/api/regions/' . rawurlencode($regionCode) . '/cities-municipalities/';
            break;
        }
        respondError('Missing province_code or region_code');
        break;
    case 'barangays':
        if ($cityCode === '') {
            respondError('Missing city_code');
        }
        $remoteUrl = 'https://psgc.gitlab.io/api/cities-municipalities/' . rawurlencode($cityCode) . '/barangays/';
        break;
    default:
        respondError('Unsupported endpoint');
}

try {
    $decoded = fetchRemoteJson($remoteUrl);
} catch (Throwable $e) {
    $decoded = getOfflineResponse($endpoint, $regionCode, $provinceCode, $cityCode);
}

$data = [];
foreach ($decoded as $row) {
    if (!is_array($row)) {
        continue;
    }
    $code = trim((string)($row['code'] ?? ''));
    $name = trim((string)($row['name'] ?? ''));
    if ($code === '' || $name === '') {
        continue;
    }
    $data[] = [
        'code' => $code,
        'name' => $name
    ];
}

echo json_encode([
    'success' => true,
    'data' => $data
]);
