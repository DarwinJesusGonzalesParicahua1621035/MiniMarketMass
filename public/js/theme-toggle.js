document.addEventListener("DOMContentLoaded", () => {
    // 1. ELIMINAR el botón que el main.php puso dentro del perfil
    const botonViejo = document.getElementById('theme-toggle');
    if (botonViejo) {
        botonViejo.remove(); // Lo borramos del HTML
    }

    // 2. CREAR el botón que queremos en la esquina superior derecha
    let btnEsquina = document.createElement('button');
    btnEsquina.id = 'btn-esquina';
    // Estilos para que flote en la esquina sin fondo blanco
    btnEsquina.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 99999; font-size: 24px; background: transparent; border: none; cursor: pointer; outline: none;';
    document.body.appendChild(btnEsquina);

    // 3. Lógica del modo oscuro
    if(localStorage.getItem('theme') === 'dark') {
        document.documentElement.setAttribute('data-theme', 'dark');
        btnEsquina.innerHTML = '☀️';
    } else {
        btnEsquina.innerHTML = '🌙';
    }

    btnEsquina.addEventListener('click', function() {
        let t = document.documentElement.getAttribute('data-theme');
        if(t === 'dark') {
            document.documentElement.setAttribute('data-theme', 'light');
            localStorage.setItem('theme', 'light'); 
            btnEsquina.innerHTML = '🌙';
        } else {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark'); 
            btnEsquina.innerHTML = '☀️';
        }
    });
});