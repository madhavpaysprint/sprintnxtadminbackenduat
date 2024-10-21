<?php
namespace App\Libraries\Common;


class Curl
{
    private static function response($response){
        $res =  json_decode($response, TRUE);
        return $res;
    }

    public static function writelog($type,$req,$dirname) {
        if(is_array($req) || is_object($req)){
            $array      =   json_encode($req, TRUE);    
        }else{
            $array      =   $req;
        }
        Logs::writelogs(array("dir"=>$dirname,"type"=>$type,"data"=>$array));
    }

    public static function hit($reqData){
        // $location = isset($reqData['dirname'])?$reqData['dirname']:'others';
        // $requestId = rand(111111,999999);
        // self::writelog("REQUEST-".$requestId, $reqData, $location);
        try{
        if(!empty($reqData['parameter'])){
            $parameter  =   $reqData['parameter'];
        }else{
            $parameter  =   "";
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $reqData['url'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_HTTPHEADER => array('Content-Type:application/json'),
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $reqData['method'],
            CURLOPT_POSTFIELDS => $reqData['parameter'],
        ));
        $response = curl_exec($curl); 
       // self::writelog("RESPONSE-".$requestId, $response, $location);
        $response = json_decode($response);
        if(curl_errno($curl)){
            $resp   =   array("statuscode"=>"CP001","status"=>curl_errno($curl),"message"=>curl_error($curl));
        }
        else{
            if(!empty($response)){
                $resp  =   (array)$response;
            }else{
                $resp  =   array("statuscode"=>201,"status"=>curl_errno($curl),"data"=>array(),"message"=>"Failed");
            }
        }
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errors = json_decode($e->getResponse()->getBody()->getContents());
            self::writelog("RESPONSE-".$requestId, $errors, $location);
            $resp = $errors;
        }
        return $resp;
    }

    public static function cibregister($req){
        if(env('APP_ENV') == "prod" || env('APP_ENV') == "production"){
            $url = env('ICICI_BANK_LIVE');
        }else{
            $url = env('ICICI_BANK_UAT');
        }
        $reqData    =   array(
            "dirname"   =>  "cib-registration",
            "method"    =>  "POST",
            "url"       =>  $url."cib-registration",
            "parameter" =>  $req
        );
        $resp = self::hit($reqData);
        return $resp;
    }

    public static function cibstatus($req){
        if(env('APP_ENV') == "prod" || env('APP_ENV') == "production"){
            $url = env('ICICI_BANK_LIVE');
            $reqData    =   array(
                "dirname"   =>  "cib-status",
                "method"    =>  "POST",
                "url"       =>  $url."cib-registration-status",
                "parameter" =>  $req
            );
            $resp = self::hit($reqData);
            return $resp;
        }else{
            $json = '{"statuscode": 200,
                "status": true,
                "responsecode": 1,
                "message": "success",
                "data": {
                    "status": "Registered",
                    "ResponseCode": "0000",
                    "RESPONSE": "Success"
                }
            }';
            $resp = json_decode($json);
            return (array)$resp;
        }
        
    }

    public static function getPayoutStatus($req){
        if(env('APP_ENV') == "prod" || env('APP_ENV') == "production"){
            $url = env('ICICI_BANK_LIVE');
        }else{
            $url = env('ICICI_BANK_UAT');
        }
        $data = [
            "url"=> $url."payout-status",
            "method"=>"POST",
            "parameter"=> $req,
            "directory"=> "payout-status"
        ]; 
        $resp = self::hit($data);
        return $resp;
    }


    public static function hitmerchantreg($reqData){ 
                $curl = curl_init(); 
                curl_setopt_array($curl, array(
                  CURLOPT_URL => 'https://api.sprintnxt.in/api/v2/UPIService/UPI/merchant-registration-admin',
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_SSL_VERIFYHOST=> 0,
                  CURLOPT_SSL_VERIFYPEER=> 0,

                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'POST',
                  CURLOPT_POSTFIELDS =>$reqData['parameter'],
                  CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                    'User-Agent: agent'
                  ),
                )); 
                $response = curl_exec($curl); 
                curl_close($curl);
                 dd($response); 
    }

    public static function merchantRegistraion($req)
    {
        if (env('APP_ENV') == "prod" || env('APP_ENV') == "production") {
            $url = env('FINO_BANK_MER_REG_LIVE');
        } else {
            $url = env('FINO_BANK_MER_REG_UAT');
        }

        $reqData    =   array(
            "dirname"   =>  "merchant-registration",
            "method"    =>  "POST",
            "url"       =>  $url,
            "parameter" =>  json_encode($req),
        ); 
        $resp = self::hit($reqData);
        return $resp;
    }
}
?>
