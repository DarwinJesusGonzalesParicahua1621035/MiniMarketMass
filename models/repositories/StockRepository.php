<?php
class StockRepository extends Repository
{
    protected string $tabla = 'stock';
    protected string $listaSql = 'SELECT st.sucursal_id, st.producto_id, s.nombre AS sucursal,
        p.codigo_barras, p.nombre AS producto, p.unidad, st.cantidad
        FROM stock st JOIN sucursal s ON s.id = st.sucursal_id
        JOIN producto p ON p.id = st.producto_id ORDER BY s.nombre, p.nombre';

    public function resumen(?int $sucursalId = null): array
    {
        $sql = 'SELECT st.sucursal_id, st.producto_id, s.nombre AS sucursal,
                p.codigo_barras, p.nombre AS producto, p.unidad, st.cantidad
                FROM stock st JOIN sucursal s ON s.id = st.sucursal_id
                JOIN producto p ON p.id = st.producto_id '
             . ($sucursalId ? 'WHERE st.sucursal_id = ? ' : '')
             . 'ORDER BY s.nombre, p.nombre';
        $s = $this->db()->prepare($sql);
        $s->execute($sucursalId ? [$sucursalId] : []);
        return $s->fetchAll();
    }

    public function historial(?int $sucursalId = null, ?string $tipo = null, int $limite = 200): array
    {
        $sql = 'SELECT m.id, m.fecha, s.nombre AS sucursal, p.nombre AS producto, m.tipo,
                m.cantidad, m.motivo, e.nombre AS empleado, m.venta_id
                FROM movimiento_stock m
                JOIN sucursal s ON s.id = m.sucursal_id
                JOIN producto p ON p.id = m.producto_id
                JOIN empleado e ON e.id = m.empleado_id
                WHERE 1 = 1';
        $p = [];
        if ($sucursalId) { $sql .= ' AND m.sucursal_id = ?'; $p[] = $sucursalId; }
        if ($tipo)       { $sql .= ' AND m.tipo = ?';        $p[] = $tipo; }
        $sql .= ' ORDER BY m.fecha DESC, m.id DESC LIMIT ' . (int)$limite;
        $s = $this->db()->prepare($sql);
        $s->execute($p);
        return $s->fetchAll();
    }

    // Combinaciones sucursal-producto que ya tienen stock (valor "sucursal-producto").
    public function combinaciones(): array
    {
        $o = [];
        $q = "SELECT st.sucursal_id, st.producto_id,
              CONCAT(s.nombre, ' · ', p.nombre, ' (', st.cantidad, ' ', p.unidad, ')') AS texto
              FROM stock st JOIN sucursal s ON s.id = st.sucursal_id
              JOIN producto p ON p.id = st.producto_id ORDER BY s.nombre, p.nombre";
        foreach ($this->db()->query($q) as $r) {
            $o[$r['sucursal_id'] . '-' . $r['producto_id']] = $r['texto'];
        }
        return $o;
    }

    // Productos activos que todavía no tienen fila de stock en alguna sucursal.
    public function faltantes(): array
    {
        return $this->db()->query(
            "SELECT s.id AS sucursal_id, p.id AS producto_id,
                    CONCAT(s.nombre, ' · ', p.nombre) AS texto
             FROM sucursal s CROSS JOIN producto p
             LEFT JOIN stock st ON st.sucursal_id = s.id AND st.producto_id = p.id
             WHERE p.activo = 1 AND st.producto_id IS NULL
             ORDER BY s.nombre, p.nombre"
        )->fetchAll();
    }
}