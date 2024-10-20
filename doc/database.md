# Database script
Code to create the database

``` SQL
CREATE DATABASE LIBRARY_SYSTEM;
USE LIBRARY_SYSTEM;

CREATE TABLE Usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100),
    apellido VARCHAR(100),
    correo VARCHAR(100) UNIQUE,
    direccion VARCHAR(255),
    rol ENUM('usuario', 'bibliotecario', 'administrador') NOT NULL,
    password VARCHAR(255) NOT NULL,
    activation_code VARCHAR(32),
    is_active BOOLEAN DEFAULT 0
);

CREATE TABLE Libros (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(255),
    autor VARCHAR(255),
    fecha_publicacion DATE,
    editorial VARCHAR(100),
    sinopsis TEXT,
    cantidad INT
);

CREATE TABLE Reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    libro_id INT,
    fecha_recepcion DATE NOT NULL,
    fecha_devolucion DATE,
    FOREIGN KEY (usuario_id) REFERENCES Usuarios(id),
    FOREIGN KEY (libro_id) REFERENCES Libros(id)
);

```
