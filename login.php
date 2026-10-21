
<?php
session_start();
require_once 'config.php';

$mensaje = '';
$bienvenido = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    $sql = "SELECT id_usuario, nombre, usuario, password
            FROM usuarios
            WHERE usuario = :usuario
            LIMIT 1";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['usuario' => $usuario]);
    $datos = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($datos && password_verify($password, $datos['password'])) {

        session_regenerate_id(true);

        $_SESSION['id_usuario'] = $datos['id_usuario'];
        $_SESSION['nombre'] = $datos['nombre'];
        $_SESSION['usuario'] = $datos['usuario'];

        $mensaje = '¡Bienvenido a SIGEM, ' .
                   htmlspecialchars($datos['nombre'], ENT_QUOTES, 'UTF-8') .
                   '!';

        $bienvenido = true;

    } else {
        $mensaje = 'Usuario o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login SIGEM</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #eaf0f8;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }

        .contenedor {
            background: white;
            padding: 30px;
            border-radius: 12px;
            width: 320px;
            box-shadow: 0 4px 15px #0002;
            text-align: center;
        }

        h1 {
            color: #173e68;
        }

        label {
            display: block;
            text-align: left;
            margin-top: 15px;
        }

        input {
            width: 100%;
            box-sizing: border-box;
            padding: 10px;
            margin-top: 6px;
        }

        button {
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            background: #173e68;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .mensaje {
            margin-top: 20px;
            font-weight: bold;
        }
    </style>
</head>

<body>
<div class="contenedor">

    <h1>SIGEM</h1>
    <p>Sistema Integral de Gestión de Mesas de Examen</p>

    <?php if ($mensaje !== ''): ?>
        <p class="mensaje">
            <?= $mensaje ?>
        </p>
    <?php endif; ?>

    <?php if (!$bienvenido): ?>
        <form action="" method="POST">

            <label for="usuario">Usuario</label>
            <input
                type="text"
                id="usuario"
                name="usuario"
                required
            >

            <label for="password">Contraseña</label>
            <input
                type="password"
                id="password"
                name="password"
                required
            >

            <button type="submit">Iniciar sesión</button>
        </form>
    <?php else: ?>
        <p>Has iniciado sesión correctamente.</p>
        <a href="login.php">Volver</a>
    <?php endif; ?>

</div>
</body>
</html>
