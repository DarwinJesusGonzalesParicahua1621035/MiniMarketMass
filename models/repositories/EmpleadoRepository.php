<?php
class EmpleadoRepository extends Repository
{
    protected string $tabla = 'empleado';
    protected string $listaSql = "SELECT e.id, e.nombre, e.usuario, e.rol, s.nombre AS sucursal,
        COALESCE(sup.nombre, '—') AS supervisor, e.activo
        FROM empleado e JOIN sucursal s ON s.id = e.sucursal_id
        LEFT JOIN empleado sup ON sup.id = e.supervisor_id ORDER BY e.id";
}