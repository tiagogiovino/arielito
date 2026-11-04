<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config.php';
require __DIR__ . '/funciones.php'; // h(), CSRF y borrado de docentes

// Esta página solo es accesible con sesión de preceptor (el login está en preceptores.php).
if (!es_preceptor()) {
    header('Location: preceptores.php');
    exit;
}

$mensaje = (string)($_SESSION['sigem_mensaje_flash'] ?? '');
$error = (string)($_SESSION['sigem_error_flash'] ?? '');
unset($_SESSION['sigem_mensaje_flash'], $_SESSION['sigem_error_flash']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = (string)($_POST['accion'] ?? '');
    $mensaje = '';
    $error = '';
    try {
        if (!csrf_valido()) {
            throw new RuntimeException('El formulario venció. Recargá la página e intentá de nuevo.');
        }

        if ($accion === 'crear_docente') {
            $nombre = trim((string)($_POST['nombre'] ?? ''));
            $apellido = trim((string)($_POST['apellido'] ?? ''));
            $dni = trim((string)($_POST['dni'] ?? ''));
            if ($nombre === '' || $apellido === '' || $dni === '') {
                throw new RuntimeException('Completá nombre, apellido y DNI del docente.');
            }
            if (!preg_match('/^[0-9]{6,12}$/', $dni)) {
                throw new RuntimeException('El DNI debe tener solo números (entre 6 y 12 dígitos).');
            }
            $usuario = $nombre . ' ' . $apellido; // mismo formato que las cuentas existentes

            $st = $conn->prepare('SELECT 1 FROM docente WHERE dni = ? LIMIT 1');
            $st->execute([$dni]);
            if ($st->fetchColumn()) {
                throw new RuntimeException('Ya existe un docente con ese DNI.');
            }
            $st = $conn->prepare('SELECT 1 FROM acceso_docente WHERE usuario = ? LIMIT 1');
            $st->execute([$usuario]);
            if ($st->fetchColumn()) {
                throw new RuntimeException('Ya existe una cuenta con el usuario "' . $usuario . '". Si es otro docente con el mismo nombre, agregá un segundo nombre o inicial para distinguirlo.');
            }

            $conn->beginTransaction();
            $conn->prepare('INSERT INTO docente (nombre, apellido, dni) VALUES (?, ?, ?)')
                 ->execute([$nombre, $apellido, $dni]);
            $idDocente = (int)$conn->lastInsertId();
            $conn->prepare('INSERT INTO acceso_docente (id_docente, usuario, password_hash) VALUES (?, ?, ?)')
                 ->execute([$idDocente, $usuario, password_hash($dni, PASSWORD_DEFAULT)]);
            $conn->commit();
            $mensaje = 'Docente registrado. Usuario: ' . $usuario . ' | Contraseña inicial: su DNI.';

        } elseif ($accion === 'crear_acceso') {
            // Para docentes cargados antes de que existiera esta pantalla y que no tienen cuenta.
            $idObjetivo = (int)($_POST['id_objetivo'] ?? 0);
            $st = $conn->prepare('SELECT nombre, apellido, dni FROM docente WHERE id_docente = ?');
            $st->execute([$idObjetivo]);
            $d = $st->fetch(PDO::FETCH_ASSOC);
            if (!$d) {
                throw new RuntimeException('El docente ya no existe. Recargá la página.');
            }
            $st = $conn->prepare('SELECT 1 FROM acceso_docente WHERE id_docente = ? LIMIT 1');
            $st->execute([$idObjetivo]);
            if ($st->fetchColumn()) {
                throw new RuntimeException('Ese docente ya tiene cuenta de acceso.');
            }
            $usuario = trim($d['nombre'] . ' ' . $d['apellido']);
            $st = $conn->prepare('SELECT 1 FROM acceso_docente WHERE usuario = ? LIMIT 1');
            $st->execute([$usuario]);
            if ($st->fetchColumn()) {
                throw new RuntimeException('Ya existe una cuenta con el usuario "' . $usuario . '". Eliminá el registro duplicado o cargá al docente con un nombre distinto.');
            }
            $conn->prepare('INSERT INTO acceso_docente (id_docente, usuario, password_hash) VALUES (?, ?, ?)')
                 ->execute([$idObjetivo, $usuario, password_hash((string)$d['dni'], PASSWORD_DEFAULT)]);
            $mensaje = 'Cuenta creada. Usuario: ' . $usuario . ' | Contraseña inicial: su DNI.';

        } elseif ($accion === 'eliminar_docente') {
            $idObjetivo = (int)($_POST['id_objetivo'] ?? 0);
            if ($idObjetivo <= 0) {
                throw new RuntimeException('Docente inválido.');
            }
            $mensaje = borrar_docente($conn, $idObjetivo);
        }
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        error_log('[SIGEM preceptor docentes] ' . $e->getMessage());
        $mensaje = '';
        if ($e instanceof RuntimeException) {
            $error = $e->getMessage();
        } elseif (str_contains($e->getMessage(), 'Duplicate entry')) {
            $error = 'El DNI o el nombre de usuario ya existe. Revisá los datos.';
        } elseif (str_starts_with($accion, 'eliminar_')) {
            $error = 'No se pudo eliminar el docente. No se borró nada; intentá de nuevo.';
        } else {
            $error = 'No se pudo guardar. Verificá los datos y que las tablas de SIGEM estén creadas.';
        }
    }
    // Post/Redirect/Get: evita repetir la acción al recargar el navegador.
    $_SESSION['sigem_mensaje_flash'] = $mensaje;
    $_SESSION['sigem_error_flash'] = $error;
    header('Location: preceptor_docentes.php');
    exit;
}

