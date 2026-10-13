/**
 * Sidebar Collapsible - Sistema de Almacén
 * Maneja el toggle del sidebar y el overlay móvil
 */

document.addEventListener('DOMContentLoaded', function() {
    // Elementos del DOM
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const contentWrapper = document.getElementById('contentWrapper');
    
    // Verificar si los elementos existen
    if (!sidebarToggle || !sidebar || !contentWrapper) {
        console.warn('Elementos del sidebar no encontrados');
        return;
    }
    
    // Crear overlay para móvil si no existe
    let overlay = document.querySelector('.sidebar-overlay');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.className = 'sidebar-overlay';
        document.body.appendChild(overlay);
    }
    
    // Recuperar estado guardado en localStorage
    const savedState = localStorage.getItem('sidebarCollapsed');
    
    // Si es escritorio y hay estado guardado, aplicarlo
    if (window.innerWidth > 768 && savedState === 'true') {
        sidebar.classList.add('collapsed');
        contentWrapper.classList.add('expanded');
    }
    
    // Evento click en el botón toggle
    sidebarToggle.addEventListener('click', function(e) {
        e.preventDefault();
        toggleSidebar();
    });
    
    // Función para toggle del sidebar
    function toggleSidebar() {
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            // Modo móvil: mostrar/ocultar overlay
            sidebar.classList.toggle('mobile-open');
            overlay.classList.toggle('active');
            document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
        } else {
            // Modo escritorio: colapsar/expandir
            sidebar.classList.toggle('collapsed');
            contentWrapper.classList.toggle('expanded');
            
            // Guardar estado en localStorage
            const isCollapsed = sidebar.classList.contains('collapsed');
            localStorage.setItem('sidebarCollapsed', isCollapsed);
            
            // Disparar evento para que otros componentes se ajusten
            window.dispatchEvent(new CustomEvent('sidebarToggled', {
                detail: { collapsed: isCollapsed }
            }));
        }
    }
    
    // Cerrar sidebar móvil al hacer click en overlay
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('mobile-open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    // Reajustar al cambiar el tamaño de la ventana
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            const isMobile = window.innerWidth <= 768;
            
            if (!isMobile) {
                // Cambió a escritorio
                sidebar.classList.remove('mobile-open');
                overlay.classList.remove('active');
                document.body.style.overflow = '';
                
                // Aplicar estado guardado
                const savedCollapsed = localStorage.getItem('sidebarCollapsed');
                if (savedCollapsed === 'true') {
                    sidebar.classList.add('collapsed');
                    contentWrapper.classList.add('expanded');
                } else {
                    sidebar.classList.remove('collapsed');
                    contentWrapper.classList.remove('expanded');
                }
            } else {
                // Cambió a móvil - resetear estados de escritorio
                sidebar.classList.remove('collapsed');
                contentWrapper.classList.remove('expanded');
            }
        }, 150);
    });
    
    // Manejar submenús en modo colapsado
    function setupSubmenus() {
        const submenuToggles = document.querySelectorAll('.sidebar-submenu-toggle');
        
        submenuToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                const isCollapsed = sidebar.classList.contains('collapsed');
                const isMobile = window.innerWidth <= 768;
                
                if (isCollapsed && !isMobile) {
                    // En modo colapsado de escritorio, los submenús se muestran como dropdown flotante
                    e.preventDefault();
                    
                    // Cerrar otros submenús abiertos
                    document.querySelectorAll('.sidebar .dropdown-menu.show').forEach(menu => {
                        if (menu !== toggle.nextElementSibling) {
                            menu.classList.remove('show');
                        }
                    });
                    
                    const targetId = toggle.getAttribute('data-bs-target');
                    if (targetId) {
                        const targetMenu = document.querySelector(targetId);
                        if (targetMenu) {
                            targetMenu.classList.toggle('show');
                        }
                    }
                }
                // En modo normal, Bootstrap Collapse maneja el evento
            });
        });
    }
    
    // Inicializar submenús
    setupSubmenus();
    
    // Cerrar submenús flotantes al hacer click fuera
    document.addEventListener('click', function(e) {
        const isCollapsed = sidebar.classList.contains('collapsed');
        const isMobile = window.innerWidth <= 768;
        
        if (isCollapsed && !isMobile) {
            if (!e.target.closest('.sidebar')) {
                document.querySelectorAll('.sidebar .dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        }
    });
});

// Función global para toggle (útil para debugging)
window.toggleSidebar = function() {
    const event = new Event('click');
    document.getElementById('sidebarToggle')?.dispatchEvent(event);
};