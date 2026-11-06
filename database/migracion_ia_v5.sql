-- Migración v5: acceso SEGURO de solo lectura para el Asistente IA.
-- Ejecutar con un usuario administrador de MySQL (root), UNA vez (se puede repetir sin problema).
--
-- Qué hace:
--   1) Crea vistas ia_* que exponen los datos del negocio SIN columnas sensibles:
--        - empleado: sin usuario, clave ni secretos 2FA
--        - cliente:  sin nombre ni número de documento (solo id y tipo de documento)
--        - proveedor: sin RUC, contacto, teléfono, correo ni dirección
--        - pago_venta: sin referencia de operación
--   2) Crea el usuario MySQL "ia_lector" que SOLO puede hacer SELECT sobre esas vistas.
--      No puede ver las tablas reales, ni modificar nada.
--
-- IMPORTANTE: cambia la contraseña 'ia_lector_2026' por otra y ponla igual en config/gemini.local.php

USE minimarket_mass;

-- ------------------------------------------------------------ Vistas seguras
CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_distrito AS
    SELECT id, nombre FROM distrito;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_sucursal AS
    SELECT s.id, s.nombre, s.direccion, s.distrito_id, d.nombre AS distrito, s.serie_boleta, s.serie_factura
    FROM sucursal s JOIN distrito d ON d.id = s.distrito_id;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_caja AS
    SELECT id, sucursal_id, nombre FROM caja;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_empleado AS
    SELECT id, sucursal_id, supervisor_id, nombre, rol, activo FROM empleado;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_categoria AS
    SELECT id, nombre FROM categoria;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_proveedor AS
    SELECT id, razon_social, activo FROM proveedor;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_producto AS
    SELECT id, categoria_id, proveedor_id, codigo_barras, nombre, unidad, precio, activo FROM producto;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_historial_precio AS
    SELECT id, producto_id, precio, fecha_inicio, fecha_fin FROM historial_precio;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_stock AS
    SELECT sucursal_id, producto_id, cantidad FROM stock;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_asistencia AS
    SELECT id, empleado_id, entrada, salida, estado FROM asistencia;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_capacitacion AS
    SELECT id, empleado_id, tema, fecha, horas FROM capacitacion;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_historia_caja AS
    SELECT id, caja_id, empleado_id, apertura, cierre, monto_inicial, monto_final, estado FROM historia_caja;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_incidente AS
    SELECT id, sucursal_id, tipo, descripcion, fecha, estado FROM incidente;

-- Del cliente solo se expone el id (para contar compras por cliente) y el tipo de documento.
CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_cliente AS
    SELECT id, tipo_documento FROM cliente;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_venta AS
    SELECT id, sucursal_id, historia_caja_id, empleado_id, cliente_id, tipo_comprobante, serie, correlativo,
           fecha, subtotal, igv, total, estado, motivo_anulacion
    FROM venta;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_detalle_venta AS
    SELECT id, venta_id, producto_id, cantidad, precio_unitario, total_linea FROM detalle_venta;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_pago_venta AS
    SELECT id, venta_id, medio_pago, monto FROM pago_venta;

CREATE OR REPLACE SQL SECURITY DEFINER VIEW ia_movimiento_stock AS
    SELECT id, sucursal_id, producto_id, empleado_id, venta_id, proveedor_id, tipo, cantidad, motivo, fecha
    FROM movimiento_stock;

-- ------------------------------------------------------------ Usuario de solo lectura
-- Se crea para 'localhost' y '127.0.0.1' porque PHP puede conectar por cualquiera de los dos.
CREATE USER IF NOT EXISTS 'ia_lector'@'localhost'  IDENTIFIED BY 'ia_lector_2026';
CREATE USER IF NOT EXISTS 'ia_lector'@'127.0.0.1' IDENTIFIED BY 'ia_lector_2026';
ALTER USER 'ia_lector'@'localhost'  IDENTIFIED BY 'ia_lector_2026';
ALTER USER 'ia_lector'@'127.0.0.1' IDENTIFIED BY 'ia_lector_2026';

-- Quita cualquier permiso anterior y da SELECT solo sobre las vistas.
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'ia_lector'@'localhost';
REVOKE ALL PRIVILEGES, GRANT OPTION FROM 'ia_lector'@'127.0.0.1';

GRANT SELECT ON minimarket_mass.ia_distrito         TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_sucursal         TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_caja             TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_empleado         TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_categoria        TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_proveedor        TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_producto         TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_historial_precio TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_stock            TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_asistencia       TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_capacitacion     TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_historia_caja    TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_incidente        TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_cliente          TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_venta            TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_detalle_venta    TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_pago_venta       TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';
GRANT SELECT ON minimarket_mass.ia_movimiento_stock TO 'ia_lector'@'localhost', 'ia_lector'@'127.0.0.1';

FLUSH PRIVILEGES;
