<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config.php';

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
$mensaje = (string)($_SESSION['sigem_mensaje_flash'] ?? '');
$error = (string)($_SESSION['sigem_error_flash'] ?? '');
unset($_SESSION['sigem_mensaje_flash'], $_SESSION['sigem_error_flash']);

try {
    // El SQL del proyecto agrega usuario y contraseña directamente a preceptor.
    $conn->exec('ALTER TABLE usuarios MODIFY usuario VARCHAR(150) NOT NULL');
} catch (Throwable $e) {
    error_log('[SIGEM configuración] ' . $e->getMessage());
}

if (isset($_GET['salir'])) {
    unset($_SESSION['tipo_usuario'], $_SESSION['id_preceptor'], $_SESSION['nombre_preceptor']);
    header('Location: preceptores.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'login_preceptor') {
    $usuario = trim((string)($_POST['usuario'] ?? ''));
    $clave = (string)($_POST['password'] ?? '');
    try {
        $stmt = $conn->prepare(
            'SELECT id_preceptor, usuario, contraseña, CONCAT(nombre, " ", apellido) AS nombre
             FROM preceptor WHERE usuario = ? LIMIT 1'
        );
        $stmt->execute([$usuario]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        // En estos datos de prueba, la contraseña inicial es el DNI del preceptor.
        if ($p && hash_equals((string)$p['contraseña'], $clave)) {
            session_regenerate_id(true);
            $_SESSION['tipo_usuario'] = 'preceptor';
            $_SESSION['id_preceptor'] = (int)$p['id_preceptor'];
            $_SESSION['nombre_preceptor'] = $p['nombre'];
            header('Location: preceptores.php');
            exit;
        }
        $error = 'Usuario o contraseña de preceptor incorrectos.';
    } catch (Throwable $e) {
        error_log('[SIGEM login preceptor] ' . $e->getMessage());
        $error = 'No se pudo validar el acceso. Verificá las tablas y las credenciales de preceptoría.';
    }
}

$autorizado = (($_SESSION['tipo_usuario'] ?? '') === 'preceptor');
if (!$autorizado) {
    ?>
    <!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>SIGEM | Acceso de preceptores</title>
    <style>*{box-sizing:border-box}body{margin:0;background:#eef3f9;font-family:Arial,sans-serif;color:#1f2937}.box{background:white;width:min(420px,calc(100% - 30px));margin:8vh auto;padding:28px;border-radius:14px;box-shadow:0 4px 18px #14253b12}h1{color:#173e68}label{display:block;margin:14px 0 6px}input{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:7px}button,.btn{display:inline-block;width:100%;margin-top:18px;background:#173e68;color:white;padding:12px;border:0;border-radius:7px;text-decoration:none;text-align:center;cursor:pointer}.err{background:#fff0f0;color:#991b1b;padding:10px;border-radius:7px}</style></head><body>
    <div class="box"><h1>SIGEM</h1><h2>Ingreso de preceptores</h2><p>Acceso exclusivo para personal de preceptoría.</p>
    <?php if ($error): ?><p class="err"><?= h($error) ?></p><?php endif; ?>
    <form method="post"><input type="hidden" name="accion" value="login_preceptor"><label>Usuario</label><input name="usuario" required autocomplete="username"><label>Contraseña</label><input type="password" name="password" required autocomplete="current-password"><button>Ingresar al panel</button></form>
    <a class="btn" href="index.php">Volver al portal de alumnos</a>
    <p style="font-size:13px;color:#64748b">Las cuentas de preceptor se habilitan desde la base de datos; no se usa el DNI como contraseña automáticamente.</p></div></body></html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = (string)($_POST['accion'] ?? '');
    try {
        if ($accion === 'crear_alumno') {
            $nombre = trim((string)($_POST['nombre'] ?? ''));
            $apellido = trim((string)($_POST['apellido'] ?? ''));
            $dni = trim((string)($_POST['dni'] ?? ''));
            $curso = trim((string)($_POST['curso'] ?? ''));
            if ($nombre === '' || $apellido === '' || $dni === '') {
                throw new RuntimeException('Completá nombre, apellido y DNI del alumno.');
            }
            $conn->beginTransaction();
            $stmt = $conn->prepare('INSERT INTO alumno (nombre, apellido, dni, curso) VALUES (?, ?, ?, ?)');
            $stmt->execute([$nombre, $apellido, $dni, $curso !== '' ? $curso : null]);
            $nombreCompleto = $nombre . ' ' . $apellido;
            $stmt = $conn->prepare('INSERT INTO usuarios (nombre, usuario, `contraseña`) VALUES (?, ?, ?)');
            $stmt->execute([$nombreCompleto, $nombreCompleto, password_hash($dni, PASSWORD_DEFAULT)]);
            $conn->commit();
            $mensaje = 'Alumno registrado y usuario creado. Usuario: ' . $nombreCompleto . ' | Contraseña inicial: DNI.';
        } elseif ($accion === 'crear_docente') {
            $nombre = trim((string)($_POST['nombre'] ?? ''));
            $apellido = trim((string)($_POST['apellido'] ?? ''));
            $dni = trim((string)($_POST['dni'] ?? ''));
            if ($nombre === '' || $apellido === '' || $dni === '') throw new RuntimeException('Completá nombre, apellido y DNI del docente.');
            $stmt = $conn->prepare('INSERT INTO docente (nombre, apellido, dni) VALUES (?, ?, ?)');
            $stmt->execute([$nombre, $apellido, $dni]);
            $mensaje = 'Docente cargado correctamente.';
        } elseif ($accion === 'crear_mesa') {
            $fecha = (string)($_POST['fecha'] ?? '');
            $horario = (string)($_POST['horario'] ?? '');
            $materia = (int)($_POST['id_materia'] ?? 0);
            $docente = (int)($_POST['id_docente'] ?? 0);
            $aula = (int)($_POST['id_aula'] ?? 0);
            if ($fecha === '' || $horario === '' || $materia <= 0) throw new RuntimeException('Completá fecha, horario y materia para crear la mesa.');
            $stmt = $conn->prepare('INSERT INTO mesa (fecha, horario, id_aula, id_docente, id_materia) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$fecha, $horario, $aula ?: null, $docente ?: null, $materia]);
            $mensaje = 'Mesa de examen creada correctamente.';
        } elseif ($accion === 'crear_inscripcion') {
            $idAlumno = (int)($_POST['id_alumno'] ?? 0);
            $idMesa = (int)($_POST['id_mesa'] ?? 0);
            if ($idAlumno <= 0 || $idMesa <= 0) throw new RuntimeException('Seleccioná un alumno y una mesa.');
            $stmt = $conn->prepare('INSERT INTO inscripcion (fecha_inscripcion, estado, id_alumno, id_mesa) VALUES (CURDATE(), ?, ?, ?)');
            $stmt->execute(['Inscripto', $idAlumno, $idMesa]);
            $mensaje = 'Alumno inscripto en la mesa correctamente.';
        }
    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        error_log('[SIGEM panel preceptor] ' . $e->getMessage());
        if ($e instanceof RuntimeException) $error = $e->getMessage();
        elseif (str_contains($e->getMessage(), 'Duplicate entry')) $error = 'El DNI, el nombre de usuario o la inscripción ya existe. Revisá los datos.';
        else $error = 'No se pudo guardar. Verificá los datos y que las tablas de SIGEM estén creadas.';
    }
    // Post/Redirect/Get: evita que al recargar el navegador se repita el INSERT.
    $_SESSION['sigem_mensaje_flash'] = $mensaje;
    $_SESSION['sigem_error_flash'] = $error;
    header('Location: preceptores.php');
    exit;
}

$alumnos = $docentes = $materias = $aulas = $mesas = [];
try {
    $alumnos = $conn->query('SELECT id_alumno, nombre, apellido, dni, curso FROM alumno ORDER BY apellido, nombre')->fetchAll(PDO::FETCH_ASSOC);
    $docentes = $conn->query('SELECT id_docente, nombre, apellido, dni FROM docente ORDER BY apellido, nombre')->fetchAll(PDO::FETCH_ASSOC);
    $materias = $conn->query('SELECT id_materia, nombre FROM materia ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
    $aulas = $conn->query('SELECT id_aula, numero FROM aula ORDER BY numero')->fetchAll(PDO::FETCH_ASSOC);
    $mesas = $conn->query('SELECT me.id_mesa, me.fecha, me.horario, ma.nombre AS materia, CONCAT(d.apellido, ", ", d.nombre) AS docente, a.numero AS aula
        FROM mesa me LEFT JOIN materia ma ON ma.id_materia=me.id_materia LEFT JOIN docente d ON d.id_docente=me.id_docente
        LEFT JOIN aula a ON a.id_aula=me.id_aula ORDER BY me.fecha, me.horario')->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[SIGEM listas preceptor] ' . $e->getMessage());
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>SIGEM | Panel de preceptoría</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#eef3f9;color:#1f2937;font-family:Arial,sans-serif}header{background:#173e68;color:white;padding:24px max(18px,calc((100% - 1100px)/2));display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap}header h1{margin:0}header p{margin:6px 0;color:#dce8f5}header a{color:white}.wrap{max-width:1100px;margin:22px auto;padding:0 15px}.card{background:white;border-radius:12px;padding:20px;margin-bottom:18px;box-shadow:0 3px 14px #1322380b}form{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end}label{display:flex;flex-direction:column;gap:6px;font-size:14px}input,select{width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:7px;background:white}button,.btn{display:inline-block;padding:11px 15px;background:#1c5d9d;color:white;border:0;border-radius:7px;text-decoration:none;cursor:pointer}button{align-self:end}.ok{color:#166534}.err{color:#991b1b}.muted{color:#64748b;font-size:14px}.scroll{overflow-x:auto}table{border-collapse:collapse;width:100%;min-width:600px}th,td{padding:10px;text-align:left;border-bottom:1px solid #e5e7eb;font-size:14px}th{background:#edf3f9}
</style></head><body>
<header><div><h1>SIGEM · Preceptoría</h1><p>Sesión iniciada: <?= h($_SESSION['nombre_preceptor'] ?? 'Preceptor/a') ?></p></div><a class="btn" href="preceptores.php?salir=1">Cerrar sesión</a></header>
<div class="wrap">
<?php if ($mensaje): ?><section class="card"><p class="ok" role="status"><?= h($mensaje) ?></p></section><?php endif; ?>
<?php if ($error): ?><section class="card"><p class="err" role="alert"><?= h($error) ?></p></section><?php endif; ?>
<section class="card"><h2>Registrar alumno</h2><p class="muted">Al guardar al alumno, se crea automáticamente su usuario de acceso. Usuario = nombre y apellido; contraseña inicial = DNI.</p>
<form method="post"><input type="hidden" name="accion" value="crear_alumno">
<label>Nombre<input name="nombre" maxlength="50" required></label><label>Apellido<input name="apellido" maxlength="50" required></label><label>DNI<input name="dni" maxlength="20" required></label><label>Curso<input name="curso" maxlength="30"></label><button>Guardar alumno y crear usuario</button></form></section>
<section class="card"><h2>Cargar docente</h2><form method="post"><input type="hidden" name="accion" value="crear_docente"><label>Nombre<input name="nombre" maxlength="50" required></label><label>Apellido<input name="apellido" maxlength="50" required></label><label>DNI<input name="dni" maxlength="20" required></label><button>Guardar docente</button></form>
<?php if ($docentes): ?><h3>Docentes registrados</h3><div class="scroll"><table><thead><tr><th>Apellido</th><th>Nombre</th><th>DNI</th></tr></thead><tbody><?php foreach ($docentes as $d): ?><tr><td><?= h($d['apellido']) ?></td><td><?= h($d['nombre']) ?></td><td><?= h($d['dni']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="card"><h2>Crear mesa de examen</h2><form method="post"><input type="hidden" name="accion" value="crear_mesa"><label>Fecha<input type="date" name="fecha" required></label><label>Horario<input type="time" name="horario" required></label><label>Materia<select name="id_materia" required><option value="">Elegir materia...</option><?php foreach ($materias as $m): ?><option value="<?= h($m['id_materia']) ?>"><?= h($m['nombre']) ?></option><?php endforeach; ?></select></label><label>Docente<select name="id_docente"><option value="">Sin asignar</option><?php foreach ($docentes as $d): ?><option value="<?= h($d['id_docente']) ?>"><?= h($d['apellido'] . ', ' . $d['nombre']) ?></option><?php endforeach; ?></select></label><label>Aula<select name="id_aula"><option value="">Sin asignar</option><?php foreach ($aulas as $a): ?><option value="<?= h($a['id_aula']) ?>"><?= h($a['numero']) ?></option><?php endforeach; ?></select></label><button>Crear mesa</button></form>
<?php if ($mesas): ?><h3>Mesas creadas</h3><div class="scroll"><table><thead><tr><th>Materia</th><th>Fecha</th><th>Horario</th><th>Aula</th><th>Docente</th></tr></thead><tbody><?php foreach ($mesas as $m): ?><tr><td><?= h($m['materia']) ?></td><td><?= h($m['fecha']) ?></td><td><?= h($m['horario']) ?></td><td><?= h($m['aula']) ?></td><td><?= h($m['docente']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<section class="card"><h2>Inscribir alumno en una mesa</h2><form method="post"><input type="hidden" name="accion" value="crear_inscripcion"><label>Alumno<select name="id_alumno" required><option value="">Elegir alumno...</option><?php foreach ($alumnos as $a): ?><option value="<?= h($a['id_alumno']) ?>"><?= h($a['apellido'] . ', ' . $a['nombre'] . ' — DNI ' . $a['dni']) ?></option><?php endforeach; ?></select></label><label>Mesa<select name="id_mesa" required><option value="">Elegir mesa...</option><?php foreach ($mesas as $m): ?><option value="<?= h($m['id_mesa']) ?>"><?= h($m['materia'] . ' — ' . $m['fecha'] . ' ' . $m['horario']) ?></option><?php endforeach; ?></select></label><button>Inscribir alumno</button></form></section>
<section class="card"><h2>Alumnos registrados</h2><?php if (!$alumnos): ?><p class="muted">Todavía no hay alumnos cargados.</p><?php else: ?><div class="scroll"><table><thead><tr><th>Apellido</th><th>Nombre</th><th>DNI</th><th>Curso</th></tr></thead><tbody><?php foreach ($alumnos as $a): ?><tr><td><?= h($a['apellido']) ?></td><td><?= h($a['nombre']) ?></td><td><?= h($a['dni']) ?></td><td><?= h($a['curso']) ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></section>
<p class="muted">Las actas y notas existentes pueden seguir administrándose desde el módulo de actas original, una vez protegido también con la sesión de preceptor.</p>
</div></body></html>
