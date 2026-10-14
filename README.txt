SIGEM - conexión PDO completa (base inicial)
==============================================

ARCHIVOS
- config.php: establece la conexión PDO con MySQL.
- probar_conexion.php: comprueba conexión, base seleccionada y tablas esperadas.
- index.php: panel consultando MySQL; registra alumnos y lista alumnos, materias y mesas.
- sistema_examenes.sql: no está duplicado aquí; usá el archivo SQL que ya tenés.

INSTALACIÓN EN WINDOWS CON XAMPP
1. Extraé la carpeta SIGEM_Conexion_PDO_Completa en:
   C:\xampp\htdocs\
   (La carpeta final debe ser C:\xampp\htdocs\SIGEM_Conexion_PDO_Completa\)

2. Abrí el Panel de Control de XAMPP e iniciá Apache y MySQL.

3. Abrí http://localhost/phpmyadmin
   - Entrá a la pestaña SQL y ejecutá el contenido de sistema_examenes.sql,
     o importá el archivo desde la opción Importar.
   - El script crea la base de datos sistema_examenes y sus 11 tablas.

4. Revisá config.php:
   - host: 127.0.0.1
   - port: 3306
   - dbname: sistema_examenes
   - username: root
   - password: vacío en la configuración inicial habitual de XAMPP.
   Si configuraste una contraseña, escribila en $password.

5. Probá primero:
   http://localhost/SIGEM_Conexion_PDO_Completa/probar_conexion.php

6. Cuando diga que la conexión funciona, abrí:
   http://localhost/SIGEM_Conexion_PDO_Completa/

SI FALLA LA CONEXIÓN
- Error “Unknown database”: no se importó el SQL o el nombre de base de datos no coincide.
- Error “Access denied”: revisá usuario y contraseña en config.php.
- Error “Connection refused”: iniciá MySQL en XAMPP y revisá el puerto.
- Error “could not find driver”: en la configuración de PHP/XAMPP debe estar habilitada la extensión pdo_mysql.

QUÉ ESTÁ CONECTADO
- PDO con manejo de errores.
- Alta de alumnos con consulta preparada.
- Lectura de alumnos, materias y mesas de examen.
- JOIN para combinar la mesa con materia, aula y docente.
- Contadores consultados directamente de MySQL.

LÍMITES
Esta entrega completa la conexión y una pantalla demostrativa, pero no equivale al SIGEM completo del Hackathon.
Faltan autenticación y permisos, inscripciones y validación de correlativas, carga de notas, actas PDF,
comprobantes PDF con QR, lectura de QR y validación del ingreso. Es importante entender el código
y poder explicarlo durante la defensa técnica.
