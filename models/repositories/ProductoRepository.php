<?php
class ProductoRepository extends Repository
{
    protected string $tabla = 'producto';
    protected string $listaSql = 'SELECT p.id, p.codigo_barras, p.nombre, c.nombre AS categoria,
        p.unidad, p.precio, p.activo
        FROM producto p JOIN categoria c ON c.id = p.categoria_id ORDER BY p.nombre';

    // Lista de productos; se puede filtrar por categoría y/o proveedor.
    public function resumen(?int $categoriaId = null, ?int $proveedorId = null): array
    {
        $where = []; $args = [];
        if ($categoriaId) { $where[] = 'p.categoria_id = ?'; $args[] = $categoriaId; }
        if ($proveedorId) { $where[] = 'p.proveedor_id = ?'; $args[] = $proveedorId; }
        $sql = 'SELECT p.id, p.categoria_id, p.proveedor_id, p.codigo_barras, p.nombre, c.nombre AS categoria,
                p.unidad, p.precio, p.activo
                FROM producto p JOIN categoria c ON c.id = p.categoria_id '
             . ($where ? 'WHERE ' . implode(' AND ', $where) . ' ' : '')
             . 'ORDER BY p.nombre';
        $s = $this->db()->prepare($sql);
        $s->execute($args);
        return $s->fetchAll();
    }

    // Sucursales donde se maneja cada producto: [producto_id => [sucursal_id => cantidad]].
    public function asignaciones(): array
    {
        $o = [];
        foreach ($this->db()->query('SELECT producto_id, sucursal_id, cantidad FROM stock') as $r) {
            $o[(int)$r['producto_id']][(int)$r['sucursal_id']] = (float)$r['cantidad'];
        }
        return $o;
    }

    /**
     * Deja el producto en las sucursales marcadas. $marcadas: [sucursal_id => stock inicial (entero)].
     * - Sucursal nueva: crea su fila de stock y, si hay stock inicial, lo registra como ENTRADA.
     * - Sucursal desmarcada: se quita solo si nunca tuvo movimientos.
     */
    public function asignarSucursales(int $productoId, array $marcadas, int $empleadoId): void
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $actual = [];
            $q = $db->prepare('SELECT sucursal_id FROM stock WHERE producto_id = ? FOR UPDATE');
            $q->execute([$productoId]);
            foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $sid) {
                $actual[(int)$sid] = true;
            }

            $mov = new MovimientoStock($db);
            $ins = $db->prepare('INSERT INTO stock (sucursal_id, producto_id, cantidad) VALUES (?, ?, 0)');
            foreach ($marcadas as $sid => $inicial) {
                if (isset($actual[$sid])) {
                    continue;
                }
                $ins->execute([$sid, $productoId]);
                if ($inicial > 0) {
                    $mov->registrar([
                        'sucursal_id' => $sid, 'producto_id' => $productoId, 'empleado_id' => $empleadoId,
                        'tipo' => 'ENTRADA', 'cantidad' => (string)$inicial, 'motivo' => 'Stock inicial',
                    ]);
                }
            }

            $hist = $db->prepare('SELECT COUNT(*) FROM movimiento_stock WHERE sucursal_id = ? AND producto_id = ?');
            $del = $db->prepare('DELETE FROM stock WHERE sucursal_id = ? AND producto_id = ?');
            $nombre = $db->prepare('SELECT nombre FROM sucursal WHERE id = ?');
            foreach (array_keys($actual) as $sid) {
                if (isset($marcadas[$sid])) {
                    continue;
                }
                $hist->execute([$sid, $productoId]);
                if ((int)$hist->fetchColumn() > 0) {
                    $nombre->execute([$sid]);
                    throw new RuntimeException('no se puede quitar de ' . $nombre->fetchColumn()
                        . ' porque ya tiene movimientos en el historial.');
                }
                $del->execute([$sid, $productoId]);
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }

    // Un producto con el nombre de su categoría.
    public function detalle(int $id): ?array
    {
        $s = $this->db()->prepare(
            'SELECT p.id, p.codigo_barras, p.nombre, c.nombre AS categoria, p.unidad, p.precio, p.activo
             FROM producto p JOIN categoria c ON c.id = p.categoria_id WHERE p.id = ?'
        );
        $s->execute([$id]);
        return $s->fetch() ?: null;
    }

    public function existeCodigo(string $codigo, int $excluirId = 0): bool
    {
        $s = $this->db()->prepare('SELECT COUNT(*) FROM producto WHERE codigo_barras = ? AND id <> ?');
        $s->execute([$codigo, $excluirId]);
        return (int)$s->fetchColumn() > 0;
    }

    // Precios del producto, del más reciente al más antiguo.
    public function historial(int $productoId): array
    {
        $s = $this->db()->prepare(
            'SELECT id, precio, fecha_inicio, fecha_fin FROM historial_precio
             WHERE producto_id = ? ORDER BY fecha_inicio DESC'
        );
        $s->execute([$productoId]);
        return $s->fetchAll();
    }

    // Crea el producto y abre su primer precio en historial_precio (misma transacción).
    public function insertar(array $d): int
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $id = parent::insertar($d);
            $db->prepare('INSERT INTO historial_precio (producto_id, precio, fecha_inicio) VALUES (?, ?, NOW())')
               ->execute([$id, $d['precio']]);
            $db->commit();
            return $id;
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    // Actualiza el producto; si cambió el precio, cierra el vigente y abre uno nuevo.
    public function actualizar($id, array $d): void
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $s = $db->prepare('SELECT precio FROM producto WHERE id = ? FOR UPDATE');
            $s->execute([$id]);
            $anterior = $s->fetchColumn();
            if ($anterior === false) {
                throw new RuntimeException('el producto no existe.');
            }
            parent::actualizar($id, $d);
            if (round((float)$anterior, 2) !== round((float)$d['precio'], 2)) {
                $this->cambiarPrecio((int)$id, $d['precio']);
            }
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            throw $e;
        }
    }

    // Se llama dentro de la transacción de actualizar().
    private function cambiarPrecio(int $id, $precio): void
    {
        $db = $this->db();
        $s = $db->prepare('SELECT COUNT(*) FROM historial_precio WHERE producto_id = ? AND fecha_inicio >= NOW()');
        $s->execute([$id]);
        if ((int)$s->fetchColumn() > 0) {
            throw new RuntimeException('el precio ya cambió hace menos de un segundo, intenta de nuevo.');
        }
        $db->prepare('UPDATE historial_precio SET fecha_fin = NOW() WHERE producto_id = ? AND fecha_fin IS NULL')
           ->execute([$id]);
        $db->prepare('INSERT INTO historial_precio (producto_id, precio, fecha_inicio) VALUES (?, ?, NOW())')
           ->execute([$id, $precio]);
    }
}