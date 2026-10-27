<?php
class StockRepository extends Repository
{
    protected string $tabla = 'stock';
    protected string $listaSql = 'SELECT s.nombre AS sucursal, p.nombre AS producto, st.cantidad
        FROM stock st JOIN sucursal s ON s.id = st.sucursal_id JOIN producto p ON p.id = st.producto_id
        ORDER BY s.nombre, p.nombre';

    public function movimientos(): array
    {
        return $this->db()->query("SELECT m.id, s.nombre AS sucursal, p.nombre AS producto, m.tipo, m.cantidad,
            COALESCE(m.motivo, '—') AS motivo, m.fecha
            FROM movimiento_stock m JOIN sucursal s ON s.id = m.sucursal_id JOIN producto p ON p.id = m.producto_id
            ORDER BY m.fecha DESC, m.id DESC")->fetchAll();
    }

    public function registrar(int $suc, int $prod, string $tipo, float $cant, ?string $motivo, int $emp): void
    {
        $db = $this->db();
        $db->beginTransaction();
        try {
            $db->prepare('INSERT IGNORE INTO stock (sucursal_id, producto_id, cantidad) VALUES (?, ?, 0)')->execute([$suc, $prod]);
            $signo = in_array($tipo, ['SALIDA', 'MERMA'], true) ? -1 : 1;
            $db->prepare('UPDATE stock SET cantidad = cantidad + ? WHERE sucursal_id = ? AND producto_id = ?')
               ->execute([$signo * $cant, $suc, $prod]);
            $db->prepare('INSERT INTO movimiento_stock (sucursal_id, producto_id, empleado_id, tipo, cantidad, motivo, fecha)
                VALUES (?, ?, ?, ?, ?, ?, NOW())')->execute([$suc, $prod, $emp, $tipo, $cant, $motivo]);
            $db->commit();
        } catch (Throwable $e) {
            $db->rollBack();
            throw $e;
        }
    }
}