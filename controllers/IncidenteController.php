<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/repositories/IncidenteRepository.php';

/**
 * Controlador del módulo Incidentes.
 *
 * FERNANDEZ CHAUCAYANQUI JEAN MICHEL
 */
class IncidenteController
{
    private IncidenteRepository $incidentes;

    public function __construct(PDO $pdo)
    {
        $this->incidentes = new IncidenteRepository($pdo);
    }

    public function index(int $sucursalId): void
    {
        $lista = $this->incidentes->listarPorSucursal($sucursalId);
        require __DIR__ . '/../views/incidentes/index.php';
    }

    public function crear(int $sucursalId, string $tipo, string $descripcion): void
    {
        try {
            $id = $this->incidentes->crear($sucursalId, $tipo, $descripcion);
            $mensaje = "Incidente #{$id} registrado.";
            $error = null;
        } catch (Throwable $e) {
            $mensaje = null;
            $error = $e->getMessage();
        }

        require __DIR__ . '/../views/incidentes/crear.php';
    }

    public function resolver(int $id, int $sucursalId): void
    {
        try {
            $this->incidentes->resolver($id);
            $mensaje = "Incidente #{$id} marcado como RESUELTO.";
            $error = null;
        } catch (Throwable $e) {
            $mensaje = null;
            $error = $e->getMessage();
        }

        $lista = $this->incidentes->listarPorSucursal($sucursalId);
        require __DIR__ . '/../views/incidentes/index.php';
    }
}
