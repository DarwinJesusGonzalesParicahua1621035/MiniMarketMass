<?php
class CategoriaRepository extends Repository
{
    protected string $tabla = 'categoria';
    protected string $listaSql = 'SELECT id, nombre FROM categoria ORDER BY nombre';
}