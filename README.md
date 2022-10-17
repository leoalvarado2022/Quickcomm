# Quickcomm

###### API para llamar a los webhooks de notificacion de Quickcomm.co

## Obtener token

La API usa dos URL diferente, una para obtener el Token y otra para llamar a las notifaciones, obtenemos el token y se guarda en una cookie durante una hora, se obtiene en la linea 128, el cual se consulta si la cookie existe lo devuelve

```
$token = $this->obtenerToken()->get("token");
```

las peticiones deben llevar un Bearer Token el cual se obtuvo antes, para usar el API es tan sencillo como solo llamar a la funcion de notificacion

```
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
```