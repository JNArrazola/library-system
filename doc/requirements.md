# Casos de uso funcionales 
## Caso de Uso 1: Registro de Usuarios
* **Actor:** Usuario no registrado.
* **Precondición:** El usuario no debe tener una cuenta registrada en el sistema.
* **Descripción:** Permitir que un usuario se registre en el sistema proporcionando su nombre, correo electrónico y una contraseña segura

### Flujo principal
1. El usuario accede al formulario de registro.
2. El usuario introduce su nombre, correo electrónico y contraseña.
3. El sistema valida que el correo no esté registrado previamente.
4. El sistema valida la complejidad de la contraseña.
5. El sistema almacena los datos y envía un correo de verificación.
6. El usuario recibe y confirma su cuenta haciendo clic en el enlace de activación.

### Excepciones
* 3a. El correo ya está registrado: El sistema muestra un mensaje de error.
* 4a. La contraseña no cumple con los criterios: El sistema solicita una contraseña más segura.

**Postcondición:** El usuario se registra con éxito y puede iniciar sesión después de verificar su cuenta por correo.

## Caso de Uso 2: Iniciar sesión
* **Actor:** Usuario registrado.
* **Precondición:** El usuario debe haberse registrado prevamente y haber confirmado su cuenta.
* **Descripción:** Permitir que un usuario registrado inicie sesión en el sistema usando su correo electrónico y contraseña.

### Flujo principal
1. El usuario accede a la página de inicio de sesión.
2. Introduce su correo electrónico y contraseña.
3. El sistema valida las credenciales.
4. EL sistema otorga acceso a la cuenta del usuario.

### Excepciones
* 3a. Credenciales incorrectas: El sistema muestra un mensaje de error.
* 3b. El usuario intenta iniciar sesión múltiples veces con credenciales incorrectas: El sistema bloquea temporalmente la cuenta.

**Postcondición:** El usuario inicia sesión exitosamente y accede a su cuenta.

## Caso de Uso 3: Búsqueda de Libros
* **Actor:** Usuario registrado
* **Precondición:** El catálogo de libros debe estar disponible en el sistema.
* **Descripción:** Permitir que los usuarios busquen libros por título, autor o categoría en el catálogo de la biblioteca.

### Flujo principal
1. El usuario accede al catálogo de libros.
2. El usuario introduce el criterio de búsqueda (título, autor, categoría).
3. El sistema muestra una lista de libros que coinciden con el criterio de búsqueda.
4. El usuario selecciona un libro para ver más detalles.

### Excepciones
* 3a. No se encuentran libros que coincidan con el criterio: El sistema muestra un mensaje indicando que no hay resultados.

**Postcondición:** El usuario visualiza los resultados de la búsqueda y puede acceder a la información del libro.

## Caso de Uso 4: Reserva de Libros
* **Actor:** Usuario registrado.
* **Precondición:** El usuario debe haber iniciado sesión en el sistema. El libro debe estar disponible para reserva.
* **Descripción:** Permitir que los usuarios reserven un libro disponible en la biblioteca para recogerlo en una fecha determinada.

### Flujo principal
1. El usuario busca un libro y lo selecciona.
2. El sistema muestra si el libro está disponible para reserva.
3. El usuario selecciona la opción de "Reservar".
4. El sistema solicita al usuario elegir una fecha de recogida.
5. El sistema confirma la reserva del libro y muestra la fecha de recogida.

### Excepciones
* 2a. El libro no está disponible: El sistema muestra un mensaje indicando que no hay ejemplares disponibles.
* 5a. El usuario no selecciona una fecha recogida: el sistema no permite proceder con la reserva.

**Postcondición:** El usuario ha reservado el libro exitosamente y no puede recogerlo en la fecha seleccionada.

## Caso de Uso 5: Notificaciones
* **Actor:** Sistema, Usuario registrado.
* **Precondición:** El usuario debe haber realizado una reserva o estar en una lista de espera.
* **Descripción:** El sistema debe enviar notificaciones automáticas por correo electrónico cuando una reserva esté lista para ser recogida o un libro reservado esté disponible.

### Flujo principal
1. El usuario reserva un libro.
2. El sistema monitorea el estado de la reserva.
3. Cuando el libro está disponible para recogida, el sistema envía una notificación por correo electrónico al usuario.
4. El usuario recibe y procede a la biblioteca a recoger el libro.

### Excepciones
* 4a. El correo no se entrega correctamente: El usuario no recibe la notificación, pero puede consultar el estado de su reserva en el sistema.

**Postcondición:** El usuario recibe una notificación cuando su reserva está lista.

## Caso de Uso 6: Historial de Reservas
* **Actor:** Usuario registrado.
* **Precondición:** El usuario debe haber iniciado sesión en el sistema y tener un historial de reservas.
* **Descripción:** Permitir que los usuarios consulten su historial de reservas, incluyendo el estado de cada una (pendiente, recogido, entregado).

### Flujo principal
1. El usuario accede a su cuenta.
2. El usuario selecciona la opción "Historial de Reservas".
3. El sistema muestra una lista de toddas las reservas realizadas por el usuario, junto con su estado.
4. El usuario puede filtrar las reservas por estado o fecha.

