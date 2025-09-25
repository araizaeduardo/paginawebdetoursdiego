<?php
// Iniciar sesión para manejar datos de usuario
session_start();

// Configuración
$siteName = "TravelPro";
$siteTagline = "Tours y Vuelos";

// ==========================
// Amadeus API: utilidades
// ==========================
// Las credenciales se leen desde variables de entorno para evitar exponerlas en el repositorio.
// Exporte antes de iniciar el servidor PHP:
//   export AMADEUS_API_KEY=... && export AMADEUS_API_SECRET=...
// Servidor de pruebas: https://test.api.amadeus.com
// Cargar variables desde .env si existe
function load_env($path)
{
    if (!is_file($path) || !is_readable($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(ltrim($line), '#') === 0) continue;
        if (!str_contains($line, '=')) continue;
        list($k, $v) = array_map('trim', explode('=', $line, 2));
        // Quitar comillas envolventes si las hay
        if ((str_starts_with($v, '"') && str_ends_with($v, '"')) || (str_starts_with($v, "'") && str_ends_with($v, "'"))) {
            $v = substr($v, 1, -1);
        }
        putenv("$k=$v");
        $_ENV[$k] = $v;
    }
}

load_env(__DIR__ . '/.env');

define('AMADEUS_BASE_URL', getenv('AMADEUS_BASE_URL') ?: 'https://test.api.amadeus.com');

function amadeus_get_credentials() {
    $key = getenv('AMADEUS_API_KEY') ?: '';
    $secret = getenv('AMADEUS_API_SECRET') ?: '';
    return [$key, $secret];
}

function amadeus_get_token() {
    // Reusar token si está vigente
    if (!empty($_SESSION['amadeus_token']) && !empty($_SESSION['amadeus_token_expires']) && time() < $_SESSION['amadeus_token_expires']) {
        return $_SESSION['amadeus_token'];
    }

    list($clientId, $clientSecret) = amadeus_get_credentials();
    if (empty($clientId) || empty($clientSecret)) {
        throw new Exception('Faltan credenciales de Amadeus. Configure AMADEUS_API_KEY y AMADEUS_API_SECRET.');
    }

    if (!function_exists('curl_init')) {
        throw new Exception('La extensión PHP cURL no está habilitada. Instala php8.3-curl (o equivalente) y reinicia el servidor.');
    }
    $url = AMADEUS_BASE_URL . '/v1/security/oauth2/token';
    $postFields = http_build_query([
        'grant_type' => 'client_credentials',
        'client_id' => $clientId,
        'client_secret' => $clientSecret,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postFields,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Error al obtener token de Amadeus: ' . $err);
    }
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $data = json_decode($resp, true);
    if ($status < 200 || $status >= 300 || empty($data['access_token'])) {
        $msg = isset($data['error_description']) ? $data['error_description'] : 'Respuesta inválida del servidor de autorización';
        throw new Exception('No se pudo obtener token de Amadeus (' . $status . '): ' . $msg);
    }

    $_SESSION['amadeus_token'] = $data['access_token'];
    // Guardar expiración (ahora + expires_in - un margen de 60s)
    $expiresIn = isset($data['expires_in']) ? (int)$data['expires_in'] : 1700;
    $_SESSION['amadeus_token_expires'] = time() + max(60, $expiresIn - 60);
    return $_SESSION['amadeus_token'];
}

function amadeus_search_flights($origin, $destination, $departureDate, $returnDate = null, $adults = 1, $currency = 'USD', $max = 10) {
    if (!function_exists('curl_init')) {
        throw new Exception('La extensión PHP cURL no está habilitada. Instala php8.3-curl (o equivalente) y reinicia el servidor.');
    }
    $token = amadeus_get_token();
    $params = [
        'originLocationCode' => strtoupper($origin),
        'destinationLocationCode' => strtoupper($destination),
        'departureDate' => $departureDate,
        'adults' => $adults,
        'currencyCode' => $currency,
        'max' => $max,
    ];
    if (!empty($returnDate)) {
        $params['returnDate'] = $returnDate;
    }

    $url = AMADEUS_BASE_URL . '/v2/shopping/flight-offers?' . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Accept: application/json',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $resp = curl_exec($ch);
    if ($resp === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Error al consultar vuelos: ' . $err);
    }
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);
    $data = json_decode($resp, true);
    if ($status < 200 || $status >= 300) {
        $msg = isset($data['errors'][0]['detail']) ? $data['errors'][0]['detail'] : 'Error en la API de Amadeus';
        throw new Exception('Consulta de vuelos falló (' . $status . '): ' . $msg);
    }
    return $data;
}

function iso8601_duration_to_text($duration) {
    // Ejemplo: PT8H45M -> 8h 45m
    if (!is_string($duration)) return '';
    $hours = 0; $minutes = 0;
    if (preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?/i', $duration, $m)) {
        $hours = isset($m[1]) ? (int)$m[1] : 0;
        $minutes = isset($m[2]) ? (int)$m[2] : 0;
    }
    $parts = [];
    if ($hours > 0) $parts[] = $hours . 'h';
    if ($minutes > 0) $parts[] = $minutes . 'm';
    return trim(implode(' ', $parts));
}

