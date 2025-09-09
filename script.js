// DOM Elements
const hamburger = document.getElementById('hamburger');
const navMenu = document.getElementById('nav-menu');
const navbar = document.getElementById('navbar');
const scrollTopBtn = document.getElementById('scroll-top');

// Mobile Navigation Toggle
hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    navMenu.classList.toggle('active');
});

// Close mobile menu when clicking on a link
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', () => {
        hamburger.classList.remove('active');
        navMenu.classList.remove('active');
    });
});

// Navbar scroll effect
window.addEventListener('scroll', () => {
    if (window.scrollY > 100) {
        navbar.classList.add('scrolled');
    } else {
        navbar.classList.remove('scrolled');
    }

    // Show/hide scroll to top button
    if (window.scrollY > 300) {
        scrollTopBtn.classList.add('show');
    } else {
        scrollTopBtn.classList.remove('show');
    }
});

// Scroll to top functionality
scrollTopBtn.addEventListener('click', () => {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});

// Search tabs functionality
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const tabName = btn.getAttribute('data-tab');
        
        // Remove active class from all tabs and forms
        document.querySelectorAll('.tab-btn').forEach(tab => tab.classList.remove('active'));
        document.querySelectorAll('.search-form').forEach(form => form.classList.remove('active'));
        
        // Add active class to clicked tab and corresponding form
        btn.classList.add('active');
        document.getElementById(tabName + '-search').classList.add('active');
    });
});

// Countdown timer for promotions
function updateCountdown() {
    const endDate = new Date();
    endDate.setDate(endDate.getDate() + 30); // 30 days from now
    
    const now = new Date().getTime();
    const distance = endDate.getTime() - now;
    
    const days = Math.floor(distance / (1000 * 60 * 60 * 24));
    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((distance % (1000 * 60)) / 1000);
    
    document.getElementById('days').textContent = days.toString().padStart(2, '0');
    document.getElementById('hours').textContent = hours.toString().padStart(2, '0');
    document.getElementById('minutes').textContent = minutes.toString().padStart(2, '0');
    document.getElementById('seconds').textContent = seconds.toString().padStart(2, '0');
    
    if (distance < 0) {
        document.getElementById('countdown').innerHTML = "¡Oferta expirada!";
    }
}

// Update countdown every second
setInterval(updateCountdown, 1000);
updateCountdown(); // Initial call

// Smooth scrolling for navigation links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
            target.scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
});

// Form handling
class FormHandler {
    constructor() {
        this.setupForms();
    }

    setupForms() {
        // Contact form
        const contactForm = document.getElementById('contact-form');
        if (contactForm) {
            contactForm.addEventListener('submit', (e) => this.handleContactForm(e));
        }

        // Affiliate form
        const affiliateForm = document.getElementById('affiliate-form');
        if (affiliateForm) {
            affiliateForm.addEventListener('submit', (e) => this.handleAffiliateForm(e));
        }

        // Newsletter form
        const newsletterForm = document.querySelector('.newsletter-form');
        if (newsletterForm) {
            newsletterForm.addEventListener('submit', (e) => this.handleNewsletterForm(e));
        }

        // Search forms
        const flightSearchForm = document.getElementById('flights-search');
        const tourSearchForm = document.getElementById('tours-search');
        
        if (flightSearchForm) {
            flightSearchForm.addEventListener('submit', (e) => this.handleFlightSearch(e));
        }
        
        if (tourSearchForm) {
            tourSearchForm.addEventListener('submit', (e) => this.handleTourSearch(e));
        }
    }

    handleContactForm(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        this.showLoading(e.target);

        // Simulate API call
        setTimeout(() => {
            this.hideLoading(e.target);
            this.showNotification('¡Mensaje enviado con éxito! Te contactaremos pronto.', 'success');
            e.target.reset();
        }, 2000);
    }

    handleAffiliateForm(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        this.showLoading(e.target);

        // Simulate API call
        setTimeout(() => {
            this.hideLoading(e.target);
            this.showNotification('¡Registro de afiliado exitoso! Recibirás un email con más información.', 'success');
            e.target.reset();
        }, 2000);
    }

