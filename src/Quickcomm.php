<?php

    class Quickcomm {

        protected $valores;
        private $urlNotificacion;
        private $urlToken;
        /**
         * $urlBaseToken : string - url base para obtener el Token
        **/
        private $urlBaseToken;
        /**
         * $urlBaseNotificacion : string - url base para llamar las notificaciones
        **/
        private $urlBaseNotificacion;
        /**
         * $sandbox : boolean - para pasar a modo producción o dev
        **/
        private $sandbox;
        public function __construct() {
            $this->sandbox = true;
            if($this->sandbox){
                $this->urlBaseToken = "https://sandbox-account.quickcomm.co";
                $this->urlBaseNotificacion = "https://sandbox-api.quickcomm.co";
            }else {
                $this->urlBaseToken = "URL DE PRODUCCION";
                $this->urlBaseNotificacion = "URL DE PRODUCCION";
            }
            $this->urlNotificacion = $this->urlBaseNotificacion."/marketplaces/hooks/xxxxxx-xxxxxx-xxxxxx-xxxxxx-xxxxxxxxx/woocommerce/xxxxxx-xxxxxx-xxxxxx-xxxxxx-xxxxxxxxxxx/xxxxxx-xxxxxx-xxxxxx-xxxxxx-xxxxxxxxxxx/";
            $this->urlToken = $this->urlBaseToken."/connect/token";
        }

        protected function set($key,$value) {
            $this->valores[$key] = filter_var($value,FILTER_SANITIZE_STRING);
        }

        protected function get($key){
            return $this->valores[$key];
        }

        private function _response($code,$payload) {
            return json_encode([
                "code" => $code,
                "datos" => $payload
            ]);
        }

        private function _cURL($url,$payload,$headers= []) {
            $ch = curl_init();
        
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            /**
             * ELIMINAR ESTO EN PRODUCCIÓN - SOLO PARA PROPOSITOS DE PRUEBA EN LOCAL
            */
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            /**
             * FIN ELIMINAR ESTO EN PRODUCCIÓN
            */

            $result = curl_exec($ch);
            if (curl_errno($ch)) {
                return $this->_response(500,["mensaje" => 'Error:' . curl_error($ch)]);
            }

            curl_close($ch);

            $datos = json_decode($result);
            if(property_exists($datos,"statusCode") && $datos->statusCode == 404) {
                return $this->_response(404,["mensaje" => $datos->message]);
            }
            
            return $this->_response(200,$datos);
        }

        private function curlToken() {
            $payload = [
                "grant_type" => "client_credentials",
                "scope" => "api IdentityServerApi",
                "client_id" => "fcd54e22-XXXX-XXXX-XXXX-44340943794e",
                "client_secret" => "XXXX/XXXX/AcPZLrWriMnzA8FnEynQtjKwPjIwSftuo=",
                "x-Org-Id" => "14d4f68f-XXXX-XXXX-XXXX-e7fb298ebe0d"
            ];

            $headers = [
                'Content-Type: application/x-www-form-urlencoded'
            ];
            
            $jsonData = json_decode($this->_cURL($this->urlToken,$payload,$headers));
            if($jsonData->code == 200) {
                $this->_setCookie("token",["token"=>$jsonData->datos->access_token],time() + 3600);
            }
            return $jsonData;
        }

        /**
         * Obtener token : string
         * Devuelte un token en string, lo obtiene por primera vez de Quickcomm, si existe en la cookie lo retorna
        **/
        protected function obtenerToken() {
            $_cookie = $this->_getCookie("token");
            if($_cookie !== null) {
                $token = $_cookie->token;
            }else {
                $jsonToken = $this->curlToken();
                if($jsonToken->code == 200){
                    $token = $jsonToken->datos->access_token;
                }else {
                    $token = null;
                }
            }
            $this->set("token",$token);

            return $this;
        }

        /**
         * notificacion
         * @param $endPoint - para concatenar a la url de notificaciones si es products | orders
         * @param $eventType - tipo de evento para la notificacion ProductCreated | ProductUpdated | ProductDeleted | ProductSkuPriceUpdated | ProductSkuStockUpdated | OrderCreated
         * @param $payload - datos a enviar para el Hook de Notificación
        **/
        public function notificacion($endPoint,$eventType,$payload) {
            $token = $this->obtenerToken()->get("token");
            if($token == null) {
                return $this->_response(401,["mensaje" => "No Autorizado, Error al obtener el Token"]);
            }
            $url = $this->urlNotificacion.$endPoint;

            $payload = [
                "eventType" => $eventType,
                "data" => $payload
            ];
            $headers = [
                "Authorization: Bearer ".$token
            ];

            $dataNotificacion = $this->_cURL($url,$payload,$headers);

            if((json_decode($dataNotificacion))->code != 200) {
                return $this->_response(404,["mensaje" => "Error, URL de notificación no encontrada"]);
            }

            return $this->_response(200,$dataNotificacion);
        }

        protected function _setCookie($nombre,$data,$expira) {
            setcookie($nombre,json_encode($data),$expira);
        }

        protected function _getCookie($nombre) {
            if (isset($_COOKIE[$nombre]) && !empty($_COOKIE[$nombre])) {
                return json_decode($_COOKIE[$nombre]);
            }
            return null;
        }

    }