function map_amadeus_offers_to_flights($apiResponse) {
    $flights = [];
    if (empty($apiResponse['data'])) return $flights;
    $carriers = isset($apiResponse['dictionaries']['carriers']) ? $apiResponse['dictionaries']['carriers'] : [];

    $id = 1;
    foreach ($apiResponse['data'] as $offer) {
        // Tomar el primer itinerario como base para mostrar
        $itineraries = $offer['itineraries'] ?? [];
        if (empty($itineraries)) continue;
        $firstIt = $itineraries[0];
        $segments = $firstIt['segments'] ?? [];
        if (empty($segments)) continue;

        $firstSeg = $segments[0];
        $lastSeg = $segments[count($segments) - 1];
        $carrierCode = $firstSeg['carrierCode'] ?? '';
        $airlineName = isset($carriers[$carrierCode]) ? $carriers[$carrierCode] : $carrierCode;

        $departureAt = $firstSeg['departure']['at'] ?? '';
        $arrivalAt = $lastSeg['arrival']['at'] ?? '';
        $departureTime = $departureAt ? date('H:i', strtotime($departureAt)) : '';
        $arrivalTime = $arrivalAt ? date('H:i', strtotime($arrivalAt)) : '';

        $duration = iso8601_duration_to_text($firstIt['duration'] ?? '');
        $stops = max(0, count($segments) - 1);

        $price = isset($offer['price']['total']) ? (float)$offer['price']['total'] : 0.0;

        $flights[] = [
            'id' => $id++,
            'airline' => $airlineName ?: '—',
            'airline_logo' => 'https://via.placeholder.com/80x24?text=' . urlencode($carrierCode ?: 'AIR'),
            'departure_time' => $departureTime,
            'departure_airport' => $firstSeg['departure']['iataCode'] ?? '',
            'arrival_time' => $arrivalTime,
            'arrival_airport' => $lastSeg['arrival']['iataCode'] ?? '',
            'duration' => $duration,
            'stops' => $stops,
            'old_price' => null,
            'price' => $price,
            'featured' => false,
        ];
    }

    // Marcar como destacado el de menor precio
    if (!empty($flights)) {
        $minIndex = 0;
        $minPrice = $flights[0]['price'];
        foreach ($flights as $i => $f) {
            if ($f['price'] < $minPrice) {
                $minPrice = $f['price'];
                $minIndex = $i;
            }
        }
        $flights[$minIndex]['featured'] = true;
    }

    return $flights;
}

// Función para mostrar mensajes de alerta
function showAlert($message, $type = 'success') {
    if(isset($message)) {
        echo "<div class='alert alert-{$type}'>{$message}</div>";
    }
}

// Procesar formularios si se han enviado
$formMessage = '';
$formMessageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['form_type'])) {
        switch ($_POST['form_type']) {
            case 'contact':
                // Procesar formulario de contacto
                $formMessage = "¡Mensaje enviado con éxito! Te contactaremos pronto.";
                break;
            case 'affiliate':
                // Procesar formulario de afiliados
                $formMessage = "¡Registro de afiliado exitoso! Recibirás un email con más información.";
                break;
            case 'newsletter':
                // Procesar formulario de newsletter
                $formMessage = "¡Suscripción exitosa! Recibirás nuestras mejores ofertas.";
                break;
            case 'flight_search':
                // Procesar búsqueda de vuelos con Amadeus
                $origin = isset($_POST['origen']) ? trim($_POST['origen']) : '';
                $destination = isset($_POST['destino']) ? trim($_POST['destino']) : '';
                $departure = isset($_POST['fechaIda']) ? trim($_POST['fechaIda']) : '';
                $return = isset($_POST['fechaVuelta']) ? trim($_POST['fechaVuelta']) : '';
                $flightType = isset($_POST['flight_type']) ? $_POST['flight_type'] : 'round_trip';
                // Persistir selección de tipo de viaje
                $_SESSION['last_flight_type'] = $flightType;
                if ($flightType === 'one_way') {
                    // Ignorar fecha de regreso en modo solo ida
                    $return = '';
                }

                // Validaciones básicas (se espera código IATA de 3 letras)
                if (!preg_match('/^[A-Za-z]{3}$/', $origin) || !preg_match('/^[A-Za-z]{3}$/', $destination)) {
                    $formMessage = 'Por favor ingresa códigos IATA válidos (ej. BOG, MIA).';
                    $formMessageType = 'warning';
                    break;
                }
                if (empty($departure)) {
                    $formMessage = 'La fecha de ida es obligatoria.';
                    $formMessageType = 'warning';
                    break;
                }

                // Número de personas (adultos)
                $adults = isset($_POST['personas']) ? (int)$_POST['personas'] : 1;
                if ($adults < 1) $adults = 1;
                if ($adults > 9) $adults = 9; // límite típico

                try {
                    $apiResp = amadeus_search_flights($origin, $destination, $departure, $return, $adults);
                    $mapped = map_amadeus_offers_to_flights($apiResp);
                    if (empty($mapped)) {
                        $formMessage = 'No encontramos ofertas para tu búsqueda. Intenta con otras fechas o destinos.';
                        $formMessageType = 'info';
                    } else {
                        // Sobrescribir $flights global más abajo (usaremos variable global luego de definirla)
                        $GLOBALS['flights'] = $mapped;
                        $formMessage = 'Mostrando resultados en tiempo real de Amadeus.';
                        $formMessageType = 'success';
                    }
                } catch (Exception $e) {
                    $formMessage = 'Error al buscar vuelos: ' . htmlspecialchars($e->getMessage());
                    $formMessageType = 'error';
                }
                break;
            case 'tour_search':
                // Procesar búsqueda de tours
                $formMessage = "Búsqueda realizada. Mostrando tours disponibles.";
                $formMessageType = 'info';
                break;
        }
    }
}

