<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

$version = $pdo->query('SELECT VERSION()')->fetchColumn();
$dbActual = $pdo->query('SELECT DATABASE()')->fetchColumn();

$tablasEsperadas = [
    'alumno', 'docente', 'preceptor', 'aula', 'materia', 'correlativa',
    'mesa', 'inscripcion', 'nota', 'comprobante', 'validacion_ingreso'
];

$stmt = $pdo->query('SHOW TABLES');
$tablasExistentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
$faltantes = array_values(array_diff($tablasEsperadas, $tablasExistentes));

function h($value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Prueba de conexión - SIGEM</title>
<style>
body{font-family:Arial,sans-serif;max-width:850px;margin:35px auto;padding:0 16px;background:#f3f6fa;color:#1e293b}
main{background:#fff;padding:24px;border-radius:12px;box-shadow:0 4px 18px #0000000d}
.ok{color:#166534}.warn{color:#92400e}.muted{color:#64748b}
li{margin:7px 0}code{background:#eef2f7;padding:2px 5px;border-radius:4px}
a{color:#155eab}
</style>
</head>
<body><main>
<h1>Prueba de conexión PDO — SIGEM</h1>
<p class="ok"><strong>Conexión PHP → PDO → MySQL realizada correctamente.</strong></p>
<p><strong>Base seleccionada:</strong> <?= h($dbActual) ?></p>
<p><strong>Versión de MySQL/MariaDB:</strong> <?= h($version) ?></p>
<h2>Revisión de las tablas de SIGEM</h2>
<?php if (!$faltantes): ?>
<p class="ok">Están presentes las 11 tablas esperadas.</p>
<?php else: ?>
<p class="warn">La conexión funciona, pero faltan algunas tablas. Importá el archivo <code>sistema_examenes.sql</code> en phpMyAdmin.</p>
<p>Tablas faltantes:</p>
<ul><?php foreach ($faltantes as $tabla): ?><li><?= h($tabla) ?></li><?php endforeach; ?></ul>
<?php endif; ?>
<h2>Tablas detectadas</h2>
<ul><?php foreach ($tablasExistentes as $tabla): ?><li><?= h($tabla) ?></li><?php endforeach; ?></ul>
<p><a href="index.php">Ir al panel de SIGEM</a></p>
</main></body></html>
