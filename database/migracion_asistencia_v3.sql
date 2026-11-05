-- Reglas de asistencia (hora de Lima):
--   hasta 08:30       -> PRESENTE (temprano)
--   después de 08:30  -> TARDANZA (tarde)
--   FALTA solo si el administrador la registra (hora 00:00:00); no se toca.
-- Recalcula el estado de los registros ya guardados.
USE minimarket_mass;

UPDATE asistencia
SET estado = IF(TIME(entrada) > '08:30:59', 'TARDANZA', 'PRESENTE')
WHERE NOT (estado = 'FALTA' AND TIME(entrada) = '00:00:00');
