<?php
$dbconn = pg_connect("user=videoclub dbname=videoclub password=videoclub") or die("no se ha podido conectar"); // conectamos a la base de datos llamado videoclub
//include 'portada.php';
// funcion para hacer una consulta y saber si la pelicula está alquilada

/*
@codPeli: será el codigo de pelicula,
@codSoci: será el código de socio,

false = 0
true != 0
@return array(hayDVD?, noTieneCopia?, tieneMenosDeTresDVD?);
*/

function llogarDVD($codSoci, $codPeli) { // la funcion recive un numero que será la pelicula para ver si está disponible
	/* variables para retornar un array donde si todos son true, si puede alquilar una pelicula */
	$tiene_pelicula_igual = false;
	$tiene_tres_pelicula = false;

	global $dbconn;
	$buscar_disponible = "select coddvd from dvd where not exists(select coddvd from lloguer where dvd.coddvd=lloguer.coddvd and lloguer.datadev is null) and codpeli=".$codPeli.";";
	$buscar_doble = "select lloguer.* from lloguer inner join dvd on (lloguer.coddvd=dvd.coddvd) where dvd.codpeli=".$codPeli." and codsoci=".$codSoci." and lloguer.datadev is null;";
	$buscar_maximo = "select count(lloguer.*) from lloguer inner join dvd on (lloguer.coddvd=dvd.coddvd) where codsoci=".$codSoci." and datadev is null;";

	$result_disponible = pg_query($dbconn, $buscar_disponible);
	$result_doble = pg_query($dbconn, $buscar_doble);
	$result_maximo = pg_query($dbconn, $buscar_maximo);

	// retorna  
	$row_disponible = pg_fetch_row($result_disponible); // pg_fetch_array() es un array asociativo
	$row_doble = pg_fetch_row($result_doble);
	$row_maximo = pg_fetch_row($result_maximo);
	
	
	if (!$row_doble[0]) {
		$tiene_pelicula_igual = true;
	}
	if ($row_maximo[0] < 3) {
		$tiene_tres_pelicula = true;
	}
	
	$array = array($row_disponible[0], $tiene_pelicula_igual, $tiene_tres_pelicula);

	return $array;
}

function alquilar($codSoci, $codPeli) {
	global $dbconn;
	$array = llogarDvd($id_user, $codPeli);

	# si hay algun recargo lo paso a una variable
	if (retornaRecargo($codSoci)) {
		$precioPeli = retornaRecargo($codSoci);
	} else { # en caso contrario cogo el precio de la pelicula
		$buscar_precio_pelicula_string = "select preu from pelicula inner join dvd on (pelicula.codpeli=dvd.codpeli) where pelicula.codpeli=".$codPeli.";";
		$buscar_precio_pelicula_result = pg_query($dbconn, $buscar_precio_pelicula_string);
		$buscar_precio = pg_fetch_row($buscar_precio_pelicula_result);
		$precioPeli = $buscar_precio[0];
	}
	
	# como saldrá el boton para alquilar solo si cumple las tres condiciones de llogarDVD no me hace falta poner un condicional
	$alquilar_dvd_string = "insert into lloguer values(".$codSoci.",".llogarDVD($codSoci, $codPeli)[0].",now(),null,".$precioPeli.");";
	$alquilar_dvd_result = pg_query($dbconn, $alquilar_dvd_string);

	pagarRecargo($codSoci); # si ha alquilado borro los datos de recargo
	cancelarReserva($codSoci, $codPeli); # borro de la tabla llistaespera
}

function retornaRecargo($codSoci) {
	global $dbconn;
	$buscar_recargo_string = "select sum(recarrec) from recarrec where codsoci=".$codSoci.";";
	$buscar_recargo_result = pg_query($dbconn, $buscar_recargo_string);
	$buscar_recargo = pg_fetch_row($buscar_recargo_result);

	return $buscar_recargo[0];
}

function pagarRecargo($codSoci) {
	global $dbconn;
	$pagar_recargo_string = "delete from recarrec where codsoci=".$codSoci.";";
	$pagar_recargo_result = pg_query($dbconn, $pagar_recargo_string);
}

function recargo($dias_retraso, $codDvd, $codSoci, $precioPeli) {
	global $dbconn;
	
	# si no existe el cliente en la tabla recargo y se ha pasado de 24 horas, añadimos un nuevo recargo
	if ($dias_retraso > 1) {
		$importe_total = $dias_retraso + $precioPeli;

		$agregar_recargo_string = "insert into recarrec values(".$codSoci.",".$codDvd.",now(),".$importe_total.");";
		$agregar_recargo_result = pg_query($dbconn, $agregar_recargo_string);
	}
}










