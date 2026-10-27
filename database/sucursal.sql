CREATE TABLE sucursal (
  id_sucursal INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(100) NOT NULL,
  direccion VARCHAR(200) NOT NULL,
  id_distrito INT NOT NULL,
  FOREIGN KEY (id_distrito) REFERENCES distrito(id_distrito)
);