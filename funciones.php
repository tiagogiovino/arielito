<?php
declare(strict_types=1);

/*
 * SIGEM - funciones compartidas por el portal de preceptores.
 * Lo usan preceptores.php y preceptor_docentes.php.
 */

if (!function_exists('h')) {
    function h($v): string { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); }
}

function es_preceptor(): bool
{
    return ($_SESSION['tipo_usuario'] ?? '') === 'preceptor';
}

/* ---------- Protección CSRF para formularios que modifican datos ---------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf']) || !is_string($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . h(csrf_token()) . '">';
}

function csrf_valido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && is_string($_POST['csrf'])
        && is_string($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/* ---------- Borrado de alumnos y docentes ---------- */

/**
 * Elimina un alumno y todo lo que depende de él, en una sola transacción:
 * validaciones de ingreso, comprobantes, notas, inscripciones, el alumno,
 * y (si ningún otro alumno comparte nombre) su usuario de acceso y su
 * historial de ingresos.
 */
function borrar_alumno(PDO $conn, int $idAlumno): string
{
    $conn->beginTransaction();
    try {
        $st = $conn->prepare('SELECT nombre, apellido FROM alumno WHERE id_alumno = ? FOR UPDATE');
        $st->execute([$idAlumno]);
        $alumno = $st->fetch(PDO::FETCH_ASSOC);
        if (!$alumno) {
            throw new RuntimeException('El alumno ya no existe. Recargá la página.');
        }
        $nombreCompleto = trim($alumno['nombre'] . ' ' . $alumno['apellido']);

        $st = $conn->prepare('SELECT id_inscripcion FROM inscripcion WHERE id_alumno = ?');
        $st->execute([$idAlumno]);
        $inscripciones = $st->fetchAll(PDO::FETCH_COLUMN);

        $delValidaciones = $conn->prepare(
            'DELETE v FROM validacion_ingreso v
             INNER JOIN comprobante c ON c.id_comprobante = v.id_comprobante
             WHERE c.id_inscripcion = ?'
        );
        $delComprobante = $conn->prepare('DELETE FROM comprobante WHERE id_inscripcion = ?');
        $delNota = $conn->prepare('DELETE FROM nota WHERE id_inscripcion = ?');
        $delInscripcion = $conn->prepare('DELETE FROM inscripcion WHERE id_inscripcion = ?');
        foreach ($inscripciones as $idInscripcion) {
            $delValidaciones->execute([(int)$idInscripcion]);
            $delComprobante->execute([(int)$idInscripcion]);
            $delNota->execute([(int)$idInscripcion]);
            $delInscripcion->execute([(int)$idInscripcion]);
        }

        $conn->prepare('DELETE FROM alumno WHERE id_alumno = ?')->execute([$idAlumno]);

        // La cuenta de acceso se vincula por nombre completo (igual que en index.php).
        // Solo se borra si ya no queda otro alumno con ese mismo nombre.
        $st = $conn->prepare(
            'SELECT COUNT(*) FROM alumno WHERE LOWER(TRIM(CONCAT(nombre, " ", apellido))) = LOWER(?)'
        );
        $st->execute([$nombreCompleto]);
        if ((int)$st->fetchColumn() === 0) {
            $st = $conn->prepare('SELECT id_usuario FROM usuarios WHERE LOWER(TRIM(nombre)) = LOWER(?)');
            $st->execute([$nombreCompleto]);
            $delRegistro = $conn->prepare('DELETE FROM registro_ingresos_alumnos WHERE id_usuario = ?');
            $delUsuario = $conn->prepare('DELETE FROM usuarios WHERE id_usuario = ?');
            foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $idUsuario) {
                $delRegistro->execute([(int)$idUsuario]);
                $delUsuario->execute([(int)$idUsuario]);
            }
        }

        $conn->commit();
        return 'Alumno eliminado: ' . $nombreCompleto . '.';
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $e;
    }
}

/**
 * Elimina un docente y su cuenta de acceso. Las mesas que tenía asignadas
 * NO se borran: quedan "sin asignar" para poder asignarles otro docente.
 */
