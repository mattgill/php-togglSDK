<?php
require_once('Classloader.php');

$classLoader = new Toggl_Classloader();
spl_autoload_register(array(&$classLoader, "loadClass"));

class Toggl{

    /*
     * API URL parts
     */
    private static $token;
    public static $debug = false;
    public static $verifyPeer = true;

    public static function setKey($apiKey) {
        self::$token = $apiKey;
    }
    public static function verifyPeer($bool){
        self::$verifyPeer = $bool;
    }

    private static function sendWithAuth($params) {
        $url = $params['url'];

        if ($params['method'] != 'POST'){
            $ignore_params = array('method','url');
            $addon_params = array();
            foreach ($params as $param_key => $param_value){
                if (in_array($param_key, $ignore_params)){ continue; }
                $addon_params[$param_key] = $param_value;
            }
            $url .= '?' . http_build_query($addon_params);
        }

        if (self::$debug == true){
            echo 'Request URL: ' . $url . "\n";
        }
        unset($params['url']);
        $method = $params['method'];
        if (self::$debug == true){
            echo 'Request method: ' . $method . "\n";
        }
        unset($params['method']);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_USERPWD, self::$token . ':api_token');
        if (self::$verifyPeer == false){
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        }

        if (self::$debug == true){
            echo 'API Token: ' . self::$token;
        }
        if ($method == 'POST'){
            curl_setopt($curl, CURLOPT_POST, true);
            $params = json_encode($params);
            if (self::$debug == true){
                echo "POST json: " . $params;
            }
            curl_setopt($curl, CURLOPT_POSTFIELDS, $params);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($params),
            ));
        }
        $result = curl_exec($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
        $resultJson = json_decode($result, true);
        if (is_array($resultJson)){
            if (count($resultJson) == 1 && isset($resultJson['data'])){
                $resultJson = $resultJson['data'];
            }
            return $resultJson;
        } else {
            $errorMessage = 'Toggl API call failed -- Request URL: ' . $url . (is_string($params)? ' Request Data: ' . $params : null) . ' Response code: ' . $info['http_code'] . ' Raw response dump: ' . $result . ' serialized CURL info: ' . serialize($info);
            if (self::$debug == true)
            {
	        echo $errorMessage . "\n";
            }
            throw new Exception($errorMessage);
        }
    }

    public static function send($params = array()) {
        return self::sendWithAuth($params);
    }

    public static function checkConnection(){
        TogglUser::getCurrentUserData();
    }

}
