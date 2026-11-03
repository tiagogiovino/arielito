<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config.php';

function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

$error = '';
$mensaje = '';

if (isset($_GET['salir'])) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'login_alumno') {
    $usuario = trim((string)($_POST['usuario'] ?? ''));
    $dni = trim((string)($_POST['password'] ?? ''));

    try {
        // El usuario del alumno es su nombre completo y la clave inicial es el DNI.
        $stmt = $conn->prepare('SELECT id_usuario, nombre, usuario, `contraseña` FROM usuarios WHERE usuario = ? LIMIT 1');
        $stmt->execute([$usuario]);
        $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);

        $valida = false;
        if ($cuenta) {
            $guardada = (string)$cuenta['contraseña'];
            $valida = password_verify($dni, $guardada) || hash_equals($guardada, $dni);
        } else {
            // Compatibilidad con alumnos cargados antes de que existiera la tabla usuarios.
            // Si nombre completo + DNI coinciden en alumno, crea su cuenta automáticamente.
            $buscar = $conn->prepare('SELECT id_alumno, nombre, apellido, dni FROM alumno WHERE LOWER(TRIM(CONCAT(nombre, " ", apellido))) = LOWER(TRIM(?)) LIMIT 1');
            $buscar->execute([$usuario]);
            $alumno = $buscar->fetch(PDO::FETCH_ASSOC);
            if ($alumno && hash_equals(trim((string)$alumno['dni']), trim($dni))) {
                $nombreCompleto = trim($alumno['nombre'] . ' ' . $alumno['apellido']);
                $crear = $conn->prepare('INSERT INTO usuarios (nombre, usuario, `contraseña`) VALUES (?, ?, ?)');
                $crear->execute([$nombreCompleto, $nombreCompleto, password_hash($dni, PASSWORD_DEFAULT)]);
                $stmt->execute([$nombreCompleto]);
                $cuenta = $stmt->fetch(PDO::FETCH_ASSOC);
                $valida = (bool)$cuenta;
            }
        }

        if ($valida) {
            // Vincular la cuenta con el registro del alumno para filtrar sus inscripciones.
            $buscarAlumno = $conn->prepare(
                'SELECT id_alumno FROM alumno WHERE LOWER(TRIM(CONCAT(nombre, " ", apellido))) = LOWER(TRIM(?)) LIMIT 1'
            );
            $buscarAlumno->execute([(string)$cuenta['nombre']]);
            $idAlumno = $buscarAlumno->fetchColumn();

            session_regenerate_id(true);
            $_SESSION['tipo_usuario'] = 'alumno';
            $_SESSION['id_usuario'] = (int)$cuenta['id_usuario'];
            $_SESSION['id_alumno'] = $idAlumno !== false ? (int)$idAlumno : 0;
            $_SESSION['nombre_usuario'] = $cuenta['nombre'];

            // Registrar cada inicio de sesión correcto del alumno.
            // La tabla registro_ingresos_alumnos se crea con el SQL adjunto.
            try {
                $log = $conn->prepare(
                    'INSERT INTO registro_ingresos_alumnos (id_usuario, usuario, fecha_hora) VALUES (?, ?, NOW())'
                );
                $log->execute([(int)$cuenta['id_usuario'], (string)$cuenta['usuario']]);
            } catch (Throwable $logError) {
                // Un problema con el historial no debe impedir el acceso del alumno.
                error_log('[SIGEM historial de ingresos] ' . $logError->getMessage());
            }

            header('Location: index.php');
            exit;
        }
        $error = 'Nombre de usuario o DNI incorrectos. Si recién te registraron, consultá con preceptoría.';
    } catch (Throwable $e) {
        error_log('[SIGEM login alumno] ' . $e->getMessage());
        $error = 'No se pudo iniciar sesión. Verificá que la tabla usuarios exista en la base de datos.';
    }
}

