<?php
declare(strict_types=1);
session_start();
require __DIR__ . '/config.php';
function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
if (($_SESSION['tipo_usuario'] ?? '') !== 'docente') { http_response_code(403); exit('Debés iniciar sesión como docente.'); }
$idMesa = (int)($_GET['mesa'] ?? 0);
try {
    $st = $conn->prepare('SELECT me.id_mesa, me.fecha, me.horario, ma.nombre AS materia,
        CONCAT(d.apellido, ", ", d.nombre) AS docente, a.numero AS aula
        FROM mesa me LEFT JOIN materia ma ON ma.id_materia=me.id_materia
        LEFT JOIN docente d ON d.id_docente=me.id_docente LEFT JOIN aula a ON a.id_aula=me.id_aula
        WHERE me.id_mesa=? AND me.id_docente=? LIMIT 1');
    $st->execute([$idMesa, (int)$_SESSION['id_docente']]);
    $mesa = $st->fetch(PDO::FETCH_ASSOC);
    if (!$mesa) { http_response_code(404); exit('La mesa no existe o no está asignada a tu usuario.'); }
    $st = $conn->prepare('SELECT al.dni, al.apellido, al.nombre, n.calificacion
        FROM inscripcion i JOIN alumno al ON al.id_alumno=i.id_alumno
        LEFT JOIN nota n ON n.id_inscripcion=i.id_inscripcion
        WHERE i.id_mesa=? ORDER BY al.apellido,al.nombre');
    $st->execute([$idMesa]); $alumnos = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    error_log('[SIGEM impresión mesa] '.$e->getMessage());
    http_response_code(500); exit('No se pudo generar el acta de la mesa.');
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Acta de examen - Mesa <?=h($mesa['id_mesa'])?></title><style>
*{box-sizing:border-box}body{font-family:Arial,sans-serif;color:#111;margin:0;background:#eef3f9}.paper{width:min(1000px,calc(100% - 30px));margin:28px auto;background:white;padding:38px;border:1px solid #d7dee8}.top{text-align:center;border-bottom:2px solid #173e68;padding-bottom:16px}.top h1{margin:0 0 8px;color:#173e68}.details{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:22px 0}.details div{padding:8px;border-bottom:1px solid #d9e0e8}table{width:100%;border-collapse:collapse}th,td{padding:9px;border:1px solid #9ca3af;text-align:left}th{background:#eaf0f6}.actions{text-align:center;margin:20px}.btn{border:0;border-radius:6px;background:#173e68;color:white;padding:11px 18px;cursor:pointer;text-decoration:none;margin:0 5px}
@media print{body{background:white}.paper{width:100%;margin:0;padding:8mm;border:0}.no-print{display:none!important}th{background:#eee!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}@page{size:A4 landscape;margin:10mm}}
</style></head><body><main class="paper">
<div class="top"><h1>SIGEM</h1><h2>Acta de examen</h2></div>
<div class="details"><div><strong>N.º de mesa:</strong> <?=h($mesa['id_mesa'])?></div><div><strong>Materia / mesa:</strong> <?=h($mesa['materia'] ?? 'Sin materia')?></div><div><strong>Fecha:</strong> <?=h($mesa['fecha'])?></div><div><strong>Horario:</strong> <?=h($mesa['horario'])?></div><div><strong>Aula:</strong> <?=h($mesa['aula'] ?? 'A confirmar')?></div><div><strong>Docente:</strong> <?=h($mesa['docente'] ?? 'Sin asignar')?></div></div>
<h3>Alumnos inscriptos en esta mesa (<?=count($alumnos)?>)</h3>
<table><thead><tr><th>N.º</th><th>DNI</th><th>Apellido y nombre</th><th>Nota</th><th>Firma / observaciones</th></tr></thead><tbody>
<?php if(!$alumnos): ?><tr><td colspan="5">No hay alumnos inscriptos en esta mesa.</td></tr><?php else: foreach($alumnos as $i=>$a): ?><tr><td><?=($i+1)?></td><td><?=h($a['dni'])?></td><td><?=h($a['apellido'].', '.$a['nombre'])?></td><td><?=h($a['calificacion'] ?? '')?></td><td style="height:32px"></td></tr><?php endforeach; endif; ?>
</tbody></table><p style="margin-top:28px">Observaciones: __________________________________________________________________________________</p>
<p style="margin-top:45px;text-align:right">Firma del docente: ____________________________________</p>
</main><div class="actions no-print"><button class="btn" onclick="window.print()">Imprimir solo el acta de esta mesa</button><button class="btn" onclick="window.close()">Cerrar</button></div></body></html>
