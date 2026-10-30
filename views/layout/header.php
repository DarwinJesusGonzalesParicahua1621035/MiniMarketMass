<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Tiendas Mass · Panel de administración</title>
<!-- Aquí pega TODO el bloque de <style> de tu HTML original -->
<style>
/* ... (Copia los estilos CSS del archivo HTML) ... */
</style>
</head>
<body>
<aside id="aside">
  <div class="logo"><i>M</i><div>Tiendas Mass<small>PANEL ADMIN</small></div></div>
  <nav id="nav">
    <!-- Navegación estática por ahora, luego se puede hacer dinámica -->
    <h6>General</h6>
    <a href="/">📊 Dashboard</a>
    <h6>Operaciones</h6>
    <a href="/ventas">🧾 Ventas</a>
    <a href="/caja">💵 Cajas</a>
    <h6>Inventario</h6>
    <a href="/productos">📦 Productos</a>
    <a href="/stock">🗃️ Stock</a>
    <h6>Personas</h6>
    <a href="/clientes">👥 Clientes</a>
  </nav>
  <div class="me"><b>AD</b><div><?= Auth::user() ?><small>Sesión activa</small></div></div>
</aside>
<main>
  <header>
    <button id="menu" onclick="aside.classList.toggle('open')">☰</button>
    <h1 id="title">Panel de Control</h1>
    <a href="/logout" class="btn g" style="text-decoration:none;">Salir</a>
  </header>
  <div id="view">