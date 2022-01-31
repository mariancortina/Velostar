<style>
    body {
        background-image: url('fondo.jpg');
        background-repeat: no-repeat;
        background-attachment: fixed;
        background-size: cover;
    }

    div.elem-group {
        margin: 20px 0;
    }

    label {
        display: block;
        font-family: 'Aleo';
        color: white;
        padding-bottom: 4px;
        font-size: 1.25em;
    }

    .izquierda {
        color: black;
    }

    .titulo {
        font-family: 'Arial Black';
        color: black;
        padding-bottom: 3px;
        font-size: 2em;
    }



    input,
    select,
    textarea {
        border-radius: 2px;
        border: 1px solid #ccc;
        box-sizing: border-box;
        font-size: 1.25em;
        font-family: 'Aleo';
        width: 250px;
        padding: 6px;
    }

    textarea {
        height: 250px;
    }

    button {
        height: 100px;
        width: 270px;
        background: green;
        color: white;
        border: 5px solid darkgreen;
        font-size: 2em;
        font-family: 'Arial';
        border-radius: 10px;
        cursor: pointer;
    }


    button:hover {
        border: 5px solid black;
    }


    * {
        box-sizing: border-box;
    }

    /* Create three equal columns that floats next to each other */
    .column {
        float: left;
        width: 33.33%;
        padding: 10px;
        height: 90px;
        /* Should be removed. Only for demonstration */
    }

    /* Clear floats after the columns */
    .row:after {
        content: "";
        display: table;
        clear: both;
    }
</style>

<html>

<head>
    <title> Nuevo Cliente </title>
</head>

<body>
    <?php
    $abono_ciudad = array();
    $abono_quinta = array();
    $abono_rural = array();
    $ABONO = array();

    $abono_ciudad[0] = "6Mbps_descarga_x_1Mbps_subida $1560";
    $abono_ciudad[1] = "10Mbps_descarga_x_2Mbps_subida $1950";
    $abono_ciudad[2] = "15Mbps_descarga_x_2Mbps_subida $2250";
    $abono_ciudad[3] = "20Mbps_descarga_x_5Mbps_subida $2400";

    $abono_quinta[0] = "6Mbps_descarga_x_1Mbps_subida $2150";
    $abono_quinta[1] = "10Mbps_descarga_x_2Mbps_subida $2700";
    $abono_quinta[2] = "10Mbps_descarga_x_5Mbps_subida $3350";

    $abono_rural[0] = "6Mbps_descarga_x_1Mbps_subida $3750";
    $abono_rural[1] = "10Mbps_descarga_x_2Mbps_subida $5400";
    $abono_rural[2] = "10Mbps_descarga_x_5Mbps_subida $7000";


    $localidad = $_GET["loc"];
    $telefono = $_GET["tel"];

    if ($localidad == "Rural_Medanos" || $localidad == "Rural_Algarrobo") {
        $ABONO = $abono_rural;
    } else if ($localidad == "Quinta_Medanos" || $localidad == "Quinta_Algarrobo") {
        $ABONO = $abono_quinta;
    } else {
        $ABONO = $abono_ciudad;
    }
    ?>

    <label class=titulo> COMPLETE ESTE FORMULARIO PARA PODER AGENDARLO <br>EN NUESTRA LISTA DE ESPERA </label>
    <form action="contact.php" method="post">

        <div class="row">
            <div class="column">
                <div class="elem-group">
                    <label class=izquierda for="nombre">Nombre</label>
                    <input type="text" id="nombre" name="nombre" placeholder="su nombre" pattern=[A-Z\sa-z]{3,20} required>
                </div>
            </div>
            <div class="column">
                <div class="elem-group">
                    <label for="apellido">Apellido</label>
                    <input type="text" id="apellido" name="apellido" placeholder="su apellido" pattern=[A-Z\sa-z]{3,20} required>
                </div>
            </div>
            <div class="column">
                <div class="elem-group">
                    <label for="email">E-mail</label>
                    <input type="email" id="email" name="email" placeholder="suemail@gmail.com" required>
                </div>
            </div>

        </div>
        <div class="row">
            <div class="column">
                <div class="elem-group">
                    <label class=izquierda for="localidad">Localidad</label>
                    <input type="text" id="localidad" name="localidad" value="<?php echo $localidad ?>" placeholder="<?php echo $localidad ?>" readonly>
                </div>
            </div>
            <div class="column">
                <div class="elem-group">
                    <label for="title">Direccion</label>
                    <input type="text" id="direccion" name="direccion" required placeholder="direccion donde se instalara el servicio" pattern=[A-Za-z0-9\s]{8,60}>
                </div>
            </div>
            <div class="column">
                <div class="elem-group">
                    <label for="title">Abono</label>
                    <select id="abono" name="abono" required>
                    <?php $i=0;   foreach($ABONO as $abono) {?>
                        <option <?php if($i==0){echo "selected";}?> value=<?php echo $abono ?>><?php echo $abono ?> </option>
                        <?php $i++;   
                    }
                    ?>
                    </select>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="column">
                <div class="elem-group">
                    <label class=izquierda for="title">Telefono</label>
                    <input type="text" id="telefono" name="telefono" value="<?php echo $telefono ?>" placeholder="<?php echo $telefono ?>" readonly>
                </div>
            </div>
            <div class="column">
                <div class="elem-group">
                    <label for="title">DNI</label>
                    <input type="text" id="documento" name="documento" required placeholder="su DNI" pattern=[0-9\s]{5,60}>
                </div>
            </div>

        </div>
        <br><br><br><br>
        <button type="submit">Enviar</button>
    </form>

</body>

</html>