### Excepciones
* 3a. El usuario no tiene reservas en su historial: El sistema muestra un mensaje indicando que no hay reservas.

**Postcondición:** El usuario consulta el historial de reservas.

## Caso de Uso 7: Administración de Inventarios
* **Actor:** Bibliotecario
* **Precondición:** El bibliotecario debe haber iniciado sesión en el sistema con permisos de administrador.
* **Descripción:** Permitir a los bibliotecarios añadir, eliminar o modificar los registros de libros disponibles en el catálogo de biblioteca.

### Flujo principal
1. El bibliotecario accede a la sección de administración de inventarios.
2. El bibliotecario selecciona la opción de "Añadir", "Eliminar" o "Modificar" un libro.
3. El sistema solicita los detalles del libro.
4. El bibliotecario introduce o actualiza la información requerida.
5. El sistema confirma la acción y actualiza el catálogo de libros.

### Excepciones
* 3a. El bibliotecario introduce datos incorrectos o incompletos: El sistema muestra un mensaje de error y no permite continuar.

**Postcondición:** El bibliotecario gestiona el catálogo de libros exitosamente.

## Caso de Uso 8: Devoluciones de Libros
* **Actor:** Usuario registrado, Bibliotecario
* **Precondición:** El usuario debe haber tomado prestado un libro y estar en prceso de devolverlo.
* **Descripción:** Registrar la devolución de libros por prte de los usuarios y actualizar la disponibilidad del libro en el catálogo.

### Flujo principal
1. El usuario acude a la biblioteca para devolver un libro.
2. El bibliotecario escanea el código de barras del libro.
3. El sistema actualiza el estado del libro a "Disponible".
4. El sistema notifica al usuario que la devolución ha sido exitosa.

### Excepciones
* 2a. El sistema no puede leer el código de barras: El bibliotecario ingresa manualmente la información del libro.

**Postcondición:** El libro se devuelve correctamente y se actualiza su disponibilidad en el catálogo.

# Casos de uso no funcionales
## Casos de Uso 1: Seguridad - Autenticación Segura
* **Actor:** Usuario, Sistema
* **Precondición:** El sistema debe tener certificado HTTPS y protocolos de seguridad activos.
* **Descripción:** Proteger las credenciales de los usuarios mediante autenticación segura y encriptación.

### Flujo principal:
1. El usuario accede a la página de inicio de sesión mediante un enlace HTTPS.
2. El usuario introduce su correo y contraseña.
3. El sistema encripta las credenciales antes de enviarlas al servidor.
4. El servidor verifica las credenciales y permite el acceso al usuario.

### Excepciones
* 2a. Si la conexión no es segura, el sistema bloquea la operación y muestra un mensaje de alerta.
* 3a. Si la contraseña se ingresa correctamente tres veces, se bloquea temporalmente la cuenta.

**Postcondición:** El usuario accede al sistema de manera segura sin comprometer la información personal

## Caso de uso 2: Rendimiento - Manejo de Consultas Simultáneas
* **Actor:** Usuario, Sistema
* **Precondición:** El sistema debe tener la capacidad de gestionar múltiples consultas. 
* **Descripción:** El sistema debe soportar hasta 1,000 consultas de búsqueda simultáneas sin ralentización significativa.

### Flujo principal
1. Los usuarios acceden al sistema y realizan consultas de búsqueda.
2. El sistema procesa todas las solicitudes simultáneamente utilizando recursos optimizados del servidor.
3. Los resultados se devuelven a cada usuario en tiempo real sin interrupciones.

### Excepciones
* 2a. Si el límite de consultas se supera, el sistema distribuye la carga en un servidor alterno.

**Postcondición:** Las búsquedas se completan sin comprometer la velocidad de respuesta.

## Caso de Uso 3: Disponibilidad - Alta Disponibilidad del Sistema
* **Actor:** Usuario, Sistema
* **Precondición:** El sistema debe tener configuraciones de respaldo y monitoreo
* **Descripción:** Garantizar que el sistema esté disponible el 99.9% del tiempo, con un máximo de 1 hora de inactividad al mes.

### Flujo principal
1. El sistema monitorea su disponibilidad continuamente.
2. Si se detecta una posible falla, el sistema activa servidores de respaldo automáticamente.
3. El administrador recibe notificaciones si se produce una caída inesperada.

### Excepciones
* 2a. Si el sistema excede la hora de inactividad mensual, se despliega un mensaje explicando la situación al usuario.

**Postcondición:** El sistema se mantiene operativo casi todo el tiempo sin afectar a los usuarios.

## Caso de Uso 4: Usabilidad - Interfaz Intuitiva
* **Actor:** Usuario
* **Precondición:** El sistema debe estar diseñado con principios de UX/UI simples.
* **Descripción:** Los usuarios deben encontrar y reservar libros en menos de tres pasos.

### Flujo principal
1. El usuario accede al catálog desde la página principal.
2. Introduce el título o autor del libro en la barra de búsqueda.
3. Selecciona el libro y hace click en "Reservar".

### Excepciones
* 3a. Si el proceso de reserva excede los tres pasos, se detecta como un problema de usabilidad.

**Postcondición:** El usuario completa la reserva de forma sencilla y rápida.

## Caso de Uso 5: Compatibilidad - Navegadores y Dispositivos