    handleNewsletterForm(e) {
        e.preventDefault();
        const email = e.target.querySelector('input[type="email"]').value;

        if (!this.validateEmail(email)) {
            this.showNotification('Por favor ingresa un email válido', 'error');
            return;
        }

        this.showLoading(e.target);

        // Simulate API call
        setTimeout(() => {
            this.hideLoading(e.target);
            this.showNotification('¡Suscripción exitosa! Recibirás nuestras mejores ofertas.', 'success');
            e.target.querySelector('input').value = '';
        }, 1500);
    }

    handleFlightSearch(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        // Validate required fields
        if (!data.origen || !data.destino || !data.fechaIda) {
            this.showNotification('Por favor completa todos los campos requeridos', 'error');
            return;
        }

        this.showLoading(e.target);

        // Simulate search
        setTimeout(() => {
            this.hideLoading(e.target);
            this.showNotification('Búsqueda realizada. Mostrando resultados disponibles.', 'info');
            // Scroll to flights section
            document.getElementById('flights').scrollIntoView({ behavior: 'smooth' });
        }, 2000);
    }

    handleTourSearch(e) {
        e.preventDefault();
        const formData = new FormData(e.target);
        const data = Object.fromEntries(formData);

        if (!data.destino || !data.fecha) {
            this.showNotification('Por favor completa todos los campos requeridos', 'error');
            return;
        }

        this.showLoading(e.target);

        // Simulate search
        setTimeout(() => {
            this.hideLoading(e.target);
            this.showNotification('Búsqueda realizada. Mostrando tours disponibles.', 'info');
            // Scroll to tours section
            document.getElementById('tours').scrollIntoView({ behavior: 'smooth' });
        }, 2000);
    }

    validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    showLoading(form) {
        form.classList.add('loading');
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Procesando...';
        }
    }

    hideLoading(form) {
        form.classList.remove('loading');
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = false;
            // Restore original text
            if (form.id === 'contact-form') {
                submitBtn.textContent = 'Enviar Mensaje';
            } else if (form.id === 'affiliate-form') {
                submitBtn.textContent = 'Registrarse como Afiliado';
            } else if (form.classList.contains('newsletter-form')) {
                submitBtn.textContent = 'Suscribirse';
            } else {
                submitBtn.textContent = 'Buscar';
            }
        }
    }

    showNotification(message, type = 'info') {
        // Remove existing notification
        const existingNotification = document.querySelector('.notification');
        if (existingNotification) {
            existingNotification.remove();
        }

        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <span class="notification-message">${message}</span>
                <button class="notification-close">&times;</button>
            </div>
        `;

        // Add to document
        document.body.appendChild(notification);

        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 100);

        // Auto hide after 5 seconds
        setTimeout(() => {
            this.hideNotification(notification);
        }, 5000);

        // Close button functionality
        notification.querySelector('.notification-close').addEventListener('click', () => {
            this.hideNotification(notification);
        });
    }

    hideNotification(notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }
}

// Tour and flight card interactions
class CardInteractions {
    constructor() {
        this.setupCardHovers();
        this.setupButtons();
    }

    setupCardHovers() {
        // Tour cards
        document.querySelectorAll('.tour-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-10px)';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });

        // Flight cards
        document.querySelectorAll('.flight-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-5px)';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
    }

    setupButtons() {
        // Tour "Ver Detalles" buttons
        document.querySelectorAll('.tour-card .btn-primary').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const tourCard = btn.closest('.tour-card');
                const tourName = tourCard.querySelector('h3').textContent;
                this.showTourModal(tourName, tourCard);
            });
        });

        // Flight "Seleccionar" buttons
        document.querySelectorAll('.flight-card .btn-primary').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                const flightCard = btn.closest('.flight-card');
                this.selectFlight(flightCard);
            });
        });
    }

    showTourModal(tourName, card) {
        const modal = this.createModal();
        const price = card.querySelector('.new-price').textContent;
        const location = card.querySelector('.location').textContent;
        const description = card.querySelector('.description').textContent;
        const imageUrl = card.querySelector('img').src;

        modal.innerHTML = `
            <div class="modal-overlay">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2>${tourName}</h2>
                        <button class="modal-close">&times;</button>
                    </div>
                    <div class="modal-body">
                        <img src="${imageUrl}" alt="${tourName}" class="modal-image">
                        <div class="modal-info">
                            <p class="modal-location">${location}</p>
                            <p class="modal-description">${description}</p>
                            <div class="modal-features">
                                <h4>Incluye:</h4>
                                <ul>
                                    <li>✈️ Vuelo ida y vuelta</li>
                                    <li>🏨 Alojamiento en hotel</li>
                                    <li>🍽️ Desayuno incluido</li>
                                    <li>🗺️ Tours guiados</li>
                                    <li>📱 App móvil con información</li>
                                </ul>
                            </div>
                            <div class="modal-price">
                                <span class="price-label">Precio por persona:</span>
                                <span class="price-value">${price}</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" onclick="this.closest('.modal').remove()">Cerrar</button>
                        <button class="btn btn-primary" onclick="this.reserveTour('${tourName}')">Reservar Ahora</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(modal);
        setTimeout(() => modal.classList.add('show'), 100);

        // Close modal functionality
        modal.querySelector('.modal-close').addEventListener('click', () => {
            this.closeModal(modal);
        });

        modal.querySelector('.modal-overlay').addEventListener('click', (e) => {
            if (e.target === modal.querySelector('.modal-overlay')) {
                this.closeModal(modal);
            }
        });
    }

    selectFlight(flightCard) {
        const airline = flightCard.querySelector('.airline span').textContent;
        const price = flightCard.querySelector('.price').textContent;
        const departure = flightCard.querySelector('.departure .time').textContent;
        const arrival = flightCard.querySelector('.arrival .time').textContent;

        // Highlight selected flight
        document.querySelectorAll('.flight-card').forEach(card => {
            card.classList.remove('selected');
        });
        flightCard.classList.add('selected');

        // Show selection notification
        const formHandler = new FormHandler();
        formHandler.showNotification(`Vuelo seleccionado: ${airline} - ${departure} a ${arrival} por ${price}`, 'success');

        // Scroll to contact form
        setTimeout(() => {
            document.getElementById('contact').scrollIntoView({ behavior: 'smooth' });
        }, 1500);
    }

    createModal() {
        const modal = document.createElement('div');
        modal.className = 'modal';
        return modal;
    }

    closeModal(modal) {
        modal.classList.remove('show');
        setTimeout(() => modal.remove(), 300);
    }

    reserveTour(tourName) {
        const formHandler = new FormHandler();
        formHandler.showNotification(`Iniciando reserva para: ${tourName}`, 'info');
        
        // Scroll to contact form
        setTimeout(() => {
            document.getElementById('contact').scrollIntoView({ behavior: 'smooth' });
            // Pre-fill the subject
            const subjectInput = document.querySelector('#contact-form input[placeholder="Asunto"]');
            if (subjectInput) {
                subjectInput.value = `Reserva: ${tourName}`;
            }
        }, 1000);
    }
}

// Animation on scroll
class ScrollAnimations {
    constructor() {
        this.observeElements();
    }

    observeElements() {
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);

        // Observe elements
        document.querySelectorAll('.tour-card, .flight-card, .contact-item, .benefit-item').forEach(el => {
            observer.observe(el);
        });
    }
}

// Filter functionality for flights
class FlightFilter {
    constructor() {
        this.setupFilters();
    }

    setupFilters() {
        document.querySelectorAll('.filter-select').forEach(select => {
            select.addEventListener('change', () => this.applyFilters());
        });
    }

    applyFilters() {
        const filters = {
            type: document.querySelector('.filter-select').value,
            class: document.querySelectorAll('.filter-select')[1]?.value,
            airline: document.querySelectorAll('.filter-select')[2]?.value
        };

        // Simulate filtering (in real app, this would filter actual data)
        const flightCards = document.querySelectorAll('.flight-card');
        
        flightCards.forEach(card => {
            // Add filter animation
            card.style.opacity = '0.5';
            setTimeout(() => {
                card.style.opacity = '1';
            }, 300);
        });

        // Show filter notification
        const formHandler = new FormHandler();
        formHandler.showNotification('Filtros aplicados', 'info');
    }
}

