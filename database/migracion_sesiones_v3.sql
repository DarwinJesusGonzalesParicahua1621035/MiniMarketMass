CREATE TABLE IF NOT EXISTS sesion_recordada (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  empleado_id int unsigned NOT NULL,
  selector CHAR(18) NOT NULL UNIQUE,
  validador_hash CHAR(64) NOT NULL,
  expira DATETIME NOT NULL,
  agente VARCHAR(200) NULL,
  creado TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_sesion_empleado (empleado_id),
  CONSTRAINT fk_sesion_empleado FOREIGN KEY (empleado_id) REFERENCES empleado(id) ON DELETE CASCADE
) ENGINE=InnoDB;