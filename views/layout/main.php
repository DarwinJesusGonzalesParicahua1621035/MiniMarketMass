<?php
$menu = [
    'General'       => [['dashboard', '📊', 'Resumen']],
    'Operaciones'   => [['ventas', '🧾', 'Ventas'], ['caja', '💵', 'Cajas']],
    'Inventario'    => [['productos', '📦', 'Productos'], ['categorias', '🏷️', 'Categorías'], ['proveedores', '🏭', 'Proveedores'], ['stock', '🗃️', 'Stock']],
    'Personas'      => [['clientes', '👥', 'Clientes'], ['empleados', '🧑‍💼', 'Empleados'], ['asistencia', '🕘', 'Asistencia']],
    'Configuración' => [['sucursales', '🏬', 'Sucursales'], ['incidentes', '⚠️', 'Incidentes']],
];
$actual = explode('/', $_GET['r'] ?? 'dashboard/index')[0];
$tema = (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'dark' : 'light';
?>
<!DOCTYPE html>
<html lang="es" data-theme="<?= $tema ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo ?? 'Panel') ?> · Tiendas Mass</title>

<style>
    html[data-theme="dark"], html[data-theme="dark"] body {
        background-color: #0D0F12 !important;
        color: #F1F3F5 !important;
    }
</style>

<link rel="stylesheet" href="<?= Controller::base() ?>/css/app.css">
<link rel="stylesheet" href="<?= Controller::base() ?>/css/dark-mode.css">

<style>
    /* --- 1. BASE Y ESTRUCTURA --- */
    html[data-theme="dark"] body { background-color: #0D0F12 !important; color: #F1F3F5 !important; }
    html[data-theme="dark"] #aside { background-color: #14171C !important; border-right: 1px solid #2A303A !important; }
    html[data-theme="dark"] main > header { background-color: #161A20 !important; border-bottom: 1px solid #2A303A !important; }
    html[data-theme="dark"] h1, html[data-theme="dark"] h2, html[data-theme="dark"] h3 { color: #F1F3F5 !important; }
    html[data-theme="dark"] p, html[data-theme="dark"] small { color: #9AA3AF !important; }

    /* --- 2. CONTENEDORES GENERALES --- */
    html[data-theme="dark"] .card, 
    html[data-theme="dark"] .tw,
    html[data-theme="dark"] .box,
    html[data-theme="dark"] .panel { 
        background-color: #161A20 !important; 
        border-color: #2A303A !important; 
        color: #F1F3F5 !important; 
    }

    /* --- 3. BOTONES --- */
    html[data-theme="dark"] .btn, 
    html[data-theme="dark"] button {
        background-color: #2A303A !important;
        color: #F1F3F5 !important;
        border: 1px solid #3A404A !important;
    }
    html[data-theme="dark"] .btn.y,
    html[data-theme="dark"] button.btn-primary {
        background-color: #FFC400 !important;
        color: #111318 !important;
        border-color: #FFC400 !important;
        font-weight: bold;
    }

    /* --- 4. ETIQUETAS Y PÍLDORAS --- */
    html[data-theme="dark"] .tag, 
    html[data-theme="dark"] .badge,
    html[data-theme="dark"] .prd-chip,
    html[data-theme="dark"] .role {
        background-color: #2A303A !important; 
        color: #F1F3F5 !important; 
        border: 1px solid #3A404A !important;
    }
    html[data-theme="dark"] .prd-chip.on {
        background-color: #FFC400 !important;
        color: #111318 !important;
        border-color: #FFC400 !important;
    }

    /* --- 5. INPUTS --- */
    html[data-theme="dark"] input, 
    html[data-theme="dark"] select, 
    html[data-theme="dark"] textarea {
        background-color: #1D222A !important; 
        color: #F1F3F5 !important; 
        border: 1px solid #2A303A !important;
    }

    /* --- 6. VENTAS --- */
    html[data-theme="dark"] .vt-filters { background-color: #161A20 !important; border-color: #2A303A !important; color: #F1F3F5 !important; }
    html[data-theme="dark"] .vt-fld { background-color: #1D222A !important; color: #F1F3F5 !important; border: 1px solid #2A303A !important; }

    /* --- 7. PROVEEDORES --- */
    html[data-theme="dark"] .pv-item { background-color: #1D222A !important; border: 1px solid #2A303A !important; }
    html[data-theme="dark"] .pv-item * { color: #F1F3F5 !important; }

    /* ========================================== */
    /* 8. DASHBOARD (Estadísticas) */
    /* ========================================== */
    html[data-theme="dark"] .kp { background-color: #161A20 !important; border-color: #2A303A !important; }
    html[data-theme="dark"] .kp h2 { color: #F1F3F5 !important; }
    html[data-theme="dark"] .kp p { color: #9AA3AF !important; }
    html[data-theme="dark"] .kp em { color: #F1F3F5 !important; }
    html[data-theme="dark"] .kp em.d { color: #F87171 !important; }
    html[data-theme="dark"] .kp.y { border-top: 4px solid #FFC400 !important; }
    html[data-theme="dark"] .kp.b { border-top: 4px solid #5B8CFF !important; }
    html[data-theme="dark"] .kp.r { border-top: 4px solid #F87171 !important; }
    html[data-theme="dark"] .kp.p { border-top: 4px solid #A78BFA !important; }

    /* ========================================== */
    /* 9. CATEGORÍAS */
    /* ========================================== */
    html[data-theme="dark"] .cat-grid { background-color: #0D0F12 !important; }
    html[data-theme="dark"] .cat-item { background-color: #161A20 !important; border: 1px solid #2A303A !important; }
    html[data-theme="dark"] .cat-info { background-color: #1D222A !important; color: #F1F3F5 !important; }
    html[data-theme="dark"] .cat-item *, html[data-theme="dark"] .cat-info * { color: #F1F3F5 !important; }
    html[data-theme="dark"] .cat-item .tag,
    html[data-theme="dark"] .cat-info .tag,
    html[data-theme="dark"] .cat-info .circle { background-color: #2A303A !important; color: #F1F3F5 !important; }

    /* --- 10. EMPLEADOS --- */
    html[data-theme="dark"] .emp-card,
    html[data-theme="dark"] .emp-item,
    html[data-theme="dark"] .card-emp,
    html[data-theme="dark"] .emp-grid > div {
        background-color: #1D222A !important;
        border: 1px solid #2A303A !important;
    }
    html[data-theme="dark"] .emp-card *, html[data-theme="dark"] .emp-item * { color: #F1F3F5 !important; }

    /* --- 11. CAZADOR DE FONDOS BLANCOS EN LÍNEA --- */
    html[data-theme="dark"] div[style*="background-color: #fff"],
    html[data-theme="dark"] div[style*="background-color: white"],
    html[data-theme="dark"] div[style*="background-color: #ffffff"],
    html[data-theme="dark"] div[style*="background: #fff"],
    html[data-theme="dark"] div[style*="background: white"],
    html[data-theme="dark"] div[style*="background-color:rgb(255, 255, 255)"],
    html[data-theme="dark"] .bg-white {
        background-color: #1D222A !important;
        border-color: #2A303A !important;
        color: #F1F3F5 !important;
    }

    /* --- 12. BOTÓN FLOTANTE --- */
    #btn-dark-mode {
        position: fixed !important; top: 20px !important; right: 20px !important; z-index: 99999 !important;
        font-size: 24px !important; background: transparent !important; border: none !important;
        cursor: pointer !important; outline: none !important;
    }

    /* --- 13. SCROLLBAR --- */
    html[data-theme="dark"] ::-webkit-scrollbar { width: 8px; height: 8px; }
    html[data-theme="dark"] ::-webkit-scrollbar-track { background: #0D0F12; }
    html[data-theme="dark"] ::-webkit-scrollbar-thumb { background: #2A303A; border-radius: 4px; }

    /* ========================================== */
    /* 14. ARREGLO DEL HOVER EN LAS TABLAS */
    /* ========================================== */
    html[data-theme="dark"] table tbody tr:hover,
    html[data-theme="dark"] .tw table tbody tr:hover,
    html[data-theme="dark"] .scroll table tbody tr:hover {
        background-color: #1D222A !important; 
        color: #F1F3F5 !important;
    }
    html[data-theme="dark"] table tbody tr:hover td,
    html[data-theme="dark"] table tbody tr:hover th {
        background-color: #1D222A !important;
        color: #F1F3F5 !important;
    }

    /* ========================================== */
    /* 15. ARREGLO PARA LA VISTA DE CAJAS */
    /* ========================================== */
    html[data-theme="dark"] .caja-filtros,
    html[data-theme="dark"] .filtros-panel,
    html[data-theme="dark"] .historial-filtros,
    html[data-theme="dark"] .cx-filtros {
        background-color: #161A20 !important;
        border: 1px solid #2A303A !important;
        color: #F1F3F5 !important;
    }
    html[data-theme="dark"] .caja-card,
    html[data-theme="dark"] .cx-card {
        background-color: #1D222A !important;
        border: 1px solid #2A303A !important;
    }
    html[data-theme="dark"] .caja-card *,
    html[data-theme="dark"] .cx-card * {
        color: #F1F3F5 !important;
    }
    html[data-theme="dark"] .cx-chip,
    html[data-theme="dark"] .caja-chip {
        background-color: #2A303A !important;
        color: #F1F3F5 !important;
        border: 1px solid #3A404A !important;
    }
    html[data-theme="dark"] .cx-chip.on,
    html[data-theme="dark"] .caja-chip.on {
        background-color: #FFC400 !important;
        color: #111318 !important;
    }
    html[data-theme="dark"] .cx-buscar input,
    html[data-theme="dark"] .caja-buscar input {
        background-color: #1D222A !important;
        color: #F1F3F5 !important;
        border: 1px solid #2A303A !important;
    }

    /* ========================================== */
    /* 16. EL DESTRUCTOR DE BLANCOS (Fuerza bruta) */
    /* ========================================== */
    html[data-theme="dark"] main div, 
    html[data-theme="dark"] main section, 
    html[data-theme="dark"] main form {
        background-color: transparent; 
    }
    
    /* Excepción: Las tarjetas que SÍ deben tener fondo propio */
    html[data-theme="dark"] .kp, 
    html[data-theme="dark"] .tw, 
    html[data-theme="dark"] .card, 
    html[data-theme="dark"] .pv-item,
    html[data-theme="dark"] .cat-item,
    html[data-theme="dark"] .emp-card,
    html[data-theme="dark"] .modal-content,
    html[data-theme="dark"] .drawer,
    html[data-theme="dark"] button,
    html[data-theme="dark"] input,
    html[data-theme="dark"] select {
        background-color: #161A20; 
    }
    
    html[data-theme="dark"] .cat-info,
    html[data-theme="dark"] .modal-content,
    html[data-theme="dark"] .drawer {
        background-color: #1D222A; 
    }

    /* ========================================== */
    /* 17. FUERZA BRUTA PARA STOCK Y PRODUCTOS */
    /* ========================================== */
    html[data-theme="dark"] aside,
    html[data-theme="dark"] .sidebar,
    html[data-theme="dark"] .stock-sidebar,
    html[data-theme="dark"] .resumen-stock,
    html[data-theme="dark"] .panel-stock {
        background-color: #161A20 !important;
        border: 1px solid #2A303A !important;
        color: #F1F3F5 !important;
    }
    html[data-theme="dark"] aside *:not(#aside), 
    html[data-theme="dark"] .stock-sidebar *,
    html[data-theme="dark"] .resumen-stock * {
        color: #F1F3F5 !important;
    }
    html[data-theme="dark"] .producto,
    html[data-theme="dark"] .prd-card,
    html[data-theme="dark"] .item-producto,
    html[data-theme="dark"] .card-producto,
    html[data-theme="dark"] .prd-item {
        background-color: #1D222A !important;
        border: 1px solid #2A303A !important;
        color: #F1F3F5 !important;
    }
    html[data-theme="dark"] .producto *,
    html[data-theme="dark"] .prd-card *,
    html[data-theme="dark"] .item-producto *,
    html[data-theme="dark"] .card-producto *,
    html[data-theme="dark"] .prd-item * {
        color: #F1F3F5 !important;
    }
    html[data-theme="dark"] .producto button,
    html[data-theme="dark"] .prd-card button,
    html[data-theme="dark"] .item-producto button {
        background-color: #2A303A !important;
        color: #F1F3F5 !important;
        border: 1px solid #3A404A !important;
    }

    /* ========================================== */
    /* 18. SOLUCIÓN DEFINITIVA PARA EL MODAL DE CAJA (dialog) */
    /* ========================================== */
    
    /* El contenedor del modal (la etiqueta <dialog>) */
    html[data-theme="dark"] dialog,
    html[data-theme="dark"] dialog.cj-d {
        background-color: #161A20 !important; 
        border: 1px solid #2A303A !important;
        color: #F1F3F5 !important;
        box-shadow: 0 10px 25px rgba(0,0,0,0.5) !important;
    }

    /* Los formularios y divs dentro del modal */
    html[data-theme="dark"] dialog form,
    html[data-theme="dark"] dialog div {
        background-color: #161A20 !important;
        color: #F1F3F5 !important;
    }

    /* Títulos y textos */
    html[data-theme="dark"] dialog h3,
    html[data-theme="dark"] dialog p,
    html[data-theme="dark"] dialog label,
    html[data-theme="dark"] dialog span {
        color: #F1F3F5 !important;
    }

    /* Inputs y selects dentro del modal */
    html[data-theme="dark"] dialog input,
    html[data-theme="dark"] dialog select {
        background-color: #1D222A !important;
        color: #F1F3F5 !important;
        border: 1px solid #2A303A !important;
    }

    /* Botón de Guardar/Abrir (Amarillo) */
    html[data-theme="dark"] dialog .btn.y,
    html[data-theme="dark"] dialog button[type="submit"] {
        background-color: #FFC400 !important;
        color: #111318 !important;
        border-color: #FFC400 !important;
    }

    /* Botón de Cancelar/Cerrar (Gris) */
    html[data-theme="dark"] dialog .btn.g,
    html[data-theme="dark"] dialog button[type="button"] {
        background-color: #2A303A !important;
        color: #F1F3F5 !important;
        border: 1px solid #3A404A !important;
    }

    /* ========================================== */
    /* 19. REGLA DE ORO FINAL (Cualquier fondo blanco) */
    /* ========================================== */
    html[data-theme="dark"] *[style*="background-color: white"],
    html[data-theme="dark"] *[style*="background-color: #fff"],
    html[data-theme="dark"] *[style*="background-color: #ffffff"],
    html[data-theme="dark"] *[style*="background: white"],
    html[data-theme="dark"] *[style*="background: #fff"],
    html[data-theme="dark"] *[style*="background-color: rgb(255"],
    html[data-theme="dark"] *[style*="background-color:rgb(255"],
    html[data-theme="dark"] .modal,
    html[data-theme="dark"] .modal-content,
    html[data-theme="dark"] .modal-box,
    html[data-theme="dark"] .dialog,
    html[data-theme="dark"] .popup {
        background-color: #1D222A !important;
        color: #F1F3F5 !important;
        border-color: #2A303A !important;
    }
</style>
</head>
<body>
<aside id="aside">
  <div class="logo"><i>M</i><div>Tiendas Mass</div></div>
  <div class="rol-top"><?= e(mb_strtoupper($user['rol'])) ?></div>
  <nav>
    <?php foreach ($menu as $grupo => $items): ?>
      <h6><?= e($grupo) ?></h6>
      <?php foreach ($items as $m): ?>
        <a href="<?= Controller::url($m[0] . '/index') ?>" class="<?= $actual === $m[0] ? 'on' : '' ?>"><span><?= $m[1] ?></span><?= e($m[2]) ?></a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </nav>
  <div class="me"><b><?= e(mb_substr($user['nombre'], 0, 1)) ?></b>
    <div><?= e($user['nombre']) ?><small><a href="<?= Controller::url('auth/salir') ?>">Cerrar sesión</a></small></div></div>
</aside>
<main>
  <header>
    <button id="menu">☰</button>
    <h1><?= e($titulo ?? '') ?></h1>
  </header>
  
  <?php if ($flash): ?><div class="flash <?= $flash[0] === 'err' ? 'err' : '' ?>"><?= e($flash[1]) ?></div><?php endif; ?>
  <?= $contenido ?>
</main>

<button id="btn-dark-mode" title="Cambiar tema">🌙</button>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        let btn = document.getElementById('btn-dark-mode');
        let html = document.documentElement;

        if (html.getAttribute('data-theme') === 'dark') {
            btn.innerHTML = '☀️';
        } else {
            btn.innerHTML = '🌙';
        }

        btn.addEventListener('click', function() {
            let currentTheme = html.getAttribute('data-theme');
            if (currentTheme === 'dark') {
                html.setAttribute('data-theme', 'light');
                localStorage.setItem('theme', 'light'); 
                document.cookie = "theme=light; path=/; max-age=31536000"; 
                btn.innerHTML = '🌙';
            } else {
                html.setAttribute('data-theme', 'dark');
                localStorage.setItem('theme', 'dark'); 
                document.cookie = "theme=dark; path=/; max-age=31536000"; 
                btn.innerHTML = '☀️';
            }
        });
    });
</script>

<script src="<?= Controller::base() ?>/js/app.js"></script>
</body>
</html>