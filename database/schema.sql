-- Minimarket Mass - estructura de la base de datos (MySQL 8.4, InnoDB)
-- Uso: mysql -u root -h 127.0.0.1 -P 3307 -e "source C:/ruta/al/repo/database/schema.sql"

DROP DATABASE IF EXISTS minimarket_mass;
CREATE DATABASE minimarket_mass CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci;
USE minimarket_mass;

-- ============================================================
-- Tablas sin dependencias
-- ============================================================

-- Distritos donde se ubican las sucursales.
CREATE TABLE distrito (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(60)  NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_distrito_nombre UNIQUE (nombre)
) ENGINE=InnoDB;

-- Categorías de productos.
CREATE TABLE categoria (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(60)  NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_categoria_nombre UNIQUE (nombre)
) ENGINE=InnoDB;

-- Clientes identificados con DNI o RUC.
CREATE TABLE cliente (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo_documento   VARCHAR(3)   NOT NULL,
    numero_documento VARCHAR(11)  NOT NULL,
    nombre           VARCHAR(120) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_cliente_documento UNIQUE (tipo_documento, numero_documento),
    CONSTRAINT ck_cliente_tipo CHECK (tipo_documento IN ('DNI', 'RUC')),
    CONSTRAINT ck_cliente_doc_long CHECK (
        (tipo_documento = 'DNI' AND CHAR_LENGTH(numero_documento) = 8)
        OR (tipo_documento = 'RUC' AND CHAR_LENGTH(numero_documento) = 11)
    )
) ENGINE=InnoDB;

-- ============================================================
-- Tablas con claves foráneas
-- ============================================================

-- Locales del minimarket, con las series de boleta y factura de cada uno.
CREATE TABLE sucursal (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre        VARCHAR(80)  NOT NULL,
    direccion     VARCHAR(150) NOT NULL,
    distrito_id   INT UNSIGNED NOT NULL,
    serie_boleta  CHAR(4)      NOT NULL,
    serie_factura CHAR(4)      NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_sucursal_serie_boleta  UNIQUE (serie_boleta),
    CONSTRAINT uq_sucursal_serie_factura UNIQUE (serie_factura),
    CONSTRAINT fk_sucursal_distrito FOREIGN KEY (distrito_id) REFERENCES distrito (id)
) ENGINE=InnoDB;

-- Cajas registradoras de cada sucursal.
CREATE TABLE caja (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sucursal_id INT UNSIGNED NOT NULL,
    nombre      VARCHAR(40)  NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_caja_sucursal_nombre UNIQUE (sucursal_id, nombre),
    CONSTRAINT fk_caja_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursal (id)
) ENGINE=InnoDB;

-- Personal del minimarket con usuario, rol, sucursal y supervisor opcional.
-- Nota: el diseño original tenía CHECK (supervisor_id IS NULL OR supervisor_id <> id).
-- MySQL no permite columnas AUTO_INCREMENT dentro de un CHECK, por eso no se incluye.
CREATE TABLE empleado (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sucursal_id   INT UNSIGNED NOT NULL,
    supervisor_id INT UNSIGNED NULL,
    nombre        VARCHAR(100) NOT NULL,
    usuario       VARCHAR(30)  NOT NULL,
    clave         VARCHAR(255) NOT NULL,
    rol           VARCHAR(15)  NOT NULL,
    activo        TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    CONSTRAINT uq_empleado_usuario UNIQUE (usuario),
    CONSTRAINT ck_empleado_rol CHECK (rol IN ('ADMINISTRADOR', 'ALMACENERO', 'CAJERO')),
    CONSTRAINT ck_empleado_activo CHECK (activo IN (0, 1)),
    CONSTRAINT fk_empleado_sucursal   FOREIGN KEY (sucursal_id)   REFERENCES sucursal (id),
    CONSTRAINT fk_empleado_supervisor FOREIGN KEY (supervisor_id) REFERENCES empleado (id)
) ENGINE=InnoDB;

-- Productos a la venta con código de barras, unidad y precio vigente.
CREATE TABLE producto (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    categoria_id  INT UNSIGNED  NOT NULL,
    codigo_barras CHAR(13)      NOT NULL,
    nombre        VARCHAR(120)  NOT NULL,
    unidad        VARCHAR(20)   NOT NULL,
    precio        DECIMAL(10,2) NOT NULL,
    activo        TINYINT UNSIGNED NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    CONSTRAINT uq_producto_codigo UNIQUE (codigo_barras),
    CONSTRAINT ck_producto_precio CHECK (precio >= 0),
    CONSTRAINT ck_producto_activo CHECK (activo IN (0, 1)),
    CONSTRAINT fk_producto_categoria FOREIGN KEY (categoria_id) REFERENCES categoria (id)
) ENGINE=InnoDB;

