<?php
// Datos y registro de ventas para la pantalla del cajero.
class PosRepository extends Repository
{
    protected string $tabla = 'venta';

    // Turno abierto del cajero (prefiere la caja de su propia sucursal).
    public function turno(int $empleadoId): ?array
    {
        $s = $this->db()->prepare("SELECT h.id, h.caja_id, c.nombre AS caja, c.sucursal_id,
                s.nombre AS sucursal, s.direccion, s.serie_boleta, s.serie_factura, h.apertura
            FROM historia_caja h
            JOIN caja c ON c.id = h.caja_id
            JOIN sucursal s ON s.id = c.sucursal_id
            JOIN empleado e ON e.id = h.empleado_id
            WHERE h.empleado_id = ? AND h.estado = 'ABIERTA'
            ORDER BY (c.sucursal_id = e.sucursal_id) DESC, h.apertura DESC, h.id DESC LIMIT 1");
        $s->execute([$empleadoId]);
        return $s->fetch() ?: null;
    }

    public function sucursalDe(int $empleadoId): string
    {
        $s = $this->db()->prepare('SELECT s.nombre FROM empleado e JOIN sucursal s ON s.id = e.sucursal_id WHERE e.id = ?');
        $s->execute([$empleadoId]);
        return (string)($s->fetchColumn() ?: '');
    }

    // Todos los productos con su estado y su stock en la sucursal (0 si no tienen registro).
    public function productos(int $sucursalId): array
    {
        $s = $this->db()->prepare('SELECT p.id, p.codigo_barras, p.nombre, c.nombre AS categoria,
                p.unidad, p.precio, p.activo, COALESCE(st.cantidad, 0) AS cantidad
            FROM producto p
            JOIN categoria c ON c.id = p.categoria_id
            LEFT JOIN stock st ON st.producto_id = p.id AND st.sucursal_id = ?
            ORDER BY p.nombre');
        $s->execute([$sucursalId]);
        return array_map(fn($p) => [
            'id' => (int)$p['id'], 'codigo' => $p['codigo_barras'], 'nombre' => $p['nombre'],
            'categoria' => $p['categoria'], 'unidad' => $p['unidad'], 'activo' => (bool)$p['activo'],
            'precio' => (float)$p['precio'], 'stock' => (float)$p['cantidad'],
        ], $s->fetchAll());
    }

    // Número del próximo comprobante de una serie (solo para mostrarlo).
    public function siguiente(int $sucursalId, string $serie): int
    {
        $s = $this->db()->prepare('SELECT COALESCE(MAX(correlativo), 0) + 1 FROM venta WHERE sucursal_id = ? AND serie = ?');
        $s->execute([$sucursalId, $serie]);
        return (int)$s->fetchColumn();
    }

    public function cliente(string $doc): ?array
    {
        $s = $this->db()->prepare('SELECT nombre FROM cliente WHERE numero_documento = ? LIMIT 1');
        $s->execute([$doc]);
        return $s->fetch() ?: null;
    }

    /**
     * Registra la venta completa en una transacción: venta, detalle, pagos y salida de stock.
     * $d: tipo, doc, nombre, items [{id, qty}], pagos [{m, monto, ref}].
     * Los precios se leen de la base de datos, nunca del navegador.
     * Devuelve ['id', 'recibido', 'vuelto'].
     */
    public function registrar(array $turno, int $empleadoId, array $d): array
    {
        $tipo = (string)($d['tipo'] ?? '');
        if (!in_array($tipo, ['BOLETA', 'FACTURA'], true)) {
            throw new InvalidArgumentException('Tipo de comprobante inválido.');
        }
        $sid = (int)$turno['sucursal_id'];

        // Líneas: un mismo producto se junta en una sola.
        $pedido = [];
        foreach ((array)($d['items'] ?? []) as $i) {
            $id = (int)($i['id'] ?? 0);
            $qty = round((float)($i['qty'] ?? 0), 3);
            if ($id <= 0 || $qty <= 0) {
                throw new InvalidArgumentException('Hay una línea inválida en la venta.');
            }
            $pedido[$id] = round(($pedido[$id] ?? 0) + $qty, 3);
        }
        if (!$pedido) {
            throw new InvalidArgumentException('Agrega al menos un producto.');
        }

        // Cliente.
        $doc = preg_replace('/\D/', '', (string)($d['doc'] ?? ''));
        $nom = trim((string)($d['nombre'] ?? ''));
        if ($tipo === 'FACTURA' && strlen($doc) !== 11) {
            throw new InvalidArgumentException('La factura requiere un RUC de 11 dígitos.');
        }
        if (($doc !== '' || $nom !== '') && !in_array(strlen($doc), [8, 11], true)) {
            throw new InvalidArgumentException('El DNI debe tener 8 dígitos y el RUC 11.');
        }

        $db = $this->db();
        $db->beginTransaction();
        try {
            // Bloquea la sucursal: dos cajeros no pueden tomar el mismo correlativo.
            $db->prepare('SELECT id FROM sucursal WHERE id = ? FOR UPDATE')->execute([$sid]);

            // Productos, precios y stock desde la base de datos (en centavos para no perder decimales).
            $ps = $db->prepare('SELECT p.id, p.nombre, p.unidad, p.precio, p.activo, st.cantidad
                FROM producto p JOIN stock st ON st.producto_id = p.id AND st.sucursal_id = ?
                WHERE p.id = ? FOR UPDATE');
            $lineas = [];
            $totalC = 0;
            foreach ($pedido as $id => $qty) {
                $ps->execute([$sid, $id]);
                $p = $ps->fetch();
                if (!$p) {
                    throw new RuntimeException('Un producto de la venta no existe en esta sucursal.');
                }
                if (!$p['activo']) {
                    throw new RuntimeException($p['nombre'] . ' está inactivo.');
                }
                if ($p['unidad'] !== 'kg' && $qty != floor($qty)) {
                    throw new InvalidArgumentException($p['nombre'] . ' solo se vende por unidades enteras.');
                }
                if ($qty > (float)$p['cantidad']) {
                    throw new RuntimeException('Stock insuficiente de ' . $p['nombre'] . ' (disponible ' . (float)$p['cantidad'] . ').');
                }
                $puC = (int)round((float)$p['precio'] * 100);
                $tlC = (int)round($qty * $puC);
                $totalC += $tlC;
                $lineas[] = ['id' => $id, 'qty' => $qty, 'puC' => $puC, 'tlC' => $tlC];
            }
            if ($totalC <= 0) {
                throw new InvalidArgumentException('El total de la venta debe ser mayor que 0.');
            }

            // Pagos: el vuelto solo puede salir del efectivo recibido.
            $pagos = [];
            $recibidoC = 0;
            $efectivoC = 0;
            foreach ((array)($d['pagos'] ?? []) as $p) {
                $m = strtoupper((string)($p['m'] ?? ''));
                if (!in_array($m, ['EFECTIVO', 'YAPE', 'PLIN', 'TARJETA'], true)) {
                    throw new InvalidArgumentException('Medio de pago inválido.');
                }
                $c = (int)round((float)($p['monto'] ?? 0) * 100);
                if ($c <= 0) {
                    throw new InvalidArgumentException('Cada pago debe tener un monto mayor que 0.');
                }
                $pagos[] = ['m' => $m, 'c' => $c, 'ref' => mb_substr(trim((string)($p['ref'] ?? '')), 0, 30)];
                $recibidoC += $c;
                if ($m === 'EFECTIVO') {
                    $efectivoC += $c;
                }
            }
            if ($recibidoC < $totalC) {
                throw new InvalidArgumentException('El monto pagado no cubre el total.');
            }
            $vueltoC = $recibidoC - $totalC;
            if ($vueltoC > $efectivoC) {
                throw new InvalidArgumentException('El vuelto solo puede salir del efectivo recibido.');
            }
            $resta = $vueltoC;
            foreach ($pagos as &$p) {
                if ($p['m'] === 'EFECTIVO' && $resta > 0) {
                    $x = min($resta, $p['c']);
                    $p['c'] -= $x;
                    $resta -= $x;
                }
            }
            unset($p);
            $pagos = array_values(array_filter($pagos, fn($p) => $p['c'] > 0));

            // Cliente: se usa el registrado o se crea uno nuevo.
            $clienteId = null;
            if ($doc !== '') {
                $tdoc = strlen($doc) === 8 ? 'DNI' : 'RUC';
                $s = $db->prepare('SELECT id FROM cliente WHERE tipo_documento = ? AND numero_documento = ?');
                $s->execute([$tdoc, $doc]);
                $clienteId = $s->fetchColumn() ?: null;
                if (!$clienteId) {
                    if ($nom === '') {
                        throw new InvalidArgumentException('Falta el nombre o razón social del cliente.');
                    }
                    $db->prepare('INSERT INTO cliente (tipo_documento, numero_documento, nombre) VALUES (?, ?, ?)')
                       ->execute([$tdoc, $doc, mb_substr($nom, 0, 120)]);
                    $clienteId = (int)$db->lastInsertId();
                }
            }

            // Correlativo e importes (el precio ya incluye IGV).
            $serie = $tipo === 'BOLETA' ? $turno['serie_boleta'] : $turno['serie_factura'];
            $s = $db->prepare('SELECT COALESCE(MAX(correlativo), 0) + 1 FROM venta WHERE sucursal_id = ? AND serie = ?');
            $s->execute([$sid, $serie]);
            $corr = (int)$s->fetchColumn();
            $subC = (int)round($totalC / 1.18);
            $igvC = $totalC - $subC;

            $db->prepare('INSERT INTO venta (sucursal_id, historia_caja_id, empleado_id, cliente_id, tipo_comprobante,
                    serie, correlativo, fecha, subtotal, igv, total, estado)
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)')
               ->execute([$sid, $turno['id'], $empleadoId, $clienteId, $tipo, $serie, $corr,
                          $this->dec($subC), $this->dec($igvC), $this->dec($totalC), 'EMITIDA']);
            $ventaId = (int)$db->lastInsertId();
            $comprobante = sprintf('%s-%06d', $serie, $corr);

            $det = $db->prepare('INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, total_linea) VALUES (?, ?, ?, ?, ?)');
            $stock = new MovimientoStock($db);   // se une a esta misma transacción
            foreach ($lineas as $l) {
                $qty = number_format($l['qty'], 3, '.', '');
                $det->execute([$ventaId, $l['id'], $qty, $this->dec($l['puC']), $this->dec($l['tlC'])]);
                $stock->registrar([
                    'sucursal_id' => $sid, 'producto_id' => $l['id'], 'empleado_id' => $empleadoId,
                    'venta_id' => $ventaId, 'tipo' => 'SALIDA', 'cantidad' => $qty, 'motivo' => 'Venta ' . $comprobante,
                ]);
            }

            $pg = $db->prepare('INSERT INTO pago_venta (venta_id, medio_pago, monto, referencia) VALUES (?, ?, ?, ?)');
            foreach ($pagos as $p) {
                $pg->execute([$ventaId, $p['m'], $this->dec($p['c']), $p['m'] === 'EFECTIVO' || $p['ref'] === '' ? null : $p['ref']]);
            }

            $db->commit();
            return ['id' => $ventaId, 'recibido' => $recibidoC / 100, 'vuelto' => $vueltoC / 100];
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    private function dec(int $centavos): string
    {
        return number_format($centavos / 100, 2, '.', '');
    }
}