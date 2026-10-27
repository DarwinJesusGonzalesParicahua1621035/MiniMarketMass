<?php

class SucursalRepository
{
    public function __construct(private PDO $db) {}

    public function listar(string $q = ''): array
    {
        $sql = 'SELECT s.id, s.nombre, s.direccion, s.distrito_id, d.nombre AS distrito,
                       s.serie_boleta, s.serie_factura
                FROM sucursal s
                JOIN distrito d ON d.id = s.distrito_id';
        $p = [];
        if ($q !== '') {
            $sql .= ' WHERE s.nombre LIKE :a OR s.direccion LIKE :b OR d.nombre LIKE :c
                      OR s.serie_boleta LIKE :d OR s.serie_factura LIKE :e';
            foreach (['a', 'b', 'c', 'd', 'e'] as $k) {
                $p[':' . $k] = '%' . $q . '%';
            }
        }
        $sql .= ' ORDER BY s.id';
        $st = $this->db->prepare($sql);
        $st->execute($p);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function distritos(): array
    {
        return $this->db->query('SELECT id, nombre FROM distrito ORDER BY nombre')
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(string $nombre, string $direccion, int $distritoId, string $serieB, string $serieF): void
    {
        $st = $this->db->prepare(
            'INSERT INTO sucursal (nombre, direccion, distrito_id, serie_boleta, serie_factura)
             VALUES (:n, :d, :di, :sb, :sf)'
        );
        $st->execute([':n' => $nombre, ':d' => $direccion, ':di' => $distritoId, ':sb' => $serieB, ':sf' => $serieF]);
    }

    public function actualizar(int $id, string $nombre, string $direccion, int $distritoId, string $serieB, string $serieF): void
    {
        $st = $this->db->prepare(
            'UPDATE sucursal
             SET nombre = :n, direccion = :d, distrito_id = :di, serie_boleta = :sb, serie_factura = :sf
             WHERE id = :id'
        );
        $st->execute([':n' => $nombre, ':d' => $direccion, ':di' => $distritoId, ':sb' => $serieB, ':sf' => $serieF, ':id' => $id]);
    }

    public function eliminar(int $id): void
    {
        $st = $this->db->prepare('DELETE FROM sucursal WHERE id = :id');
        $st->execute([':id' => $id]);
    }
}