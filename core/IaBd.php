<?php
/**
 * Acceso de SOLO LECTURA a la base de datos para el Asistente IA.
 *
 * Defensa en capas:
 *  1) Conexión con el usuario MySQL "ia_lector", que solo tiene SELECT sobre las vistas ia_* (ver database/migracion_ia_v5.sql).
 *  2) Sesión en modo READ ONLY y con tiempo máximo de ejecución.
 *  3) Este validador: una sola sentencia SELECT/WITH, solo vistas ia_*, sin comentarios ni funciones peligrosas.
 *  4) Máximo de filas devueltas.
 */
class IaBd
{
    public const MAX_FILAS = 200;
    private const MAX_SQL  = 4000;

    private static ?PDO $pdo = null;

    /** Descripción de las vistas que se le da a la IA. */
    public const ESQUEMA = <<<'TXT'
Base de datos MySQL 8. SOLO existen estas vistas (todas empiezan con ia_). Usa únicamente estas, sin prefijo de base de datos:

ia_sucursal(id, nombre, direccion, distrito_id, distrito, serie_boleta, serie_factura)
ia_caja(id, sucursal_id, nombre)
ia_empleado(id, sucursal_id, supervisor_id, nombre, rol, activo)   -- rol: ADMINISTRADOR | ALMACENERO | CAJERO; activo: 1/0
ia_categoria(id, nombre)
ia_proveedor(id, razon_social, activo)
ia_producto(id, categoria_id, proveedor_id, codigo_barras, nombre, unidad, precio, activo)
ia_historial_precio(id, producto_id, precio, fecha_inicio, fecha_fin)   -- fecha_fin NULL = precio vigente
ia_stock(sucursal_id, producto_id, cantidad)   -- una fila = el producto se maneja en esa sucursal
ia_asistencia(id, empleado_id, entrada, salida, estado)   -- estado: PRESENTE (hasta 08:30) | TARDANZA | FALTA
ia_capacitacion(id, empleado_id, tema, fecha, horas)
ia_historia_caja(id, caja_id, empleado_id, apertura, cierre, monto_inicial, monto_final, estado)   -- estado: ABIERTA | CERRADA
ia_incidente(id, sucursal_id, tipo, descripcion, fecha, estado)   -- estado: ABIERTO | RESUELTO
ia_cliente(id, tipo_documento)   -- DNI | RUC; los nombres y documentos NO están disponibles
ia_venta(id, sucursal_id, historia_caja_id, empleado_id, cliente_id, tipo_comprobante, serie, correlativo, fecha, subtotal, igv, total, estado, motivo_anulacion)
   -- tipo_comprobante: BOLETA | FACTURA; estado: EMITIDA | ANULADA
ia_detalle_venta(id, venta_id, producto_id, cantidad, precio_unitario, total_linea)
ia_pago_venta(id, venta_id, medio_pago, monto)   -- medio_pago: EFECTIVO | PLIN | TARJETA | YAPE
ia_movimiento_stock(id, sucursal_id, producto_id, empleado_id, venta_id, proveedor_id, tipo, cantidad, motivo, fecha)
   -- tipo: ENTRADA (compra a proveedor) | VENTA | SALIDA | AJUSTE | MERMA

Relaciones: ia_venta.sucursal_id→ia_sucursal.id, ia_venta.empleado_id→ia_empleado.id (cajero), ia_detalle_venta.venta_id→ia_venta.id,
ia_detalle_venta.producto_id→ia_producto.id, ia_producto.categoria_id→ia_categoria.id, ia_producto.proveedor_id→ia_proveedor.id,
ia_stock(sucursal_id, producto_id), ia_historia_caja.caja_id→ia_caja.id, ia_caja.sucursal_id→ia_sucursal.id.
Notas: "ventas" = ia_venta con estado='EMITIDA' (las ANULADA no cuentan). Efectivo esperado en una caja = monto_inicial + suma de pagos EFECTIVO de sus ventas EMITIDAS.
No hay costos de compra, sueldos ni datos personales de clientes en la base.
TXT;

