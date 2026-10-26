<?php
declare(strict_types=1);

require_once __DIR__ . '/../Incidente.php';

/**
 * Acceso a datos de la tabla incidente.
 *
 * FERNANDEZ CHAUCAYANQUI JEAN MICHEL
 */
class IncidenteRepository
{
    public function __construct(private PDO $pdo) {}

    public function buscarPorId(int $id): ?Incidente
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, sucursal_id, tipo, descripcion, fecha, estado
             FROM incidente WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapear($row) : null;
    }

    public function listarPorSucursal(int $sucursalId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, sucursal_id, tipo, descripcion, fecha, estado
             FROM incidente WHERE sucursal_id = :sucursal_id ORDER BY fecha DESC'
        );
        $stmt->execute(['sucursal_id' => $sucursalId]);

        $lista = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lista[] = $this->mapear($row);
        }
        return $lista;
    }

    public function listarAbiertos(int $sucursalId): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, sucursal_id, tipo, descripcion, fecha, estado
             FROM incidente
             WHERE sucursal_id = :sucursal_id AND estado = 'ABIERTO'
             ORDER BY fecha DESC"
        );
        $stmt->execute(['sucursal_id' => $sucursalId]);

        $lista = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lista[] = $this->mapear($row);
        }
        return $lista;
    }

    public function crear(int $sucursalId, string $tipo, string $descripcion): int
    {
        if (trim($tipo) === '' || trim($descripcion) === '') {
            throw new InvalidArgumentException('Tipo y descripción son obligatorios.');
        }
        if (strlen($descripcion) > 250) {
            throw new InvalidArgumentException('La descripción no puede superar 250 caracteres.');
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO incidente (sucursal_id, tipo, descripcion, fecha, estado)
             VALUES (:sucursal_id, :tipo, :descripcion, NOW(), 'ABIERTO')"
        );
        $stmt->execute([
            'sucursal_id' => $sucursalId,
            'tipo'        => $tipo,
            'descripcion' => $descripcion,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function resolver(int $id): void
    {
        $incidente = $this->buscarPorId($id);
        if ($incidente === null) {
            throw new RuntimeException('Incidente no encontrado.');
        }
        if (!$incidente->estaAbierto()) {
            throw new RuntimeException('Solo se puede resolver un incidente ABIERTO.');
        }

        $stmt = $this->pdo->prepare(
            "UPDATE incidente SET estado = 'RESUELTO' WHERE id = :id AND estado = 'ABIERTO'"
        );
        $stmt->execute(['id' => $id]);
    }

    private function mapear(array $row): Incidente
    {
        return new Incidente(
            (int) $row['id'],
            (int) $row['sucursal_id'],
            $row['tipo'],
            $row['descripcion'],
            $row['fecha'],
            $row['estado']
        );
    }
}
