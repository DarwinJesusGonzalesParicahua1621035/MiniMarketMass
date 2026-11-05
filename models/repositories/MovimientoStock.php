<?php
declare(strict_types=1);

class MovimientoStock
{
    private const TIPOS = ['ENTRADA', 'SALIDA', 'VENTA'];
    // ENTRADA suma existencias; SALIDA (manual) y VENTA las descuentan.
    private const SUMAN = ['ENTRADA'];

    private PDO $db;

    // El PDO debe tener PDO::ERRMODE_EXCEPTION activado.
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Registra un movimiento y actualiza el stock en una sola transacción.
     * Claves: sucursal_id, producto_id, empleado_id, tipo, cantidad,
     *         motivo (opcional), venta_id (opcional).
     * Devuelve el id del movimiento creado.
     */
    public function registrar(array $d): int
    {
        $sucursalId = $this->entero($d, 'sucursal_id');
        $productoId = $this->entero($d, 'producto_id');
        $empleadoId = $this->entero($d, 'empleado_id');
        $ventaId    = isset($d['venta_id']) ? $this->entero($d, 'venta_id') : null;

        $tipo = strtoupper(trim((string)($d['tipo'] ?? '')));
        if (!in_array($tipo, self::TIPOS, true)) {
            throw new InvalidArgumentException(
                'Tipo inválido. Use: ' . implode(', ', self::TIPOS) . '.'
            );
        }

        $cantidad = $this->validarCantidad($d['cantidad'] ?? null);

        $motivo = isset($d['motivo']) ? trim((string)$d['motivo']) : null;
        if ($motivo === '') {
            $motivo = null;
        }
        if ($motivo !== null && mb_strlen($motivo) > 100) {
            throw new InvalidArgumentException('El motivo no puede pasar de 100 caracteres.');
        }

        // Si ya hay una transacción abierta (por ejemplo la de ventas), nos unimos a ella.
        $propia = !$this->db->inTransaction();
        if ($propia) {
            $this->db->beginTransaction();
        }

        try {
            // Bloquea la fila de stock mientras dura la transacción.
            $st = $this->db->prepare(
                'SELECT cantidad FROM stock
                 WHERE sucursal_id = :s AND producto_id = :p
                 FOR UPDATE'
            );
            $st->execute(['s' => $sucursalId, 'p' => $productoId]);
            if ($st->fetch(PDO::FETCH_ASSOC) === false) {
                throw new RuntimeException(
                    'Ese producto no está asignado a esa sucursal. Asígnalo desde Productos.'
                );
            }

            if (in_array($tipo, self::SUMAN, true)) {
                $up = $this->db->prepare(
                    'UPDATE stock SET cantidad = cantidad + :c
                     WHERE sucursal_id = :s AND producto_id = :p'
                );
                $up->execute(['c' => $cantidad, 's' => $sucursalId, 'p' => $productoId]);
            } else {
                // Resta solo si alcanza.
                $up = $this->db->prepare(
                    'UPDATE stock SET cantidad = cantidad - :c
                     WHERE sucursal_id = :s AND producto_id = :p AND cantidad >= :c2'
                );
                $up->execute([
                    'c' => $cantidad, 'c2' => $cantidad,
                    's' => $sucursalId, 'p' => $productoId,
                ]);
                if ($up->rowCount() !== 1) {
                    throw new RuntimeException('Stock insuficiente para este movimiento.');
                }
            }

            $ins = $this->db->prepare(
                'INSERT INTO movimiento_stock
                    (sucursal_id, producto_id, empleado_id, venta_id, tipo, cantidad, motivo, fecha)
                 VALUES
                    (:s, :p, :e, :v, :t, :c, :m, NOW())'
            );
            $ins->execute([
                's' => $sucursalId, 'p' => $productoId, 'e' => $empleadoId,
                'v' => $ventaId, 't' => $tipo, 'c' => $cantidad, 'm' => $motivo,
            ]);
            $id = (int)$this->db->lastInsertId();

            if ($propia) {
                $this->db->commit();
            }
            return $id;
        } catch (Throwable $e) {
            if ($propia && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    // Lee un número entero mayor que 0 desde el arreglo de datos.
    private function entero(array $d, string $clave): int
    {
        $v = $d[$clave] ?? null;
        if (!is_numeric($v) || (int)$v <= 0 || (string)(int)$v !== trim((string)$v)) {
            throw new InvalidArgumentException("El campo $clave debe ser un número entero mayor que 0.");
        }
        return (int)$v;
    }

    // Cantidad > 0, hasta 7 enteros y 3 decimales (DECIMAL(10,3)).
    private function validarCantidad(mixed $valor): string
    {
        if (is_float($valor)) {
            $texto = rtrim(rtrim(sprintf('%.10F', $valor), '0'), '.');
        } else {
            $texto = trim((string)$valor);
        }

        if (!preg_match('/^\d{1,7}(\.\d{1,3})?$/', $texto) || (float)$texto <= 0) {
            throw new InvalidArgumentException(
                'La cantidad debe ser mayor que 0, con hasta 7 enteros y 3 decimales.'
            );
        }
        return $texto;
    }
}