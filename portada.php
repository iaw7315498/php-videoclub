<?php 
	# creamos una sesion o iniciamos la sesion anterior
	session_start();

	# si hay coockie quiere decir que recuerda el usuario y reenviamos directamente a sesion.php
	if (isset($_COOKIE['conectado'])) {
		header("location: sesion.php");
	} else if (isset($_POST['boto'])) { # en caso de no estar la coockie y pulsa el boton enviar, hago la conexcion a la base de datos videoclub
		$pass = htmlentities(htmlspecialchars($_POST["pass"], ENT_QUOTES));
		$user = htmlentities(htmlspecialchars($_POST["user"], ENT_QUOTES));
		$dbconn = pg_connect("user=videoclub dbname=videoclub password=videoclub") or die("no se ha podido conectar"); // conectamos a la base de datos llamado videoclub
		$buscar = "select * from soci where login='".$user."' and password='".md5($pass)."'";
		$result = pg_query($dbconn, $buscar);
		
		# guardo en un array el resultado de la busqueda
		$row = pg_fetch_row($result);

		# si introduce mal el usuario (no hay nada en el array) le informaremos que el usuario o password es incorrecto
		if (!$row) {
			echo "usuario o password incorrecto<br>";
			echo "<a href='#' value='nada'>recordar contraseña</a>";
		} else { # en caso de poner bien los datos, reenviamos a la pagina sesion.php
			# pong la fecha en el formato que me interesa
			date_default_timezone_set("Europe/Madrid");

			# creamos un array dentro de la sesion y le ponemos los datos que nos interesarán
			$_SESSION['login'] = array();
			$_SESSION['login']['id'] = $row[0]; # para saber sobre que id estaremos trabajando
			$_SESSION['login']['user'] = $_POST['user']; # para conocer el usuario que se ha identifica
			$_SESSION['login']['nombre'] = $row[4]; #cogemos el nombre del usuario
			$_SESSION['login']['apellido'] = $row[5]; # cogemos el apellido del usuario
			$_SESSION['login']['data_conexion'] = date('d-m-Y  H:i:s', time());
			
			# si ha marcado para que no se desconecte, creo una coockie
			if ($_POST['mantener_sesion']) {
				# creo un array con los datos anteriormente puesto en el array de sesion para pasar a la cookie
				$array = array($row[0], $_POST['user'], $row[4], $row[5], date('d-m-Y  H:i:s', time()));
				$_SESSION['conectado'] = 1; 
				setcookie('conectado', serialize($array), time() + 3600, "/");
			}

			# reedirijo a sesion.php
			header("location: sesion.php");
		}

	} else { # en caso contrario, muestro la portada para que haga su conexion

?>
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<title>Iniciar sesión</title>
</head>
<body>
	<div id="imagen" style="position: fixed; height: 100%; width: 58%; background: url('pelicula_portada.jpg');">
    </div>
    <div id="sesion" style="position: fixed; left: 61%;">
    	<div style="position: fixed; height: 48%; width: 39%; background: url('videoclub.jpg') no-repeat scroll 50% 28% transparent;"></div>

    	<div style="position: fixed; top: 35%; left: 69%;">
    		<span>inicie sesión con su cuenta profesional</span><br><br><br>

    		<form id="formulario" method="post" action="<?php $_SERVER['PHP_SELF'] ?>">
    			<input type="text" name="user" placeholder="Usuario" autofocus><br>
				<input type="password" name="pass" placeholder="Contraseña"><br>
				<input type="checkbox" name="mantener_sesion" > Mantener la sesión iniciada<br><br>

				<input type="submit" name="boto" value="Iniciar sesión">
    		</form><br><br><br>
    		<a href="algo.html" value="registrar">No estás registrado?</a>
    	</div>
    </div>
</body>
</html>
<?php } ?>