function borrar_docente(PDO $conn, int $idDocente): string
{
    $conn->beginTransaction();
    try {
        $st = $conn->prepare('SELECT nombre, apellido FROM docente WHERE id_docente = ? FOR UPDATE');
        $st->execute([$idDocente]);
        $docente = $st->fetch(PDO::FETCH_ASSOC);
        if (!$docente) {
            throw new RuntimeException('El docente ya no existe. Recargá la página.');
        }
        $nombreCompleto = trim($docente['nombre'] . ' ' . $docente['apellido']);

        $st = $conn->prepare('UPDATE mesa SET id_docente = NULL WHERE id_docente = ?');
        $st->execute([$idDocente]);
        $mesasLiberadas = $st->rowCount();

        $conn->prepare('DELETE FROM acceso_docente WHERE id_docente = ?')->execute([$idDocente]);
        $conn->prepare('DELETE FROM docente WHERE id_docente = ?')->execute([$idDocente]);

        $conn->commit();
        $texto = 'Docente eliminado: ' . $nombreCompleto . '.';
        if ($mesasLiberadas > 0) {
            $texto .= ' ' . $mesasLiberadas . ($mesasLiberadas === 1 ? ' mesa quedó' : ' mesas quedaron')
                . ' sin docente asignado.';
        }
        return $texto;
    } catch (Throwable $e) {
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        throw $e;
    }
}

/* ---------- Ventana de confirmación al hacer clic en un nombre ---------- */

function sigem_css_eliminar(): string
{
    return '.nombre-link{background:none;border:0;padding:0;color:#1c5d9d;text-decoration:underline;cursor:pointer;font:inherit;text-align:left}'
        . '.nombre-link:hover{color:#173e68}'
        . '#dlg-eliminar{border:0;border-radius:12px;padding:22px;width:min(440px,calc(100% - 30px));box-shadow:0 10px 40px #0006}'
        . '#dlg-eliminar::backdrop{background:#0008}'
        . '#dlg-eliminar form{display:block}#dlg-eliminar h3{margin-top:0}'
        . '.dlg-botones{display:flex;gap:10px;justify-content:flex-end;margin-top:18px}'
        . '.btn-gris{background:#64748b!important}.btn-peligro{background:#b91c1c!important}';
}

/**
 * Imprime la ventana de confirmación. Cada nombre clickeable debe ser un botón
 * con data-eliminar, data-accion, data-id, data-nombre y data-detalle.
 */
function sigem_dialogo_eliminar(): void
{
    ?>
<dialog id="dlg-eliminar">
<form method="post">
<?= csrf_campo() ?>
<input type="hidden" name="accion" value="">
<input type="hidden" name="id_objetivo" value="">
<h3 id="dlg-titulo"></h3>
<p id="dlg-detalle"></p>
<p><strong>Esta acción no se puede deshacer.</strong></p>
<div class="dlg-botones">
<button type="button" id="dlg-cancelar" class="btn-gris">Cancelar</button>
<button type="submit" class="btn-peligro">Eliminar definitivamente</button>
</div>
</form>
</dialog>
<script>
(function () {
  var dlg = document.getElementById('dlg-eliminar');
  if (!dlg) { return; }
  var f = dlg.querySelector('form');
  document.addEventListener('click', function (ev) {
    var b = ev.target.closest('[data-eliminar]');
    if (!b) { return; }
    f.elements['accion'].value = b.dataset.accion || '';
    f.elements['id_objetivo'].value = b.dataset.id || '';
    document.getElementById('dlg-titulo').textContent = '¿Eliminar a ' + (b.dataset.nombre || '') + '?';
    document.getElementById('dlg-detalle').textContent = b.dataset.detalle || '';
    if (typeof dlg.showModal === 'function') {
      dlg.showModal();
    } else if (confirm('¿Eliminar a ' + (b.dataset.nombre || '') + '? Esta acción no se puede deshacer.')) {
      f.submit();
    }
  });
  document.getElementById('dlg-cancelar').addEventListener('click', function () { dlg.close(); });
})();
</script>
    <?php
}
