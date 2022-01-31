
      

            <?php
            // sites Barrio Country     Barrio del tanque Medanos   Don Ramiro              Torre nueva Quiroga     Lomitas             Nodo Bordeu                 Nodo Gon La Reserva
            //       Nodo Maiten        Nodo Mascotas               Nodo Medanos Omar       Nuevo Chañares Nuevo    Torre Algarrobo     Torre Cisnet Algarrobo      Torre Médanos Cisnet
            // mensajes 
            $mensaje = array();
            $mensaje["LAN"] = "Su antena esta conectada, pero no se detecta el router, por favor, verifique que desde la entrada'LAN' de la fuente POE este conectado en la WAN del router";
            $mensaje["dispositivo"] = "Su dispositivo se encuentra desconectado, verifique que la fuente POE este encendida y que el cable de la antena se encuentre en el conector `poe` ";
            $mensaje["sites_generico"] = "Hubo problemas en el sitio donde esta conectado, en breve los tecnicos estaran trabajando para solucionarlo, disculpe las molestias";
            $mensaje["sites_particular"] = "El sitio donde esta conctada su antena se encuentr con problemas de energia, en breve los tecnicos estaran trabajando para solucionarlo, disculpe las molestias";



            $id = $_GET["ID"];
            class UApiAccess
            {
                const API_CRM_URL = 'https://ubuntu2.velostar.com.ar/crm/api/v1.0';
                const APP_CRM_KEY = 'X-Auth-App-Key: FhUCNJMOv88ld9uJ5jNZcCG5zWXbXJ6J6o72IUiy833XWuTclQ8WG1zyI+iEkG5G';
                const API_NMS_URL = 'https://ubuntu2.velostar.com.ar/nms/api/v2.1';
                const APP_NMS_KEY = 'x-auth-token: 6d684aea-1362-41d0-af4f-d9c0ebb34553';
                /**
                 * @param string $url
                 * @param string $method
                 * @param array  $post
                 *
                 * @return array|null
                 */

                public static function doRequest($ubnt, $url, $method = 'GET', $post = [])
                {
                    $method = strtoupper($method);

                    $ch = curl_init();
                    if ($ubnt == "crm") {
                        $API_URL = self::API_CRM_URL;
                        $APP_KEY = self::APP_CRM_KEY;
                    } else {
                        $API_URL = self::API_NMS_URL;
                        $APP_KEY = self::APP_NMS_KEY;
                    }
                    curl_setopt(
                        $ch,
                        CURLOPT_URL,
                        sprintf('%s/%s', $API_URL, $url)
                    );
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_HEADER, false);

                    curl_setopt(
                        $ch,
                        CURLOPT_HTTPHEADER,
                        [
                            'Content-Type: application/json',
                            sprintf('%s', $APP_KEY),
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





            $response = UApiAccess::doRequest(
                "crm",

                sprintf('clients/services?clientId=%d', $id),

                'GET',

                []

            );

            $siteId = $response[0]["unmsClientSiteId"];
            $response2 = UApiAccess::doRequest(
                "nms",

                sprintf('sites/%s', $siteId),

                'GET',

                []

            );

            $response3 = UApiAccess::doRequest(
                "nms",

                sprintf('devices'), //sprintf('devices/9448d3ea-1ee9-45a9-9467-40eb22678c13/detail'),

                'GET',

                []

            );
            $i = 0;
            $lugar = 0;
            foreach ($response3 as $site) {
                if (array_key_exists(0, $site["identification"])) {
                    if ($site["identification"]["site"]["id"] == $siteId)
                        $lugar = $i;
                }
                $i++;
            }
            $idDevice = $response3[$lugar]["identification"]["id"];

            $response4 = UApiAccess::doRequest(
                "nms",

                sprintf('devices/%s/detail', $idDevice),

                'GET',

                []

            );
            //print_r($response4);
            // estado del nodo al que esta conectado el cliente
            if ($response2["identification"]["parent"]["status"] != "active") {
                $nodo = $response2["identification"]["parent"]["name"];

                header('Content-Type: application/json');

                $datos = array(
                    'estado' => "nodo desconectado",
                    'mensaje' => $mensaje["sites_generico"]
                );
                //Devolvemos el array pasado a JSON como objeto
                echo json_encode($datos);
            } else {
                // estado del dispositivo del cliente
                if ($response2["identification"]["status"] != "active") {
                    header('Content-Type: application/json');

                    $datos = array(
                        'estado' => "dispositivo desconectado",
                        'mensaje' => $mensaje["dispositivo"]
                    );
                    //Devolvemos el array pasado a JSON como objeto
                    echo json_encode($datos);
                } else 
                    if ($response4["interfaces"][0]["status"]["speed"] != "100-full") {
                    header('Content-Type: application/json');

                    $datos = array(
                        'estado' => "LAN desconectada",
                        'mensaje' => $mensaje["LAN"]
                    );
                    //Devolvemos el array pasado a JSON como objeto
                    echo json_encode($datos);
                } else {
                    header('Content-Type: application/json');

                    $datos = array(
                        'estado' => "normal",
                        'mensaje' => ""
                    );
                    //Devolvemos el array pasado a JSON como objeto
                    echo json_encode($datos);
                }
            }




            ?>
