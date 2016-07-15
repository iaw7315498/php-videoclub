<?php
	session_start();
	include("funciones.php");
	
	# miro si ha entrado mediantes las cookies, si lo ha echo, saco el identificador de usuario
	if (isset($_COOKIE['conectado'])) {
		$id_user = unserialize($_COOKIE['conectado'])[0];
	} else { # en caso contrario cogo los valores de la sesion
		$id_user = $_SESSION['login']['id'];
	}
	
	# vemos si ha pulsado el boton retornar
	if (isset($_GET['retornar'])) {
		# guardamos el condigo del dvd que nos envió de la otra página
		$coddvd = $_GET['retornar'];
		retornarDVD($coddvd, $id_user); # llamamos a la funcion retornarDVD
	}

	# vemos si ha pulsado el boton alquilar
	if (isset($_GET['alquilar'])) {
		$codPeli = $_GET['alquilar'];
		alquilar($id_user, $codPeli);
	}

	# vemos si ha pulsado el boton reservar
	if (isset($_GET['reservar'])) {
		$codPeli = $_GET['reservar'];
		reservar($id_user, $codPeli);
	}

	# buscamos si ha pulsado el boton cancelar
	if (isset($_GET['cancelar'])) {
		$codPeli = $_GET['cancelar'];
		cancelarReserva($id_user, $codPeli);
	}

	# regresamos a la página del usuario
	header("location: sesion.php");

	# cerramos la conexión 
	pg_close($dbconn);
?>