// Datos para tours (en una aplicación real, estos vendrían de una base de datos)
$tours = [
    [
        'id' => 1,
        'name' => 'París Romántico',
        'location' => 'Francia',
        'description' => '5 días explorando la ciudad del amor con tours guiados',
        'image' => 'https://images.unsplash.com/photo-1506905925346-21bda4d32df4?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80',
        'features' => ['Vuelo incluido', 'Hotel 4 estrellas', 'Desayuno incluido'],
        'old_price' => 1200,
        'new_price' => 840,
        'discount' => '-30%'
    ],
    [
        'id' => 2,
        'name' => 'Tokio Moderno',
        'location' => 'Japón',
        'description' => '7 días descubriendo la cultura japonesa moderna y tradicional',
        'image' => 'https://images.unsplash.com/photo-1539650116574-75c0c6d73c6e?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80',
        'features' => ['Vuelo incluido', 'Hotel 5 estrellas', 'Transporte privado'],
        'old_price' => null,
        'new_price' => 2100,
        'discount' => '¡NUEVO!'
    ],
    [
        'id' => 3,
        'name' => 'Machu Picchu Místico',
        'location' => 'Perú',
        'description' => '4 días explorando la maravilla del mundo inca',
        'image' => 'https://images.unsplash.com/photo-1518709268805-4e9042af2176?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1000&q=80',
        'features' => ['Vuelo incluido', 'Trekking guiado', 'Tour fotográfico'],
        'old_price' => 800,
        'new_price' => 600,
        'discount' => '-25%'
    ]
];

