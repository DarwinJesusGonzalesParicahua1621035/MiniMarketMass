# Minimarket Mass

Base de datos del sistema "Minimarket Mass": sucursales, empleados, productos, stock, cajas y ventas con boleta o factura.
Proyecto del curso Backend Developer Web (SENATI CFP Arequipa), Grupo 1.

## Integrantes

- Darwin Jesus Gonzales Paricahua
- Andree Franzua Taco Apaza
- Anthony Andre Mullisaca Apaza
- Jean Michael Fernandez Chaucayanqui
- Bryan Moises Mamani Quispe
- Jhon Fabricio Ramirez Huayta

## Archivos del repositorio

| Archivo | Descripción |
|---|---|
| `database/schema.sql` | Crea la estructura de la base de datos (tablas, claves y restricciones). |
| `database/datos.sql` | Carga los datos de la base de datos. Pendiente: detallar qué datos incluye. |
| `database/consultas.sql` | Consultas sobre la base de datos. Pendiente: detallar cuáles. |
| `database/boleta.sql` | Pendiente: no hay información sobre su contenido. |
| `docs/diagrama-er.png` | Diagrama entidad-relación del diseño. |

## Tablas del diseño

El diseño tiene 17 tablas.

| Tabla | Para qué sirve |
|---|---|
| `DISTRITO` | Distritos donde se ubican las sucursales. |
| `SUCURSAL` | Locales del minimarket, con su dirección y las series de boleta y factura. |
| `CAJA` | Cajas de cada sucursal. |
| `EMPLEADO` | Personal con usuario, clave, rol, sucursal y supervisor opcional. |
| `ASISTENCIA` | Entrada, salida y estado de asistencia de cada empleado. |
| `CAPACITACION` | Capacitaciones de cada empleado: tema, fecha y horas. |
| `HISTORIA_CAJA` | Aperturas y cierres de caja, con montos inicial y final y el empleado responsable. |
| `CATEGORIA` | Categorías de productos. |
| `PRODUCTO` | Productos con código de barras, unidad, precio y estado activo. |
| `HISTORIAL_PRECIO` | Precios de cada producto con fecha de inicio y de fin. |
| `STOCK` | Cantidad disponible de cada producto en cada sucursal. |
| `MOVIMIENTO_STOCK` | Entradas, salidas, ajustes y mermas de stock. |
| `CLIENTE` | Clientes identificados con DNI o RUC. |
| `VENTA` | Comprobantes emitidos (boleta o factura) con subtotal, IGV, total y estado. |
| `DETALLE_VENTA` | Productos, cantidades y precios de cada venta. |
| `PAGO_VENTA` | Pagos de cada venta por medio de pago. |
| `INCIDENTE` | Incidentes registrados por sucursal, con estado. |

## Reglas de negocio

Reglas que el diseño impone con restricciones (`CHECK`, `UNIQUE`, claves foráneas).

