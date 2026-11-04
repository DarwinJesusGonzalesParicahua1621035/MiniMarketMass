<?php
class IncidenteRepository extends Repository
{
    protected string $tabla = 'incidente';
    protected string $listaSql = 'SELECT i.id, i.sucursal_id, s.nombre AS sucursal, i.tipo, i.descripcion, i.fecha, i.estado
        FROM incidente i JOIN sucursal s ON s.id = i.sucursal_id
        ORDER BY i.estado ASC, i.fecha DESC, i.id DESC';
}