// Datos para vuelos (por defecto, si no hay resultados de Amadeus)
if (!isset($flights) || !is_array($flights) || empty($flights)) {
$flights = [
    [
        'id' => 1,
        'airline' => 'Avianca',
        'airline_logo' => 'https://logos-world.net/wp-content/uploads/2023/01/Avianca-Logo.png',
        'departure_time' => '08:30',
        'departure_airport' => 'BOG',
        'arrival_time' => '11:00',
        'arrival_airport' => 'MDE',
        'duration' => '2h 30m',
        'stops' => 0,
        'old_price' => null,
        'price' => 180,
        'featured' => false
    ],
    [
        'id' => 2,
        'airline' => 'LATAM',
        'airline_logo' => 'https://logoeps.com/wp-content/uploads/2013/03/latam-vector-logo.png',
        'departure_time' => '14:15',
        'departure_airport' => 'BOG',
        'arrival_time' => '23:00',
        'arrival_airport' => 'MIA',
        'duration' => '8h 45m',
        'stops' => 1,
        'old_price' => 650,
        'price' => 480,
        'featured' => true
    ],
    [
        'id' => 3,
        'airline' => 'American Airlines',
        'airline_logo' => 'https://1000logos.net/wp-content/uploads/2019/05/American-Airlines-Logo.png',
        'departure_time' => '06:45',
        'departure_airport' => 'MIA',
        'arrival_time' => '10:05',
        'arrival_airport' => 'JFK',
        'duration' => '3h 20m',
        'stops' => 0,
        'old_price' => null,
        'price' => 320,
        'featured' => false
    ]
];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $siteName; ?> - <?php echo $siteTagline; ?></title>
    <link rel="stylesheet" href="styles.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* Estilos para alertas */
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
            animation: fadeIn 0.5s ease;
        }
        
        .alert-success {
            background-color: rgba(40, 167, 69, 0.2);
            border-left: 4px solid #28a745;
            color: #155724;
        }
        
        .alert-info {
            background-color: rgba(0, 123, 255, 0.2);
            border-left: 4px solid #007bff;
            color: #004085;
        }
        
        .alert-warning {
            background-color: rgba(255, 193, 7, 0.2);
            border-left: 4px solid #ffc107;
            color: #856404;
        }
        
        .alert-error {
            background-color: rgba(220, 53, 69, 0.2);
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="nav-container">
            <div class="nav-logo">
                <i class="fas fa-plane"></i>
                <span><?php echo $siteName; ?></span>
            </div>
            <ul class="nav-menu" id="nav-menu">
                <?php
                // Definir los elementos del menú
                $menuItems = [
                    ['href' => '#home', 'text' => 'Inicio'],
                    ['href' => '#tours', 'text' => 'Tours'],
                    ['href' => '#flights', 'text' => 'Vuelos'],
                    ['href' => '#affiliates', 'text' => 'Afiliados'],
                    ['href' => '#contact', 'text' => 'Contacto']
                ];
                
                // Generar los elementos del menú
                foreach ($menuItems as $item) {
                    echo '<li class="nav-item">'
                       . '<a href="' . $item['href'] . '" class="nav-link">' . $item['text'] . '</a>'
                       . '</li>';
                }
                ?>
            </ul>
            <div class="hamburger" id="hamburger">
                <span class="bar"></span>
                <span class="bar"></span>
                <span class="bar"></span>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-content">
            <?php if (!empty($formMessage)): ?>
                <div class="alert alert-<?php echo $formMessageType; ?>">
                    <?php echo $formMessage; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <div class="pagination" style="display:flex;gap:6px;justify-content:center;align-items:center;margin-top:16px;flex-wrap:wrap;">
                    <?php
                        // Botón "Anterior"
                        if ($page > 1) {
                            $q = $queryParams; $q['page'] = $page - 1;
                            $url = $basePath . '?' . http_build_query($q) . '#flights';
                            echo '<a class="btn btn-secondary btn-small" href="' . htmlspecialchars($url) . '">Anterior</a>';
                        }
                        // Números de página
                        for ($p = 1; $p <= $totalPages; $p++) {
                            $q = $queryParams; $q['page'] = $p;
                            $url = $basePath . '?' . http_build_query($q) . '#flights';
                            $active = ($p === $page) ? ' style="font-weight:600;pointer-events:none;opacity:.7;"' : '';
                            echo '<a class="btn btn-primary btn-small"' . $active . ' href="' . htmlspecialchars($url) . '">' . $p . '</a>';
                        }
                        // Botón "Siguiente"
                        if ($page < $totalPages) {
                            $q = $queryParams; $q['page'] = $page + 1;
                            $url = $basePath . '?' . http_build_query($q) . '#flights';
                            echo '<a class="btn btn-secondary btn-small" href="' . htmlspecialchars($url) . '">Siguiente</a>';
                        }
                    ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            <h1 class="hero-title">Descubre el Mundo</h1>
            <p class="hero-subtitle">Los mejores tours y vuelos con promociones increíbles</p>
            <div class="hero-buttons">
                <a href="#tours" class="btn btn-primary">Explorar Tours</a>
                <a href="#flights" class="btn btn-secondary">Ver Vuelos</a>
            </div>
        </div>
        <div class="hero-search">
            <div class="search-tabs">
                <button class="tab-btn active" data-tab="flights">Vuelos</button>
                <button class="tab-btn" data-tab="tours">Tours</button>
            </div>
            <?php $lastFlightType = isset($_SESSION['last_flight_type']) ? $_SESSION['last_flight_type'] : 'round_trip'; ?>
            <div class="search-content">
                <div class="search-form active" id="flights-search">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>#flights">
                        <input type="hidden" name="form_type" value="flight_search">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tipo de viaje</label>
                                <div class="radio-group">
                                    <label style="display:inline-flex;align-items:center;gap:6px;margin-right:12px;cursor:pointer;">
                                        <input type="radio" name="flight_type" value="round_trip" <?php echo ($lastFlightType === 'round_trip') ? 'checked' : ''; ?>>
                                        <span>Ida y vuelta</span>
                                    </label>
                                    <label style="display:inline-flex;align-items:center;gap:6px;cursor:pointer;">
                                        <input type="radio" name="flight_type" value="one_way" <?php echo ($lastFlightType === 'one_way') ? 'checked' : ''; ?>>
                                        <span>Solo ida</span>
                                    </label>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Origen</label>
                                <input type="text" name="origen" placeholder="Código IATA (ej. LAX)" list="airports-list" value="LAX" required>
                            </div>
                            <div class="form-group">
                                <label>Destino</label>
                                <input type="text" name="destino" placeholder="Código IATA (ej. GDL)" list="airports-list" value="GDL" required>
                            </div>
                            <div class="form-group">
                                <label>Fecha ida</label>
                                <input type="date" name="fechaIda" required>
                            </div>
                            <div class="form-group" id="return-date-group">
                                <label>Fecha vuelta</label>
                                <input type="date" name="fechaVuelta">
                            </div>
                            <div class="form-group">
                                <label>Personas</label>
                                <input type="number" name="personas" min="1" max="9" value="1" required>
                            </div>
                            <button type="submit" class="btn btn-search">Buscar Vuelos</button>
                        </div>
                        <!-- Datalist compartido para autocompletar aeropuertos/ciudades -->
                        <datalist id="airports-list">
                            <option value="LAX">Los Angeles (LAX) - United States</option>
                            <option value="GDL">Guadalajara (GDL) - Mexico</option>
                            <option value="MEX">Ciudad de México (MEX) - Mexico</option>
                            <option value="CUN">Cancún (CUN) - Mexico</option>
                            <option value="GUA">Guatemala City (GUA) - Guatemala</option>
                            <option value="SJO">San José (SJO) - Costa Rica</option>
                            <option value="BOG">Bogotá (BOG) - Colombia</option>
                            <option value="MDE">Medellín (MDE) - Colombia</option>
                            <option value="CLO">Cali (CLO) - Colombia</option>
                            <option value="CTG">Cartagena (CTG) - Colombia</option>
                            <option value="MIA">Miami (MIA) - United States</option>
                            <option value="JFK">New York (JFK) - United States</option>
                            <option value="SFO">San Francisco (SFO) - United States</option>
                            <option value="ORD">Chicago (ORD) - United States</option>
                            <option value="DFW">Dallas (DFW) - United States</option>
                            <option value="MAD">Madrid (MAD) - Spain</option>
                            <option value="BCN">Barcelona (BCN) - Spain</option>
                            <option value="CDG">Paris (CDG) - France</option>
                            <option value="FRA">Frankfurt (FRA) - Germany</option>
                            <option value="LHR">London (LHR) - United Kingdom</option>
                            <option value="UIO">Quito (UIO) - Ecuador</option>
                            <option value="GYE">Guayaquil (GYE) - Ecuador</option>
                            <option value="LIM">Lima (LIM) - Peru</option>
                            <option value="SCL">Santiago (SCL) - Chile</option>
                            <option value="EZE">Buenos Aires (EZE) - Argentina</option>
                            <option value="GRU">São Paulo (GRU) - Brazil</option>
                            <option value="PTY">Panamá (PTY) - Panama</option>
                        </datalist>
                    </form>
                </div>
                <div class="search-form" id="tours-search">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>#tours">
                        <input type="hidden" name="form_type" value="tour_search">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Destino</label>
                                <input type="text" name="destino" placeholder="¿A dónde quieres ir?" required>
                            </div>
                            <div class="form-group">
                                <label>Fecha</label>
                                <input type="date" name="fecha" required>
                            </div>
                            <div class="form-group">
                                <label>Personas</label>
                                <select name="personas" required>
                                    <option value="1">1 persona</option>
                                    <option value="2">2 personas</option>
                                    <option value="3">3 personas</option>
                                    <option value="4">4+ personas</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-search">Buscar Tours</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    

    <!-- Tours Section -->
    <section id="tours" class="tours">
        <div class="container">
            <h2 class="section-title">Tours Destacados</h2>
            <div class="tours-grid">
                <?php foreach ($tours as $tour): ?>
                <div class="tour-card">
                    <div class="card-image">
                        <img src="<?php echo htmlspecialchars($tour['image']); ?>" alt="<?php echo htmlspecialchars($tour['name']); ?>">
                        <?php if (!empty($tour['discount'])): ?>
                        <div class="card-badge"><?php echo htmlspecialchars($tour['discount']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($tour['name']); ?></h3>
                        <p class="location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($tour['location']); ?></p>
                        <p class="description"><?php echo htmlspecialchars($tour['description']); ?></p>
                        <div class="card-features">
                            <?php foreach ($tour['features'] as $feature): ?>
                            <span>
                                <?php 
                                // Asignar el icono adecuado según el texto de la característica
                                $icon = 'fa-star'; // Icono por defecto
                                if (strpos($feature, 'Vuelo') !== false) $icon = 'fa-plane';
                                if (strpos($feature, 'Hotel') !== false) $icon = 'fa-hotel';
                                if (strpos($feature, 'Desayuno') !== false) $icon = 'fa-utensils';
                                if (strpos($feature, 'Transporte') !== false) $icon = 'fa-car';
                                if (strpos($feature, 'Trekking') !== false) $icon = 'fa-mountain';
                                if (strpos($feature, 'Tour') !== false) $icon = 'fa-camera';
                                ?>
                                <i class="fas <?php echo $icon; ?>"></i> <?php echo htmlspecialchars($feature); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-footer">
                            <div class="price">
                                <?php if ($tour['old_price']): ?>
                                <span class="old-price">$<?php echo number_format($tour['old_price']); ?></span>
                                <?php endif; ?>
                                <span class="new-price">$<?php echo number_format($tour['new_price']); ?></span>
                            </div>
                            <a href="tour_details.php?id=<?php echo $tour['id']; ?>" class="btn btn-primary">Ver Detalles</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Flights Section -->
    <section id="flights" class="flights">
        <div class="container">
            <h2 class="section-title">Vuelos en Oferta</h2>
            <div class="flights-container">
                <div class="flight-filters">
                    <h3>Filtrar por:</h3>
                    <?php $defaultFilterFlightType = isset($_GET['flight_type']) ? $_GET['flight_type'] : (isset($_SESSION['last_flight_type']) ? $_SESSION['last_flight_type'] : 'round_trip'); ?>
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>#flights">
                        <div class="filter-group">
                            <label>Tipo de vuelo:</label>
                            <select class="filter-select" name="flight_type">
                                <option value="round_trip" <?php echo ($defaultFilterFlightType == 'round_trip') ? 'selected' : ''; ?>>Ida y vuelta</option>
                                <option value="one_way" <?php echo ($defaultFilterFlightType == 'one_way') ? 'selected' : ''; ?>>Solo ida</option>
                                <option value="multi_city" <?php echo ($defaultFilterFlightType == 'multi_city') ? 'selected' : ''; ?>>Multidestino</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Clase:</label>
                            <select class="filter-select" name="flight_class">
                                <option value="economy" <?php echo isset($_GET['flight_class']) && $_GET['flight_class'] == 'economy' ? 'selected' : ''; ?>>Económica</option>
                                <option value="premium" <?php echo isset($_GET['flight_class']) && $_GET['flight_class'] == 'premium' ? 'selected' : ''; ?>>Premium</option>
                                <option value="business" <?php echo isset($_GET['flight_class']) && $_GET['flight_class'] == 'business' ? 'selected' : ''; ?>>Business</option>
                                <option value="first" <?php echo isset($_GET['flight_class']) && $_GET['flight_class'] == 'first' ? 'selected' : ''; ?>>Primera clase</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label>Aerolínea:</label>
                            <select class="filter-select" name="airline">
                                <option value="all" <?php echo !isset($_GET['airline']) || $_GET['airline'] == 'all' ? 'selected' : ''; ?>>Todas</option>
                                <?php 
                                // Obtener aerolíneas únicas
                                $airlines = array_unique(array_column($flights, 'airline'));
                                foreach ($airlines as $airline): 
                                    $selected = isset($_GET['airline']) && $_GET['airline'] == strtolower(str_replace(' ', '_', $airline)) ? 'selected' : '';
                                ?>
                                <option value="<?php echo strtolower(str_replace(' ', '_', $airline)); ?>" <?php echo $selected; ?>>
                                    <?php echo htmlspecialchars($airline); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-small">Aplicar Filtros</button>
                    </form>
                </div>

                <?php
                    // Paginación de vuelos (5 por página)
                    $perPage = 5;
                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    if ($page < 1) { $page = 1; }
                    $totalFlights = is_array($flights) ? count($flights) : 0;
                    $totalPages = max(1, (int)ceil($totalFlights / $perPage));
                    if ($page > $totalPages) { $page = $totalPages; }
                    $startIndex = ($page - 1) * $perPage;
                    $pagedFlights = array_slice($flights, $startIndex, $perPage);

                    // Construir base de query preservando filtros actuales
                    $queryParams = $_GET;
                    unset($queryParams['page']);
                    $basePath = htmlspecialchars($_SERVER['PHP_SELF']);
                ?>
                <div class="flights-list">
                    <?php foreach ($pagedFlights as $flight): ?>
                    <div class="flight-card <?php echo $flight['featured'] ? 'featured' : ''; ?>">
                        <?php if ($flight['featured']): ?>
                        <div class="flight-badge">Mejor Precio</div>
                        <?php endif; ?>
                        <div class="flight-info">
                            <div class="airline">
                                <img src="<?php echo htmlspecialchars($flight['airline_logo']); ?>" alt="<?php echo htmlspecialchars($flight['airline']); ?>" class="airline-logo">
                                <span><?php echo htmlspecialchars($flight['airline']); ?></span>
                            </div>
                            <div class="flight-route">
                                <div class="departure">
                                    <span class="time"><?php echo htmlspecialchars($flight['departure_time']); ?></span>
                                    <span class="airport"><?php echo htmlspecialchars($flight['departure_airport']); ?></span>
                                </div>
                                <div class="flight-duration">
                                    <div class="duration-line"></div>
                                    <span class="duration-text"><?php echo htmlspecialchars($flight['duration']); ?></span>
                                    <?php if ($flight['stops'] > 0): ?>
                                    <span class="stops"><?php echo $flight['stops']; ?> escala<?php echo $flight['stops'] > 1 ? 's' : ''; ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="arrival">
                                    <span class="time"><?php echo htmlspecialchars($flight['arrival_time']); ?></span>
                                    <span class="airport"><?php echo htmlspecialchars($flight['arrival_airport']); ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="flight-price">
                            <?php if ($flight['old_price']): ?>
                            <span class="old-price">$<?php echo number_format($flight['old_price']); ?></span>
                            <?php endif; ?>
                            <span class="price">$<?php echo number_format($flight['price']); ?></span>
                            <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>#contact">
                                <input type="hidden" name="form_type" value="select_flight">
                                <input type="hidden" name="flight_id" value="<?php echo $flight['id']; ?>">
                                <button type="submit" class="btn btn-primary btn-small">Seleccionar</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Affiliates Section -->
    <section id="affiliates" class="affiliates">
        <div class="container">
            <h2 class="section-title">Programa de Afiliados</h2>
            <div class="affiliates-content">
                <div class="affiliates-info">
                    <h3>Únete a nuestro programa y gana dinero</h3>
                    <p>Conviértete en socio de TravelPro y obtén comisiones por cada venta que generes. Es fácil, rápido y rentable.</p>
                    
                    <div class="benefits-list">
                        <div class="benefit-item">
                            <i class="fas fa-percentage"></i>
                            <div>
                                <h4>Hasta 15% de comisión</h4>
                                <p>Gana hasta el 15% por cada reserva realizada</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-chart-line"></i>
                            <div>
                                <h4>Panel de seguimiento</h4>
                                <p>Controla tus ventas y comisiones en tiempo real</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-headset"></i>
                            <div>
                                <h4>Soporte 24/7</h4>
                                <p>Asistencia completa para maximizar tus ganancias</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <i class="fas fa-tools"></i>
                            <div>
                                <h4>Herramientas de marketing</h4>
                                <p>Banners, enlaces y materiales promocionales</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="affiliate-form">
                    <h3>Registro de Afiliado</h3>
                    <form id="affiliate-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>#affiliates">
                        <input type="hidden" name="form_type" value="affiliate">
                        <div class="form-group">
                            <input type="text" name="nombre" placeholder="Nombre completo" required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" placeholder="Correo electrónico" required>
                        </div>
                        <div class="form-group">
                            <input type="tel" name="telefono" placeholder="Teléfono" required>
                        </div>
                        <div class="form-group">
                            <input type="url" name="sitio_web" placeholder="Sitio web (opcional)">
                        </div>
                        <div class="form-group">
                            <select name="tipo_promocion" required>
                                <option value="">Tipo de promoción</option>
                                <option value="website">Sitio web</option>
                                <option value="social">Redes sociales</option>
                                <option value="blog">Blog</option>
                                <option value="email">Email marketing</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <textarea name="estrategia" placeholder="Cuéntanos sobre tu estrategia de promoción" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">Registrarse como Afiliado</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="contact">
        <div class="container">
            <h2 class="section-title">Contáctanos</h2>
            <div class="contact-content">
                <div class="contact-info">
                    <h3>Información de Contacto</h3>
                    <div class="contact-item">
                        <i class="fas fa-map-marker-alt"></i>
                        <div>
                            <h4>Dirección</h4>
                            <p>Calle 123 #45-67, Bogotá, Colombia</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-phone"></i>
                        <div>
                            <h4>Teléfono</h4>
                            <p>+57 (1) 234-5678</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-envelope"></i>
                        <div>
                            <h4>Email</h4>
                            <p>info@travelpro.com</p>
                        </div>
                    </div>
                    <div class="contact-item">
                        <i class="fas fa-clock"></i>
                        <div>
                            <h4>Horario de atención</h4>
                            <p>Lun - Vie: 8:00 AM - 6:00 PM<br>Sáb: 9:00 AM - 2:00 PM</p>
                        </div>
                    </div>
                    
                    <div class="social-links">
                        <h4>Síguenos</h4>
                        <div class="social-icons">
                            <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                            <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
                
                <div class="contact-form">
                    <h3>Envíanos un mensaje</h3>
                    <form id="contact-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>#contact">
                        <input type="hidden" name="form_type" value="contact">
                        <div class="form-row">
                            <div class="form-group">
                                <input type="text" name="nombre" placeholder="Nombre" required>
                            </div>
                            <div class="form-group">
                                <input type="email" name="email" placeholder="Correo electrónico" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <input type="text" name="asunto" placeholder="Asunto" required>
                        </div>
                        <div class="form-group">
                            <select name="tipo_consulta" required>
                                <option value="">Tipo de consulta</option>
                                <option value="tours">Consulta sobre tours</option>
                                <option value="flights">Consulta sobre vuelos</option>
                                <option value="affiliates">Programa de afiliados</option>
                                <option value="support">Soporte técnico</option>
                                <option value="other">Otro</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <textarea name="mensaje" placeholder="Mensaje" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-full">Enviar Mensaje</button>
                    </form>
                    <?php if (!empty($formMessage) && isset($_POST['form_type']) && $_POST['form_type'] == 'contact'): ?>
                    <div class="alert alert-<?php echo $formMessageType; ?>" style="margin-top: 20px;">
                        <?php echo $formMessage; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>TravelPro</h4>
                    <p>Tu agencia de viajes de confianza. Ofrecemos los mejores tours y vuelos con precios increíbles.</p>
                    <div class="footer-social">
                        <a href="#" class="social-icon"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="social-icon"><i class="fab fa-youtube"></i></a>
                    </div>
                </div>
                <div class="footer-section">
                    <h4>Enlaces rápidos</h4>
                    <ul class="footer-links">
                        <li><a href="#tours">Tours</a></li>
                        <li><a href="#flights">Vuelos</a></li>
                        <li><a href="#promotions">Promociones</a></li>
                        <li><a href="#affiliates">Afiliados</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Soporte</h4>
                    <ul class="footer-links">
                        <li><a href="#contact">Contacto</a></li>
                        <li><a href="#">Preguntas frecuentes</a></li>
                        <li><a href="#">Términos y condiciones</a></li>
                        <li><a href="#">Política de privacidad</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Newsletter</h4>
                    <p>Suscríbete para recibir ofertas exclusivas</p>
                    <form class="newsletter-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>#footer">
                        <input type="hidden" name="form_type" value="newsletter">
                        <input type="email" name="email" placeholder="Tu email" required>
                        <button type="submit" class="btn btn-primary">Suscribirse</button>
                    </form>
                    <?php if (!empty($formMessage) && isset($_POST['form_type']) && $_POST['form_type'] == 'newsletter'): ?>
                    <div class="alert alert-<?php echo $formMessageType; ?>" style="margin-top: 10px; font-size: 0.9rem;">
                        <?php echo $formMessage; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="footer-bottom" id="footer">
                <p>&copy; <?php echo date('Y'); ?> <?php echo $siteName; ?>. Todos los derechos reservados.</p>
            </div>
        </div>
    </footer>

    <!-- Scroll to Top Button -->
    <button id="scroll-top" class="scroll-top" title="Volver arriba">
        <i class="fas fa-arrow-up"></i>
    </button>

    <script>
    (function(){
        const form = document.querySelector('#flights-search form');
        if (!form) return;
        const originInput = form.querySelector('input[name="origen"]');
        const destInput = form.querySelector('input[name="destino"]');
        const datalist = document.getElementById('airports-list');
        const radioRound = form.querySelector('input[name="flight_type"][value="round_trip"]');
        const radioOne = form.querySelector('input[name="flight_type"][value="one_way"]');
        const returnGroup = document.getElementById('return-date-group');
        const returnInput = form.querySelector('input[name="fechaVuelta"]');

        function normalizeIATA(input){
            if (!input) return;
            let v = (input.value || '').trim();
            // Si el usuario eligió del datalist con formato "Ciudad (XXX)", extraer el código.
            const inParens = v.match(/\(([A-Za-z]{3})\)/);
            if (inParens) {
                v = inParens[1];
            } else {
                // O si escribió texto, intenta capturar un código de 3 letras aislado.
                const m = v.match(/\b([A-Za-z]{3})\b/);
                if (m) v = m[1];
            }
            input.value = v.toUpperCase();
        }

        // Autocompletado dinámico con debounce
        let debounceTimer = null;
        function fetchSuggestions(q){
            if (!q || q.length < 2) return;
            fetch('locations.php?q=' + encodeURIComponent(q))
                .then(r => r.ok ? r.json() : [])
                .then(list => {
                    if (!Array.isArray(list)) return;
                    // Mantener algunas opciones fijas por defecto
                    const fixed = [
                        {code:'LAX', label:'Los Angeles (LAX) - United States'},
                        {code:'GDL', label:'Guadalajara (GDL) - Mexico'}
                    ];
                    const options = new Map();
                    fixed.forEach(i => options.set(i.code, i.label));
                    list.forEach(item => {
                        if (item && item.code) {
                            options.set(item.code.toUpperCase(), item.label || item.code.toUpperCase());
                        }
                    });
                    // Renderizar
                    if (datalist) {
                        datalist.innerHTML = '';
                        options.forEach((label, code) => {
                            const opt = document.createElement('option');
                            opt.value = code;
                            opt.textContent = label;
                            datalist.appendChild(opt);
                        });
                    }
                })
                .catch(() => {/* silenciar errores de red en UI */});
        }

        function onInput(e){
            const q = e.target.value.trim();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => fetchSuggestions(q), 250);
        }
        originInput && originInput.addEventListener('input', onInput);
        destInput && destInput.addEventListener('input', onInput);

        // Mostrar/ocultar fecha de regreso según tipo de viaje
        function syncReturnVisibility(){
            const isOneWay = radioOne && radioOne.checked;
            if (returnGroup) {
                returnGroup.style.display = isOneWay ? 'none' : '';
            }
            if (returnInput) {
                if (isOneWay) {
                    returnInput.value = '';
                    returnInput.removeAttribute('required');
                    returnInput.setAttribute('disabled', 'disabled');
                } else {
                    returnInput.removeAttribute('disabled');
                    // opcional: dejar sin required para permitir búsquedas flexibles; si quieres required, descomenta
                    // returnInput.setAttribute('required', 'required');
                }
            }
        }
        if (radioRound) radioRound.addEventListener('change', syncReturnVisibility);
        if (radioOne) radioOne.addEventListener('change', syncReturnVisibility);
        // Inicializar estado al cargar
        syncReturnVisibility();

        // Normalizar antes de enviar
        form.addEventListener('submit', function(){
            normalizeIATA(originInput);
            normalizeIATA(destInput);
        });
    })();
    </script>
    <script src="script.js"></script>
</body>
</html>