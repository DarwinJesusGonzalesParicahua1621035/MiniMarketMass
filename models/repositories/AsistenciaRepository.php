<?php
class AsistenciaRepository extends Repository
{
    protected string $tabla = 'asistencia';

    // Regla única de asistencia para todo el sistema (hora de Lima):
    //   hasta las 08:30 → PRESENTE (temprano) · después de las 08:30 → TARDANZA (tarde)
    //   FALTA solo si el administrador la registra; quien no marcó aparece como "sin marcar".
    public const LIMITE_TARDE = '08:30';

    // Estado que corresponde a una hora de entrada (acepta 'AAAA-MM-DD HH:MM:SS').
    public static function estadoPorEntrada(string $entrada): string
    {
        return date('H:i', strtotime($entrada)) > self::LIMITE_TARDE ? 'TARDANZA' : 'PRESENTE';
    }

    // Una falta cargada a mano (o por no marcar) se guarda con la hora 00:00:00: no hay entrada real.
    public static function sinEntrada(array $a): bool
    {
        return ($a['estado'] ?? '') === 'FALTA' && date('H:i:s', strtotime($a['entrada'])) === '00:00:00';
    }

    // Clasificación final para mostrar: temprano | tarde | falta | pendiente (sin marcar)
    // $a = registro de hoy (o null). Se calcula por la hora de entrada, así los registros viejos también quedan bien.
    public static function clasificar(?array $a): string
    {
        if (!$a) return 'pendiente';
        if (self::sinEntrada($a)) return 'falta';
        return ['PRESENTE' => 'temprano', 'TARDANZA' => 'tarde', 'FALTA' => 'falta'][self::estadoPorEntrada($a['entrada'])];
    }

    public static function etiqueta(string $estado): string
    {
        return ['PRESENTE' => 'Temprano', 'TARDANZA' => 'Tarde', 'FALTA' => 'Falta'][$estado] ?? $estado;
    }
    // Cambios: ahora trae a.empleado_id (la vista lo necesita para editar) y deja a.salida tal cual
    // (NULL si todavía no marcó salida). Antes se devolvía el texto '—', y la vista lo confundía con una hora.
    protected string $listaSql = "SELECT a.id, a.empleado_id, e.nombre AS empleado, a.entrada, a.salida, a.estado
        FROM asistencia a JOIN empleado e ON e.id = a.empleado_id ORDER BY a.entrada DESC, a.id DESC";

    // Asistencia del empleado en el día de hoy (null si todavía no la registró).
    public function deHoy(int $empleadoId): ?array
    {
        $s = $this->db()->prepare('SELECT id, entrada, salida, estado FROM asistencia
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
        $ahora = date('Y-m-d H:i:s');
        $this->insertar([
            'empleado_id' => $empleadoId,
            'entrada'     => $ahora,
            'estado'      => self::estadoPorEntrada($ahora),
        ]);
    }

    // Marca la salida del empleado (una sola vez por día y sin caja abierta).
    public function registrarSalida(int $empleadoId): void
    {
        $a = $this->deHoy($empleadoId);
        if (!$a) {
            throw new RuntimeException('primero registra tu asistencia.');
        }
        if ($a['salida']) {
            throw new RuntimeException('ya registraste tu salida hoy.');
        }
        $s = $this->db()->prepare("SELECT COUNT(*) FROM historia_caja WHERE empleado_id = ? AND estado = 'ABIERTA'");
        $s->execute([$empleadoId]);
        if ((int)$s->fetchColumn() > 0) {
            throw new RuntimeException('cierra tu caja antes de terminar el turno.');
        }
        $this->actualizar($a['id'], ['salida' => date('Y-m-d H:i:s')]);
    }
}
