<?php

namespace App\Recaptcha;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Service permettant de vérifier si un captcha est valide auprès des serveurs de Google
 */
class RecaptchaValidator{

    /**
     * Importation du service d'accès aux paramètres globaux du site
     */
    public function __construct(private ParameterBagInterface $params){}

    /**
     * Méthode qui renverra "true" si le captcha dont le code est passé en paramètre est valide, sinon false
     */
    public function verify($code, $ip = null)
    {
        if(empty($code)) {
            return false;
        }
        $params = [
            'secret'    => $this->params->get('google_recaptcha_private_key'),  // Récupération de la clé privée de Google Recaptcha depuis les paramètres du site
            'response'  => $code
        ];
        if($ip){
            $params['remoteip'] = $ip;
        }
        $url = "https://www.google.com/recaptcha/api/siteverify?" . http_build_query($params);
        if(function_exists('curl_version')){
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, false);
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($curl, CURLOPT_TIMEOUT, 10);
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($curl);
        }else{
            $response = file_get_contents($url);
        }
        if(empty($response) || is_null($response)){
            return false;
        }
        $json = json_decode($response);
        return $json->success;
    }

}