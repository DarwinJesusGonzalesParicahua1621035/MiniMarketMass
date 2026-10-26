<?php
declare(strict_types=1);

/**
 * Apertura y cierre de caja (turno).
 * Tabla: historia_caja
 * Estados: ABIERTA | CERRADA
 *
 * FERNANDEZ CHAUCAYANQUI JEAN MICHEL
 */
class HistoriaCaja
{
    public function __construct(
        private int $id,
        private int $cajaId,
        private int $empleadoId,
        private string $apertura,
        private ?string $cierre,
        private float $montoInicial,
        private ?float $montoFinal,
        private string $estado
    ) {
        if ($montoInicial < 0) {
            throw new InvalidArgumentException('El monto inicial no puede ser negativo.');
        }
        if (!in_array($estado, ['ABIERTA', 'CERRADA'], true)) {
            throw new InvalidArgumentException('Estado inválido: use ABIERTA o CERRADA.');
        }
    }

    public function getId(): int              { return $this->id; }
    public function getCajaId(): int          { return $this->cajaId; }
    public function getEmpleadoId(): int      { return $this->empleadoId; }
    public function getApertura(): string     { return $this->apertura; }
    public function getCierre(): ?string      { return $this->cierre; }
    public function getMontoInicial(): float  { return $this->montoInicial; }
    public function getMontoFinal(): ?float   { return $this->montoFinal; }
    public function getEstado(): string       { return $this->estado; }

    public function estaAbierta(): bool { return $this->estado === 'ABIERTA'; }
    public function estaCerrada(): bool { return $this->estado === 'CERRADA'; }

    /** Diferencia entre monto final e inicial (solo si ya cerró). */
    public function diferencia(): ?float
    {
        if ($this->montoFinal === null) {
            return null;
        }
        return round($this->montoFinal - $this->montoInicial, 2);
    }
}
