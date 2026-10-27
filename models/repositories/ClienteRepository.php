<?php
class ClienteRepository extends Repository
{
    protected string $tabla = 'cliente';
    protected string $listaSql = 'SELECT id, tipo_documento, numero_documento, nombre FROM cliente ORDER BY nombre';
}