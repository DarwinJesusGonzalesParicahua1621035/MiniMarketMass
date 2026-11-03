<?php
class ClienteRepository extends Repository
{
    protected string $tabla = 'cliente';
    protected string $listaSql = 'SELECT c.id, c.tipo_documento, c.numero_documento, c.nombre,
        (SELECT COUNT(*) FROM venta v WHERE v.cliente_id = c.id) AS ventas
        FROM cliente c ORDER BY c.nombre';
}