    private static function pdo(): PDO
    {
        if (self::$pdo) {
            return self::$pdo;
        }
        $c   = (require ROOT . '/config/config.php')['db'];
        $cfg = Gemini::config();
        try {
            $pdo = new PDO(
                "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4",
                $cfg['db_user'], $cfg['db_pass'],
                [
                    PDO::ATTR_ERRMODE                  => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE       => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES         => false,
                    PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false, // permite cortar en MAX_FILAS sin cargar todo
                ]
            );
        } catch (PDOException $e) {
            throw new RuntimeException('No se pudo entrar con el usuario "' . $cfg['db_user'] . '". '
                . 'Ejecuta database/migracion_ia_v5.sql en MySQL y revisa la contraseña en config/gemini.local.php.');
        }
        $pdo->exec("SET time_zone = '-05:00'");
        $pdo->exec('SET SESSION TRANSACTION READ ONLY');
        $pdo->exec('SET SESSION max_execution_time = 8000');
        return self::$pdo = $pdo;
    }

    /** ¿Está creado el usuario de solo lectura y se puede conectar? */
    public static function disponible(): bool
    {
        try {
            self::pdo()->query('SELECT 1 FROM ia_sucursal LIMIT 1')->closeCursor();
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /** Devuelve la consulta limpia o lanza RuntimeException si no es segura. */
    public static function validar(string $sql): string
    {
        $sql = trim($sql);
        $sql = rtrim($sql, "; \t\n\r");
        if ($sql === '' || strlen($sql) > self::MAX_SQL) {
            throw new RuntimeException('Consulta vacía o demasiado larga.');
        }
        // Se quitan los textos entre comillas para revisar solo la estructura.
        $limpio = preg_replace("/'(?:[^'\\\\]|\\\\.|'')*'|\"(?:[^\"\\\\]|\\\\.)*\"/s", "''", $sql);

        if (preg_match('/;|--|#|\/\*|\*\/|`|\\\\/', $limpio)) {
            throw new RuntimeException('Solo se permite una consulta, sin comentarios ni caracteres especiales.');
        }
        if (!preg_match('/^\s*\(*\s*(SELECT|WITH)\b/i', $limpio)) {
            throw new RuntimeException('Solo se permiten consultas SELECT.');
        }
        $prohibidas = 'INSERT|UPDATE|DELETE|DROP|ALTER|CREATE|TRUNCATE|RENAME|GRANT|REVOKE|INTO|OUTFILE|DUMPFILE|LOAD_FILE|LOAD|'
            . 'SLEEP|BENCHMARK|GET_LOCK|RELEASE_LOCK|CALL|HANDLER|SET|LOCK|UNLOCK|PREPARE|EXECUTE|DEALLOCATE|SHOW|DESCRIBE|EXPLAIN|'
            . 'USE|KILL|FLUSH|INFORMATION_SCHEMA|PERFORMANCE_SCHEMA|MYSQL|SYS|FOR\s+UPDATE|LOCK\s+IN\s+SHARE|USER|VERSION|DATABASE|SCHEMA|CURRENT_USER';
        if (preg_match('/\b(' . $prohibidas . ')\b/i', $limpio, $m)) {
            throw new RuntimeException('Palabra no permitida en la consulta: ' . strtoupper(trim($m[1])) . '.');
        }
        // Solo vistas ia_* (los nombres de CTE definidos con WITH también valen).
        $ctes = [];
        if (preg_match_all('/\b([a-z_]\w*)\s+AS\s*\(/i', $limpio, $mm)) {
            $ctes = array_map('strtolower', $mm[1]);
        }
        if (preg_match_all('/\b(?:FROM|JOIN)\s+([a-z_]\w*)(\s*\.\s*[a-z_]\w*)?/i', $limpio, $mm, PREG_SET_ORDER)) {
            foreach ($mm as $t) {
                $n = strtolower($t[1]);
                if (!empty($t[2]) || (!str_starts_with($n, 'ia_') && !in_array($n, $ctes, true))) {
                    throw new RuntimeException('Tabla no permitida: ' . trim($t[0]) . '. Usa solo las vistas ia_*.');
                }
            }
        }
        return $sql;
    }

    /**
     * Ejecuta la consulta ya validada.
     * @return array{columnas: string[], filas: array<int, array>, truncado: bool}
     */
    public static function ejecutar(string $sql): array
    {
        $sql = self::validar($sql);
        $st  = self::pdo()->query($sql);
        $filas = [];
        $trunc = false;
        while (($f = $st->fetch()) !== false) {
            if (count($filas) >= self::MAX_FILAS) {
                $trunc = true;
                break;
            }
            $filas[] = $f;
        }
        $st->closeCursor();
        return [
            'columnas' => $filas ? array_keys($filas[0]) : [],
            'filas'    => $filas,
            'truncado' => $trunc,
        ];
    }
}
