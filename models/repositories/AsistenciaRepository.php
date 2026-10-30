<?php

class AsistenciaRepository
{
    public function __construct(private PDO $db) {}

    public function listar(string $q = ''): array
    {
        $sql = 'SELECT a.id, a.empleado_id, e.nombre AS empleado, a.entrada, a.salida, a.estado
                FROM asistencia a
                JOIN empleado e ON e.id = a.empleado_id';
        $p = [];
        if ($q !== '') {
            $sql .= ' WHERE e.nombre LIKE :a OR a.estado LIKE :b';
            $p = [':a' => '%' . $q . '%', ':b' => '%' . $q . '%'];
        }
        $sql .= ' ORDER BY a.entrada DESC, a.id DESC';
        $st = $this->db->prepare($sql);
        $st->execute($p);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // Solo lectura de empleado (módulo de Darwin): id y nombre para el selector.
    public function empleados(): array
    {
        return $this->db->query('SELECT id, nombre FROM empleado ORDER BY nombre')
                        ->fetchAll(PDO::FETCH_ASSOC);
    }

    public function crear(int $empleadoId, string $entrada, ?string $salida, string $estado): void
    {
        $st = $this->db->prepare(
            'INSERT INTO asistencia (empleado_id, entrada, salida, estado) VALUES (:e, :en, :sa, :es)'
        );
        $st->execute([':e' => $empleadoId, ':en' => $entrada, ':sa' => $salida, ':es' => $estado]);
    }

    public function actualizar(int $id, int $empleadoId, string $entrada, ?string $salida, string $estado): void
    {
        $st = $this->db->prepare(
            'UPDATE asistencia SET empleado_id = :e, entrada = :en, salida = :sa, estado = :es WHERE id = :id'
        );
        $st->execute([':e' => $empleadoId, ':en' => $entrada, ':sa' => $salida, ':es' => $estado, ':id' => $id]);
    }

    public function eliminar(int $id): void
    {
        $st = $this->db->prepare('DELETE FROM asistencia WHERE id = :id');
        $st->execute([':id' => $id]);
    }
}