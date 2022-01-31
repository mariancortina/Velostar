<html>

<head>
</head>

<body>

    <h1> Ejecucion Periodica</h1>


    <?php

    class UcrmApiAccess
    {
        const API_URL = 'https://ubuntu2.velostar.com.ar/crm/api/v1.0';
        const APP_KEY = 'FhUCNJMOv88ld9uJ5jNZcCG5zWXbXJ6J6o72IUiy833XWuTclQ8WG1zyI+iEkG5G';

        /**
         * @param string $url
         * @param string $method
         * @param array  $post
         *
         * @return array|null
         */

        public static function doRequest($url, $method = 'GET', $post = [])
        {
            $method = strtoupper($method);

            $ch = curl_init();

            curl_setopt(
                $ch,
                CURLOPT_URL,
                sprintf('%s/%s', self::API_URL, $url)
            );
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HEADER, false);

            curl_setopt(
                $ch,
                CURLOPT_HTTPHEADER,
                [
                    'Content-Type: application/json',
                    sprintf('X-Auth-App-Key: %s', self::APP_KEY),
                ]
            );

            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, true);
            } elseif ($method !== 'GET') {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            }

            if (!empty($post)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
            }

            $response = curl_exec($ch);

            if (curl_errno($ch) !== 0) {
                echo sprintf('Curl error: %s', curl_error($ch)) . PHP_EOL;
            }

            if (curl_getinfo($ch, CURLINFO_HTTP_CODE) >= 400) {
                echo sprintf('API error: %s', $response) . PHP_EOL;
                $response = false;
            }

            curl_close($ch);

            return $response !== false ? json_decode($response, true) : null;
        }
    }

    // Setting unlimited time limit (updating lots of clients can take a long time).
    set_time_limit(0);




    $server = "server1.velostar.com.ar";
    $user = "velostar_mx";
    $password = "vsh98q256";
    $DB = "UISP_registro";
    $table = "Cientes_Suspendidos";

    $conexion = mysqli_connect($server, $user, $password, $DB);

    if (!$conexion) {
        echo "<br>NO se pudo conectar con el servidor:" . mysqli_connect_error() . "<br>\n, pero igual continuara con el corte de servicio<br>";
    } else echo "<br>coneccion exitosa<br>";

    $hoy = getdate();

    $clients_info = UcrmApiAccess::doRequest(sprintf('clients') ?: []);

    $datos = array();

    $dia = $hoy['mday'];
    $mes = $hoy['mon'];
    $anio = $hoy['year'];
    $hora = $hoy['hours'];
    $minuto = $hoy['minutes'];
    $segundo = $hoy['seconds'];

    $sql = " SELECT ID,date,ID_UISP,IP,device,city,id_coneccion,servicePlan,accion,nombre,apellido FROM $table WHERE date BETWEEN '$anio-$mes-$dia 00:00:00' AND '$anio-$mes-$dia 23:59:59' ";

    if ($resultado = mysqli_query($conexion, $sql))
        echo "<br>campos buscados correctamente";
    else {
        echo "<br>error al buscados los datos";
        die;
    }

    if ($resultado->num_rows === 0) {
        echo "<br>NO HUBO INTERACCIONES EL DIA DE HOY";
    } else {
        $to = "admin@velostar.com.ar";
        $subject = "Resumen diario de Actividades";
        $message = "<html><head><title>Reporte de Actividades</title></head><body><br><br>";
        $i=0;
        foreach ($resultado as $busqueda) {

            $ID_cliente = $busqueda["ID_UISP"];
            $nombre_cliente = $busqueda["nombre"];
            $apellido_cliente = $busqueda["apellido"];
            $city_cliente = $busqueda["city"];
            $i++;

            if ($busqueda["accion"] == "NINGUNA")
                if ($busqueda["device"] == "MULTIPLES")
                    $message = $message . "<label> $i VER -- </label> NO SE REALIZO NINUNA ACCION CON EL CLIENTE  $ID_cliente ($nombre_cliente  $apellido_cliente) por tener multiples conexiones <br>";
                else
                    $message = $message . "<label> $i VER -- </label> NO SE REALIZO NINUNA ACCION CON EL CLIENTE  $ID_cliente ($nombre_cliente  $apellido_cliente) por NO tener equipo asociado <br>";

            if ($busqueda["accion"] == "SUSPENDER")
                $message = $message . "$i OK -- SE SUSPENDIO EL CLIENTE  $ID_cliente ($nombre_cliente  $apellido_cliente) <br>";

            if ($busqueda["accion"] == "FIN_CONTRATO")
                if ($busqueda["device"] == "NO")
                    $message = $message . "<label> $i VER -- </label> SE FINALIZÓ EL CONTRATO DEL CLIENTE  $ID_cliente ($nombre_cliente  $apellido_cliente) por estar suspendido hace MÁS de un MES, pero OJO !!! no tiene equipo <br>";
                else
                    $message = $message . "$i OK -- SE FINALIZÓ EL CONTRATO DEL CLIENTE  $ID_cliente ($nombre_cliente  $apellido_cliente) por estar suspendido hace MÁS de un MES <br>";

            if ($busqueda["accion"] == "NADA_-15dias")
                if ($busqueda["device"] == "NO")
                    $message = $message . "<label> $i VER -- </label> SE QUISO VOLVER A SUSPENDER EL CLIENTE  $ID_cliente ($nombre_cliente  $apellido_cliente) pero NO se finalizo el contrato por ser suspendido recientemente , pero OJO !!! NO tiene equipo <br>";
                else
                    $message = $message . "$i OK -- SE QUISO VOLVER A SUSPENDER EL CLIENTE  $ID_cliente ($nombre_cliente  $apellido_cliente) pero NO se finalizo el contrato por ser suspendido recientemente <br>";

            if ($busqueda["accion"] == "NO ESTABA SUSPENDIDO")
                $message = $message . "<label> $i VER -- </label> SE QUISO DEVOLVER EL CLIENTE  $ID_cliente ($nombre_cliente  $apellido_cliente) PERO NO ESTABA SUSPENDIDO <br>";

            if ($busqueda["accion"] == "DEVOLVER")
                if ($busqueda["device"] == "NO")
                    $message = $message . "<label> $i VER -- </label> SE QUISO DEVOLVER CLIENTE  $ID_cliente ($nombre_cliente  $apellido_cliente) pero NO tiene equipo <br>";
                else
                    $message = $message . "$i OK -- SE DEVOLVIO EL CLIENTE  $ID_cliente ($nombre_cliente  $apellido_cliente) <br>";
        }
        $message = $message . "</body></html>";
        
        $cabeceras  = 'MIME-Version: 1.0' . "\r\n";
        $cabeceras .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";
        
        mail($to, $subject, $message, $cabeceras);
    }

    mysqli_close($conexion);
    ?>
    </div>







    </div>

</body>

</html>