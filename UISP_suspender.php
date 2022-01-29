<html>

<head>

    <title> Prueba Interconeccion UISP </title>

</head>
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
?>

<body>

    <h1> Corte de Servicio a Clientes segun ID</h1>

    <?php
    $hoy = getdate();
    $file = fopen("archivo.txt", "a");

    $dia = $hoy['mday'];
    $mes = $hoy['mon'];
    $anio = $hoy['year'];
    $hora = $hoy['hours'];
    $minuto = $hoy['minutes'];
    $segundo = $hoy['seconds'];

    fwrite($file, "SUSPENDER SERVICIO !! $dia-$mes-$anio  $hora:$minuto:$segundo  "  . PHP_EOL);
    //fwrite($file, "  se ha recivido =  $recibido"  . PHP_EOL);
    fwrite($file, "" . PHP_EOL);
    fclose($file);


    $server = "server1.velostar.com.ar";
    $user = "velostar_mx";
    $password = "vsh98q256";
    $DB = "UISP_registro";
    $table = "Cientes_Suspendidos";

    $conexion = mysqli_connect($server, $user, $password, $DB);

    if (!$conexion) {
        echo "<br>NO se pudo conectar con el servidor:" . mysqli_connect_error() . "\n, pero igual continuara con el corte de servicio<br>";
    } else echo "<br>coneccion exitosa<br>";

    $sql = " INSERT INTO $table (ID,date,ID_UISP,IP,device,city,id_coneccion,servicePlan,accion,nombre,apellido) VALUES ";

    if (isset($_POST['array_ID']) && !empty($_POST['array_ID'])) {

        $recibido = $_POST['array_ID']; // Resto de código
    } else if (isset($_GET['array_ID']) && !empty($_GET['array_ID']))
        $recibido = $_GET['array_ID']; // Resto de código
    else
        $recibido = 0;



    $clients_info = UcrmApiAccess::doRequest(sprintf('clients') ?: []);

    $datos = array();
    $actividad = array();
    $actividad[0]["public"] = true;
    $actividad[0]["comment"] = array();
    $subject = "";
    $status=0;//new

    if ($recibido != 0) {
        $array = explode(",", $recibido);
        foreach ($array as $id_suspender) {

            $UISP_ID = (int) $id_suspender;

            foreach ($clients_info as $datos_cliente) {
                if ($datos_cliente["id"] == $id_suspender)
                    $datos = $datos_cliente;
            }
            $city = $datos['city'];
            $nombre = $datos['firstName'];
            $apellido = $datos['lastName'];

            $clients = UcrmApiAccess::doRequest(sprintf('clients/services?clientId=%d', $id_suspender) ?: []);
            $i = 0;
            /*
            echo "<br><br>";
            print_r($clients);
            echo "<br><br>";
            */
            $mult_conn = "";
            foreach ($clients as $conecciones) {
                $id_coneccion = $clients[$i]["id"];

                echo  "<br>  Coneccion: " . $id_coneccion . "  ";
                $mult_conn = "$mult_conn / $id_coneccion";
                $i++;
            }


            $fecha_ant = date("y-m-d 00:00:00", strtotime("-7 day"));
            $sql_consulta = " SELECT ID,date,ID_UISP,IP,device,city,id_coneccion,servicePlan,accion,nombre,apellido FROM $table WHERE date BETWEEN '$fecha_ant' AND '$anio-$mes-$dia 23:59:59' AND ID_UISP= '$id_suspender' AND accion != 'DEVOLVER' ";


            if ($resultado = mysqli_query($conexion, $sql_consulta))
                echo "<br>campos buscados correctamente";
            else {
                die("<br>error al buscados los datos");
            }

            if ($resultado->num_rows == 0) {
                // esto es si esta desconectado el dispositivo 
                /*if ($datos["hasOutage"] == "1") {

                    $sql = $sql . "(null, null,$id_suspender,'','SI','$city','$id_coneccion', '','YA_FINALIZADO','$nombre','$apellido')";
                    if (mysqli_query($conexion, $sql))
                        echo "<br>campos cargados correctamente";
                    else echo " <br>error al cargar los datos";

                    $actividad[0]["comment"]["body"] = "El Cliente ya se encontraba con el contrato Finalizado" . $datos['city'];
                    $subject = "Ya se encontraba Finalizado";

                    echo "<br> ya tenia el contrato finalizado <br>";
                } else {*/
                    if ($datos["hasSuspendedService"] == "1") { // si ya estaba suspendido anteriormente
                        echo "<br> ya tenia el servicio suspendido <br>";


                        // si el cliente estaba suspendido debo ver cual es la fecha en el que se lo suspendio el la BD para terminar el contrato 
                        $clients = UcrmApiAccess::doRequest(
                            sprintf('clients/services/%d/end', $id_coneccion), //  sprintf('clients/services/%d/end', $id_coneccion),
                            'PATCH',
                            [
                                'servicePlanPeriodId' => 1,
                            ]
                        );
                        if ($datos["hasServiceWithoutDevices"] == "") { // si tiene antena

                            $sql = $sql . "(null, null,$id_suspender,'','SI','$city','$id_coneccion', '','FIN_CONTRATO','$nombre','$apellido')";
                            if (mysqli_query($conexion, $sql))
                                echo "<br>campos cargados correctamente";
                            else echo " <br>error al cargar los datos";

                            $actividad[0]["comment"]["body"] = "Finalizacion de contrato automatico por falta de pago, retirar equipo, es de " . $datos['city'];
                            $subject = "Contrato finalizado por falta de Pago";
                            $status=0;//new
                            echo $datos['lastName'] . " " . $datos['firstName'] . " ,cliente con ID=$id_suspender, Ya se encontraba cortado del mes Anterior y se finalizo el contrato!!";
                        } else { // si NO tiene antena
                            $sql = $sql . "(null, null,$id_suspender,'','NO','$city','$id_coneccion', '','FIN_CONTRATO','$nombre','$apellido')";
                            if (mysqli_query($conexion, $sql))
                                echo "<br>campos cargados correctamente";
                            else echo " <br>error al cargar los datos";

                            $actividad[0]["comment"]["body"] = "Finalizacion de contrato automatico por falta de pago, retirar equipo aunque no tiene uno asociado en el sistema, es de " . $datos['city'];
                            $subject = "Contrato finalizado por falta de Pago";
                            $status=0;//new

                            echo $datos['lastName'] . " " . $datos['firstName'] . " ,cliente con ID=$id_suspender, Ya se encontraba cortado del mes Anterior pero NO TIENE EQUIPO ASOCIADO !!";
                        }
                    } else { // si NO  estaba suspendido anteriormente
                        echo "<br> NO tenia servicio suspendido";

                        if ($datos["hasServiceWithoutDevices"] == "") { // si tiene antena
                            echo "<br>  tiene antena <br> ";
                            if ($i > 1) {
                                $sql = $sql . "(null, null,$id_suspender,'','MULTIPLES','$city','$mult_conn', '','NINGUNA','$nombre','$apellido')";
                                if (mysqli_query($conexion, $sql))
                                    echo "<br>campos cargados correctamente";
                                else echo " <br>error al cargar los datos";

                                echo  $datos['lastName'] . " " . $datos['firstName'] . " ,cliente con ID=$id_suspender posee mas de una coneccion , no sabemos cual cortar...";
                            } else {
                                UcrmApiAccess::doRequest(
                                    sprintf('clients/services/%d/suspend', $id_coneccion),
                                    'PATCH',
                                    [
                                        'suspensionReasonId' => 1,
                                    ]

                                );

                                echo $datos['lastName'] . " " . $datos['firstName'] . " ,cliente con ID=$id_suspender, corte de servicio fue realizado con exito !!";
                            }
                            $sql = $sql . "(null, null,$id_suspender,'','SI','$city','$id_coneccion', '','SUSPENDER','$nombre','$apellido')";
                            if (mysqli_query($conexion, $sql))
                                echo "<br>campos cargados correctamente";
                            else echo " <br>error al cargar los datos";

                            $actividad[0]["comment"]["body"] = "Supension de servicio automatico por falta de pago, es de " . $datos['city'];
                            $subject = "2 Servicio SUSPENDIDO por falta de Pago";
                            $status=3;//solved
                        } else { // si NO tiene antena
                            $clients = UcrmApiAccess::doRequest(
                                sprintf('clients/services/%d/suspend', $id_coneccion),
                                'PATCH',
                                [
                                    'suspensionReasonId' => 1,
                                ]
                            );

                            $sql = $sql . "(null, null,$id_suspender,'','NO','$city','$id_coneccion', '','NINGUNA','$nombre','$apellido')";
                            if (mysqli_query($conexion, $sql))
                                echo "<br>campos cargados correctamente";
                            else echo " <br>error al cargar los datos";

                            echo $datos['lastName'] . " " . $datos['firstName'] . " ,cliente con ID=$id_suspender, NO TIENE EQUIPO ASOCIADO !!";
                            $actividad[0]["comment"]["body"] = "Supension de servicio automatico por falta de pago, es de " . $datos['city'];
                            $subject = "3 Servicio SUSPENDIDO por falta de Pago (no tiene antena)";
                            $status=0;//new
                        }
                    }
                //}
                // si sufrio modificaciones dentro de los 15 dias no genera ticket 
                $response = UcrmApiAccess::doRequest(
                    sprintf('ticketing/tickets'),
                    'POST',
                    [
                        'subject' => $subject, //Contrato finalizado por
                        'clientId' => $UISP_ID,
                        'activity' => $actividad,
                        'status' => $status,
                    ]
                );
                echo "<br> ticket creado correctamente";
            } else {
                echo "no se ejecuto ninguna accion porque ya habia sido modificado dentro de los 15 dias anteriores";
            }
        }
    }
    mysqli_close($conexion);
    ?>

</body>

</html>