function retornarDVD($codDvd, $codSoci) {
	global $dbconn;

	# primero cojo la diferencia de dias 
	$calcular_fecha_string = "select extract(days from now() - datapres) from lloguer where coddvd=".$codDvd." and codsoci=".$codSoci." and datadev is null;";
	$calcular_fecha_result = pg_query($dbconn, $calcular_fecha_string);
	$diferencia_dias = pg_fetch_row($calcular_fecha_result);

	# actualizo la fecha de entrega
	$sql_update_devolver_string = "update lloguer set datadev=current_timestamp where coddvd=".$codDvd." and codsoci=".$codSoci." and datadev is null;";
	$sql_update_devolver_result = pg_query($dbconn,$sql_update_devolver_string);

	# busco el precio de la pelicula para pasarle a la funcion recargo y allí mirar si hay recargo
	$buscar_precio_pelicula_string = "select preu from pelicula inner join dvd on (pelicula.codpeli=dvd.codpeli) where coddvd=".$codDvd.";";
	$buscar_precio_pelicula_result = pg_query($dbconn, $buscar_precio_pelicula_string);
	$buscar_precio = pg_fetch_row($buscar_precio_pelicula_result);

	# llamo a la función recargo pasandole los datos que necesito para añadir un recargo o no.
	recargo($diferencia_dias[0], $codDvd, $codSoci, $buscar_precio[0]);
	
	# miro si hay reservas y le asigno el dvd al primero que haya reservado
	$buscar_reserva_echa_string = "select llistaespera.codsoci from llistaespera inner join dvd on (dvd.codpeli=llistaespera.codpeli) where dvd.coddvd=".$codDvd." order by data_res;";
	$buscar_reserva_echa_result = pg_query($dbconn, $buscar_reserva_echa_string);
	$buscar_reserva = pg_fetch_row($buscar_reserva_echa_result);

	# si hay reserva, le asigno el dvd a ese socio
	if ($buscar_reserva[0]) {
		actualizarReserva($buscar_reserva[0], $codDvd);
	}
}

function actualizarReserva($codSoci, $codDvd) {
	global $dbconn;
	# saco el codigo de la pelicula
	$codigo_pelicula_string = "select dvd.codpeli from dvd inner join llistaespera on (dvd.codpeli=llistaespera.codpeli) where dvd.coddvd=".$codDvd.";";
	$codigo_pelicula_result = pg_query($dbconn, $codigo_pelicula_string);
	$codigo_pelicula = pg_fetch_row($codigo_pelicula_result);

	# actualizo los datos de la tabla dvd y la columna reservat
	$actializar_reserva_dvd_string = "update dvd set reservat='S' where coddvd=".$codDvd.";";
	$actializar_reserva_dvd_result = pg_query($dbconn, $actializar_reserva_dvd_string);

	# actualizo los datos de la tabla llistaespera añadiendole el codigo del dvd
	$actualizar_reserva_espera_string = "update llistaespera set coddvd=".$codDvd." where codsoci=".$codSoci." and codpeli=".$codigo_pelicula[0].";";
	$actualizar_reserva_espera_result = pg_query($dbconn, $actualizar_reserva_espera_string);
}

function actualizarAlertaReserva($codSoci) {
	global $dbconn;
	
	# primero saco el codigo de pelicula que tiene asignado el usuario
	$buscar_codigo_pelicula_string = "select llistaespera.codpeli from dvd inner join llistaespera on(dvd.codpeli=llistaespera.codpeli) where codsoci=".$codSoci." and reservat='S';";
	$buscar_codigo_pelicula_result = pg_query($dbconn, $buscar_codigo_pelicula_string);
	$buscar_codigo_pelicula = pg_fetch_row($buscar_codigo_pelicula_result);
	
	$actualizar_alerta_reserva_string = "update llistaespera set data_avis=now() where codpeli=".$buscar_codigo_pelicula[0]." and codsoci=".$codSoci.";";
	$actualizar_alerta_reserva_result = pg_query($actualizar_alerta_reserva_string);
}

function puedeReservar($codSoci,$codPeli) {
	global $dbconn;
	# primero veo que ese cliente no ha reservado una pelicula
	$buscar_reserva_anterior_string = "select * from llistaespera where codsoci=".$codSoci." and codpeli=".$codPeli.";";
	$buscar_reserva_anterior_result = pg_query($dbconn, $buscar_reserva_anterior_string);
	$buscar_reserva = pg_fetch_row($buscar_reserva_anterior_result);

	return $buscar_reserva[0];
}

