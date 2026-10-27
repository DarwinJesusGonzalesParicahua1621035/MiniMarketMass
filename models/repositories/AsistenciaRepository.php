<?php
class AsistenciaRepository extends Repository
{
    protected string $tabla = 'asistencia';
    protected string $listaSql = "SELECT a.id, e.nombre AS empleado, a.entrada, COALESCE(a.salida, '—') AS salida, a.estado
        FROM asistencia a JOIN empleado e ON e.id = a.empleado_id ORDER BY a.entrada DESC";
}