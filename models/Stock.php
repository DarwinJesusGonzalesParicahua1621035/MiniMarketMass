<?php
declare(strict_types=1);

class Stock
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // Lista el stock. Si se da una sucursal, solo muestra esa sucursal.
    public function listar(?int $sucursalId = null): array
    {
        $sql = 'SELECT sucursal_id, producto_id, cantidad FROM stock';
        $params = [];

        if ($sucursalId !== null) {
            $sql .= ' WHERE sucursal_id = :sucursal_id';
            $params['sucursal_id'] = $sucursalId;
        }

        $sql .= ' ORDER BY sucursal_id, producto_id';

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    // Devuelve el stock de un producto en una sucursal, o null si no existe.
    public function obtener(int $sucursalId, int $productoId): ?array
    {
        $st = $this->db->prepare(
            'SELECT sucursal_id, producto_id, cantidad
             FROM stock
             WHERE sucursal_id = :sucursal_id AND producto_id = :producto_id'
        );
        $st->execute([
            'sucursal_id' => $sucursalId,
            'producto_id' => $productoId,
        ]);

        $fila = $st->fetch(PDO::FETCH_ASSOC);
        return $fila === false ? null : $fila;
    }

    // Crea el registro de stock en 0 para un producto en una sucursal.
    // Devuelve true si lo creó y false si ya existía.
    public function inicializar(int $sucursalId, int $productoId): bool
    {
        $st = $this->db->prepare(
            'INSERT INTO stock (sucursal_id, producto_id, cantidad)
             VALUES (:sucursal_id, :producto_id, 0)
             ON DUPLICATE KEY UPDATE sucursal_id = sucursal_id'
        );
        $st->execute([
            'sucursal_id' => $sucursalId,
            'producto_id' => $productoId,
        ]);

        return $st->rowCount() === 1;
    }
}