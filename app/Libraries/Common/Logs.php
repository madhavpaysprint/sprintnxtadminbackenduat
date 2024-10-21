<?php
namespace App\Libraries\Common;

class Logs
{
    public static function init($data){
        $curl = curl_init();

        curl_setopt_array($curl, array(
          CURLOPT_URL => 'https://logs.sprintnxt.in/write.php',
          CURLOPT_RETURNTRANSFER => true,
          CURLOPT_ENCODING => '',
          CURLOPT_MAXREDIRS => 10,
          CURLOPT_TIMEOUT => 0,
          CURLOPT_FOLLOWLOCATION => true,
          CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
          CURLOPT_CUSTOMREQUEST => 'POST',
          CURLOPT_POSTFIELDS => $data,
        ));

        $response = curl_exec($curl);

        curl_close($curl);
    }
    public static function writelogs($data = "")
    {        
        if(env('APP_ENV') == "prod" || env('APP_ENV') == "production"){
            $senddata = [
                'domain_name' => "admin",
                'dir' => $data['dir'],
                'data' => json_encode(array('date' => date("Y-m-d H:i:s"),'request' => $data['type'],'data' => json_decode($data['data'])), TRUE),
            ];
            self::init($senddata);
        }else{
            $path  = "logs/".date("Y-m-d")."/".$data['dir'];
            $file_name = $path."/logs.txt";
            if(!is_dir($path)){
              mkdir($path, 0777, TRUE);
            }
            $handle     =   fopen($file_name, 'a');
            fwrite($handle,json_encode(array('date' => date("Y-m-d H:i:s"),'request' => $data['type'],'data' => json_decode($data['data'])), TRUE)." \n");
            fclose($handle);
        }
    }
}
