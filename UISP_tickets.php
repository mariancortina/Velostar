<?php



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

$UISP_ID = (int) $_GET["ID"];
$asunto = $_GET["asunto"];

$actividad = array();
$actividad[0]["public"] = false;
$actividad[0]["comment"] = array();


// buscar NODO ---------------- inicio
$response = UApiAccess::doRequest(
    "crm",

    sprintf('clients/services?clientId=%d', $UISP_ID),

    'GET',

    []

);

$siteId = $response[0]["unmsClientSiteId"];


$dispositivo = UApiAccess::doRequest(
    "nms",

    sprintf('devices?siteId=%s', $siteId), //sprintf('devices/9448d3ea-1ee9-45a9-9467-40eb22678c13/detail'),

    'GET',

    []

);
$nodo = $dispositivo[0]["identification"]["site"]["parent"]["name"];
// buscar NODO ---------------- fin


$array = new StdClass();


$consulta =  UApiAccess::doRequest(
    "crm",

    sprintf('ticketing/tickets?clientId=%d', $UISP_ID),

    'GET',

    []

);


if (empty($consulta))
    $consulta[0]["status"] = 2;

if ($consulta[0]["status"] == 0 || $consulta[0]["status"] == 1) {

    $actividad[0]["comment"]["body"] = "Nuevo reclamo generado por $asunto";

    $ticket_id = $consulta[0]["id"];

    $sobreescritura =  UApiAccess::doRequest(
        "crm",

        sprintf('ticketing/tickets/%d', $ticket_id),

        'PATCH',

        ['activity' => $actividad,]

    );
    header('Content-Type: application/json');

    $datos = array(
        'N_reclamo' => "sigue siendo $ticket_id", 'nodo' => $nodo,
    );
    //Devolvemos el array pasado a JSON como objeto
    echo json_encode($datos);
} else {

    if ($asunto == "baja") {
        $actividad[0]["comment"]["body"] = "Generacion Automatica desde inbox messegebird";
        $response = UApiAccess::doRequest(
            "crm",

            sprintf('ticketing/tickets'),

            'POST',

            [
                'subject' => "Baja de Servicio",
                'clientId' => $UISP_ID,
                'activity' => $actividad,
            ]

        );


        $response2 =  UApiAccess::doRequest(
            "crm",

            sprintf('ticketing/tickets?clientId=%d', $UISP_ID),

            'GET',

            []

        );

        header('Content-Type: application/json');

        $datos = array(
            'N_reclamo' => $response2[0]["id"], 'nodo' => $nodo,
        );
        //Devolvemos el array pasado a JSON como objeto
        echo json_encode($datos);
    } else 

if (substr($asunto, 0, 12) == "sin_servicio") {
        $mensajes = array();
        $mensajes[1] = "sin aparente problema de nuestra parte \nGeneracion Automatica desde inbox messegebird";
        $mensajes[2] = "tiene su equipo desconectado \nGeneracion Automatica desde inbox messegebird";
        $mensajes[3] = "el nodo esta desconectado \nGeneracion Automatica desde inbox messegebird";
        $mensajes[4] = "el cliente tiene la LAN desconectada \nGeneracion Automatica desde inbox messegebird";

        $actividad[0]["comment"]["body"] = $mensajes[substr($asunto, 13)];
        $response =  UApiAccess::doRequest(
            "crm",

            sprintf('ticketing/tickets'),

            'POST',

            [
                'subject' => "SIN Servicio",
                'clientId' => $UISP_ID,
                'activity' => $actividad,
            ]

        );

        $response2 =  UApiAccess::doRequest(
            "crm",

            sprintf('ticketing/tickets?clientId=%d', $UISP_ID),

            'GET',

            []

        );

        header('Content-Type: application/json');

        $datos = array(
            'N_reclamo' => $response2[0]["id"], 'nodo' => $nodo,
        );
        //Devolvemos el array pasado a JSON como objeto
        echo json_encode($datos);
    } else

        echo "no hay accion especificada, solo fonciona para baja por el momento";
}