$docentes = [];
try {
    $docentes = $conn->query(
        'SELECT d.id_docente, d.nombre, d.apellido, d.dni, ad.usuario,
                (SELECT COUNT(*) FROM mesa m WHERE m.id_docente = d.id_docente) AS mesas
         FROM docente d
         LEFT JOIN acceso_docente ad ON ad.id_docente = d.id_docente
         ORDER BY d.apellido, d.nombre'
    )->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[SIGEM lista docentes preceptor] ' . $e->getMessage());
    $error = $error ?: 'No se pudo consultar la lista de docentes.';
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>SIGEM | Gestión de docentes</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#eef3f9;color:#1f2937;font-family:Arial,sans-serif}header{background:#173e68;color:white;padding:24px max(18px,calc((100% - 1100px)/2));display:flex;align-items:center;justify-content:space-between;gap:15px;flex-wrap:wrap}header h1{margin:0}header p{margin:6px 0;color:#dce8f5}.wrap{max-width:1100px;margin:22px auto;padding:0 15px}.card{background:white;border-radius:12px;padding:20px;margin-bottom:18px;box-shadow:0 3px 14px #1322380b}form.alta{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;align-items:end}label{display:flex;flex-direction:column;gap:6px;font-size:14px}input{width:100%;padding:11px;border:1px solid #cbd5e1;border-radius:7px;background:white}button,.btn{display:inline-block;padding:11px 15px;background:#1c5d9d;color:white;border:0;border-radius:7px;text-decoration:none;cursor:pointer;font-size:14px}.btn-chico{padding:6px 10px;font-size:13px}.ok{color:#166534}.err{color:#991b1b}.muted{color:#64748b;font-size:14px}.scroll{overflow-x:auto}table{border-collapse:collapse;width:100%;min-width:600px}th,td{padding:10px;text-align:left;border-bottom:1px solid #e5e7eb;font-size:14px}th{background:#edf3f9}
<?= sigem_css_eliminar() ?>
</style></head><body>
<header><div><h1>SIGEM · Gestión de docentes</h1><p>Sesión iniciada: <?= h($_SESSION['nombre_preceptor'] ?? 'Preceptor/a') ?></p></div><div><a class="btn" href="preceptores.php">Volver al panel de preceptoría</a> <a class="btn" href="preceptores.php?salir=1">Cerrar sesión</a></div></header>
<div class="wrap">
<?php if ($mensaje): ?><section class="card"><p class="ok" role="status"><?= h($mensaje) ?></p></section><?php endif; ?>
<?php if ($error): ?><section class="card"><p class="err" role="alert"><?= h($error) ?></p></section><?php endif; ?>

<section class="card"><h2>Registrar docente</h2>
<p class="muted">Al guardar, se crea también su cuenta de acceso al portal docente. Usuario = nombre y apellido; contraseña inicial = DNI.</p>
<form class="alta" method="post"><?= csrf_campo() ?><input type="hidden" name="accion" value="crear_docente">
<label>Nombre<input name="nombre" maxlength="50" required></label>
<label>Apellido<input name="apellido" maxlength="50" required></label>
<label>DNI (solo números)<input name="dni" maxlength="12" inputmode="numeric" pattern="[0-9]{6,12}" title="Entre 6 y 12 dígitos, solo números" required></label>
<button>Guardar docente</button></form></section>

<section class="card"><h2>Docentes registrados</h2>
<p class="muted">Hacé clic en el nombre de un docente para eliminarlo si se cargó con datos erróneos. Sus mesas no se borran: quedan sin docente asignado.</p>
<?php if (!$docentes): ?><p class="muted">Todavía no hay docentes cargados.</p><?php else: ?>
<div class="scroll"><table><thead><tr><th>Apellido y nombre</th><th>DNI</th><th>Usuario de acceso</th><th>Mesas asignadas</th></tr></thead><tbody>
<?php foreach ($docentes as $d):
    $nm = (int)$d['mesas'];
    $detalle = 'DNI ' . $d['dni'] . '. Se eliminará también su cuenta de acceso'
        . ($d['usuario'] !== null ? ' (' . $d['usuario'] . ')' : '') . '.'
        . ($nm > 0 ? ' ' . $nm . ($nm === 1 ? ' mesa quedará' : ' mesas quedarán') . ' sin docente asignado.' : '');
?>
<tr>
<td><button type="button" class="nombre-link" data-eliminar data-accion="eliminar_docente" data-id="<?= h($d['id_docente']) ?>" data-nombre="<?= h($d['apellido'] . ', ' . $d['nombre']) ?>" data-detalle="<?= h($detalle) ?>"><?= h($d['apellido'] . ', ' . $d['nombre']) ?></button></td>
<td><?= h($d['dni']) ?></td>
<td><?php if ($d['usuario'] !== null): ?><?= h($d['usuario']) ?><?php else: ?><span class="err">Sin cuenta</span>
<form method="post" style="display:inline"><?= csrf_campo() ?><input type="hidden" name="accion" value="crear_acceso"><input type="hidden" name="id_objetivo" value="<?= h($d['id_docente']) ?>"><button class="btn-chico">Crear acceso</button></form><?php endif; ?></td>
<td><?= $nm ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?></section>
</div>
<?php sigem_dialogo_eliminar(); ?>
</body></html>
