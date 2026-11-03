<?php
class CategoriaRepository extends Repository
{
    protected string $tabla = 'categoria';
    protected string $listaSql = 'SELECT c.id, c.nombre, COUNT(p.id) AS productos
        FROM categoria c LEFT JOIN producto p ON p.categoria_id = c.id
        GROUP BY c.id, c.nombre ORDER BY c.nombre';
}