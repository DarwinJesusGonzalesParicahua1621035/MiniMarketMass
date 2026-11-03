# Módulo Caja e Incidentes

FERNANDEZ CHAUCAYANQUI JEAN MICHEL

Rama: `feature/caja`

## Tablas
- `caja`
- `historia_caja`
- `incidente`

## Rutas

| Ruta | Pantalla | Roles |
|---|---|---|
| `?r=caja/index` | Cajas con su turno actual | ADMINISTRADOR, CAJERO |
| `?r=caja/historial` | Aperturas y cierres (`historia_caja`) | ADMINISTRADOR, CAJERO |
| `?r=caja/nueva` | Crear caja | ADMINISTRADOR |
| `?r=caja/abrir` | Abrir turno con monto inicial | ADMINISTRADOR, CAJERO |
| `?r=caja/cerrar` | Cerrar turno con monto final | ADMINISTRADOR, CAJERO |
| `?r=incidentes/index` | Lista de incidentes | ADMINISTRADOR |
| `?r=incidentes/nuevo` | Registrar incidente | ADMINISTRADOR |
| `?r=incidentes/editar&id=N` | Editar o resolver incidente | ADMINISTRADOR |

## Archivos
- `controllers/CajaController.php`
- `controllers/IncidentesController.php`
- `models/repositories/CajaRepository.php`
- `models/repositories/HistoriaCajaRepository.php`
- `models/repositories/IncidenteRepository.php`
- `views/caja/index.php`, `historial.php`, `nueva.php`, `abrir.php`, `cerrar.php`
- `views/incidentes/index.php`, `form.php`

## Cómo probar
1. Cargar `database/schema.sql` y `database/datos.sql` en Laragon.
2. Entrar con `admin` (ADMINISTRADOR) o `jsalas` (CAJERO).
3. Abrir las rutas de la tabla desde el menú: Cajas e Incidentes.