// Initialize all functionality when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new FormHandler();
    new CardInteractions();
    new ScrollAnimations();
    new FlightFilter();

    // Add some dynamic behavior to the page
    addDynamicEffects();
});

// Dynamic effects
function addDynamicEffects() {
    // Parallax effect for hero section
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const hero = document.querySelector('.hero');
        if (hero) {
            hero.style.transform = `translateY(${scrolled * 0.5}px)`;
        }
    });

    // Typing effect for hero title
    const heroTitle = document.querySelector('.hero-title');
    if (heroTitle) {
        const text = heroTitle.textContent;
        heroTitle.textContent = '';
        let i = 0;
        
        const typeWriter = () => {
            if (i < text.length) {
                heroTitle.textContent += text.charAt(i);
                i++;
                setTimeout(typeWriter, 100);
            }
        };
        
        setTimeout(typeWriter, 1000);
    }

    // Add floating animation to cards
    document.querySelectorAll('.tour-card').forEach((card, index) => {
        card.style.animationDelay = `${index * 0.2}s`;
        card.classList.add('float-animation');
    });
}

// Add CSS for additional animations and modal
const additionalCSS = `
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    z-index: 10000;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.modal.show {
    opacity: 1;
    visibility: visible;
}

.modal-overlay {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.8);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.modal-content {
    background: white;
    border-radius: 20px;
    max-width: 600px;
    width: 100%;
    max-height: 90vh;
    overflow-y: auto;
    transform: scale(0.7);
    transition: transform 0.3s ease;
}

.modal.show .modal-content {
    transform: scale(1);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 30px;
    border-bottom: 1px solid #eee;
}

.modal-close {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background 0.3s ease;
}

.modal-close:hover {
    background: #f0f0f0;
}

.modal-body {
    padding: 30px;
}

.modal-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    border-radius: 15px;
    margin-bottom: 20px;
}

.modal-location {
    color: #667eea;
    font-weight: 500;
    margin-bottom: 15px;
}

.modal-description {
    margin-bottom: 20px;
    line-height: 1.6;
}

.modal-features h4 {
    margin-bottom: 15px;
    color: #2c3e50;
}

.modal-features ul {
    list-style: none;
    padding: 0;
}

.modal-features li {
    padding: 8px 0;
    border-bottom: 1px solid #f0f0f0;
}

.modal-price {
    margin-top: 20px;
    padding: 20px;
    background: #f8f9fa;
    border-radius: 15px;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.price-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #667eea;
}

.modal-footer {
    padding: 20px 30px;
    border-top: 1px solid #eee;
    display: flex;
    gap: 15px;
    justify-content: flex-end;
}

.notification {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    z-index: 10001;
    transform: translateX(400px);
    transition: transform 0.3s ease;
    max-width: 400px;
}

.notification.show {
    transform: translateX(0);
}

.notification-content {
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.notification-success {
    border-left: 4px solid #28a745;
}

.notification-error {
    border-left: 4px solid #dc3545;
}

.notification-info {
    border-left: 4px solid #007bff;
}

.notification-close {
    background: none;
    border: none;
    font-size: 20px;
    cursor: pointer;
    padding: 0;
    margin-left: 15px;
    opacity: 0.7;
}

.notification-close:hover {
    opacity: 1;
}

.animate-in {
    animation: slideInUp 0.6s ease forwards;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.float-animation {
    animation: float 6s ease-in-out infinite;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0px);
    }
    50% {
        transform: translateY(-10px);
    }
}

.flight-card.selected {
    border-color: #667eea;
    box-shadow: 0 10px 30px rgba(102, 126, 234, 0.2);
    background: linear-gradient(135deg, #f8f9ff 0%, #ffffff 100%);
}

@media (max-width: 768px) {
    .modal-content {
        margin: 20px;
        max-height: calc(100vh - 40px);
    }
    
    .modal-footer {
        flex-direction: column;
    }
    
    .notification {
        right: 10px;
        left: 10px;
        max-width: none;
    }
}
`;

// Inject additional CSS
const style = document.createElement('style');
style.textContent = additionalCSS;
document.head.appendChild(style);