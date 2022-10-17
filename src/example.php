<?php

    require_once("Quickcomm");

    $Quickcomm = new Quickcomm();

    $payload = [
        "skuId" => "123345456",
        "productId" => "123123123"
    ];
    $dataNotificacion = $Quickcomm->notificacion("products","ProductCreated",$payload);
    $jsonNotificacion = json_decode($dataNotificacion);

    if($jsonNotificacion->code != 200) {
        echo $jsonNotificacion->datos->mensaje;
    }