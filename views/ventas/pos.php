<!-- views/ventas/pos.php -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Tiendas Mass · Punto de venta</title>
    
    <!-- 1. LLAMAMOS AL CSS -->
    <link rel="stylesheet" href="../../public/css/pos.css">
</head>
<body>
<aside id="aside">
  <div class="logo"><i>M</i><div>Tiendas Mass<small>PUNTO DE VENTA</small></div></div>
  <nav id="nav"></nav>
  <div class="keys"><kbd>F2</kbd> Buscar / escanear<br><kbd>F9</kbd> Cobrar<br><kbd>Esc</kbd> Cerrar ventana</div>
  <div class="me"><b>JS</b><div>Jorge Salas<small>Cajero · Mass Cayma</small></div></div>
</aside>

<main>
  <header>
    <!-- EL BOTÓN DEL MENÚ -->
    <button id="menu" onclick="document.getElementById('aside').classList.toggle('open')">☰</button>
    <h1 id="title"></h1>
    <span class="chip">🏬 Mass Cayma</span>
    <span class="chip">💵 Caja 1 <span id="cajaTag"></span></span>
    <span class="chip" id="clock">🕘</span>
  </header>
  <div id="view"></div>
</main>

<div class="modal" id="modal"><div class="mbox" id="mbox"></div></div>
<div id="toast"></div>

<!-- 2. INYECTAMOS LOS PRODUCTOS DE LA BASE DE DATOS (ESTO ES PHP) -->
<script>
    const PRODUCTOS_DB = <?= json_encode($productos ?? []) ?>;
</script>

<!-- 3. LLAMAMOS AL JAVASCRIPT -->
<script src="../../public/js/pos.js"></script>
</body>
</html>