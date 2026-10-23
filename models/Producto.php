<?php
declare(strict_types=1);

class Producto
{
    public function __construct(
        private int $id,
        private int $categoriaId,
        private string $codigoBarras,
        private string $nombre,
        private string $unidad,
        private float $precio,
        private bool $activo
    ) {}

    public function getId(): int              { return $this->id; }
    public function getCategoriaId(): int     { return $this->categoriaId; }
    public function getCodigoBarras(): string { return $this->codigoBarras; }
    public function getNombre(): string       { return $this->nombre; }
    public function getUnidad(): string       { return $this->unidad; }
    public function getPrecio(): float        { return $this->precio; }

    public function estaActivo(): bool { return $this->activo; }

    // El precio ya incluye IGV 18%.
    public function igv(): float      { return round($this->precio * 18 / 118, 2); }
    public function subtotal(): float { return round($this->precio - $this->igv(), 2); }
}