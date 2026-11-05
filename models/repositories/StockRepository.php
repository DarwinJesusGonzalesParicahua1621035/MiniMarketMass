<?php
class StockRepository extends Repository
{
    protected string $tabla = 'stock';
    protected string $listaSql = 'SELECT st.sucursal_id, st.producto_id, s.nombre AS sucursal,
        p.codigo_barras, p.nombre AS producto, p.unidad, st.cantidad
        FROM stock st JOIN sucursal s ON s.id = st.sucursal_id
        JOIN producto p ON p.id = st.producto_id ORDER BY s.nombre, p.nombre';

    // Todo el inventario: una fila por producto activo en cada sucursal donde se maneja.
    public function inventario(): array
    {
        return $this->db()->query(
            'SELECT st.sucursal_id, s.nombre AS sucursal, p.id AS producto_id, p.codigo_barras,
                    p.nombre AS producto, c.nombre AS categoria, p.unidad, p.precio, st.cantidad,
                    (SELECT MAX(m.fecha) FROM movimiento_stock m
                      WHERE m.sucursal_id = st.sucursal_id AND m.producto_id = st.producto_id) AS ultimo
             FROM stock st
             JOIN sucursal s ON s.id = st.sucursal_id
             JOIN producto p ON p.id = st.producto_id
             JOIN categoria c ON c.id = p.categoria_id
             WHERE p.activo = 1
             ORDER BY s.nombre, p.nombre'
        )->fetchAll();
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

    // Productos que se manejan en cada sucursal (solo ellos admiten movimientos), con su categoría.
    public function opcionesStock(): array
    {
        return $this->db()->query(
            "SELECT s.id AS sucursal_id, s.nombre AS sucursal,
                    p.id AS producto_id, p.nombre AS producto, p.codigo_barras AS codigo, p.unidad,
                    c.id AS categoria_id, c.nombre AS categoria,
                    st.cantidad
             FROM stock st
             JOIN sucursal s ON s.id = st.sucursal_id
             JOIN producto p ON p.id = st.producto_id
             JOIN categoria c ON c.id = p.categoria_id
             WHERE p.activo = 1
             ORDER BY s.nombre, p.nombre"
        )->fetchAll();
    }

    // Motivos escritos antes en movimientos manuales, del más reciente al más antiguo (sin repetir).
    // Alimentan las sugerencias del campo "Motivo".
    public function motivosRecientes(int $limite = 30): array
    {
        $q = "SELECT motivo FROM movimiento_stock
              WHERE motivo IS NOT NULL AND motivo <> '' AND venta_id IS NULL
              GROUP BY motivo
              ORDER BY MAX(fecha) DESC, MAX(id) DESC
              LIMIT " . (int)$limite;
        return array_map('strval', $this->db()->query($q)->fetchAll(PDO::FETCH_COLUMN));
    }

}
