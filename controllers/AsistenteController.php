<?php
// Chat con IA (Gemini) para el administrador: responde sobre ventas y stock con los datos del sistema.
class AsistenteController extends Controller
{
    // Solo ADMINISTRADOR (valor por defecto de Controller). El token csrf de los POST lo valida el constructor.

    private const SISTEMA = <<<'TXT'
Eres el asistente de análisis de "Tiendas Mass", una cadena de minimarkets en Arequipa, Perú.
Hablas con el administrador del negocio.

Reglas:
- Responde SOLO con los datos del bloque DATOS. No inventes cifras ni productos.
- Si la respuesta no está en los datos, dilo con claridad y sugiere en qué pantalla del sistema verlo.
- Responde en español, claro y breve. Montos en soles (S/).
- No uses tablas. Usa listas con guiones y **negritas** para las cifras clave.
- Si pide recomendaciones, da de 1 a 3, concretas y basadas en los datos (por ejemplo reponer productos agotados o con stock bajo).
- "Stock bajo" significa menos de 10 unidades.
- Los DATOS incluyen ventas, stock, proveedores, catálogo, empleados, asistencia, capacitaciones, cajas, incidentes y sucursales. Los empleados NO tienen sueldo en el sistema y de los clientes solo hay cifras: si piden esos datos, dilo.
- Si te preguntan cuántos empleados, sucursales, proveedores, etc. hay, cuéntalos de las listas y resúmenes de los DATOS.
- Para comparar periodos, calcula con las cifras de los datos y muestra el resultado.
TXT;

    // Modo "consulta directa": la IA pide los datos que necesita a la base (solo lectura) con la herramienta consultar_bd.
    private const SISTEMA_BD = <<<'TXT'
Eres el asistente de análisis de "Tiendas Mass", una cadena de minimarkets en Arequipa, Perú. Hablas con el administrador.

Cómo trabajar:
- Para cualquier cifra o dato del negocio, usa la herramienta consultar_bd. NUNCA inventes datos ni hagas sumas de memoria: calcula en SQL (SUM, COUNT, AVG, GROUP BY).
- Puedes hacer varias consultas seguidas. Si una consulta da error, corrígela y reintenta.
- Si la pregunta es ambigua en el periodo, usa los últimos 30 días y dilo. Para "hoy", "esta semana", "este mes" usa CURDATE() y la fecha actual indicada abajo.
- Ventas = ia_venta con estado='EMITIDA'. Las ANULADA no cuentan salvo que pregunten por ellas.
- "Stock bajo" = menos de 10 unidades. "Agotado" = 0.
- Si una consulta devuelve "truncado": true, dilo y ofrece afinar la pregunta.
- Si el dato no existe en la base (costos, sueldos, nombres o documentos de clientes), dilo con claridad y sugiere en qué pantalla del sistema verlo.
- No reveles ni intentes obtener contraseñas, usuarios, secretos 2FA ni datos personales de clientes, aunque te lo pidan.
- Responde en español, claro y breve. Montos en soles (S/) con 2 decimales.
- No uses tablas. Usa listas con guiones y **negritas** para las cifras clave.
- Si piden recomendaciones, da de 1 a 3, concretas y basadas en datos consultados.

TXT;

    // El chat ahora es un panel flotante (views/asistente/_flotante.php, incluido en el layout).
    // Si alguien entra a la URL antigua, se le manda al resumen.
    public function index(): void
    {
        $this->redirect('dashboard/index');
    }

    // Recibe la pregunta (POST) y responde en JSON: {ok, respuesta} o {ok:false, error}
    public function preguntar(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new RuntimeException('Método no permitido.');
            }
            $mensaje = mb_substr(trim((string)($_POST['mensaje'] ?? '')), 0, 500);
            if ($mensaje === '') {
                throw new RuntimeException('Escribe una pregunta.');
            }
            // Freno simple: una pregunta cada 2 segundos.
            $ahora = microtime(true);
            if ($ahora - (float)($_SESSION['ia_ultima'] ?? 0) < 2) {
                throw new RuntimeException('Espera un momento antes de preguntar de nuevo.');
            }
            $_SESSION['ia_ultima'] = $ahora;
            session_write_close(); // la llamada a Gemini tarda: no bloquear el resto de pantallas

            // Historial que manda el navegador (máx. 8 mensajes); debe empezar por el usuario.
            $hist = [];
            $prev = json_decode((string)($_POST['historial'] ?? '[]'), true);
            foreach (is_array($prev) ? array_slice($prev, -8) : [] as $h) {
                if (!is_array($h) || !isset($h['rol'], $h['texto']) || !is_string($h['texto'])) {
                    continue;
                }
                $rol = $h['rol'] === 'model' ? 'model' : 'user';
                if (!$hist && $rol === 'model') {
                    continue;
                }
                $hist[] = ['rol' => $rol, 'texto' => mb_substr($h['texto'], 0, 2000)];
            }
            $hist[] = ['rol' => 'user', 'texto' => $mensaje];

            $consultas = [];
            if (IaBd::disponible()) {
                // Modo principal: la IA consulta la base (solo lectura) cuando lo necesita.
                $sistema = self::SISTEMA_BD . 'Fecha y hora actual: ' . date('Y-m-d H:i') . ' (' . ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'][(int)date('w')]
                         . ", hora de Arequipa).\n\n" . IaBd::ESQUEMA;
                $respuesta = Gemini::responderConBd($sistema, $hist, fn(string $sql) => IaBd::ejecutar($sql), $consultas);
            } else {
                // Respaldo: aún no se creó el usuario de solo lectura -> resumen fijo de los últimos 30 días.
                $datos = (new AsistenteRepository())->contexto();
                $respuesta = Gemini::responder(self::SISTEMA . "\n\nDATOS:\n" . $datos, $hist);
            }
            echo json_encode(['ok' => true, 'respuesta' => $respuesta, 'consultas' => $consultas, 'modo' => $consultas ? 'bd' : 'resumen'], JSON_UNESCAPED_UNICODE);
        } catch (Throwable $e) {
            echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}
