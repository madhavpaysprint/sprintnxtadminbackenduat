<?php

namespace App\Libraries\Common;
use App\Libraries\Common\Logs;
use App\Libraries\Common\User as UserLib;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;
use App\Http\Traits\JWTTrait;
class Email
{
    private static $credential;
    use JWTTrait;
    private static function getCredential()
    {
        //
    }

    public static function sendemail2($request)
    {
        $requestId = rand(111111,999999);
        $mailRequest = json_encode(array('to'=>$request['to'],'subject'=>$request['subject'],),true);
        Logs::writelogs(array("dir" => "Email", "type" => "REQUEST-".$requestId, "data" => $mailRequest));
        try {
            require './vendor/autoload.php';
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            //self::getCredential();
          // $mail             = new PHPMailer\PHPMailer(); // create a n
            $mail->SMTPDebug  =  false; // debugging: 1 = errors and messages, 2 = messages only
            $mail->SMTPAuth   = true; // authentication enabled
            $mail->SMTPSecure = env('EML_SMTPSecure'); // secure transfer enabled REQUIRED for Gmail
            $mail->Host       = env('EML_HOST');
            $mail->Port       = env('EML_PORT');
            $mail->isHTML(true);
            $mail->Encoding="base64";
            $mail->Username = env('EML_USR');
            $mail->Password = env('EML_PASS');
            $mail->AddAddress($request['to'], "");
            $mail->SetFrom("no-reply@paysprint.in", 'PaySprint Pvt Ltd');
            $mail->Subject = $request['subject'];
            $mail->Body    = $request['template'];
            if (isset($request['cc']) && !empty($request['cc'])) {
                foreach ($request['cc'] as $eachcc) {
                    $mail->AddCC($eachcc['email'], $eachcc['name']);
                }
            }
            if (isset($request['attachment']) && !empty($request['attachment'])) {
                foreach ($request['attachment'] as $eachattachment) {
                    $mail->addAttachment($eachattachment);
                }
            }

            Logs::writelogs(array("dir" => "Email", "type" => "RESPONSE-".$requestId, "data" => json_encode(['message'=>'Mail Sent'])));

            if(env('APP_ENV') == 'production' || env('APP_ENV') == 'prod')
                return $mail->Send();
            else
                return true;

        } catch (Exception $e) {
            $data = ['error'=> $e->getMessage(), 'line'=> $e->getLine(), 'file'=> $e->getFile()];
            Logs::writelogs(array("dir" => "Email", "type" => "RESPONSE-".$requestId, "data" => json_encode($data)));
            return $e->getMessage();
        }
    }

    private static function getjwttoken(){
        $PS_JWTkEY = env("EMAILJWTKEY");
        $PS_USER = env("PARTNERID");
        $num  = time().rand(1111111,9999999);
        $tokendata = [
            "timestamp" =>  time(),
            "partnerId" =>  $PS_USER,
            "reqid"     =>  $num
        ];
        return static::generateToken($tokendata,$PS_JWTkEY);
    }

    public static function sendemail($request)
    {
        $client =[
            "to"=>["email"=>$request['to'],"name"=>"name"],
            "from"=>["email"=>"no-reply@paysprint.in","name"=>"PaySprint"],
            "subject"=>$request['subject'],
            "body"=>$request['template'],
            "attachments"=>isset($request['attachement'])?$request['attachement']:[],
      ];

        $reqData['jwt'] =   static::getjwttoken();
        $requestId = rand(111111,999999);
        $mailRequest = json_encode(array('to'=>$request['to'],'subject'=>$request['subject'],'data'=>json_encode($client)),true);


        Logs::writelogs(array("dir" => "Email", "type" => "REQUEST-".$requestId, "data" => $mailRequest));
        try {
                $curl = curl_init();
                curl_setopt_array($curl, array(
                  CURLOPT_URL => 'https://emailserver.paysprint.in/email/send',
                  CURLOPT_RETURNTRANSFER => true,
                  CURLOPT_ENCODING => '',
                  CURLOPT_MAXREDIRS => 10,
                  CURLOPT_TIMEOUT => 0,
                  CURLOPT_FOLLOWLOCATION => true,
                  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                  CURLOPT_CUSTOMREQUEST => 'POST',
                  CURLOPT_POSTFIELDS =>json_encode($client,JSON_UNESCAPED_SLASHES),
                  CURLOPT_HTTPHEADER => array(
                      "Token: ".$reqData['jwt'],
                      "Authorisedkey:MzNkYzllOGJmZGVhNWRkNXt1YTgzM2Y5ZDFlY2EyZTQ=",
                    'Content-Type: application/json'
                  ),
                ));

                $response = curl_exec($curl);
                curl_close($curl);
                Logs::writelogs(array("dir" => "Email", "type" => "RESPONSE-".$requestId, "data" =>$response));
                return $response;

        } catch (Exception $e) {
            $data = ['error'=> $e->getMessage(), 'line'=> $e->getLine(), 'file'=> $e->getFile()];
            Logs::writelogs(array("dir" => "Email", "type" => "RESPONSE-".$requestId, "data" => json_encode($data)));
            return $e->getMessage();
        }
    }
}
