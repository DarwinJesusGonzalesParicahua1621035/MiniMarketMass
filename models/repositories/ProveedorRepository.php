<?php
class ProveedorRepository extends Repository
{
    protected string $tabla = 'proveedor';

    // Lista de proveedores con la cantidad de productos que abastece.
    protected string $listaSql = 'SELECT p.id, p.ruc, p.razon_social, p.contacto, p.telefono,
        p.correo, p.direccion, p.activo, COUNT(pr.id) AS productos
        FROM proveedor p LEFT JOIN producto pr ON pr.proveedor_id = p.id
        GROUP BY p.id, p.ruc, p.razon_social, p.contacto, p.telefono, p.correo, p.direccion, p.activo
        ORDER BY p.razon_social';

    // Todos los productos, agrupados por proveedor_id (para el drawer de cada proveedor).
    public function productosPorProveedor(): array
    {
        $sql = 'SELECT pr.proveedor_id, pr.nombre, c.nombre AS categoria, pr.precio, pr.activo
                FROM producto pr JOIN categoria c ON c.id = pr.categoria_id
                ORDER BY pr.nombre';
        $o = [];
        foreach ($this->db()->query($sql) as $f) {
            $o[$f['proveedor_id']][] = $f;
        }
        return $o;
    }

    public function existeRuc(string $ruc, int $excluirId = 0): bool
    {
        $s = $this->db()->prepare('SELECT COUNT(*) FROM proveedor WHERE ruc = ? AND id <> ?');
        $s->execute([$ruc, $excluirId]);
        return (int)$s->fetchColumn() > 0;
    }

    public function contarProductos(int $id): int
    {
        $s = $this->db()->prepare('SELECT COUNT(*) FROM producto WHERE proveedor_id = ?');
        $s->execute([$id]);
        return (int)$s->fetchColumn();
    }
}