// ==========================================
// 1. APLICAR EL TEMA LO ANTES POSIBLE (ANTI-FLASH)
// ==========================================
(function() {
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
    } else {
        document.documentElement.removeAttribute('data-theme');
    }
})();

// ==========================================
// 2. CUANDO EL DOM ESTÉ LISTO, CREAR EL BOTÓN Y LA LÓGICA
// ==========================================
document.addEventListener("DOMContentLoaded", () => {
    // Inyectar CSS
    if (!document.getElementById('dark-mode-css')) {
        const link = document.createElement('link');
        link.id = 'dark-mode-css';
        link.rel = 'stylesheet';
        link.href = '/public/css/dark-mode.css';
        document.head.appendChild(link);
    }

    // Crear el Botón
    let btn = document.getElementById('btn-dark-mode');
    if (!btn) {
        btn = document.createElement('button');
        btn.id = 'btn-dark-mode';
        btn.title = "Cambiar tema";
        btn.style.cssText = 'position:fixed; top:20px; right:20px; z-index:999999; font-size:20px; background: #B8860B; border: 2px solid #000; border-radius: 50%; cursor:pointer; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.5);';
        document.body.appendChild(btn);
    }

    // Sincronizar icono
    const currentTheme = document.documentElement.getAttribute('data-theme');
    btn.innerHTML = (currentTheme === 'dark') ? '☀️' : '🌙';

    // Lógica del clic
    btn.addEventListener('click', () => {
        let isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        
        if (isDark) {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
            btn.innerHTML = '🌙';
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
            btn.innerHTML = '☀️';
        }
    });
});