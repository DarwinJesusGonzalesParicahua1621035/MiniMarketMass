<?php
class CajaRepository extends Repository
{
    protected string $tabla = 'caja';
    protected string $listaSql = 'SELECT c.id, c.sucursal_id, s.nombre AS sucursal, c.nombre
        FROM caja c JOIN sucursal s ON s.id = c.sucursal_id ORDER BY s.nombre, c.nombre';

    public function ahora(): array
    {
        $sql = "SELECT c.id, c.nombre, s.nombre AS sucursal,
                h.id AS historia_id, h.apertura, h.monto_inicial, e.nombre AS responsable,
                COALESCE(vt.n, 0) AS n, COALESCE(vt.tot, 0) AS tot, COALESCE(pg.ef, 0) AS ef
            FROM caja c
            JOIN sucursal s ON s.id = c.sucursal_id
            LEFT JOIN historia_caja h ON h.caja_id = c.id AND h.estado = 'ABIERTA'
            LEFT JOIN empleado e ON e.id = h.empleado_id
            LEFT JOIN (
                SELECT historia_caja_id AS hid, COUNT(*) AS n, SUM(total) AS tot
                FROM venta WHERE estado = 'EMITIDA' GROUP BY historia_caja_id
            ) vt ON vt.hid = h.id
            LEFT JOIN (
                SELECT v.historia_caja_id AS hid, SUM(pv.monto) AS ef
                FROM venta v JOIN pago_venta pv ON pv.venta_id = v.id
                WHERE v.estado = 'EMITIDA' AND pv.medio_pago = 'EFECTIVO'
                GROUP BY v.historia_caja_id
            ) pg ON pg.hid = h.id
            ORDER BY s.nombre, c.nombre";
        return $this->db()->query($sql)->fetchAll();
    }

    public function sucursales(): array
    {
        return $this->pares('SELECT id, nombre FROM sucursal ORDER BY nombre');
    }

    public function responsables(): array
    {
        return $this->pares("SELECT id, nombre FROM empleado
            WHERE activo = 1 AND rol IN ('ADMINISTRADOR', 'CAJERO') ORDER BY nombre");
    }

    public function crear(int $sucursalId, string $nombre): int
    {
        $nombre = trim($nombre);
        if ($sucursalId <= 0) {
            throw new InvalidArgumentException('elige una sucursal.');
        }
        if ($nombre === '' || mb_strlen($nombre) > 40) {
            throw new InvalidArgumentException('el nombre es obligatorio y tiene máximo 40 caracteres.');
        }
        return $this->insertar(['sucursal_id' => $sucursalId, 'nombre' => $nombre]);
    }
}