| Tabla | Regla |
|---|---|
| `SUCURSAL` | Las series de boleta y de factura son de 4 caracteres y no se repiten entre sucursales. |
| `CAJA` | El nombre de la caja no se repite dentro de una misma sucursal. |
| `EMPLEADO` | El rol es `ADMINISTRADOR`, `ALMACENERO` o `CAJERO`. |
| `EMPLEADO` | El usuario es único. |
| `EMPLEADO` | `ACTIVO` solo admite 0 o 1. |
| `EMPLEADO` | Un empleado no puede ser su propio supervisor. |
| `ASISTENCIA` | El estado es `FALTA`, `PRESENTE` o `TARDANZA`; por defecto `PRESENTE`. |
| `ASISTENCIA` | La salida, si existe, no puede ser anterior a la entrada. |
| `CAPACITACION` | Las horas deben ser mayores que 0. |
| `HISTORIA_CAJA` | El monto inicial no puede ser negativo. |
| `HISTORIA_CAJA` | El estado es `ABIERTA` o `CERRADA`; por defecto `ABIERTA`. |
| `CATEGORIA` | El nombre es único. |
| `PRODUCTO` | El código de barras es único y tiene 13 caracteres. |
| `PRODUCTO` | El precio no puede ser negativo; `ACTIVO` solo admite 0 o 1. |
| `HISTORIAL_PRECIO` | El precio no puede ser negativo. |
| `HISTORIAL_PRECIO` | La fecha de fin, si existe, debe ser posterior a la de inicio. |
| `HISTORIAL_PRECIO` | Un producto no puede tener dos precios con la misma fecha de inicio. |
| `STOCK` | La cantidad no puede ser negativa; hay un solo registro por sucursal y producto. |
| `MOVIMIENTO_STOCK` | El tipo es `ENTRADA`, `SALIDA`, `AJUSTE` o `MERMA`. |
| `MOVIMIENTO_STOCK` | La cantidad debe ser mayor que 0. |
| `MOVIMIENTO_STOCK` | Solo puede registrarse para un producto que tenga stock en esa sucursal. |
| `CLIENTE` | El tipo de documento es `DNI` (8 dígitos) o `RUC` (11 dígitos). |
| `CLIENTE` | El par tipo y número de documento es único. |
| `VENTA` | El tipo de comprobante es `BOLETA` o `FACTURA`. |
| `VENTA` | La factura exige cliente; la boleta puede no tenerlo. |
| `VENTA` | El estado es `EMITIDA` o `ANULADA`; por defecto `EMITIDA`. |
| `VENTA` | `TOTAL` debe ser igual a `SUBTOTAL + IGV`; subtotal e IGV no pueden ser negativos. |
| `VENTA` | La combinación sucursal, serie y correlativo es única. |
| `DETALLE_VENTA` | La cantidad debe ser mayor que 0. |
| `DETALLE_VENTA` | Un producto aparece una sola vez por venta. |
| `PAGO_VENTA` | El medio de pago es `EFECTIVO`, `PLIN`, `TARJETA` o `YAPE`. |
| `PAGO_VENTA` | El monto debe ser mayor que 0. |
| `INCIDENTE` | El estado es `ABIERTO` o `RESUELTO`; por defecto `ABIERTO`. |

## Cómo crear la base de datos

Requisitos: Laragon con MySQL en el puerto 3307. Ejecutar en PowerShell, en este orden, reemplazando `C:/ruta/al/repo` por la ruta donde está clonado el repositorio (usar `/` en la ruta, ya que PowerShell no admite el operador `<`).

```powershell
mysql -u root -h 127.0.0.1 -P 3307 -e "source C:/ruta/al/repo/database/schema.sql"
mysql -u root -h 127.0.0.1 -P 3307 -e "source C:/ruta/al/repo/database/datos.sql"
mysql -u root -h 127.0.0.1 -P 3307 -e "source C:/ruta/al/repo/database/consultas.sql"
mysql -u root -h 127.0.0.1 -P 3307 -e "source C:/ruta/al/repo/database/boleta.sql"
```

Pendiente: nombre de la base de datos que crea `schema.sql`.

## Decisiones de diseño

Pendiente: el motivo de cada decisión no está documentado. Lo que se observa en el diseño:

| Decisión | Por qué |
|---|---|
| Stock separado por sucursal, con clave primaria (`SUCURSAL_ID`, `PRODUCTO_ID`). | Pendiente |
| `HISTORIAL_PRECIO` separado de `PRODUCTO.PRECIO`. | Pendiente |
| Ventas con estado `ANULADA` y `MOTIVO_ANULACION` en lugar de borrarlas. | Pendiente |
| Series de boleta y factura asignadas por sucursal; correlativo único por sucursal y serie. | Pendiente |
| `HISTORIA_CAJA` enlaza cada venta con una apertura de caja y un empleado. | Pendiente |
| Pagos en tabla aparte (`PAGO_VENTA`), con varios por venta posibles. | Pendiente |
| Supervisor como relación del empleado consigo mismo (`SUPERVISOR_ID`). | Pendiente |
| `ACTIVO` en empleados y productos en lugar de eliminar registros. | Pendiente |

## Módulos web

La aplicación de prueba se sirve desde `public/productos.php` y comparte la conexión local a MySQL configurada allí.

| Módulo | Ruta | Funcionalidad |
|---|---|---|
| Cajas | `?m=caja` | Registrar, editar y eliminar cajas por sucursal. |
| Historial de caja | `?m=historia_caja` | Abrir y cerrar cajas, consultar aperturas y filtrar por estado. Cada caja admite una sola apertura vigente. |
| Incidentes | `?m=incidentes` | Registrar incidentes por sucursal, filtrar y marcar como resueltos o reabrirlos. |