function reservar($codSoci,$codPeli) {
	global $dbconn;

	if (!puedeReservar($codSoci, $codPeli)) {
		# añado la reserva a la tabla llistaespera
		$insertar_listaEspera_string = "insert into llistaespera values(".$codSoci.",".$codPeli.",now());";
		$insertar_listaEspera_result = pg_query($dbconn, $insertar_listaEspera_string);
	}
}


function cancelarReserva($codSoci, $codPeli) {
	global $dbconn;
	# si está si ya tiene el dvd reservado, se lo quito
	$cancelar_reserva_dvd_string = "select dvd.coddvd from dvd  inner join llistaespera on dvd.coddvd=llistaespera.coddvd where dvd.codpeli=".$codPeli." and reservat='S' and llistaespera.codsoci=".$codSoci.";";
	$cancelar_reserva_dvd_result = pg_query($dbconn, $cancelar_reserva_dvd_string);
	$cancelar_reserva = pg_fetch_row($cancelar_reserva_dvd_result);

	if ($cancelar_reserva[0]) {
		# borro de la lista de espera
		$cancelar_reserva_string = "delete from llistaespera where codsoci=".$codSoci." and codpeli=".$codPeli.";";
		$cancelar_reserva_result = pg_query($dbconn, $cancelar_reserva_string);

		# borro de la tabla dvd
		$cancelar_reserva_dvd_string = "update dvd set reservat='N' where coddvd=".$cancelar_reserva[0].";";
		$cacenlar_reserva_dvd_result = pg_query($cancelar_reserva_dvd_string);
	} else {
		# borro de la lista de espera
		$cancelar_reserva_string = "delete from llistaespera where codsoci=".$codSoci." and codpeli=".$codPeli.";";
		$cancelar_reserva_result = pg_query($dbconn, $cancelar_reserva_string);
	}
}

function peliculasAlquiladas($codsoci) {
	global $dbconn;
	$peliculas_alquiladas_string = "select titol from pelicula, dvd, lloguer where pelicula.codpeli = dvd.codpeli and dvd.coddvd = lloguer.coddvd and datadev is null and codsoci=".$codsoci.";";
	$peliculas_alquiladas_result = pg_query($dbconn, $peliculas_alquiladas_string);

	$peliculasArray = array();
	$i = 0;
	while ($row = pg_fetch_assoc($peliculas_alquiladas_result)) {
		$peliculasArray[$i] = $row['titol'];
		$i++;
	}
	return $peliculasArray;
}

function peliculasReservadas($codSoci) {
	global $dbconn;
	$peliculas_reservadas_string = "select titol from llistaespera inner join pelicula on(pelicula.codpeli=llistaespera.codpeli) where llistaespera.codsoci=".$codSoci.";";
	$peliculas_reservadas_result = pg_query($dbconn, $peliculas_reservadas_string);

	$peliculas_reservadas = array();
	$i = 0;
	while ($row = pg_fetch_assoc($peliculas_reservadas_result)) {
		$peliculas_reservadas[$i] = $row['titol'];
		$i++;
	}
	return $peliculas_reservadas;
}

function alertarReserva($codSoci) {
	global $dbconn;
	$alertar_reserva_string = "select llistaespera.* from llistaespera inner join dvd on (llistaespera.coddvd = dvd.coddvd) where llistaespera.codsoci=".$codSoci." and dvd.reservat='S' and data_avis is null;";
	$alertar_reserva_result = pg_query($dbconn, $alertar_reserva_string);
	
	$peliculas_alertadas = array();
	//$peliculas_alertadas[0] = $alertar_reserva_array['titol'];
	$i = 0;
	while ($row = pg_fetch_assoc($alertar_reserva_result)) { # si hay algun valor, creamos un array asociativo con todo el resultado de la consulta
		$peliculas_alertadas[$i] = $row['coddvd'];
		$i++;
	}
	return $peliculas_alertadas;
}

function reservaDisponible($codSoci, $codPeli) {
	global $dbconn;
	$buscar_reserva_echa_disponible_String = "select * from llistaespera where codsoci=".$codSoci." and codpeli=".$codPeli." and coddvd is not null;";
	$buscar_reserva_echa_disponible_result = pg_query($dbconn, $buscar_reserva_echa_disponible_String);
	$buscar_reserva = pg_fetch_row($buscar_reserva_echa_disponible_result);

	$buscar_reserva_echa_disponible_String2 = "select * from llistaespera where codsoci<>".$codSoci." and codpeli=".$codPeli." and coddvd is not null;";
	$buscar_reserva_echa_disponible_result2 = pg_query($dbconn, $buscar_reserva_echa_disponible_String2);
	$buscar_reserva2 = pg_fetch_row($buscar_reserva_echa_disponible_result2);

	$array = array();
	$array[0] = $buscar_reserva[0];
	$array[1] = $buscar_reserva2[0];
	return $array;
}

?>