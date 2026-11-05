<?php
class AsistenciaRepository extends Repository
{
    protected string $tabla = 'asistencia';
    // Cambios: ahora trae a.empleado_id (la vista lo necesita para editar) y deja a.salida tal cual
    // (NULL si todavía no marcó salida). Antes se devolvía el texto '—', y la vista lo confundía con una hora.
    protected string $listaSql = "SELECT a.id, a.empleado_id, e.nombre AS empleado, a.entrada, a.salida, a.estado
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
