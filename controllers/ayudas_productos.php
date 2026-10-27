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
    echo '<!doctype html><html lang="es"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>' . e($titulo) . '</title>'
       . '<style>body{font-family:system-ui,sans-serif;max-width:960px;margin:20px auto;padding:0 12px}'
       . 'table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccc;padding:6px 8px;text-align:left}'
       . 'th{background:#eee}.ok{background:#e6f4ea;padding:8px}.err{background:#fde8e8;padding:8px}'
       . 'label{display:block;margin:8px 0 2px}input,select{padding:6px;width:100%;max-width:360px}'
       . 'form.inline{display:inline}.inactivo{color:#888}</style></head><body>'
       . '<nav><a href="productos.php?m=productos">Productos</a> | '
       . '<a href="productos.php?m=categorias">Categorías</a></nav>';
    if ($f) echo '<p class="' . ($f[0] === 'ok' ? 'ok' : 'err') . '">' . e($f[1]) . '</p>';
}

function pagina_fin(): void { echo '</body></html>'; }