<?php
declare(strict_types=1);
require __DIR__ . '/config.php';

function h($value): string {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}
function countRows(PDO $pdo, string $table): int {
    // Los nombres de tabla provienen de esta lista fija, nunca de datos del usuario.
    return (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
}

$mensaje = '';
$error = '';
$alumnos = [];
$mesas = [];
$materias = [];
$estadisticas = [];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'crear_alumno') {
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $apellido = trim((string)($_POST['apellido'] ?? ''));
        $dni = trim((string)($_POST['dni'] ?? ''));
        $curso = trim((string)($_POST['curso'] ?? ''));

        if ($nombre === '' || $apellido === '' || $dni === '') {
            $error = 'Completá nombre, apellido y DNI.';
        } elseif (mb_strlen($nombre) > 50 || mb_strlen($apellido) > 50 ||
                  mb_strlen($dni) > 20 || mb_strlen($curso) > 30) {
            $error = 'Algún dato supera la longitud permitida por la base de datos.';
        } else {
            $sql = 'INSERT INTO alumno (nombre, apellido, dni, curso)
                    VALUES (:nombre, :apellido, :dni, :curso)';
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombre,
                ':apellido' => $apellido,
                ':dni' => $dni,
                ':curso' => $curso !== '' ? $curso : null,
            ]);
            $mensaje = 'Alumno guardado correctamente. ID asignado: ' . $pdo->lastInsertId();
        }
    }

    $estadisticas = [
        'Alumnos' => countRows($pdo, 'alumno'),
        'Docentes' => countRows($pdo, 'docente'),
        'Preceptores' => countRows($pdo, 'preceptor'),
        'Materias' => countRows($pdo, 'materia'),
        'Mesas de examen' => countRows($pdo, 'mesa'),
        'Inscripciones' => countRows($pdo, 'inscripcion'),
    ];

    $alumnos = $pdo->query(
        'SELECT id_alumno, nombre, apellido, dni, curso
         FROM alumno ORDER BY apellido, nombre'
    )->fetchAll();

    $materias = $pdo->query(
        'SELECT id_materia, nombre FROM materia ORDER BY nombre'
    )->fetchAll();

    $mesas = $pdo->query(
        'SELECT me.id_mesa, me.fecha, me.horario,
                ma.nombre AS materia,
                CONCAT(d.apellido, ", ", d.nombre) AS docente,
                a.numero AS aula
         FROM mesa me
         LEFT JOIN materia ma ON ma.id_materia = me.id_materia
         LEFT JOIN docente d ON d.id_docente = me.id_docente
         LEFT JOIN aula a ON a.id_aula = me.id_aula
         ORDER BY me.fecha, me.horario'
    )->fetchAll();
} catch (PDOException $e) {
    error_log('[SIGEM panel] ' . $e->getMessage());
    if (str_contains($e->getMessage(), 'Duplicate entry')) {
        $error = 'Ese DNI ya está registrado. Revisá el dato e intentá nuevamente.';
    } else {
        $error = 'No se pudo completar la operación. Revisá si las tablas fueron creadas y mirá probar_conexion.php.';
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIGEM | Panel conectado a MySQL</title>
<style>
:root{font-family:Arial,sans-serif;color:#1f2937;background:#f3f6fa}
body{max-width:1180px;margin:28px auto;padding:0 16px}
header{background:#173e68;color:#fff;padding:24px;border-radius:14px}
header h1{margin:0 0 8px}header p{margin:0;color:#e3edf8}
section{background:#fff;margin:18px 0;padding:20px;border-radius:12px;box-shadow:0 3px 14px #1322380b}
.stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(135px,1fr));gap:12px}
.stat{border:1px solid #e0e7ef;border-radius:10px;padding:14px}
.stat strong{display:block;font-size:26px;color:#174e84;margin-top:8px}
form{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end}
label{display:flex;flex-direction:column;gap:6px;font-size:14px}
input{box-sizing:border-box;width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:7px}
button,.linkbtn{display:inline-block;padding:11px 15px;background:#1c5d9d;color:white;border:0;border-radius:7px;text-decoration:none;cursor:pointer}
table{border-collapse:collapse;width:100%;min-width:650px}
th,td{padding:10px;text-align:left;border-bottom:1px solid #e5e7eb;font-size:14px}
th{background:#edf3f9}.scroll{overflow-x:auto}.ok{color:#166534}.err{color:#991b1b}.muted{color:#64748b}
</style>
</head>
<body>
<header>
<h1>SIGEM</h1>
<p>Sistema Integral de Gestión de Mesas de Examen</p>
<p style="margin-top:12px"><strong class="ok">●</strong> Conexión PDO activa con la base de datos.</p>
</header>

<?php if ($mensaje): ?><section><p class="ok" role="status"><?= h($mensaje) ?></p></section><?php endif; ?>
<?php if ($error): ?><section><p class="err" role="alert"><?= h($error) ?></p></section><?php endif; ?>

<section>
<h2>Resumen de la base de datos</h2>
<div class="stats">
<?php foreach ($estadisticas as $titulo => $cantidad): ?>
<div class="stat"><?= h($titulo) ?><strong><?= h($cantidad) ?></strong></div>
<?php endforeach; ?>
</div>
<p class="muted">Los números se obtienen directamente de MySQL mediante PDO.</p>
<p><a class="linkbtn" href="probar_conexion.php">Verificar conexión y tablas</a></p>
</section>

<section>
<h2>Registrar alumno</h2>
<p class="muted">Los datos se guardan en la tabla <code>alumno</code>. Los campos con * son obligatorios.</p>
<form method="post" action="">
<input type="hidden" name="accion" value="crear_alumno">
<label>Nombre *
<input name="nombre" maxlength="50" required value="<?= h($_POST['nombre'] ?? '') ?>"></label>
<label>Apellido *
<input name="apellido" maxlength="50" required value="<?= h($_POST['apellido'] ?? '') ?>"></label>
<label>DNI *
<input name="dni" maxlength="20" required value="<?= h($_POST['dni'] ?? '') ?>"></label>
<label>Curso
<input name="curso" maxlength="30" value="<?= h($_POST['curso'] ?? '') ?>"></label>
<div><button type="submit">Guardar alumno</button></div>
</form>
</section>

<section>
<h2>Alumnos registrados</h2>
<?php if (!$alumnos): ?><p>No hay alumnos cargados todavía.</p><?php else: ?>
<div class="scroll"><table>
<thead><tr><th>ID</th><th>Apellido</th><th>Nombre</th><th>DNI</th><th>Curso</th></tr></thead>
<tbody><?php foreach ($alumnos as $alumno): ?>
<tr><td><?= h($alumno['id_alumno']) ?></td><td><?= h($alumno['apellido']) ?></td>
<td><?= h($alumno['nombre']) ?></td><td><?= h($alumno['dni']) ?></td><td><?= h($alumno['curso']) ?></td></tr>
<?php endforeach; ?></tbody></table></div>
<?php endif; ?>
</section>

<section>
<h2>Materias</h2>
<?php if (!$materias): ?><p>No hay materias cargadas todavía.</p><?php else: ?>
<div class="scroll"><table><thead><tr><th>ID</th><th>Materia</th></tr></thead><tbody>
<?php foreach ($materias as $materia): ?><tr><td><?= h($materia['id_materia']) ?></td><td><?= h($materia['nombre']) ?></td></tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
</section>

<section>
<h2>Mesas de examen</h2>
<?php if (!$mesas): ?><p>No hay mesas cargadas todavía.</p><?php else: ?>
<div class="scroll"><table><thead><tr><th>ID</th><th>Materia</th><th>Fecha</th><th>Horario</th><th>Aula</th><th>Docente</th></tr></thead><tbody>
<?php foreach ($mesas as $mesa): ?><tr>
<td><?= h($mesa['id_mesa']) ?></td><td><?= h($mesa['materia']) ?></td><td><?= h($mesa['fecha']) ?></td>
<td><?= h($mesa['horario']) ?></td><td><?= h($mesa['aula']) ?></td><td><?= h($mesa['docente']) ?></td>
</tr><?php endforeach; ?>
</tbody></table></div><?php endif; ?>
</section>
<footer><p class="muted">Versión inicial didáctica. Todavía no contiene login, roles ni todos los módulos académicos.</p></footer>
</body>
</html>
