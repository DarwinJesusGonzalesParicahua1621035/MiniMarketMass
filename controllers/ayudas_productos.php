<?php
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function flash(?string $tipo = null, ?string $texto = null): ?array {
    if ($tipo !== null) { $_SESSION['flash'] = [$tipo, $texto]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

function redirigir(string $url): never { header('Location: ' . $url); exit; }

function mensaje_error(PDOException $ex, string $duplicado, string $conHijos = ''): string {
    error_log($ex->getMessage());
    $cod = (int)($ex->errorInfo[1] ?? 0);
    return match ($cod) {
        1062 => $duplicado,
        1451 => $conHijos ?: 'No se puede eliminar: tiene datos relacionados.',
        3819 => 'Un dato no cumple las reglas de la base de datos.',
        default => 'Ocurrió un error en la base de datos.',
    };
}

function pagina_inicio(string $titulo): void {
    $f = flash();
    $m = $_GET['m'] ?? 'productos';
    $subtitulos = [
        'productos' => 'Inventario · Gestión de productos',
        'categorias' => 'Inventario · Gestión de categorías',
        'caja' => 'Operaciones · Administración de cajas',
        'historia_caja' => 'Operaciones · Aperturas y cierres',
        'incidentes' => 'Operaciones · Seguimiento de incidentes',
    ];
    $sub = $subtitulos[$m] ?? 'Minimarket Mass · Panel de administración';
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?> · Tiendas Mass</title>
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
<aside id="aside">
  <div class="logo"><i>M</i><div>Tiendas Mass<small>PANEL ADMIN</small></div></div>
  <nav>
    <h6>Inventario</h6>
    <a href="productos.php?m=productos" class="<?= $m === 'productos' ? 'on' : '' ?>"><span>📦</span>Productos</a>
    <a href="productos.php?m=categorias" class="<?= $m === 'categorias' ? 'on' : '' ?>"><span>🏷️</span>Categorías</a>
    <h6>Operaciones</h6>
    <a href="productos.php?m=caja" class="<?= $m === 'caja' ? 'on' : '' ?>"><span>💵</span>Cajas</a>
    <a href="productos.php?m=historia_caja" class="<?= $m === 'historia_caja' ? 'on' : '' ?>"><span>📋</span>Historial de caja</a>
    <a href="productos.php?m=incidentes" class="<?= $m === 'incidentes' ? 'on' : '' ?>"><span>⚠️</span>Incidentes</a>
  </nav>
</aside>
<main>
  <header>
    <button id="menu" type="button" onclick="document.getElementById('aside').classList.toggle('open')">☰</button>
    <h1><?= e($titulo) ?><small><?= e($sub) ?></small></h1>
  </header>
  <?php if ($f): ?><div class="alert <?= $f[0] === 'ok' ? 'ok' : 'err' ?>"><?= e($f[1]) ?></div><?php endif; ?>
<?php
}

function pagina_fin(): void { echo "</main>\n</body>\n</html>"; }