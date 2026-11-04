<?php
class AsistenciaRepository extends Repository
{
    protected string $tabla = 'asistencia';
    protected string $listaSql = "SELECT a.id, e.nombre AS empleado, a.entrada, COALESCE(a.salida, '—') AS salida, a.estado
        FROM asistencia a JOIN empleado e ON e.id = a.empleado_id ORDER BY a.entrada DESC, a.id DESC";

    // Asistencia del empleado en el día de hoy (null si todavía no la registró).
    public function deHoy(int $empleadoId): ?array
    {
        $s = $this->db()->prepare('SELECT id, entrada, estado FROM asistencia
            WHERE empleado_id = ? AND entrada >= ? AND entrada < ? ORDER BY id DESC LIMIT 1');
        $s->execute([$empleadoId, date('Y-m-d 00:00:00'), date('Y-m-d 00:00:00', strtotime('+1 day'))]);
        return $s->fetch() ?: null;
    }

    // Marca la entrada del empleado (una sola vez por día).
    public function registrarEntrada(int $empleadoId): void
    {
        if ($empleadoId <= 0) {
            throw new InvalidArgumentException('empleado no válido.');
        }
        if ($this->deHoy($empleadoId)) {
            throw new RuntimeException('ya registraste tu asistencia hoy.');
        }
        $this->insertar([
            'empleado_id' => $empleadoId,
            'entrada'     => date('Y-m-d H:i:s'),
            'estado'      => 'PRESENTE',
        ]);
    }
}