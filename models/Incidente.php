<?php
declare(strict_types=1);

/**
 * Incidente registrado por sucursal.
 * Tabla: incidente
 * Estados: ABIERTO | RESUELTO
 *
 * FERNANDEZ CHAUCAYANQUI JEAN MICHEL
 */
class Incidente
{
    public function __construct(
        private int $id,
        private int $sucursalId,
        private string $tipo,
        private string $descripcion,
        private string $fecha,
        private string $estado
    ) {
        if (!in_array($estado, ['ABIERTO', 'RESUELTO'], true)) {
            throw new InvalidArgumentException('Estado inválido: use ABIERTO o RESUELTO.');
        }
        if (strlen($descripcion) > 250) {
            throw new InvalidArgumentException('La descripción no puede superar 250 caracteres.');
        }
    }

    public function getId(): int            { return $this->id; }
    public function getSucursalId(): int    { return $this->sucursalId; }
    public function getTipo(): string       { return $this->tipo; }
    public function getDescripcion(): string{ return $this->descripcion; }
    public function getFecha(): string      { return $this->fecha; }
    public function getEstado(): string     { return $this->estado; }

    public function estaAbierto(): bool  { return $this->estado === 'ABIERTO'; }
    public function estaResuelto(): bool { return $this->estado === 'RESUELTO'; }
}
