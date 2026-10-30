<?php

class CapacitacionRepository
{
    public function __construct(private PDO $db) {}

    public function listar(string $q = ''): array
    {
        $sql = 'SELECT c.id, c.empleado_id, e.nombre AS empleado, c.tema, c.fecha, c.horas
                FROM capacitacion c
                JOIN empleado e ON e.id = c.empleado_id';
        $p = [];
        if ($q !== '') {
            $sql .= ' WHERE e.nombre LIKE :a OR c.tema LIKE :b';
            $p = [':a' => '%' . $q . '%', ':b' => '%' . $q . '%'];
        }
        $sql .= ' ORDER BY c.fecha DESC, c.id DESC';
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

    public function crear(int $empleadoId, string $tema, string $fecha, string $horas): void
    {
        $st = $this->db->prepare(
            'INSERT INTO capacitacion (empleado_id, tema, fecha, horas) VALUES (:e, :t, :f, :h)'
        );
        $st->execute([':e' => $empleadoId, ':t' => $tema, ':f' => $fecha, ':h' => $horas]);
    }

    public function actualizar(int $id, int $empleadoId, string $tema, string $fecha, string $horas): void
    {
        $st = $this->db->prepare(
            'UPDATE capacitacion SET empleado_id = :e, tema = :t, fecha = :f, horas = :h WHERE id = :id'
        );
        $st->execute([':e' => $empleadoId, ':t' => $tema, ':f' => $fecha, ':h' => $horas, ':id' => $id]);
    }

    public function eliminar(int $id): void
    {
        $st = $this->db->prepare('DELETE FROM capacitacion WHERE id = :id');
        $st->execute([':id' => $id]);
    }
}