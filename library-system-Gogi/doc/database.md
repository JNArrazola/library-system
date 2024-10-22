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
    imagen LONGBLOB,
    sinopsis TEXT,
    cantidad INT
);

CREATE TABLE Reservas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT,
    libro_id INT,
    fecha_reserva DATE,  
    fecha_recepcion DATE,
    fecha_devolucion DATE,
    B_Entregado BIT NOT NULL DEFAULT 0,
    FOREIGN KEY (usuario_id) REFERENCES Usuarios(id),
    FOREIGN KEY (libro_id) REFERENCES Libros(id)
);

INSERT INTO Usuarios (nombre, apellido, correo, direccion, rol, password, activation_code, is_active)
VALUES 
    ('Juan', 'Perez', 'juan.perez@email.com', 'Calle Falsa 123', 'usuario', '$2y$10$2Qfq9iyiZwMaDNkr91US/uyL3crZrEYbV2FwiEyGwbUDOjnox3XcW', NULL, 1), -- Password: Usuario123
    ('Ana', 'Perez', 'ana.perez@email.com', 'Avenida Central 456', 'bibliotecario', '$2y$10$OMpbr1z6Fl5cL3avd3JA4uPoj9sUCtgDspfpHhL0jMsmR4ovDm5rG', NULL, 1), -- Password: Biblio123
    ('Jose', 'Perez', 'jose.perez@email.com', 'Plaza Mayor 789', 'administrador', '$2y$10$bwkAA6DQayn1qNysc18EquEM21PNh.SAYHirWM90KhvooW/P0VU7G', NULL, 1); -- Password: Admin123

INSERT INTO Libros (nombre, autor, fecha_publicacion, editorial, sinopsis, cantidad)
VALUES 
    ('El Quijote', 'Miguel de Cervantes', '1605-01-16', 'Editorial Clásica', 'Una de las novelas más importantes de la literatura española', 5),
    ('Cien Años de Soledad', 'Gabriel García Márquez', '1967-05-30', 'Editorial Sudamericana', 'La obra maestra del realismo mágico', 3),
    ('1984', 'George Orwell', '1949-06-08', 'Secker & Warburg', 'Una novela distópica sobre el control del estado', 4);

SELECT * FROM Usuarios;
SELECT * FROM Libros;

```
