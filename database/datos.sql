USE minimarket_mass;
SET NAMES utf8mb4;
SET @h = '$2y$10$IeDzDPm.yC3RdLs7pXVChOfnJeDFw5wNT3m3SKcLumjZ2MTV4tay.';

INSERT INTO distrito (nombre) VALUES ('Cayma'), ('Yanahuara'), ('Cercado'), ('Cerro Colorado'), ('José Luis Bustamante y Rivero');
INSERT INTO categoria (nombre) VALUES ('Abarrotes'), ('Lácteos'), ('Bebidas'), ('Limpieza'), ('Snacks');
INSERT INTO sucursal (nombre, direccion, distrito_id, serie_boleta, serie_factura) VALUES
 ('Mass Cayma', 'Av. Cayma 410', 1, 'B001', 'F001'),
 ('Mass Yanahuara', 'Calle Jerusalén 220', 2, 'B002', 'F002'),
 ('Mass Cercado', 'Calle Mercaderes 105', 3, 'B003', 'F003');
INSERT INTO caja (sucursal_id, nombre) VALUES (1, 'Caja 1'), (1, 'Caja 2'), (2, 'Caja 1'), (3, 'Caja 1');

INSERT INTO empleado (sucursal_id, supervisor_id, nombre, usuario, clave, rol, activo)
 VALUES (1, NULL, 'Lucía Paredes', 'admin', @h, 'ADMINISTRADOR', 1);
INSERT INTO empleado (sucursal_id, supervisor_id, nombre, usuario, clave, rol, activo) VALUES
 (1, 1, 'Jorge Salas', 'jsalas', @h, 'CAJERO', 1),
 (2, 1, 'Rosa Ccama', 'rccama', @h, 'ALMACENERO', 1),
 (3, 1, 'Diego Flores', 'dflores', @h, 'CAJERO', 0);

INSERT INTO producto (categoria_id, codigo_barras, nombre, unidad, precio, activo) VALUES
 (1, '7750182001234', 'Arroz Costeño 1 kg', 'kg', 4.50, 1),
 (2, '7750182005678', 'Leche Gloria 400 g', 'unidad', 4.20, 1),
 (3, '7750182009012', 'Inca Kola 1.5 L', 'unidad', 7.90, 1),
 (4, '7750182003456', 'Detergente Ariel 1 kg', 'unidad', 12.50, 1),
 (5, '7750182007890', 'Papas Lays 150 g', 'unidad', 5.50, 0),
 (1, '7750182002468', 'Aceite Primor 1 L', 'unidad', 9.80, 1);
INSERT INTO historial_precio (producto_id, precio, fecha_inicio) SELECT id, precio, '2026-01-01 00:00:00' FROM producto;

INSERT INTO stock (sucursal_id, producto_id, cantidad) VALUES (1, 1, 120), (1, 2, 8), (2, 3, 64), (3, 4, 5), (3, 6, 42);

INSERT INTO cliente (tipo_documento, numero_documento, nombre) VALUES
 ('DNI', '72845619', 'Carlos Quispe Mamani'), ('RUC', '20601234567', 'Distribuidora Sur SAC'),
 ('DNI', '45120987', 'María Condori Huamán'), ('RUC', '20559876543', 'Bodega Los Andes EIRL');

