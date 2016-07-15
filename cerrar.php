<?php
	session_start();
	$_SESSION = array();

	# preparo el mensaje
	$mensaje = "";

	# si existe la coockie conectado, borro la coockie
	if (isset($_COOKIE['conectado'])) {
		setcookie('conectado', "", time() -3600, "/");
		$mensaje = "coockie borrada";
	}

	# borro la sesion
	session_destroy();
	$mensaje = $mensaje."<br>Sesion cerrada con éxito";

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>inicio</title>
</head>
<body>
	<p><?php echo $mensaje ?></p>
	<a href="portada.php">Página principal</a>
</body>
</html>