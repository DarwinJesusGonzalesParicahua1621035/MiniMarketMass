<?php
declare(strict_types=1);

/**
 * Caja registradora de una sucursal.
 * Tabla: caja
 *
 * FERNANDEZ CHAUCAYANQUI JEAN MICHEL
 */
class Caja
{
    public function __construct(
        private int $id,
        private int $sucursalId,
        private string $nombre
    ) {}

    public function getId(): int         { return $this->id; }
    public function getSucursalId(): int { return $this->sucursalId; }
    public function getNombre(): string  { return $this->nombre; }
}
