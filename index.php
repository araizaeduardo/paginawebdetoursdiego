<?php
// Iniciar sesión para manejar datos de usuario
session_start();

// Configuración
$siteName = "TravelPro";
$siteTagline = "Tours y Vuelos";

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
                // Procesar búsqueda de vuelos
                $formMessage = "Búsqueda realizada. Mostrando resultados disponibles.";
                $formMessageType = 'info';
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

// Datos para vuelos
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
            <div class="search-content">
                <div class="search-form active" id="flights-search">
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>#flights">
                        <input type="hidden" name="form_type" value="flight_search">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Origen</label>
                                <input type="text" name="origen" placeholder="Ciudad de origen" required>
                            </div>
                            <div class="form-group">
                                <label>Destino</label>
                                <input type="text" name="destino" placeholder="Ciudad de destino" required>
                            </div>
                            <div class="form-group">
                                <label>Fecha ida</label>
                                <input type="date" name="fechaIda" required>
                            </div>
                            <div class="form-group">
                                <label>Fecha vuelta</label>
                                <input type="date" name="fechaVuelta">
                            </div>
                            <button type="submit" class="btn btn-search">Buscar Vuelos</button>
                        </div>
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
                    <form method="GET" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>#flights">
                        <div class="filter-group">
                            <label>Tipo de vuelo:</label>
                            <select class="filter-select" name="flight_type">
                                <option value="round_trip" <?php echo isset($_GET['flight_type']) && $_GET['flight_type'] == 'round_trip' ? 'selected' : ''; ?>>Ida y vuelta</option>
                                <option value="one_way" <?php echo isset($_GET['flight_type']) && $_GET['flight_type'] == 'one_way' ? 'selected' : ''; ?>>Solo ida</option>
                                <option value="multi_city" <?php echo isset($_GET['flight_type']) && $_GET['flight_type'] == 'multi_city' ? 'selected' : ''; ?>>Multidestino</option>
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

                <div class="flights-list">
                    <?php foreach ($flights as $flight): ?>
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

    <script src="script.js"></script>
</body>
</html>