-- Precios de cada producto por periodo de vigencia.
CREATE TABLE historial_precio (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    producto_id  INT UNSIGNED  NOT NULL,
    precio       DECIMAL(10,2) NOT NULL,
    fecha_inicio DATETIME      NOT NULL,
    fecha_fin    DATETIME      NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_historial_precio_inicio UNIQUE (producto_id, fecha_inicio),
    CONSTRAINT ck_historial_precio_precio CHECK (precio >= 0),
    CONSTRAINT ck_historial_precio_fechas CHECK (fecha_fin IS NULL OR fecha_fin > fecha_inicio),
    CONSTRAINT fk_historial_precio_producto FOREIGN KEY (producto_id) REFERENCES producto (id)
) ENGINE=InnoDB;

-- Cantidad disponible de cada producto en cada sucursal.
CREATE TABLE stock (
    sucursal_id INT UNSIGNED   NOT NULL,
    producto_id INT UNSIGNED   NOT NULL,
    cantidad    DECIMAL(10,3)  NOT NULL DEFAULT 0,
    PRIMARY KEY (sucursal_id, producto_id),
    CONSTRAINT ck_stock_cantidad CHECK (cantidad >= 0),
    CONSTRAINT fk_stock_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursal (id),
    CONSTRAINT fk_stock_producto FOREIGN KEY (producto_id) REFERENCES producto (id)
) ENGINE=InnoDB;

-- Entrada y salida de cada empleado, con su estado de asistencia.
CREATE TABLE asistencia (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    empleado_id INT UNSIGNED NOT NULL,
    entrada     DATETIME     NOT NULL,
    salida      DATETIME     NULL,
    estado      VARCHAR(10)  NOT NULL DEFAULT 'PRESENTE',
    PRIMARY KEY (id),
    CONSTRAINT ck_asistencia_estado CHECK (estado IN ('FALTA', 'PRESENTE', 'TARDANZA')),
    CONSTRAINT ck_asistencia_horas  CHECK (salida IS NULL OR salida >= entrada),
    CONSTRAINT fk_asistencia_empleado FOREIGN KEY (empleado_id) REFERENCES empleado (id)
) ENGINE=InnoDB;

-- Capacitaciones recibidas por cada empleado (tema, fecha y horas).
CREATE TABLE capacitacion (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    empleado_id INT UNSIGNED  NOT NULL,
    tema        VARCHAR(100)  NOT NULL,
    fecha       DATE          NOT NULL,
    horas       DECIMAL(5,1)  NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT ck_capacitacion_horas CHECK (horas > 0),
    CONSTRAINT fk_capacitacion_empleado FOREIGN KEY (empleado_id) REFERENCES empleado (id)
) ENGINE=InnoDB;

-- Aperturas y cierres de caja con montos inicial y final, por empleado.
CREATE TABLE historia_caja (
    id            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    caja_id       INT UNSIGNED  NOT NULL,
    empleado_id   INT UNSIGNED  NOT NULL,
    apertura      DATETIME      NOT NULL,
    cierre        DATETIME      NULL,
    monto_inicial DECIMAL(10,2) NOT NULL,
    monto_final   DECIMAL(10,2) NULL,
    estado        VARCHAR(10)   NOT NULL DEFAULT 'ABIERTA',
    PRIMARY KEY (id),
    CONSTRAINT ck_historia_caja_monto  CHECK (monto_inicial >= 0),
    CONSTRAINT ck_historia_caja_estado CHECK (estado IN ('ABIERTA', 'CERRADA')),
    CONSTRAINT fk_historia_caja_caja     FOREIGN KEY (caja_id)     REFERENCES caja (id),
    CONSTRAINT fk_historia_caja_empleado FOREIGN KEY (empleado_id) REFERENCES empleado (id)
) ENGINE=InnoDB;

-- Incidentes registrados por sucursal.
CREATE TABLE incidente (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sucursal_id INT UNSIGNED NOT NULL,
    tipo        VARCHAR(20)  NOT NULL,
    descripcion VARCHAR(250) NOT NULL,
    fecha       DATETIME     NOT NULL,
    estado      VARCHAR(10)  NOT NULL DEFAULT 'ABIERTO',
    PRIMARY KEY (id),
    CONSTRAINT ck_incidente_estado CHECK (estado IN ('ABIERTO', 'RESUELTO')),
    CONSTRAINT fk_incidente_sucursal FOREIGN KEY (sucursal_id) REFERENCES sucursal (id)
) ENGINE=InnoDB;

