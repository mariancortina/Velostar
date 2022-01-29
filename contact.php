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


if ($_POST) {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $localidad = $_POST['localidad'];
    $direccion = $_POST['direccion'];
    $telefono = $_POST['telefono'];
    $documento = $_POST['documento'];
    $email = $_POST['email'];
    $abono = $_POST['abono'];

    $id_abonos = array();

    if ($localidad == "Rural_Medanos" || $localidad == "Rural_Algarrobo") {
        //rural
        $id_abonos["6Mbps_descarga_x_1Mbps_subida"] = 128;
        $id_abonos["10Mbps_descarga_x_2Mbps_subida"] = 134;
        $id_abonos["10Mbps_descarga_x_5Mbps_subida"] = 62;
    } else if ($localidad == "Quinta_Medanos" || $localidad == "Quinta_Algarrobo") {
        //quintas
        $id_abonos["6Mbps_descarga_x_1Mbps_subida"] = 7;
        $id_abonos["10Mbps_descarga_x_2Mbps_subida"] = 134;
        $id_abonos["10Mbps_descarga_x_5Mbps_subida"] = 62;
    } else { //ciudad
        $id_abonos["6Mbps_descarga_x_1Mbps_subida"] = 7;
        $id_abonos["10Mbps_descarga_x_2Mbps_subida"] = 13;
        $id_abonos["15Mbps_descarga_x_2Mbps_subida"] = 20;
        $id_abonos["20Mbps_descarga_x_5Mbps_subida"] = 86;
    }


    if ($nombre != NULL && $apellido != NULL && $localidad != NULL && $direccion != NULL && $telefono != NULL) {
        $array = array();
        $array[0]["phone"] = $telefono;
        $array[0]["email"] = $email;
        $response = UcrmApiAccess::doRequest(
            sprintf('clients'),
            'POST',
            [
                'isLead' => true,
                'firstName' => $nombre,
                'lastName' => $apellido,
                'street1' => $direccion,
                'note' => $localidad . ' - generacion automatica por inbox',
                'city' => $localidad,
                'contacts' => $array,
            ]
        );
        $response2 = UcrmApiAccess::doRequest(
            sprintf('clients?phone=%d', $array[0]["phone"]),
            'GET',
            []
        );

        $response3 = UcrmApiAccess::doRequest(
            sprintf('clients/%d/services', $response2[0]["id"]),
            'POST',
            [
                'servicePlanPeriodId' => $id_abonos[$abono],
                'isQuoted' => true,
            ]
        );

        echo "<p>Gracias por elegirnos $nombre $apellido ha sido agregado a nuestra lista de espera exitosamente <!DOCTYPE html>.</p>";
    } else {
        echo '<p>Algo Salio mal, intente de nuevo mas tarde, o llame a la oficina para consultas</p>';
    }
} else {
    echo '<p>Something went wrong</p>';
}
