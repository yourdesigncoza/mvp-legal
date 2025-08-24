<?php
/**
 * Mobile Responsive Enhancements
 * Appeal Prospect MVP - Mobile-First Design
 */
?>

<style>
/* Mobile-First Responsive Design */

/* Base Mobile Styles (320px+) */
@media (max-width: 575.98px) {
    /* Container adjustments */
    .container-fluid {
        padding-left: 1rem;
        padding-right: 1rem;
    }
    
    /* Typography scaling */
    .display-1 { font-size: 2.5rem; }
    .display-2 { font-size: 2rem; }
    .display-3 { font-size: 1.75rem; }
    .display-4 { font-size: 1.5rem; }
    .display-5 { font-size: 1.25rem; }
    .display-6 { font-size: 1.1rem; }
    
    h1 { font-size: 1.75rem; }
    h2 { font-size: 1.5rem; }
    h3 { font-size: 1.25rem; }
    h4 { font-size: 1.1rem; }
    
    /* Button adaptations */
    .btn {
        padding: 0.75rem 1rem;
        font-size: 1rem;
        min-height: 48px; /* Touch target minimum */
    }
    
    .btn-sm {
        padding: 0.5rem 0.75rem;
        font-size: 0.9rem;
        min-height: 40px;
    }
    
    .btn-lg {
        padding: 1rem 1.5rem;
        font-size: 1.1rem;
        min-height: 56px;
    }
    
    /* Form improvements */
    .form-control,
    .form-select {
        padding: 0.75rem;
        font-size: 1rem;
        min-height: 48px;
    }
    
    .form-control:focus,
    .form-select:focus {
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
    }
    
    /* Card adjustments */
    .card {
        margin-bottom: 1rem;
        border-radius: 0.75rem;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .card-header,
    .card-footer {
        padding: 0.75rem 1rem;
    }
    
    /* Navigation */
    .navbar-brand {
        font-size: 1.1rem;
    }
    
    .navbar-nav .nav-link {
        padding: 0.75rem 1rem;
        font-size: 1rem;
    }
    
    /* Tables */
    .table-responsive {
        border-radius: 0.5rem;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    .table {
        font-size: 0.9rem;
    }
    
    .table th,
    .table td {
        padding: 0.5rem;
        white-space: nowrap;
    }
    
    /* Modals */
    .modal-dialog {
        margin: 0.5rem;
        max-width: calc(100vw - 1rem);
    }
    
    .modal-content {
        border-radius: 1rem 1rem 0 0;
    }
    
    .modal-header {
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
    }
    
    .modal-body {
        padding: 1rem;
        max-height: 60vh;
        overflow-y: auto;
    }
    
    .modal-footer {
        padding: 1rem;
        border-top: 1px solid #dee2e6;
        flex-direction: column-reverse;
    }
    
    .modal-footer .btn {
        width: 100%;
        margin-bottom: 0.5rem;
    }
    
    .modal-footer .btn:last-child {
        margin-bottom: 0;
    }
    
    /* Alerts */
    .alert {
        border-radius: 0.75rem;
        padding: 1rem;
    }
    
    /* Lists */
    .list-group-item {
        padding: 0.75rem 1rem;
    }
    
    /* Spacing adjustments */
    .py-5 { padding-top: 2rem !important; padding-bottom: 2rem !important; }
    .py-4 { padding-top: 1.5rem !important; padding-bottom: 1.5rem !important; }
    .my-5 { margin-top: 2rem !important; margin-bottom: 2rem !important; }
    .my-4 { margin-top: 1.5rem !important; margin-bottom: 1.5rem !important; }
    
    /* Hero sections */
    .hero-section {
        padding: 2rem 0;
    }
    
    .hero-section .display-4 {
        font-size: 1.75rem;
        line-height: 1.2;
    }
    
    /* Content sections */
    .content-section {
        padding: 1.5rem 0;
    }
    
    /* Fixed bottom actions */
    .fixed-bottom-actions {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: white;
        border-top: 1px solid #dee2e6;
        padding: 1rem;
        z-index: 1000;
        box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
    }
    
    .fixed-bottom-actions .btn {
        width: 100%;
    }
    
    /* Collapsible content */
    .collapse-mobile {
        display: none;
    }
    
    .collapse-mobile.show {
        display: block;
    }
    
    /* Text truncation */
    .text-truncate-mobile {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    /* Image responsiveness */
    .img-mobile {
        width: 100%;
        height: auto;
        border-radius: 0.5rem;
    }
    
    /* Progress bars */
    .progress {
        height: 8px;
        border-radius: 4px;
    }
    
    /* Breadcrumbs */
    .breadcrumb {
        background-color: transparent;
        padding: 0.5rem 0;
        margin-bottom: 1rem;
        font-size: 0.9rem;
    }
    
    /* Pagination */
    .pagination {
        justify-content: center;
    }
    
    .pagination .page-link {
        padding: 0.5rem 0.75rem;
        min-width: 44px;
        text-align: center;
    }
    
    /* File upload areas */
    .file-upload-area {
        padding: 2rem 1rem;
        text-align: center;
        border: 2px dashed #dee2e6;
        border-radius: 0.75rem;
        background-color: #f8f9fa;
    }
    
    .file-upload-area:hover {
        border-color: #0d6efd;
        background-color: rgba(13, 110, 253, 0.05);
    }
    
    /* Loading states */
    .loading-overlay .loading-content {
        margin: 1rem;
        padding: 1.5rem;
        border-radius: 1rem;
    }
    
    /* Error pages */
    .error-container {
        margin: 1rem;
        padding: 2rem 1.5rem;
        border-radius: 1rem;
    }
    
    /* Flash messages */
    .flash-messages-container .alert {
        margin-left: -1rem;
        margin-right: -1rem;
        border-radius: 0;
        border-left: none;
        border-right: none;
    }
}

/* Tablet Portrait (576px - 767.98px) */
@media (min-width: 576px) and (max-width: 767.98px) {
    .container {
        max-width: 540px;
    }
    
    /* Button groups */
    .btn-group-vertical .btn {
        width: 100%;
    }
    
    /* Card columns */
    .card-columns {
        column-count: 2;
        column-gap: 1rem;
    }
    
    /* Navigation improvements */
    .navbar-expand-md .navbar-nav {
        flex-direction: column;
        width: 100%;
    }
    
    .navbar-expand-md .navbar-nav .nav-link {
        padding: 0.75rem 0;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    /* Form layouts */
    .form-floating {
        margin-bottom: 1rem;
    }
    
    /* Modal adjustments */
    .modal-lg {
        max-width: 90vw;
    }
}

/* Tablet Landscape & Small Desktop (768px - 991.98px) */
@media (min-width: 768px) and (max-width: 991.98px) {
    .container {
        max-width: 720px;
    }
    
    /* Grid adjustments */
    .col-md-6 {
        margin-bottom: 1rem;
    }
    
    /* Card deck improvements */
    .card-deck .card {
        margin-bottom: 1rem;
    }
    
    /* Navigation */
    .navbar-expand-lg .navbar-nav .nav-link {
        padding: 0.5rem 0.75rem;
    }
    
    /* Sidebar */
    .sidebar {
        position: static;
        width: 100%;
        margin-bottom: 2rem;
    }
}

/* Large Screens (992px+) */
@media (min-width: 992px) {
    /* Enhanced interactions */
    .btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }
    
    .card:hover {
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        transition: box-shadow 0.3s ease;
    }
    
    /* Sticky elements */
    .sticky-top {
        position: sticky;
        top: 2rem;
        z-index: 1020;
    }
    
    /* Advanced layouts */
    .masonry-layout {
        column-count: 3;
        column-gap: 2rem;
    }
    
    .masonry-layout .card {
        break-inside: avoid;
        margin-bottom: 2rem;
    }
}

/* Touch Device Optimizations */
@media (hover: none) and (pointer: coarse) {
    /* Larger touch targets */
    .btn,
    .form-control,
    .nav-link {
        min-height: 48px;
        padding: 0.75rem 1rem;
    }
    
    /* Remove hover states */
    .btn:hover,
    .card:hover {
        transform: none;
        box-shadow: initial;
    }
    
    /* Improved tap targets */
    .pagination .page-link {
        min-width: 48px;
        min-height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Touch-friendly dropdowns */
    .dropdown-menu {
        border-radius: 0.75rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
    }
    
    .dropdown-item {
        padding: 0.75rem 1rem;
        font-size: 1rem;
    }
}

/* Print Styles */
@media print {
    /* Hide non-essential elements */
    .navbar,
    .sidebar,
    .btn,
    .modal,
    .fixed-bottom-actions,
    .flash-messages-container,
    .loading-overlay {
        display: none !important;
    }
    
    /* Optimize content */
    .container {
        width: 100% !important;
        max-width: none !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
        break-inside: avoid;
        margin-bottom: 1rem;
    }
    
    /* Typography */
    body {
        font-size: 12pt;
        line-height: 1.4;
        color: #000 !important;
    }
    
    h1 { font-size: 18pt; }
    h2 { font-size: 16pt; }
    h3 { font-size: 14pt; }
    h4 { font-size: 13pt; }
    
    /* Links */
    a {
        text-decoration: underline;
        color: #000 !important;
    }
    
    a[href]:after {
        content: " (" attr(href) ")";
        font-size: 0.8em;
    }
    
    /* Page breaks */
    .page-break {
        page-break-before: always;
    }
    
    .avoid-break {
        break-inside: avoid;
    }
}

/* High DPI (Retina) Displays */
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
    /* Sharper borders */
    .border {
        border-width: 0.5px;
    }
    
    /* Enhanced shadows */
    .shadow-sm {
        box-shadow: 0 0.125rem 0.5rem rgba(0, 0, 0, 0.075);
    }
    
    .shadow {
        box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15);
    }
}

/* Landscape Phone Optimization */
@media (max-height: 500px) and (orientation: landscape) {
    /* Compact layout */
    .navbar {
        min-height: auto;
        padding: 0.25rem 1rem;
    }
    
    .modal-dialog {
        margin: 0.25rem;
    }
    
    .modal-body {
        max-height: 50vh;
        overflow-y: auto;
    }
    
    /* Reduce spacing */
    .py-5 { padding-top: 1rem !important; padding-bottom: 1rem !important; }
    .my-5 { margin-top: 1rem !important; margin-bottom: 1rem !important; }
}

/* Dark Mode Adaptations */
@media (prefers-color-scheme: dark) {
    .file-upload-area {
        background-color: #343a40;
        border-color: #495057;
        color: #fff;
    }
    
    .file-upload-area:hover {
        border-color: #0d6efd;
        background-color: rgba(13, 110, 253, 0.1);
    }
    
    .fixed-bottom-actions {
        background-color: #212529;
        border-color: #495057;
        color: #fff;
    }
    
    .modal-content {
        background-color: #212529;
        color: #fff;
    }
}

/* Animation Preferences */
@media (prefers-reduced-motion: no-preference) {
    /* Smooth transitions */
    .btn,
    .card,
    .form-control,
    .modal {
        transition: all 0.2s ease;
    }
    
    /* Scroll animations */
    .fade-in-scroll {
        opacity: 0;
        transform: translateY(20px);
        transition: all 0.6s ease;
    }
    
    .fade-in-scroll.visible {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Custom utility classes for mobile */
.mobile-only {
    display: block;
}

.desktop-only {
    display: none;
}

@media (min-width: 768px) {
    .mobile-only {
        display: none;
    }
    
    .desktop-only {
        display: block;
    }
}

/* Mobile-specific components */
.mobile-card {
    border-radius: 0;
    border-left: 0;
    border-right: 0;
    margin-bottom: 0;
}

.mobile-card + .mobile-card {
    border-top: 0;
}

.mobile-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.mobile-list-item {
    padding: 1rem;
    border-bottom: 1px solid #dee2e6;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.mobile-list-item:last-child {
    border-bottom: 0;
}

/* Swipe gestures (requires JavaScript implementation) */
.swipeable {
    touch-action: pan-y;
    user-select: none;
}

.swipe-indicator {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    padding: 0.5rem;
    background: rgba(0, 0, 0, 0.1);
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.2s ease;
}

.swipe-indicator.left {
    left: 1rem;
}

.swipe-indicator.right {
    right: 1rem;
}

.swipe-indicator.visible {
    opacity: 1;
}
</style>

<script>
/**
 * Mobile Responsive Enhancements
 */
class MobileEnhancements {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupTouchOptimizations();
        this.setupSwipeGestures();
        this.setupScrollAnimations();
        this.setupMobileNavigation();
        this.setupViewportHandling();
    }
    
    setupTouchOptimizations() {
        // Add touch classes for better touch handling
        document.addEventListener('touchstart', () => {
            document.body.classList.add('touch-device');
        }, { once: true });
        
        // Prevent zoom on double tap for form inputs
        let lastTouchEnd = 0;
        document.addEventListener('touchend', (e) => {
            const now = Date.now();
            if (now - lastTouchEnd <= 300) {
                e.preventDefault();
            }
            lastTouchEnd = now;
        });
        
        // Improve button feedback
        const buttons = document.querySelectorAll('.btn, button');
        buttons.forEach(button => {
            button.addEventListener('touchstart', () => {
                button.style.transform = 'scale(0.98)';
            });
            
            button.addEventListener('touchend', () => {
                setTimeout(() => {
                    button.style.transform = '';
                }, 100);
            });
        });
    }
    
    setupSwipeGestures() {
        const swipeableElements = document.querySelectorAll('.swipeable');
        
        swipeableElements.forEach(element => {
            let startX = 0;
            let startY = 0;
            let distX = 0;
            let distY = 0;
            
            element.addEventListener('touchstart', (e) => {
                const touch = e.touches[0];
                startX = touch.clientX;
                startY = touch.clientY;
            }, { passive: true });
            
            element.addEventListener('touchmove', (e) => {
                if (!startX || !startY) return;
                
                const touch = e.touches[0];
                distX = touch.clientX - startX;
                distY = touch.clientY - startY;
                
                // Show swipe indicators
                if (Math.abs(distX) > Math.abs(distY) && Math.abs(distX) > 30) {
                    const indicator = element.querySelector(distX > 0 ? '.swipe-indicator.left' : '.swipe-indicator.right');
                    if (indicator) {
                        indicator.classList.add('visible');
                    }
                }
            }, { passive: true });
            
            element.addEventListener('touchend', () => {
                // Hide all indicators
                const indicators = element.querySelectorAll('.swipe-indicator');
                indicators.forEach(indicator => {
                    indicator.classList.remove('visible');
                });
                
                // Handle swipe
                if (Math.abs(distX) > Math.abs(distY) && Math.abs(distX) > 100) {
                    const direction = distX > 0 ? 'right' : 'left';
                    this.handleSwipe(element, direction);
                }
                
                // Reset
                startX = 0;
                startY = 0;
                distX = 0;
                distY = 0;
            }, { passive: true });
        });
    }
    
    handleSwipe(element, direction) {
        // Dispatch custom swipe event
        const swipeEvent = new CustomEvent('swipe', {
            detail: { direction, element }
        });
        
        element.dispatchEvent(swipeEvent);
        
        // Default swipe handlers
        if (element.classList.contains('modal')) {
            if (direction === 'down') {
                const closeBtn = element.querySelector('[data-bs-dismiss="modal"]');
                if (closeBtn) closeBtn.click();
            }
        }
    }
    
    setupScrollAnimations() {
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return; // Respect user preference
        }
        
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);
        
        const animatedElements = document.querySelectorAll('.fade-in-scroll');
        animatedElements.forEach(el => observer.observe(el));
    }
    
    setupMobileNavigation() {
        // Improve mobile menu behavior
        const navbarToggler = document.querySelector('.navbar-toggler');
        const navbarCollapse = document.querySelector('.navbar-collapse');
        
        if (navbarToggler && navbarCollapse) {
            // Close menu when clicking outside
            document.addEventListener('click', (e) => {
                if (!navbarToggler.contains(e.target) && 
                    !navbarCollapse.contains(e.target) && 
                    navbarCollapse.classList.contains('show')) {
                    navbarToggler.click();
                }
            });
            
            // Close menu when clicking on a link (mobile)
            if (window.innerWidth <= 768) {
                const navLinks = navbarCollapse.querySelectorAll('.nav-link');
                navLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        if (navbarCollapse.classList.contains('show')) {
                            navbarToggler.click();
                        }
                    });
                });
            }
        }
    }
    
    setupViewportHandling() {
        // Handle viewport changes (keyboard open/close on mobile)
        let viewportHeight = window.innerHeight;
        
        window.addEventListener('resize', () => {
            const currentHeight = window.innerHeight;
            const heightDiff = viewportHeight - currentHeight;
            
            // Keyboard likely opened/closed
            if (Math.abs(heightDiff) > 150) {
                document.body.classList.toggle('keyboard-open', heightDiff > 150);
                
                // Scroll to focused input
                if (heightDiff > 150) {
                    setTimeout(() => {
                        const activeElement = document.activeElement;
                        if (activeElement && (activeElement.tagName === 'INPUT' || activeElement.tagName === 'TEXTAREA')) {
                            activeElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    }, 300);
                }
            }
            
            viewportHeight = currentHeight;
        });
        
        // Handle orientation change
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                // Fix viewport height on mobile
                document.documentElement.style.setProperty('--vh', `${window.innerHeight * 0.01}px`);
            }, 100);
        });
        
        // Initial viewport height fix
        document.documentElement.style.setProperty('--vh', `${window.innerHeight * 0.01}px`);
    }
    
    // Utility methods
    isMobile() {
        return window.innerWidth <= 768;
    }
    
    isTouch() {
        return 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    }
    
    getViewportSize() {
        return {
            width: Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0),
            height: Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0)
        };
    }
}

// Initialize mobile enhancements
document.addEventListener('DOMContentLoaded', () => {
    window.mobileEnhancements = new MobileEnhancements();
});

// Global utility functions
window.isMobile = () => window.mobileEnhancements?.isMobile() ?? window.innerWidth <= 768;
window.isTouch = () => window.mobileEnhancements?.isTouch() ?? false;

// Responsive image loading
function loadResponsiveImages() {
    const images = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        images.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for older browsers
        images.forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy');
        });
    }
}

// Call on page load
document.addEventListener('DOMContentLoaded', loadResponsiveImages);
</script>