-- Comprobantes emitidos (boleta o factura) con subtotal, IGV, total y estado.
CREATE TABLE venta (
    id               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    sucursal_id      INT UNSIGNED  NOT NULL,
    historia_caja_id INT UNSIGNED  NOT NULL,
    empleado_id      INT UNSIGNED  NOT NULL,
    cliente_id       INT UNSIGNED  NULL,
    tipo_comprobante VARCHAR(7)    NOT NULL,
    serie            CHAR(4)       NOT NULL,
    correlativo      INT UNSIGNED  NOT NULL,
    fecha            DATETIME      NOT NULL,
    subtotal         DECIMAL(10,2) NOT NULL,
    igv              DECIMAL(10,2) NOT NULL,
    total            DECIMAL(10,2) NOT NULL,
    estado           VARCHAR(8)    NOT NULL DEFAULT 'EMITIDA',
    motivo_anulacion VARCHAR(150)  NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_venta_serie_correlativo UNIQUE (sucursal_id, serie, correlativo),
    CONSTRAINT ck_venta_tipo CHECK (tipo_comprobante IN ('BOLETA', 'FACTURA')),
    CONSTRAINT ck_venta_estado CHECK (estado IN ('EMITIDA', 'ANULADA')),
    CONSTRAINT ck_venta_total CHECK (subtotal >= 0 AND igv >= 0 AND total = subtotal + igv),
    CONSTRAINT ck_venta_factura_cliente CHECK (tipo_comprobante = 'BOLETA' OR cliente_id IS NOT NULL),
    CONSTRAINT fk_venta_sucursal      FOREIGN KEY (sucursal_id)      REFERENCES sucursal (id),
    CONSTRAINT fk_venta_historia_caja FOREIGN KEY (historia_caja_id) REFERENCES historia_caja (id),
    CONSTRAINT fk_venta_empleado      FOREIGN KEY (empleado_id)      REFERENCES empleado (id),
    CONSTRAINT fk_venta_cliente       FOREIGN KEY (cliente_id)       REFERENCES cliente (id)
) ENGINE=InnoDB;

-- Líneas de cada venta: producto, cantidad, precio unitario y total de la línea.
CREATE TABLE detalle_venta (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    venta_id        INT UNSIGNED  NOT NULL,
    producto_id     INT UNSIGNED  NOT NULL,
    cantidad        DECIMAL(10,3) NOT NULL,
    precio_unitario DECIMAL(10,2) NOT NULL,
    total_linea     DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT uq_detalle_venta_producto UNIQUE (venta_id, producto_id),
    CONSTRAINT ck_detalle_cantidad CHECK (cantidad > 0),
    CONSTRAINT fk_detalle_venta_venta    FOREIGN KEY (venta_id)    REFERENCES venta (id),
    CONSTRAINT fk_detalle_venta_producto FOREIGN KEY (producto_id) REFERENCES producto (id)
) ENGINE=InnoDB;

-- Pagos de cada venta según el medio de pago.
CREATE TABLE pago_venta (
    id         INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    venta_id   INT UNSIGNED  NOT NULL,
    medio_pago VARCHAR(8)    NOT NULL,
    monto      DECIMAL(10,2) NOT NULL,
    referencia VARCHAR(30)   NULL,
    PRIMARY KEY (id),
    CONSTRAINT ck_pago_medio CHECK (medio_pago IN ('EFECTIVO', 'PLIN', 'TARJETA', 'YAPE')),
    CONSTRAINT ck_pago_monto CHECK (monto > 0),
    CONSTRAINT fk_pago_venta_venta FOREIGN KEY (venta_id) REFERENCES venta (id)
) ENGINE=InnoDB;

-- Entradas, salidas, ajustes y mermas de stock; puede estar ligado a una venta.
CREATE TABLE movimiento_stock (
    id          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    sucursal_id INT UNSIGNED  NOT NULL,
    producto_id INT UNSIGNED  NOT NULL,
    empleado_id INT UNSIGNED  NOT NULL,
    venta_id    INT UNSIGNED  NULL,
    tipo        VARCHAR(7)    NOT NULL,
    cantidad    DECIMAL(10,3) NOT NULL,
    motivo      VARCHAR(100)  NULL,
    fecha       DATETIME      NOT NULL,
    PRIMARY KEY (id),
    CONSTRAINT ck_movimiento_tipo CHECK (tipo IN ('ENTRADA', 'SALIDA', 'AJUSTE', 'MERMA')),
    CONSTRAINT ck_movimiento_cantidad CHECK (cantidad > 0),
    CONSTRAINT fk_movimiento_stock    FOREIGN KEY (sucursal_id, producto_id) REFERENCES stock (sucursal_id, producto_id),
    CONSTRAINT fk_movimiento_empleado FOREIGN KEY (empleado_id) REFERENCES empleado (id),
    CONSTRAINT fk_movimiento_venta    FOREIGN KEY (venta_id)    REFERENCES venta (id)
) ENGINE=InnoDB;
