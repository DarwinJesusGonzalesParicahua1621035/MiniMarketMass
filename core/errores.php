<?php
/* S2: los errores nunca se muestran al usuario; se guardan en storage/logs/app.log */
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

$dirLogs = __DIR__ . '/../storage/logs';
if (!is_dir($dirLogs)) { @mkdir($dirLogs, 0775, true); }
ini_set('error_log', $dirLogs . '/app.log');

/* Cookie de sesion mas segura (debe cargarse antes de session_start) */
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
ini_set('session.use_strict_mode', '1');
if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    ini_set('session.cookie_secure', '1');
}

function mostrarPaginaError(): void
{
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Algo salió mal</title>'
       . '<body style="font-family:system-ui,sans-serif;display:grid;place-items:center;min-height:100vh;margin:0;background:#f4f5f8;color:#1a1a1a">'
       . '<div style="text-align:center;padding:32px"><div style="font-size:48px">😕</div>'
       . '<h1 style="margin:8px 0">Algo salió mal</h1>'
       . '<p style="color:#666">Ocurrió un problema. Intenta de nuevo en unos minutos.</p>'
       . '<a href="javascript:history.back()" style="color:#2f6bff">Volver</a></div></body></html>';
}

set_exception_handler(function (Throwable $e): void {
    error_log('[' . date('Y-m-d H:i:s') . '] ' . get_class($e) . ': ' . $e->getMessage()
        . ' en ' . $e->getFile() . ':' . $e->getLine());
    mostrarPaginaError();
});
