<?php
class ClienteRepository extends Repository
{
    protected string $tabla = 'cliente';
    protected string $listaSql = 'SELECT c.id, c.tipo_documento, c.numero_documento, c.nombre,
        (SELECT COUNT(*) FROM venta v WHERE v.cliente_id = c.id) AS ventas
        FROM cliente c ORDER BY c.nombre';

    // ---- Reglas para el estado del cliente (ajústalas a tu gusto) ----
    const VIP_COMPRAS    = 10;    // desde cuántas compras es VIP
    const VIP_MONTO      = 1000;  // o desde cuántos soles acumulados
    const DIAS_INACTIVO  = 90;    // sin comprar en estos días => Inactivo

    private const MESES = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

    public static function estado(array $f): string
    {
        if ((int)$f['compras'] === 0 || empty($f['ultima'])) return 'INACTIVO';
        if (strtotime($f['ultima']) < strtotime('-' . self::DIAS_INACTIVO . ' days')) return 'INACTIVO';
        if ((int)$f['compras'] >= self::VIP_COMPRAS || (float)$f['total'] >= self::VIP_MONTO) return 'VIP';
        return 'ACTIVO';
    }

    /** Todos los clientes con compras, total gastado, última compra y estado. */
    public function conResumen(): array
    {
        $filas = $this->db()->query(
            "SELECT c.id, c.tipo_documento, c.numero_documento, c.nombre,
                    COUNT(v.id) AS compras, COALESCE(SUM(v.total), 0) AS total, MAX(v.fecha) AS ultima
             FROM cliente c
             LEFT JOIN venta v ON v.cliente_id = c.id AND v.estado = 'EMITIDA'
             GROUP BY c.id, c.tipo_documento, c.numero_documento, c.nombre
             ORDER BY c.nombre"
        )->fetchAll();
        foreach ($filas as &$f) $f['estado'] = self::estado($f);
        return $filas;
    }

    /** Informe general de todos los clientes (panel lateral). */
    public function informeGeneral(array $filas): array
    {
        $estados = ['VIP' => 0, 'ACTIVO' => 0, 'INACTIVO' => 0];
        $compras = 0; $ingresos = 0.0;
        foreach ($filas as $f) {
            $estados[$f['estado']]++;
            $compras += (int)$f['compras'];
            $ingresos += (float)$f['total'];
        }
        $top = $filas;
        usort($top, fn($a, $b) => (float)$b['total'] <=> (float)$a['total']);
        $top = array_values(array_filter(array_slice($top, 0, 5), fn($f) => (float)$f['total'] > 0));

        return [
            'clientes' => count($filas),
            'estados'  => $estados,
            'compras'  => $compras,
            'ingresos' => $ingresos,
            'ticket'   => $compras ? $ingresos / $compras : 0,
            'meses'    => $this->ventasPorMes(null),
            'top'      => $top,
        ];
    }

    /** Ventas de los últimos $n meses. $id = null => de todos los clientes registrados. */
    public function ventasPorMes(?int $id, int $n = 6): array
    {
        $ini = date('Y-m-01', strtotime('-' . ($n - 1) . ' months', strtotime(date('Y-m-01'))));
        $sql = "SELECT DATE_FORMAT(fecha, '%Y-%m') AS ym, SUM(total) AS t FROM venta
                WHERE estado = 'EMITIDA' AND fecha >= ? AND " . ($id ? 'cliente_id = ?' : 'cliente_id IS NOT NULL') . '
                GROUP BY ym';
        $s = $this->db()->prepare($sql);
        $s->execute($id ? [$ini, $id] : [$ini]);
        $datos = array_column($s->fetchAll(), 't', 'ym');

        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $t = strtotime("+$i months", strtotime($ini));
            $out[] = ['l' => self::MESES[(int)date('n', $t)], 'v' => (float)($datos[date('Y-m', $t)] ?? 0)];
        }
        return $out;
    }

    /** Informe personal de un cliente (lo consume el panel por AJAX). */
    public function detalle(int $id): ?array
    {
        $c = $this->buscar($id);
        if (!$c) return null;

        $s = $this->db()->prepare("SELECT COUNT(*) AS compras, COALESCE(SUM(total), 0) AS total, MAX(fecha) AS ultima
                                   FROM venta WHERE cliente_id = ? AND estado = 'EMITIDA'");
        $s->execute([$id]);
        $r = $s->fetch();
        $c['compras'] = (int)$r['compras'];
        $c['total']   = (float)$r['total'];
        $c['ultima']  = $r['ultima'];
        $c['ticket']  = $c['compras'] ? $c['total'] / $c['compras'] : 0;
        $c['estado']  = self::estado($c);
        $c['meses']   = $this->ventasPorMes($id);

        $s = $this->db()->prepare(
            "SELECT v.id, v.fecha, v.tipo_comprobante, v.serie, v.correlativo, v.total,
                    (SELECT COUNT(*) FROM detalle_venta d WHERE d.venta_id = v.id) AS items
             FROM venta v WHERE v.cliente_id = ? AND v.estado = 'EMITIDA'
             ORDER BY v.fecha DESC LIMIT 5"
        );
        $s->execute([$id]);
        $c['tickets'] = $s->fetchAll();
        return $c;
    }
}