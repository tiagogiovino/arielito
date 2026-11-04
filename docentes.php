<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config.php';
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
$error = $mensaje = '';

if (isset($_GET['salir'])) {
    unset($_SESSION['tipo_usuario'], $_SESSION['id_docente'], $_SESSION['nombre_docente']);
    header('Location: docentes.php'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'login_docente') {
    try {
        $st = $conn->prepare('SELECT ad.id_docente,ad.usuario,ad.password_hash,CONCAT(d.nombre," ",d.apellido) AS nombre
          FROM acceso_docente ad JOIN docente d ON d.id_docente=ad.id_docente WHERE ad.usuario=? LIMIT 1');
        $st->execute([trim((string)($_POST['usuario'] ?? ''))]);
        $d = $st->fetch(PDO::FETCH_ASSOC);
        if ($d && password_verify((string)($_POST['password'] ?? ''), $d['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['tipo_usuario'] = 'docente';
            $_SESSION['id_docente'] = (int)$d['id_docente'];
            $_SESSION['nombre_docente'] = $d['nombre'];
            header('Location: docentes.php'); exit;
        }
        $error = 'Usuario o contraseña incorrectos.';
    } catch (Throwable $e) {
        error_log('[SIGEM login docente] '.$e->getMessage());
        $error = 'No se pudo validar el acceso. Verificá que exista la tabla acceso_docente y la cuenta.';
    }
}
if (($_SESSION['tipo_usuario'] ?? '') !== 'docente') {
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SIGEM | Docentes</title><style>body{font-family:Arial;background:#eef3f9;color:#1f2937}.box{background:white;max-width:420px;margin:8vh auto;padding:28px;border-radius:14px;box-shadow:0 4px 18px #14253b12}h1{color:#173e68}label{display:block;margin:14px 0 6px}input{box-sizing:border-box;width:100%;padding:12px;border:1px solid #cbd5e1;border-radius:7px}button,.btn{display:block;text-align:center;width:100%;margin-top:16px;background:#173e68;color:white;padding:12px;border:0;border-radius:7px;text-decoration:none;cursor:pointer}.err{color:#991b1b;background:#fff0f0;padding:10px;border-radius:7px}</style></head><body><div class="box"><h1>SIGEM</h1><h2>Portal del Docente</h2><p>Ingresá con la cuenta asignada por administración.</p><?php if($error): ?><p class="err"><?=h($error)?></p><?php endif; ?><form method="post"><input type="hidden" name="accion" value="login_docente"><label>Usuario</label><input name="usuario" required autocomplete="username"><label>Contraseña</label><input type="password" name="password" required autocomplete="current-password"><button>Ingresar</button></form><a class="btn" href="index.php">Volver al portal principal</a></div></body></html>
<?php exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'guardar_notas') {
    try {
        $idMesa = (int)($_POST['id_mesa'] ?? 0);
        $st = $conn->prepare('SELECT id_mesa FROM mesa WHERE id_mesa=? AND id_docente=?');
        $st->execute([$idMesa, (int)$_SESSION['id_docente']]);
        if (!$st->fetchColumn()) throw new RuntimeException('Esa mesa no está asignada a tu usuario docente.');
        $conn->beginTransaction();
        $stIns = $conn->prepare('SELECT id_inscripcion FROM inscripcion WHERE id_mesa=? AND id_alumno=?');
        $stExiste = $conn->prepare('SELECT id_nota FROM nota WHERE id_inscripcion=?');
        $stUpd = $conn->prepare('UPDATE nota SET calificacion=?, fecha=CURDATE() WHERE id_inscripcion=?');
        $stAdd = $conn->prepare('INSERT INTO nota (calificacion, fecha, id_inscripcion) VALUES (?, CURDATE(), ?)');
        foreach (($_POST['notas'] ?? []) as $idAlumno => $valor) {
            $valor = trim((string)$valor);
            if ($valor === '') continue;
            if (!is_numeric($valor) || (float)$valor < 1 || (float)$valor > 10) throw new RuntimeException('Las notas deben ser números entre 1 y 10.');
            $stIns->execute([$idMesa, (int)$idAlumno]);
            $idInscripcion = $stIns->fetchColumn();
            if (!$idInscripcion) continue;
            $stExiste->execute([$idInscripcion]);
            if ($stExiste->fetchColumn()) $stUpd->execute([(float)$valor, (int)$idInscripcion]);
            else $stAdd->execute([(float)$valor, (int)$idInscripcion]);
        }
        $conn->commit();
        $mensaje = 'Notas guardadas correctamente.';
    } catch (Throwable $e) {
        if ($conn->inTransaction()) $conn->rollBack();
        $error = $e instanceof RuntimeException ? $e->getMessage() : 'No se pudieron guardar las notas. Revisá la estructura de la tabla nota.';
        error_log('[SIGEM notas docente] '.$e->getMessage());
    }
}
$mesas = []; $idMesa = (int)($_GET['mesa'] ?? $_POST['id_mesa'] ?? 0); $alumnos = [];
$verIngresos = $idMesa > 0 && isset($_GET['ingresos']); $ingresos = []; $errorIngresos = '';
try {
    $st = $conn->prepare('SELECT me.id_mesa,me.fecha,me.horario,ma.nombre AS materia FROM mesa me LEFT JOIN materia ma ON ma.id_materia=me.id_materia WHERE me.id_docente=? ORDER BY me.fecha,me.horario');
    $st->execute([(int)$_SESSION['id_docente']]); $mesas = $st->fetchAll(PDO::FETCH_ASSOC);
    if ($idMesa > 0) {
        $st = $conn->prepare('SELECT al.id_alumno,al.nombre,al.apellido,al.dni,no.calificacion
          FROM mesa me JOIN inscripcion i ON i.id_mesa=me.id_mesa JOIN alumno al ON al.id_alumno=i.id_alumno
          LEFT JOIN nota no ON no.id_inscripcion=i.id_inscripcion
          WHERE me.id_mesa=? AND me.id_docente=? ORDER BY al.apellido,al.nombre');
        $st->execute([$idMesa,(int)$_SESSION['id_docente']]); $alumnos = $st->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) { error_log('[SIGEM lista docente] '.$e->getMessage()); $error = $error ?: 'No se pudieron consultar las mesas o las notas.'; }

// Registro de ingresos: cuántas veces inició sesión cada alumno inscripto en la mesa.
// Se vincula alumno -> cuenta (usuarios) por nombre completo, igual que en index.php.
// Solo se consulta para mesas asignadas al docente que tiene la sesión iniciada.
if ($verIngresos) {
    try {
        $st = $conn->prepare(
            'SELECT al.id_alumno, al.nombre, al.apellido, al.dni,
                    COUNT(r.id_registro) AS ingresos,
                    MAX(r.fecha_hora) AS ultimo,
                    GROUP_CONCAT(r.fecha_hora ORDER BY r.fecha_hora DESC SEPARATOR "|") AS fechas
             FROM (SELECT DISTINCT i.id_alumno
                   FROM inscripcion i INNER JOIN mesa me ON me.id_mesa = i.id_mesa
                   WHERE me.id_mesa = ? AND me.id_docente = ?) x
             INNER JOIN alumno al ON al.id_alumno = x.id_alumno
             LEFT JOIN usuarios u ON LOWER(TRIM(u.nombre)) = LOWER(TRIM(CONCAT(al.nombre, " ", al.apellido)))
             LEFT JOIN registro_ingresos_alumnos r ON r.id_usuario = u.id_usuario
             GROUP BY al.id_alumno, al.nombre, al.apellido, al.dni
             ORDER BY al.apellido, al.nombre'
        );
        $st->execute([$idMesa, (int)$_SESSION['id_docente']]);
        $ingresos = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        error_log('[SIGEM ingresos alumnos] '.$e->getMessage());
        $errorIngresos = 'No se pudo consultar el registro de ingresos. Verificá que exista la tabla registro_ingresos_alumnos.';
    }
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SIGEM | Panel docente</title><style>*{box-sizing:border-box}body{margin:0;background:#eef3f9;font-family:Arial;color:#1f2937}header{background:#173e68;color:white;padding:22px max(16px,calc((100% - 1000px)/2));display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}.wrap{max-width:1000px;margin:24px auto;padding:0 15px}.card{background:white;border-radius:12px;padding:22px;margin-bottom:18px;box-shadow:0 3px 14px #1322380b}select,input{padding:10px;border:1px solid #cbd5e1;border-radius:7px}button,.btn{display:inline-block;padding:11px 15px;background:#1c5d9d;color:white;border:0;border-radius:7px;text-decoration:none;cursor:pointer}.scroll{overflow-x:auto}table{border-collapse:collapse;width:100%;min-width:550px}th,td{padding:10px;text-align:left;border-bottom:1px solid #e5e7eb}th{background:#edf3f9}.err{color:#991b1b}.ok{color:#166534}.nota{width:100px}.muted{color:#64748b;font-size:14px}summary{cursor:pointer;color:#1c5d9d}details div{padding:2px 0;font-size:13px}</style></head><body>
<header><div><h1>SIGEM · Portal del Docente</h1><div>Hola, <?=h($_SESSION['nombre_docente'] ?? 'Docente')?></div></div><a class="btn" href="docentes.php?salir=1">Cerrar sesión</a></header><main class="wrap">
<?php if($mensaje): ?><section class="card"><p class="ok"><?=h($mensaje)?></p></section><?php endif; ?><?php if($error): ?><section class="card"><p class="err"><?=h($error)?></p></section><?php endif; ?>
<section class="card"><h2>Mis mesas de examen</h2><form method="get"><label for="mesa">Seleccioná una mesa asignada:</label> <select id="mesa" name="mesa" required><option value="">Elegir mesa...</option><?php foreach($mesas as $m): ?><option value="<?=h($m['id_mesa'])?>" <?=$idMesa===(int)$m['id_mesa']?'selected':''?>><?=h($m['materia'].' — '.$m['fecha'].' '.$m['horario'])?></option><?php endforeach; ?></select> <button>Consultar alumnos</button></form><?php if($idMesa>0): ?><p style="margin-top:14px"><a class="btn" target="_blank" rel="noopener" href="imprimir_acta_mesa.php?mesa=<?=h($idMesa)?>">Imprimir acta de esta mesa</a> <a class="btn" href="docentes.php?mesa=<?=h($idMesa)?>&amp;ingresos=1#ingresos">Ver registro de ingresos de alumnos</a></p><?php endif; ?><?php if(!$mesas): ?><p>No tenés mesas asignadas. Solicitá a preceptoría que te asigne como docente en una mesa.</p><?php endif; ?></section>
<?php if($idMesa>0): ?><section class="card"><h2>Registro de notas</h2><p>Ingresá una nota de 1 a 10 para cada alumno inscripto. Dejá en blanco las notas pendientes.</p><form method="post"><input type="hidden" name="accion" value="guardar_notas"><input type="hidden" name="id_mesa" value="<?=h($idMesa)?>"><div class="scroll"><table><thead><tr><th>DNI</th><th>Apellido y nombre</th><th>Nota (1 a 10)</th><th>Resultado</th></tr></thead><tbody><?php if(!$alumnos): ?><tr><td colspan="4">No hay alumnos inscriptos en esta mesa, o la mesa no pertenece a tu usuario.</td></tr><?php else: foreach($alumnos as $a): ?><tr><td><?=h($a['dni'])?></td><td><?=h($a['apellido'].', '.$a['nombre'])?></td><td><input class="nota" type="number" name="notas[<?=h($a['id_alumno'])?>]" min="1" max="10" step="0.01" value="<?=h($a['calificacion'] ?? '')?>"></td><td><?php if($a['calificacion']===null): ?>Pendiente<?php elseif((float)$a['calificacion']>=6): ?>Aprobado<?php else: ?>Desaprobado<?php endif; ?></td></tr><?php endforeach; endif; ?></tbody></table></div><p><button type="submit">Guardar notas</button></p></form></section><?php endif; ?>
<?php if($verIngresos): ?><section class="card" id="ingresos"><h2>Registro de ingresos de los alumnos</h2>
<p class="muted">Cantidad de veces que cada alumno inscripto en esta mesa inició sesión en el portal de alumnos, donde consulta sus mesas de examen. El registro no distingue qué mesa miró en cada ingreso.</p>
<?php if($errorIngresos): ?><p class="err"><?=h($errorIngresos)?></p><?php elseif(!$ingresos): ?><p>No hay alumnos inscriptos en esta mesa, o la mesa no pertenece a tu usuario.</p><?php else: ?>
<p><strong>Total de ingresos de los alumnos de esta mesa:</strong> <?=array_sum(array_map('intval', array_column($ingresos,'ingresos')))?></p>
<div class="scroll"><table><thead><tr><th>DNI</th><th>Apellido y nombre</th><th>Cantidad de ingresos</th><th>Último ingreso</th><th>Fechas</th></tr></thead><tbody>
<?php foreach($ingresos as $r): $n=(int)$r['ingresos']; $fechas=($r['fechas']!==null&&$r['fechas']!=='')?array_slice(explode('|',(string)$r['fechas']),0,20):[]; ?>
<tr><td><?=h($r['dni'])?></td><td><?=h($r['apellido'].', '.$r['nombre'])?></td><td><strong><?=$n?></strong></td><td><?=$r['ultimo']!==null?h(date('d/m/Y H:i',strtotime((string)$r['ultimo']))):'Nunca ingresó'?></td><td><?php if($fechas): ?><details><summary>Ver <?=count($fechas)?><?=$n>count($fechas)?' (de '.$n.')':''?></summary><?php foreach($fechas as $f): ?><div><?=h(date('d/m/Y H:i',strtotime($f)))?></div><?php endforeach; ?></details><?php else: ?>—<?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?>
<p><a href="docentes.php?mesa=<?=h($idMesa)?>">Ocultar registro</a></p></section><?php endif; ?>
<p><a href="index.php">Volver al portal principal</a></p></main></body></html>