INSERT INTO historia_caja (caja_id, empleado_id, apertura, cierre, monto_inicial, monto_final, estado) VALUES
 (1, 2, CONCAT(CURDATE(), ' 08:00:00'), NULL, 100.00, NULL, 'ABIERTA'),
 (3, 2, CONCAT(CURDATE(), ' 08:00:00'), NULL, 100.00, NULL, 'ABIERTA'),
 (4, 4, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:00:00'), CONCAT(CURDATE() - INTERVAL 1 DAY, ' 18:00:00'), 150.00, 420.00, 'CERRADA');

INSERT INTO venta (sucursal_id, historia_caja_id, empleado_id, cliente_id, tipo_comprobante, serie, correlativo, fecha, subtotal, igv, total, estado, motivo_anulacion) VALUES
 (1, 1, 2, 1,    'BOLETA',  'B001', 231, CONCAT(CURDATE(), ' 09:12:00'), 29.66, 5.34, 35.00, 'EMITIDA', NULL),
 (1, 1, 2, NULL, 'BOLETA',  'B001', 232, CONCAT(CURDATE(), ' 11:15:00'), 46.61, 8.39, 55.00, 'EMITIDA', NULL),
 (2, 2, 2, 2,    'FACTURA', 'F002', 45,  CONCAT(CURDATE(), ' 10:03:00'), 211.86, 38.14, 250.00, 'EMITIDA', NULL),
 (3, 3, 4, NULL, 'BOLETA',  'B003', 118, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 10:40:00'), 10.17, 1.83, 12.00, 'ANULADA', 'Error de digitación'),
 (1, 1, 2, NULL, 'BOLETA',  'B001', 233, CONCAT(CURDATE() - INTERVAL 1 DAY, ' 12:00:00'), 1559.32, 280.68, 1840.00, 'EMITIDA', NULL),
 (1, 1, 2, NULL, 'BOLETA',  'B001', 234, CONCAT(CURDATE() - INTERVAL 2 DAY, ' 12:00:00'), 1872.88, 337.12, 2210.00, 'EMITIDA', NULL),
 (1, 1, 2, NULL, 'BOLETA',  'B001', 235, CONCAT(CURDATE() - INTERVAL 3 DAY, ' 12:00:00'), 1661.02, 298.98, 1960.00, 'EMITIDA', NULL),
 (1, 1, 2, NULL, 'BOLETA',  'B001', 236, CONCAT(CURDATE() - INTERVAL 4 DAY, ' 12:00:00'), 2101.69, 378.31, 2480.00, 'EMITIDA', NULL),
 (1, 1, 2, NULL, 'BOLETA',  'B001', 237, CONCAT(CURDATE() - INTERVAL 5 DAY, ' 12:00:00'), 2644.07, 475.93, 3120.00, 'EMITIDA', NULL),
 (1, 1, 2, NULL, 'BOLETA',  'B001', 238, CONCAT(CURDATE(), ' 12:30:00'), 1016.95, 183.05, 1200.00, 'EMITIDA', NULL);

INSERT INTO detalle_venta (venta_id, producto_id, cantidad, precio_unitario, total_linea) VALUES
 (1, 1, 5, 4.50, 22.50), (1, 4, 1, 12.50, 12.50),
 (2, 3, 2, 7.90, 15.80), (2, 6, 1, 9.80, 9.80), (2, 2, 7, 4.20, 29.40),
 (3, 4, 20, 12.50, 250.00);

INSERT INTO pago_venta (venta_id, medio_pago, monto, referencia) VALUES
 (1, 'EFECTIVO', 35.00, NULL), (2, 'YAPE', 55.00, 'Y-48231'), (3, 'TARJETA', 250.00, 'VISA-1234'),
 (4, 'EFECTIVO', 12.00, NULL), (5, 'EFECTIVO', 1840.00, NULL), (6, 'YAPE', 2210.00, 'Y-77120'),
 (7, 'TARJETA', 1960.00, 'VISA-5588'), (8, 'EFECTIVO', 2480.00, NULL), (9, 'PLIN', 3120.00, 'P-30011'),
 (10, 'EFECTIVO', 1200.00, NULL);

INSERT INTO movimiento_stock (sucursal_id, producto_id, empleado_id, venta_id, tipo, cantidad, motivo, fecha) VALUES
 (1, 1, 1, NULL, 'ENTRADA', 50, 'Reposición', CONCAT(CURDATE() - INTERVAL 1 DAY, ' 09:10:00')),
 (1, 2, 2, 1,    'SALIDA',  2,  'Venta B001-000231', CONCAT(CURDATE(), ' 09:12:00')),
 (3, 4, 3, NULL, 'MERMA',   1,  'Envase dañado', CONCAT(CURDATE() - INTERVAL 1 DAY, ' 08:30:00')),
 (2, 3, 3, NULL, 'AJUSTE',  3,  'Inventario', CONCAT(CURDATE() - INTERVAL 1 DAY, ' 17:05:00'));

INSERT INTO asistencia (empleado_id, entrada, salida, estado) VALUES
 (2, CONCAT(CURDATE(), ' 07:55:00'), NULL, 'PRESENTE'),
 (3, CONCAT(CURDATE(), ' 08:20:00'), NULL, 'TARDANZA'),
 (1, CONCAT(CURDATE(), ' 07:45:00'), NULL, 'PRESENTE'),
 (4, CONCAT(CURDATE(), ' 00:00:00'), NULL, 'FALTA');

INSERT INTO capacitacion (empleado_id, tema, fecha, horas) VALUES
 (2, 'Atención al cliente', '2026-10-12', 3.0), (3, 'Manejo de inventario', '2026-10-14', 4.5),
 (4, 'Uso de caja y comprobantes', '2026-10-15', 2.0);

INSERT INTO incidente (sucursal_id, tipo, descripcion, fecha, estado) VALUES
 (3, 'Seguridad', 'Cámara del pasillo 2 sin señal', CONCAT(CURDATE() - INTERVAL 1 DAY, ' 14:20:00'), 'ABIERTO'),
 (1, 'Stock', 'Diferencia en conteo de lácteos', CONCAT(CURDATE() - INTERVAL 2 DAY, ' 18:00:00'), 'RESUELTO'),
 (2, 'Equipos', 'Impresora de tickets atascada', CONCAT(CURDATE(), ' 09:30:00'), 'ABIERTO');