<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config.php';
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }

if (($_SESSION['tipo_usuario'] ?? '') !== 'alumno') {
    http_response_code(403);
    exit('Debés iniciar sesión como alumno para ver este documento.');
}
$idMesa = (int)($_GET['mesa'] ?? 0);
$nombreCompleto = trim((string)($_SESSION['nombre_usuario'] ?? ''));
try {
    $stAlumno = $conn->prepare('SELECT id_alumno, nombre, apellido, dni, curso FROM alumno WHERE CONCAT(nombre, " ", apellido) = ? LIMIT 1');
    $stAlumno->execute([$nombreCompleto]);
    $alumno = $stAlumno->fetch(PDO::FETCH_ASSOC);
    if (!$alumno) { http_response_code(404); exit('No se encontró el registro del alumno. Consultá con preceptoría.'); }

    $st = $conn->prepare('SELECT me.id_mesa, me.fecha, me.horario, ma.nombre AS materia,
        CONCAT(d.apellido, ", ", d.nombre) AS docente, a.numero AS aula,
        i.fecha_inscripcion, i.estado, n.calificacion
        FROM inscripcion i JOIN mesa me ON me.id_mesa=i.id_mesa
        LEFT JOIN materia ma ON ma.id_materia=me.id_materia
        LEFT JOIN docente d ON d.id_docente=me.id_docente
        LEFT JOIN aula a ON a.id_aula=me.id_aula
        LEFT JOIN nota n ON n.id_inscripcion=i.id_inscripcion
        WHERE i.id_alumno=? AND me.id_mesa=? LIMIT 1');
    $st->execute([(int)$alumno['id_alumno'], $idMesa]);
    $acta = $st->fetch(PDO::FETCH_ASSOC);
    if (!$acta) { http_response_code(404); exit('No estás inscripto en esa mesa.'); }
} catch (Throwable $e) {
    error_log('[SIGEM impresión alumno] '.$e->getMessage());
    http_response_code(500); exit('No se pudo generar el documento. Revisá la base de datos.');
}
$resultado = $acta['calificacion'] === null ? 'Pendiente' : ((float)$acta['calificacion'] >= 6 ? 'Aprobado' : 'Desaprobado');
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Acta individual de examen</title><style>
*{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#111;margin:0;background:#eef3f9}.paper{width:min(800px,calc(100% - 30px));margin:28px auto;background:white;padding:42px;border:1px solid #d7dee8}.top{text-align:center;border-bottom:2px solid #173e68;padding-bottom:18px}.top h1{margin:0 0 8px;color:#173e68}.details{margin:26px 0;line-height:1.9}.row{border-bottom:1px solid #d9e0e8;padding:9px 0}.result{font-weight:bold;font-size:18px}.actions{text-align:center;margin:20px}.btn{border:0;border-radius:6px;background:#173e68;color:white;padding:11px 18px;cursor:pointer;text-decoration:none;margin:0 5px}.signatures{display:flex;justify-content:space-between;gap:35px;margin-top:70px}.signatures div{border-top:1px solid #111;width:45%;text-align:center;padding-top:8px;font-size:13px}
@media print{body{background:white}.paper{width:100%;margin:0;padding:15mm;border:0}.no-print{display:none!important}@page{size:A4;margin:12mm}}
</style></head><body>
<main class="paper"><div class="top"><h1>SIGEM</h1><h2>Comprobante individual de inscripción a mesa de examen</h2></div>
<div class="details">
<div class="row"><strong>Alumno/a:</strong> <?=h($alumno['apellido'].', '.$alumno['nombre'])?></div>
<div class="row"><strong>DNI:</strong> <?=h($alumno['dni'])?></div>
<div class="row"><strong>Curso:</strong> <?=h($alumno['curso'] ?? '—')?></div>
<div class="row"><strong>Materia:</strong> <?=h($acta['materia'] ?? '—')?></div>
<div class="row"><strong>Fecha:</strong> <?=h($acta['fecha'])?></div>
<div class="row"><strong>Horario:</strong> <?=h($acta['horario'])?></div>
<div class="row"><strong>Aula:</strong> <?=h($acta['aula'] ?? 'A confirmar')?></div>
<div class="row"><strong>Docente:</strong> <?=h($acta['docente'] ?? 'A confirmar')?></div>
<div class="row"><strong>Fecha de inscripción:</strong> <?=h($acta['fecha_inscripcion'] ?? '—')?></div>
<div class="row"><strong>Estado:</strong> <?=h($acta['estado'] ?? 'Inscripto')?></div>
<div class="row"><strong>Nota:</strong> <?=h($acta['calificacion'] ?? 'Pendiente')?></div>
<div class="row"><strong>Resultado:</strong> <span class="result"><?=h($resultado)?></span></div>
</div><p>Este comprobante corresponde únicamente a la inscripción del alumno y a la mesa indicada.</p>
<div class="signatures"><div>Firma de preceptoría</div><div>Firma del alumno/a</div></div>
</main><div class="actions no-print"><button class="btn" onclick="window.print()">Imprimir solo este documento</button><button class="btn" onclick="window.close()">Cerrar</button></div>
<script>window.addEventListener('load',()=>{ /* La impresión se inicia manualmente para evitar diálogos inesperados. */ });</script>
</body></html>
