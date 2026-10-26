<?php
declare(strict_types=1);

require_once __DIR__ . '/../HistoriaCaja.php';

/**
 * Acceso a datos de la tabla historia_caja (aperturas y cierres).
 *
 * FERNANDEZ CHAUCAYANQUI JEAN MICHEL
 */
class HistoriaCajaRepository
{
    public function __construct(private PDO $pdo) {}

    public function buscarPorId(int $id): ?HistoriaCaja
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, caja_id, empleado_id, apertura, cierre, monto_inicial, monto_final, estado
             FROM historia_caja WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapear($row) : null;
    }

    /** Devuelve la apertura ABIERTA de una caja, si existe. */
    public function abiertaDeCaja(int $cajaId): ?HistoriaCaja
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, caja_id, empleado_id, apertura, cierre, monto_inicial, monto_final, estado
             FROM historia_caja
             WHERE caja_id = :caja_id AND estado = 'ABIERTA'
             ORDER BY apertura DESC LIMIT 1"
        );
        $stmt->execute(['caja_id' => $cajaId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapear($row) : null;
    }

    public function listarPorCaja(int $cajaId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, caja_id, empleado_id, apertura, cierre, monto_inicial, monto_final, estado
             FROM historia_caja WHERE caja_id = :caja_id ORDER BY apertura DESC'
        );
        $stmt->execute(['caja_id' => $cajaId]);

        $lista = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $lista[] = $this->mapear($row);
        }
        return $lista;
    }

    public function abrir(int $cajaId, int $empleadoId, float $montoInicial): int
    {
        if ($montoInicial < 0) {
            throw new InvalidArgumentException('El monto inicial no puede ser negativo.');
        }
        if ($this->abiertaDeCaja($cajaId) !== null) {
            throw new RuntimeException('La caja ya tiene una apertura ABIERTA.');
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO historia_caja (caja_id, empleado_id, apertura, monto_inicial, estado)
             VALUES (:caja_id, :empleado_id, NOW(), :monto_inicial, 'ABIERTA')"
        );
        $stmt->execute([
            'caja_id'       => $cajaId,
            'empleado_id'   => $empleadoId,
            'monto_inicial' => $montoInicial,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function cerrar(int $historiaId, float $montoFinal): void
    {
        $historia = $this->buscarPorId($historiaId);
        if ($historia === null) {
            throw new RuntimeException('Historia de caja no encontrada.');
        }
        if (!$historia->estaAbierta()) {
            throw new RuntimeException('Solo se puede cerrar una caja en estado ABIERTA.');
        }

        $stmt = $this->pdo->prepare(
            "UPDATE historia_caja
             SET cierre = NOW(), monto_final = :monto_final, estado = 'CERRADA'
             WHERE id = :id AND estado = 'ABIERTA'"
        );
        $stmt->execute([
            'monto_final' => $montoFinal,
            'id'          => $historiaId,
        ]);
    }

    private function mapear(array $row): HistoriaCaja
    {
        return new HistoriaCaja(
            (int) $row['id'],
            (int) $row['caja_id'],
            (int) $row['empleado_id'],
            $row['apertura'],
            $row['cierre'],
            (float) $row['monto_inicial'],
            $row['monto_final'] !== null ? (float) $row['monto_final'] : null,
            $row['estado']
        );
    }
}
