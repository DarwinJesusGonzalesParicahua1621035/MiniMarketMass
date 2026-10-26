<?php
declare(strict_types=1);

require_once __DIR__ . '/../Caja.php';

/**
 * Acceso a datos de la tabla caja.
 *
 * FERNANDEZ CHAUCAYANQUI JEAN MICHEL
 */
class CajaRepository
{
    public function __construct(private PDO $pdo) {}

    public function listarPorSucursal(int $sucursalId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, sucursal_id, nombre FROM caja WHERE sucursal_id = :sucursal_id ORDER BY nombre'
        );
        $stmt->execute(['sucursal_id' => $sucursalId]);

        $cajas = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $cajas[] = $this->mapear($row);
        }
        return $cajas;
    }

    public function buscarPorId(int $id): ?Caja
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, sucursal_id, nombre FROM caja WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapear($row) : null;
    }

    public function crear(int $sucursalId, string $nombre): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO caja (sucursal_id, nombre) VALUES (:sucursal_id, :nombre)'
        );
        $stmt->execute([
            'sucursal_id' => $sucursalId,
            'nombre'      => $nombre,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function mapear(array $row): Caja
    {
        return new Caja(
            (int) $row['id'],
            (int) $row['sucursal_id'],
            $row['nombre']
        );
    }
}
