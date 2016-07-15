<?php 
	# iniciamos la sesion anteriormente guardada
	session_start();

	include("funciones.php");
	# primero de todo, si intenta entrar sin logearse o si no hay cookie lo envio a portada.php
	if (!isset($_COOKIE['conectado']) && !isset($_SESSION['login'])) { header("location: portada.php"); } else {
	# si ha venido redirigido por la cookie, buscamos los valores de la coockie
	if (isset($_COOKIE['conectado'])) {
		$id_user = unserialize($_COOKIE['conectado'])[0];
		$user = unserialize($_COOKIE['conectado'])[1];
		$nombre = unserialize($_COOKIE['conectado'])[2];
		$apellido = unserialize($_COOKIE['conectado'])[3];
		$ultima_conexion = unserialize($_COOKIE['conectado'])[4];
	} else { # en caso contrario cogo los valores de la sesion
		$id_user = $_SESSION['login']['id'];
		$user = $_SESSION['login']['user'];
		$nombre = $_SESSION['login']['nombre'];
		$apellido = $_SESSION['login']['apellido'];
		$ultima_conexion = $_SESSION['login']['data_conexion'];
	}

	# hago una conexion a la base de datos de videoclub
	$dbconn = pg_connect("user=videoclub dbname=videoclub password=videoclub") or die("no se ha podido conectar");
	$numero_total_peliculas_string = "select count(*) from pelicula;";
	$numero_total_peliculas_return = pg_query($dbconn, $numero_total_peliculas_string);
	$numero_total_peliculas = pg_fetch_row($numero_total_peliculas_return);

	# tengo que mostrar las peliculas que llevan menos de 24 horas del aviso
	$alertar_reserva = alertarReserva($id_user);
	$mensaje_pelicula_disponible = "";
	if ($alertar_reserva) {
		$mensaje_pelicula_disponible = "tienes peliculas disponible<br>";
	}
	

?>

<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>inicio</title>
	<link rel="stylesheet" type="text/css" href="sesion.css">
</head>
<body>
	<header id="cabecera">
		<?php echo "hola ".ucfirst(strtolower($nombre))." ".ucfirst(strtolower($apellido))." te has conectado el dia ".$ultima_conexion; ?>
		<a id="cerrar" href="cerrar.php">Cerrar sesión</a>
	</header>
	<nav id="menu">
			<ul>
				<li>Inicio</li>
				<li>Categorias</li>
				<li>Recomendadas</li>
				<li>Calidades</li>
				<li>Mas vistas</li>
				<li>Trailers</li>
				<li>Reservas</li>
				<li>Mis peliculas</li>
			</ul>
		</nav>
	<div id="contenedor">
		<section id="seccion">
			<div id="selector_pagina">
				<?php
					if (alertarReserva($id_user)) {
						echo "<body onload=\"alert('Ya tienes peliculas reservadas disponible')\" />";
						actualizarAlertaReserva($id_user);
					}

					## no sé como hacer la paginación ...
					$variable_aux_cantidad_paginas = 0;
					echo "<div class='pagina_pelicula'><img src='Caratules/fletxa_esquerra_roja.gif' alt='izquierda' width='21px' height='11px' /></div>";
					for ($i = 0; $i < $numero_total_peliculas[0]; $i++) {
						if ($i % 3 == 0 && $i != 0) {
							$variable_aux_cantidad_paginas++;
							echo "<div class='pagina_pelicula'>".$variable_aux_cantidad_paginas."</div>";
						}
					}
					echo "<div class='pagina_pelicula'><img src='Caratules/fletxa_dreta_cyan.gif' alt='derecha' width='21px' height='11px' /></div>";
				?>
			</div>
			<div id="mostrar_pelicula">
				<?php 
					$select_datos_pelicula_string = "select codpeli, titol, dataestrena, genere, foto, sinopsi, preu from pelicula inner join genere on(pelicula.codgen=genere.codgen) order by genere;";
					$select_datos_pelicula_result = pg_query($select_datos_pelicula_string);
					$select_datos_pelicula = pg_fetch_array($select_datos_pelicula_result, null, PGSQL_ASSOC);

					while ($select_datos_pelicula = pg_fetch_array($select_datos_pelicula_result, null, PGSQL_ASSOC)) {
						$buscar_coddvd_string = "select lloguer.coddvd from lloguer inner join dvd on (lloguer.coddvd=dvd.coddvd) where codpeli=".$select_datos_pelicula['codpeli']." and datadev is null;";
						$buscar_coddvd_result = pg_query($dbconn, $buscar_coddvd_string);
						$buscar_coddvd = pg_fetch_row($buscar_coddvd_result);

			/*print_r($buscar_coddvd);*/
						
						echo "<div class='contenido_pelicula'>";
						# veo si hay un nombre para la foto de la pelicula, si lo hay pongo la foto
						if ($select_datos_pelicula['foto']) {
							echo "<div class='imagenes'><img src='Caratules/".$select_datos_pelicula['foto']."' width='300px' height='400px'/></div>";
						} else { # en caso contrario, pongo la imagen de que no hay foto
							echo "<div class='imagenes'><img src='Caratules/no_image_found.gif' width='300px' height='400px'/></div>";
						}
						echo "<div class='parrafo'><span style='background: rgb(160,226,252); font-family: paul; font-size: 21px;'>".strtoupper($select_datos_pelicula['titol'])."</span> <br>Fecha de estreno: ".$select_datos_pelicula['dataestrena']."<br>Género: ".$select_datos_pelicula['genere']."</div>";
						$variable_boton_reservar = llogarDvd($id_user, $select_datos_pelicula['codpeli']);

						# tiene un recargo?, entonces añado un comentario al apartado del precio
						if (retornaRecargo($id_user)) {
							echo "<div class='sinopsi'>Sinopsi:<br>".$select_datos_pelicula['sinopsi']."<br><br><strong style='font-size:20px;'>Precio: ".$select_datos_pelicula['preu']."€ + ".retornaRecargo($id_user)."€ (recargo)</strong></div>";
						} else {
							echo "<div class='sinopsi'>Sinopsi:<br>".$select_datos_pelicula['sinopsi']."<br><br><strong style='font-size:20px;'>Precio: ".$select_datos_pelicula['preu']."€</strong></div>";
						}
						
						# si tiene un dvd de la pelicula pongo el boton para devolver la pelicula
						if (!$variable_boton_reservar[1]) {
							echo "<div class='mensajeYboton' style='color: red;'>Ya la tienes ";
							echo "<input type='button' value='devolver' onclick="."window.location.href='botones.php?retornar=".$buscar_coddvd[0]."'>";
							echo "</div>";
						} else if (!$variable_boton_reservar[2]) { #en el caso que tenga tres pelicula, se lo ponemos
							if (puedeReservar($id_user, $select_datos_pelicula['codpeli'])) {
								echo "<div class='mensajeYboton'><span style='color: red;'>Reservada</span>";
								echo "<input type='button' value='Cancelar Reserva' onclick="."window.location.href='botones.php?cancelar=".$select_datos_pelicula['codpeli']."'>";
								echo "</div>";
							} else {
								echo "<div class='mensajeYboton'>";
								echo "<span style='color: red;'>tienes 3 pelicula</span>";
								echo "</div>";
							}
						} else { # en caso contrario, veo si esta disponible un dvd de la pelicula
							if (reservaDisponible($id_user, $select_datos_pelicula['codpeli'])[0]) {
								echo "<div class='mensajeYboton'><span style='color: green;'>tu reserva, disponible</span>";
									echo "<input type='button' value='alquilar' onclick=window.location.href='botones.php?alquilar=".$select_datos_pelicula['codpeli']."'>";
									//echo "<a onclick=\"return(confirm('vas a reservar ".strtolower($select_datos_pelicula['titol'])."'))\"/><input type='submit' value='reservar' onclick='window.location.href='botones.php?reservar=".$buscar_coddvd[0]."'></a><br>";
									echo "</div>";
							} else if ($variable_boton_reservar[0] && !reservaDisponible($id_user, $select_datos_pelicula['codpeli'])[1]) { # si está disponible un dvd le ponemos el boton para alquilar
								echo "<div class='mensajeYboton'><span style='color: green;'>disponible</span>";
								echo "<input type='button' value='alquilar' onclick="."window.location.href='botones.php?alquilar=".$select_datos_pelicula['codpeli']."'>";
								echo "</div>";
							} else { #si no está disponible, cambiamos el boton a reservar
								if (!puedeReservar($id_user, $select_datos_pelicula['codpeli'])) {
									echo "<div class='mensajeYboton'><span style='color: red;'>no disponible</span>";
									echo "<input type='button' value='reservar' onclick=window.location.href='botones.php?reservar=".$select_datos_pelicula['codpeli']."'>";
									//echo "<a onclick=\"return(confirm('vas a reservar ".strtolower($select_datos_pelicula['titol'])."'))\"/><input type='submit' value='reservar' onclick='window.location.href='botones.php?reservar=".$buscar_coddvd[0]."'></a><br>";
									echo "</div>";
								} else {
									echo "<div class='mensajeYboton'><span style='color: red;'>Reservada</span>";
									echo "<input type='button' value='Cancelar Reserva' onclick="."window.location.href='botones.php?cancelar=".$select_datos_pelicula['codpeli']."'>";
									echo "</div>";
								}
							}//.reservar($id_user, $select_datos_pelicula['codpeli']).
						}
						echo "</div>";
					}					
				?>
			</div>
		</section>

		<aside id="columna">
			<?php
				# si tiene peliculas alquiladas se las muestro
				if (peliculasAlquiladas($id_user)) {
					$array_alquiladas = peliculasAlquiladas($id_user);
					$multi_alquiladas = (count($array_alquiladas) == 1) ? "tienes alquilada la pelicula" : "tienes alquiladas las peliculas:";
					echo "<p><strong>".$multi_alquiladas."</strong><p><br>";
					
					for ($i = 0; $i < count($array_alquiladas); $i++) { 
						echo "<blockquote class=\"pelis\">· ".strtolower($array_alquiladas[$i])."</blockquote>";
					}
				} else {
					echo "<blockquote class='pelis'><strong>no tienes peliculas alquiladas</strong></blockquote>";
				}
				echo "<br><br>";

				# si tiene peliculas reservadas se las muestro
				if (peliculasReservadas($id_user)) {
					$array_reservadas = peliculasReservadas($id_user);
					$multi_reservadas = (count($array_reservadas) == 1) ? "tienes reservada la pelicula:" : "tienes reservadas las peliculas:";

					echo "<p><strong>".$multi_reservadas."</strong></p>";
					for ($i = 0; $i < count($array_reservadas); $i++) {
						echo "<blockquote class='pelis'>· ".strtolower($array_reservadas[$i])."</blockquote>";
					}
				} else {
					echo "<blockquote class='pelis'><strong>no tienes peliculas reservadas</strong><blockquote>";
				}
			?>
		</aside>
	</div>
	<footer id="pie">
		<span>derecho reservados de <cite>paul</cite></span>
	</footer>
</body>
</html>
<?php } ?>