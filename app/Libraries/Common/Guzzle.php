<?php
namespace App\Libraries\Common;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use App\Libraries\Common\Logs;
class Guzzle
{
    

    public static function writelog($type,$req,$dirname) {
        if(is_array($req) || is_object($req)){
	        $array      =   json_encode($req, TRUE);    
	    }else{
	        $array      =   $req;
	    }
        Logs::writelogs(array("dir"=>$dirname,"type"=>$type,"data"=>$array));
    }

    public static function hit($data) {
        $location = isset($data['directory'])?$data['directory']:'others';
        $requestId = rand(111111,999999);
        self::writelog("REQUEST-".$requestId, $data, $location);
        try {
            if(env('APP_ENV') == "prod" || env('APP_ENV') == "production"){
                $url = env('ICICI_BANK_LIVE');
            }else{
                $url = env('ICICI_BANK_UAT');
            }
            $client = new Client();
            $response = $client->request($data['method'],$url.$data['url'], [
                'headers' => [ 
                    'Content-Type'  => 'application/json'
                ],
                'json' => $data['parameter'],
            ]);
            $result = $response->getBody()->getContents();
            self::writelog("RESPONSE-".$requestId, $result, $location);
            $respon = json_decode($result);
            if(!empty($respon) && isset($respon->response_code)){
                return $respon;
            }else{
                $respon = new \stdClass();
                $respon->status = false;
                $respon->status_code = 201;
                $respon->response_code = 0;
                $respon->message = "The system had an internal exception";
                return $respon;
            }
            
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errors = json_decode($e->getResponse()->getBody()->getContents());
            self::writelog("RESPONSE-".$requestId, $errors, $location);
            $resp = $errors;
        }
        return $resp;
    }

    

    public static function getPayoutStatus($req){
        $data = [
            "url"=>"payout-status",
            "method"=>"POST",
            "parameter"=> $req,
            "directory"=> "payout-status"
        ]; 
        $resp = self::hit($data);
        return $resp;
    }

}