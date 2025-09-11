# 🌍 TravelPro - Tours y Vuelos

Una página web dinámica en PHP, moderna y responsiva para promociones de tours, vuelos y programa de afiliados, con formularios funcionales y procesamiento de datos.

## 🚀 Características

### ✨ Funcionalidades Principales
- **Navegación fija** con menú hamburguesa responsivo
- **Hero section** con búsqueda interactiva de vuelos y tours
- **Promociones** con contador regresivo en tiempo real
- **Catálogo de tours** con tarjetas interactivas y modales
- **Búsqueda de vuelos** con sistema de filtros
- **Programa de afiliados** con formulario de registro
- **Sección de contacto** completa con información y formulario
- **Footer** con enlaces rápidos y newsletter

### 🎨 Diseño
- **Totalmente responsivo** (móvil, tablet, desktop)
- **Animaciones suaves** y efectos visuales modernos
- **Gradientes atractivos** y colores profesionales
- **Tipografía moderna** (Google Fonts - Poppins)
- **Iconos** de Font Awesome
- **Imágenes de alta calidad** de Unsplash

### 💻 Tecnologías
- **PHP** para procesamiento del lado del servidor
- **HTML5** semántico y accesible
- **CSS3** con Flexbox y Grid Layout
- **JavaScript ES6+** modular y moderno
- **Font Awesome 6** para iconografía
- **Google Fonts** para tipografía

## 📁 Estructura del Proyecto

```
websiteWorldwideTravel/
├── index.php           # Página principal en PHP
├── styles.css          # Estilos CSS
├── script.js           # JavaScript interactivo
└── README.md           # Este archivo
```

## 🛠️ Instalación y Uso

### Requisitos
- PHP 7.4 o superior
- Servidor web (Apache, Nginx) o servidor PHP integrado

### Opción 1: Servidor PHP integrado
```bash
# Navega al directorio del proyecto
cd websiteWorldwideTravel

# Inicia el servidor PHP integrado
php -S localhost:8080

# Visita http://localhost:8080 en tu navegador
```

### Opción 2: Servidor web tradicional
1. Coloca los archivos en tu directorio web (htdocs, www, etc.)
2. Configura tu servidor web para servir PHP
3. Accede a través de la URL correspondiente

## 📱 Responsive Design

La página está optimizada para todos los dispositivos:

- **Desktop** (1200px+): Layout completo con sidebar y grid
- **Tablet** (768px - 1199px): Layout adaptado con elementos apilados
- **Móvil** (< 768px): Menú hamburguesa y diseño vertical

## ⚡ Funcionalidades JavaScript

### 🔍 Búsqueda Interactiva
- Tabs dinámicos para vuelos y tours
- Validación de formularios
- Notificaciones de confirmación

### 🎯 Interacciones
- Navegación suave entre secciones
- Efectos hover en tarjetas
- Modales para detalles de tours
- Selección de vuelos con resaltado

### ⏰ Contador Regresivo
- Actualización en tiempo real
- Cálculo automático de fecha final
- Animación de pulsación

### 📋 Formularios
- **Contacto**: Validación completa y procesamiento con PHP
- **Afiliados**: Registro con campos específicos y manejo de datos
- **Newsletter**: Suscripción con validación de email
- **Búsqueda**: Tours y vuelos con filtros dinámicos

## 🎨 Secciones de la Página

### 🏠 Hero Section
- Título principal con efecto de escritura
- Búsqueda de vuelos y tours
- Botones de acción principales
- Imagen de fondo con overlay

### 🎉 Promociones
- Banner destacado con oferta especial
- Contador regresivo animado
- Call-to-action prominente

### 🗺️ Tours Destacados
- Grid de tarjetas interactivas
- Información detallada de cada tour
- Precios con descuentos
- Modales con información extendida

### ✈️ Vuelos en Oferta
- Lista de vuelos disponibles
- Sistema de filtros funcional
- Información de aerolíneas
- Selección interactiva

### 🤝 Programa de Afiliados
- Información de beneficios
- Formulario de registro
- Estadísticas de comisiones
- Herramientas de marketing

### 📞 Contacto
- Información de contacto completa
- Formulario de mensajes
- Redes sociales
- Horarios de atención

## 🔧 Personalización

### Colores
Los colores principales se pueden modificar en `styles.css`:
```css
:root {
  --primary-color: #667eea;
  --secondary-color: #764ba2;
  --accent-color: #ff6b6b;
}
```

### Contenido
- Modifica el texto y variables en `index.php`
- Actualiza los arrays de datos para tours y vuelos
- Cambia las imágenes actualizando las URLs
- Ajusta los precios y ofertas en las estructuras de datos PHP

### Estilos
- Responsive breakpoints en `styles.css`
- Animaciones personalizables
- Layout flexible con CSS Grid y Flexbox

## 🌐 SEO y Accesibilidad

- **HTML semántico** con estructura lógica
- **Meta tags** apropiados
- **Alt text** en todas las imágenes
- **Navegación por teclado** funcional
- **Contraste** adecuado para legibilidad

## 📱 Compatibilidad

### Navegadores Soportados
- Chrome 70+
- Firefox 65+
- Safari 12+
- Edge 79+

### Dispositivos
- Smartphones (320px+)
- Tablets (768px+)
- Laptops (1024px+)
- Desktops (1200px+)

## 🚀 Características Avanzadas

### Animaciones
- Fade in al cargar elementos
- Efectos hover suaves
- Transiciones de página
- Parallax en hero section

### Performance
- Imágenes optimizadas
- CSS minificado listo para producción
- JavaScript modular
- Carga asíncrona de recursos

### UX/UI
- Feedback visual inmediato
- Estados de carga
- Notificaciones contextuales
- Navegación intuitiva

## 📈 Futuras Mejoras

- [ ] Integración con APIs reales de vuelos
- [ ] Sistema de reservas funcional
- [ ] Panel de administración para afiliados
- [ ] Base de datos MySQL para almacenar información
- [ ] Sistema de autenticación de usuarios
- [ ] Chat en vivo
- [ ] Múltiples idiomas
- [ ] PWA (Progressive Web App)
- [ ] Integración con redes sociales

## 🤝 Contribución

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT - ver el archivo [LICENSE.md](LICENSE.md) para detalles.

## 👨‍💻 Autor

**TravelPro Team**
- Website: [TravelPro](#)
- Email: info@travelpro.com

## 🙏 Agradecimientos

- [Unsplash](https://unsplash.com) por las imágenes de alta calidad
- [Font Awesome](https://fontawesome.com) por los iconos
- [Google Fonts](https://fonts.google.com) por la tipografía Poppins
- Comunidad de desarrolladores por las mejores prácticas

---

⭐ **¡Si te gusta este proyecto, dale una estrella!** ⭐