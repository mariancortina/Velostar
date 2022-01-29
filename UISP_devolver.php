<html>

<head>

    <title> Prueba Interconeccion UISP </title>

</head>

<head>
</head>

<body>

    <h1> Devolucion de Servicio a Clientes segun ID</h1>


    <?php
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



    if ($recibido != 0) {
        $array = explode(",", $recibido);
        foreach ($array as $id_devolver) {

            $UISP_ID = (int) $id_devolver;
            foreach ($clients_info as $datos_cliente) {
                if ($datos_cliente["id"] == $id_devolver)
                    $datos = $datos_cliente;
            }

            $city = $datos['city'];
            $nombre=$datos['firstName'];
            $apellido=$datos['lastName'];
            if ($datos["hasSuspendedService"] == 1) {
                echo "<br>Cliente suspendido";

                $clients = UcrmApiAccess::doRequest(sprintf('clients/services?clientId=%d', $id_devolver) ?: []);
                $i = 0;
                if ($datos["hasServiceWithoutDevices"] == "") {
                    $actividad = array();
                    $actividad[0]["public"] = true;
                    $actividad[0]["comment"] = array();


                    foreach ($clients as $conecciones) {
                        $id_coneccion = $clients[$i]["id"];
                        echo "<br>";
                        echo  $id_coneccion;
                        UcrmApiAccess::doRequest(
                            sprintf('clients/services/%d/cancel-suspend', $id_coneccion),
                            'PATCH',
                            [
                                'suspensionReasonId' => 1,
                            ]

                        );
                        $sql = $sql . "(null, null,$id_devolver,'','SI','$city','$id_coneccion', '','DEVOLVER','$nombre','$apellido')";
                        $actividad[0]["comment"]["body"] = "Coneccion devuelta , es de " . $datos['city'];
                        echo $datos['lastName'] . " " . $datos['firstName'] . " ,cliente con ID=$id_devolver, le fue debuelto el servicio sin problema !!";
                        if ($i > 0) {

                            echo "<br> MULTIPLES SERVICIOS";
                            echo $datos['lastName'] . " " . $datos['firstName'] . " ,cliente con ID=$id_devolver, le fue debuelto el servicio sin problema !!";
                        }

                        $i++;
                    }
                } else {
                    $sql = $sql . "(null, null,$id_devolver,'','NO','$city','', '','DEVOLVER','$nombre','$apellido')";
                    echo "<br>servicio devuelto , sin equipo asociado ";
                }
                /*  $actividad[0]["comment"]["body"] = "Coneccion devuelta, NO tiene equipo asociado, es de " . $datos['city'];
                    $response = UcrmApiAccess::doRequest(
                    sprintf('ticketing/tickets'),
                    'POST',
                    [
                        'subject' => "Servicio devuelto ",
                        'clientId' => $UISP_ID,
                        'activity' => $actividad,
                    ]
                );
                echo "<br> ticket creado correctamente";*/
            } else {
                    echo "<br>Cliente NO suspendido";
                    $sql = $sql . "(null, null,$id_devolver,'','SI','$city','$id_coneccion', '','NO ESTABA SUSPENDIDO','$nombre','$apellido')";
                
            }
        }
    }

    echo "<br><br> $sql<br><br> ";

    if (mysqli_query($conexion, $sql))
        echo "<br>campos cargados correctamente";
    else echo "<br> error al cargar los datos";

    mysqli_close($conexion);
    ?>
    </div>





    <script type="text/javascript">
        document.getElementById("formulario").addEventListener('submit', function(e) {
            console.log('hohoho');
            e.preventDefault();
            addTable();
        });
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
    </script>

    </div>

</body>

</html>