$mesas = [];
if (($_SESSION['tipo_usuario'] ?? '') === 'alumno') {
    try {
        // Mostrar exclusivamente las mesas en las que este alumno está inscripto.
        $idAlumno = (int)($_SESSION['id_alumno'] ?? 0);
        if ($idAlumno > 0) {
            $consulta = $conn->prepare(
                'SELECT DISTINCT me.fecha, me.horario, ma.nombre AS materia,
                        CONCAT(d.apellido, ", ", d.nombre) AS docente, a.numero AS aula
                 FROM inscripcion i
                 INNER JOIN mesa me ON me.id_mesa = i.id_mesa
                 LEFT JOIN materia ma ON ma.id_materia = me.id_materia
                 LEFT JOIN docente d ON d.id_docente = me.id_docente
                 LEFT JOIN aula a ON a.id_aula = me.id_aula
                 WHERE i.id_alumno = ?
                 ORDER BY me.fecha, me.horario'
            );
            $consulta->execute([$idAlumno]);
            $mesas = $consulta->fetchAll(PDO::FETCH_ASSOC);
        }
    } catch (Throwable $e) {
        error_log('[SIGEM mesas alumno] ' . $e->getMessage());
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>SIGEM | Portal de alumnos</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:Arial,sans-serif;background:#eef3f9;color:#1f2937}
header{background:#173e68;color:white;padding:28px 18px;text-align:center}header h1{margin:0 0 8px}header p{margin:0;color:#dce8f5}
main{width:min(900px,calc(100% - 30px));margin:28px auto}
.card{background:white;border-radius:14px;padding:24px;margin-bottom:18px;box-shadow:0 4px 18px #14253b12}
.login{max-width:420px;margin:35px auto}label{display:block;margin:14px 0 6px;font-weight:600}
input{width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:7px;font-size:16px}
button,.btn{display:inline-block;background:#1c5d9d;color:white;border:0;border-radius:7px;padding:12px 16px;text-decoration:none;cursor:pointer;font-size:15px}
button{width:100%;margin-top:18px}.muted{color:#64748b;font-size:14px}.error{background:#fff0f0;color:#9b1c1c;padding:12px;border-radius:8px}
table{border-collapse:collapse;width:100%;min-width:600px}th,td{text-align:left;padding:11px;border-bottom:1px solid #e5e7eb}th{background:#edf3f9}.scroll{overflow-x:auto}
nav{display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:18px}
</style>
</head>
<body>
<header>
<h1>SIGEM</h1>
<p>Sistema Integral de Gestión de Mesas de Examen</p>
</header>
<main>
<?php if (($_SESSION['tipo_usuario'] ?? '') === 'alumno'): ?>
<section class="card">
<h2>¡Bienvenido/a, <?= h($_SESSION['nombre_usuario'] ?? 'alumno') ?>!</h2>
<p>Ingresaste al portal de alumnos. Desde aquí podés consultar únicamente las mesas de examen en las que estás inscripto.</p>
<a class="btn" href="index.php?salir=1">Cerrar sesión</a>
</section>
<section class="card">
<h2>Mesas de examen</h2>
<?php if (!$mesas): ?><p class="muted">No estás inscripto en ninguna mesa de examen por el momento.</p><?php else: ?>
<div class="scroll"><table><thead><tr><th>Materia</th><th>Fecha</th><th>Horario</th><th>Aula</th><th>Docente</th></tr></thead><tbody>
<?php foreach ($mesas as $m): ?><tr><td><?= h($m['materia']) ?></td><td><?= h($m['fecha']) ?></td><td><?= h($m['horario']) ?></td><td><?= h($m['aula']) ?></td><td><?= h($m['docente']) ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
</section>
<?php else: ?>
<section class="card login">
<h2>Ingreso de alumnos</h2>
<p class="muted">Ingresá con tu nombre completo (tal como fue cargado por preceptoría) y tu DNI.</p>
<?php if ($error !== ''): ?><p class="error" role="alert"><?= h($error) ?></p><?php endif; ?>
<form method="post" action="index.php">
<input type="hidden" name="accion" value="login_alumno">
<label for="usuario">Nombre completo</label>
<input id="usuario" name="usuario" maxlength="150" autocomplete="username" required>
<label for="password">DNI</label>
<input id="password" name="password" type="password" inputmode="numeric" autocomplete="current-password" required>
<button type="submit">Iniciar sesión</button>
</form>
<nav><a class="btn" href="docentes.php">Ingreso de docentes</a><a class="btn" href="preceptores.php">Ingreso de preceptores</a></nav>
</section>
<?php endif; ?>
</main>
</body>
</html>
