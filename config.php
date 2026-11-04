<?php
declare(strict_types=1);


$host = '127.0.0.1';
$port = '3306';
$dbname = 'sistema_examenes';
$username = 'root';
$password = ''; // Cambiar solo si tu usuario MySQL tiene contraseña.

$dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    // index.php, docentes.php y preceptores.php usan $conn; probar_conexion.php usa $pdo.
    $conn = $pdo;
} catch (PDOException $e) {

    error_log('[SIGEM PDO] ' . $e->getMessage());
    http_response_code(500);
    exit(
        'No se pudo conectar a la base de datos. ' .
        'Verificá que MySQL esté iniciado en XAMPP, que exista la base sistema_examenes ' .
        'y que los datos de config.php sean correctos. Después abrí probar_conexion.php.'
    );
}
