<?php
class SucursalRepository extends Repository
{
    protected string $tabla = 'sucursal';
    protected string $listaSql = 'SELECT s.id, s.nombre, s.direccion, d.nombre AS distrito, s.serie_boleta, s.serie_factura
        FROM sucursal s JOIN distrito d ON d.id = s.distrito_id ORDER BY s.id';
}