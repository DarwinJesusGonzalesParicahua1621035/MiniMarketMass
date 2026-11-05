-- Migración v2 de Minimarket Mass. Ejecutar UNA sola vez, con copia de seguridad previa.
USE minimarket_mass;

-- 1) Nuevo tipo de movimiento: VENTA (antes las ventas se guardaban como SALIDA).
ALTER TABLE movimiento_stock DROP CHECK ck_movimiento_tipo;
UPDATE movimiento_stock SET tipo = 'VENTA' WHERE venta_id IS NOT NULL AND tipo = 'SALIDA';
ALTER TABLE movimiento_stock
    ADD CONSTRAINT ck_movimiento_tipo CHECK (tipo IN ('ENTRADA', 'SALIDA', 'VENTA'));

-- 2) Un producto solo pertenece a las sucursales donde tiene fila en STOCK.
--    Se quitan las filas vacías (0 unidades y sin historial) que creó la migración anterior;
--    luego se asignan desde Productos > Editar > Sucursales.
DELETE st FROM stock st
WHERE st.cantidad = 0
  AND NOT EXISTS (SELECT 1 FROM movimiento_stock m
                  WHERE m.sucursal_id = st.sucursal_id AND m.producto_id = st.producto_id);
