<?php
class CajaRepository extends Repository
{
    protected string $tabla = 'caja';
    protected string $listaSql = 'SELECT c.id, c.sucursal_id, s.nombre AS sucursal, c.nombre
        FROM caja c JOIN sucursal s ON s.id = c.sucursal_id ORDER BY s.nombre, c.nombre';

    // Cajas con su turno abierto (si lo tiene).
    public function conTurno(?int $sucursalId = null): array
    {
        $sql = "SELECT c.id, s.nombre AS sucursal, c.nombre,
                h.id AS historia_id, h.apertura, h.monto_inicial, e.nombre AS empleado
                FROM caja c
                JOIN sucursal s ON s.id = c.sucursal_id
                LEFT JOIN historia_caja h ON h.caja_id = c.id AND h.estado = 'ABIERTA'
                LEFT JOIN empleado e ON e.id = h.empleado_id "
             . ($sucursalId ? 'WHERE c.sucursal_id = ? ' : '')
             . 'ORDER BY s.nombre, c.nombre';
        $s = $this->db()->prepare($sql);
        $s->execute($sucursalId ? [$sucursalId] : []);
        return $s->fetchAll();
    }

    // Cajas sin turno abierto, para el formulario de apertura.
    public function libres(): array
    {
        $o = [];
        $q = "SELECT c.id, CONCAT(s.nombre, ' · ', c.nombre) AS texto
              FROM caja c JOIN sucursal s ON s.id = c.sucursal_id
              WHERE NOT EXISTS (SELECT 1 FROM historia_caja h WHERE h.caja_id = c.id AND h.estado = 'ABIERTA')
              ORDER BY s.nombre, c.nombre";
        foreach ($this->db()->query($q) as $r) {
            $o[$r['id']] = $r['texto'];
        }
        return $o;
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
