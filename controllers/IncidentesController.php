<?php
class IncidentesController extends CrudController
{
    // Tipos base (los mismos que ve el cajero al reportar).
    public const TIPOS = ['Equipos', 'Seguridad', 'Stock', 'Otro'];

    protected function config(): array
    {
        $r = new IncidenteRepository();
        return [
            'carpeta' => 'incidentes', 'titulo' => 'Incidentes', 'singular' => 'incidente',
            'repo' => $r,
            'campos' => [
                'sucursal_id' => ['label' => 'Sucursal', 'tipo' => 'select', 'req' => true,
                                  'opciones' => fn() => $r->pares('SELECT id, nombre FROM sucursal ORDER BY nombre')],
                'tipo'        => ['label' => 'Tipo', 'tipo' => 'text', 'req' => true, 'max' => 20,
                                  'attr' => 'placeholder="Ej: Seguridad, Equipos, Stock"'],
                'descripcion' => ['label' => 'Descripción', 'tipo' => 'text', 'req' => true, 'max' => 250],
                'fecha'       => ['label' => 'Fecha (vacío = ahora)', 'tipo' => 'datetime-local'],
                'estado'      => ['label' => 'Estado', 'tipo' => 'select', 'req' => true,
                                  'opciones' => ['ABIERTO' => 'ABIERTO', 'RESUELTO' => 'RESUELTO']],
            ],
        ];
    }

    // Validaciones y valores por defecto antes de insertar / actualizar.
    protected function antes(array $d, int $id): array
    {
        if ($id && !(new IncidenteRepository())->buscar($id)) {
            throw new RuntimeException('el incidente ya no existe.');
        }
        if ((int)$d['sucursal_id'] <= 0) {
            throw new RuntimeException('elige la sucursal.');
        }
        if ($d['tipo'] === '') {
            throw new RuntimeException('elige el tipo de incidente.');
        }
        if (mb_strlen($d['tipo']) > 20) {
            throw new RuntimeException('el tipo admite máximo 20 caracteres.');
        }
        if ($d['descripcion'] === '') {
            throw new RuntimeException('cuenta qué pasó.');
        }
        if (mb_strlen($d['descripcion']) > 250) {
            throw new RuntimeException('la descripción admite máximo 250 caracteres.');
        }
        if (!in_array($d['estado'], ['ABIERTO', 'RESUELTO'], true)) {
            throw new RuntimeException('el estado debe ser ABIERTO o RESUELTO.');
        }
        if ($d['fecha'] === '') {
            $d['fecha'] = date('Y-m-d H:i:s');
        } else {
            $f = DateTime::createFromFormat('Y-m-d H:i:s', $d['fecha']);
            if (!$f || $f->format('Y-m-d H:i:s') !== $d['fecha']) {
                throw new RuntimeException('la fecha no es válida.');
            }
        }
        return $d;
    }

    // Botón "Resolver" / "Reabrir" de cada fila.
    // Si llega "estado" se usa tal cual (así un doble clic no lo deja en el estado contrario).
    public function cambiarestado(): void
    {
        $repo = new IncidenteRepository();
        try {
            $i = $repo->buscar((int)($_POST['id'] ?? 0));
            if (!$i) {
                throw new RuntimeException('el incidente no existe.');
            }
            $nuevo = (string)($_POST['estado'] ?? '');
            if (!in_array($nuevo, ['ABIERTO', 'RESUELTO'], true)) {
                $nuevo = $i['estado'] === 'ABIERTO' ? 'RESUELTO' : 'ABIERTO';
            }
            $repo->actualizar($i['id'], ['estado' => $nuevo]);
            $this->flash($nuevo === 'RESUELTO' ? 'Incidente marcado como resuelto' : 'Incidente reabierto');
        } catch (RuntimeException | PDOException $e) {
            $this->flash('No se pudo cambiar el estado: ' . $this->mensaje($e), 'err');
        }
        $this->redirect('incidentes/index');
    }
}
