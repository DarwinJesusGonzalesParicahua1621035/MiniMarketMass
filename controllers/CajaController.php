<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/repositories/CajaRepository.php';
require_once __DIR__ . '/../models/repositories/HistoriaCajaRepository.php';

/**
 * Controlador del módulo Caja (cajas + aperturas/cierres).
 *
 * FERNANDEZ CHAUCAYANQUI JEAN MICHEL
 */
class CajaController
{
    private CajaRepository $cajas;
    private HistoriaCajaRepository $historias;

    public function __construct(PDO $pdo)
    {
        $this->cajas     = new CajaRepository($pdo);
        $this->historias = new HistoriaCajaRepository($pdo);
    }

    /** Lista las cajas de una sucursal y si tienen apertura abierta. */
    public function index(int $sucursalId): void
    {
        $cajas = $this->cajas->listarPorSucursal($sucursalId);
        $estado = [];
        foreach ($cajas as $caja) {
            $estado[$caja->getId()] = $this->historias->abiertaDeCaja($caja->getId());
        }

        require __DIR__ . '/../views/caja/index.php';
    }

    /** Formulario / acción de apertura de caja. */
    public function abrir(int $cajaId, int $empleadoId, float $montoInicial): void
    {
        try {
            $id = $this->historias->abrir($cajaId, $empleadoId, $montoInicial);
            $mensaje = "Caja abierta correctamente (historia #{$id}).";
            $error = null;
        } catch (Throwable $e) {
            $mensaje = null;
            $error = $e->getMessage();
        }

        require __DIR__ . '/../views/caja/abrir.php';
    }

    /** Formulario / acción de cierre de caja. */
    public function cerrar(int $historiaId, float $montoFinal): void
    {
        try {
            $this->historias->cerrar($historiaId, $montoFinal);
            $historia = $this->historias->buscarPorId($historiaId);
            $mensaje = 'Caja cerrada correctamente.';
            $error = null;
        } catch (Throwable $e) {
            $historia = null;
            $mensaje = null;
            $error = $e->getMessage();
        }

        require __DIR__ . '/../views/caja/cerrar.php';
    }
}
