<?php
namespace App\Libraries\Common;
use App\Libraries\Common\Logs;
use App\Libraries\Common\Email;
class Otps
{
    public static function writelog($type,$req,$dirname) {
        if(is_array($req) || is_object($req)){
	        $array      =   json_encode($req, TRUE);    
	    }else{
	        $array      =   $req;
	    }
        Logs::writelogs(array("dir"=>$dirname,"type"=>$type,"data"=>$array));
    }

    public static function init($reqData){ 
        $location = isset($reqData['directory'])?$reqData['directory']:'other';
        $requestId = rand(111111,999999);
        self::writelog("REQUEST-".$requestId, $reqData, $location);
        try {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                    CURLOPT_URL => $reqData['url'],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 180,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                    CURLOPT_CUSTOMREQUEST => "POST",
                    CURLOPT_POSTFIELDS => $reqData['parameter'],
                ));
                $response = curl_exec($curl);
              //  self::writelog("RESPONSE-".$requestId, $response, $location);
                $response = json_decode($response);
                 
                if(curl_errno($curl)){
                    $resp   =   array("response_code"=>0,"status"=>curl_errno($curl),"message"=>curl_error($curl));
                }else{
                    $resp  =   array("response_code"=>1,"status"=>true,"message"=>"success");
                }
                return $resp;
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            $errors = json_decode($e->getResponse()->getBody()->getContents());
            self::writelog("RESPONSE-".$requestId, $errors, $location);
            $resp = $errors;
        }
        return $resp;
    }

    
    public static function generateOtp(){
        if(env('APP_ENV') == "prod" || env('APP_ENV') == "production"){
            $otp = sprintf("%04d", mt_rand(1, 9999));
        }else{
            $otp = 1234;
        }
        return $otp;
    }   

    public static function otpsend($data){ 
        if(!empty($data)){
            if(isset($data['phone']) &&  $data['phone'] != ""){
                $reqData = array();
                $reqData['url'] = env('SMS');
                $reqData['parameter'] = [
                    'mobile' => $data['phone'],
                    'directory' => "OTP",
                    'otp' => $data['otp'],
                    'template' => "LoginOtp",
                    'token' => env('smstoken'),
                ];
                if(env('APP_ENV') == "prod" || env('APP_ENV') == "production"){
                    if(isset($data['sendOnPhone'])){
                        if($data['sendOnPhone']){ 
                            self::init($reqData);  
                        }
                    }else{  
                        self::init($reqData);
                    }
                }   
            }

            if(isset($data['email']) &&  $data['email'] != ""){
                $reqData = array();
                $reqData['otp'] = $data['otp'];
                $req = [
                    "to" => $data['email'],
                    "subject"=> "SprintNXT || One-Time Password (OTP)",
                    "template"=> Emailtemplate::verifyotp($reqData),
                ]; 

                if(env('APP_ENV') == "prod" || env('APP_ENV') == "production"){
                    if(isset($data['sendOnEmail'])){
                        if($data['sendOnEmail']){ 
                            Email::sendemail($req); 
                        }
                    }else{  
                        Email::sendemail($req);
                    }
                }
            } 
            return array("status"=>true,"response_code"=>1,"message"=>"OTP send");
        }else{
            return array("status"=>false,"response_code"=>0,"message"=>"Empty Request");
        }
    }

}