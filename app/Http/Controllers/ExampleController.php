<?php

namespace App\Http\Controllers;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExampleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // added some features
    }
//    public function QrHitOLD(Request $request){
//
//        $loopCount = 100;
//        $paramsList = [];
//
//        for ($i = 1; $i <= $loopCount; $i++) {
//            $userRef = $this->userRef();
//            $ref = "RIS" . $userRef;
//            $paramsList[] = [
//                "apiId" => 20249,
//                "bankId" => 3,
//                "payeeVPA" => "ps1.sdpay@fin",
//                "mobile" => "9934464262",
//                "txnReferance" => $ref,
//                "txnNote" => $ref,
//            ];
//        }
//
//        $mh = curl_multi_init();
//        $curlArray = [];
//
//        foreach ($paramsList as $i => $params) {
//            $startTime = microtime(true);
//
//            $curl = curl_init();
//
//            curl_setopt_array($curl, [
//                CURLOPT_URL => 'https://uatnxtgen.sprintnxt.in/api/v2/UPIService/UPI',
//                CURLOPT_RETURNTRANSFER => true,
//                CURLOPT_ENCODING => '',
//                CURLOPT_MAXREDIRS => 10,
//                CURLOPT_TIMEOUT => 10,
//                CURLOPT_FOLLOWLOCATION => true,
//                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//                CURLOPT_CUSTOMREQUEST => 'POST',
//                CURLOPT_POSTFIELDS => json_encode($params),
//                CURLOPT_HTTPHEADER => array(
//                    'token: 5G00GuFFajKiFUD2tq9ltAPC6ocj3EoGgQo4V/nb9ztu4vGdwYk+1t0UoRUybc3UsH22EqSR4yiyr23JZ+F7v51AgGoPUkp3g6QsoAugPL8eNrMyKdO1/LeSIDA3cdySKL8YhWYSJLJIhKV8te7ZmZ7dPhthPvTaq+bNrgKP9Qr72OYOLn9usofnL7gvH+ev',
//                    'client-id: U1BSX05YVF91YXRfOTc3YThmYmJiY2VmNjU4Nw==',
//                    'Content-Type: application/json'
//                ),
//                CURLOPT_NOSIGNAL => 1,
//            ]);
//
//            curl_multi_add_handle($mh, $curl);
//            $curlArray[$i] = $curl;
//            $requestTimings[$i]['start'] = $startTime;
//        }
//
//        $running = null;
//        do {
//            curl_multi_exec($mh, $running);
//        } while ($running);
//
//        $responses = [];
//        foreach ($curlArray as $i => $curl) {
//            $endTime = microtime(true);
//            $duration = $endTime - $requestTimings[$i]['start'];
//            $durationInMilliseconds = $duration;
//
//            $response = curl_multi_getcontent($curl);
//            $error = curl_error($curl);
//            $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
//
//            if ($error || $httpStatusCode != 200) {
//                $responses[$i] = [
//                    'error' => $error ?: "HTTP status code: $httpStatusCode",
//                    'duration' => $durationInMilliseconds . ' second',
//                ];
//            } else {
//                $responseData = json_decode($response, true);
//                if ($responseData === null && json_last_error() !== JSON_ERROR_NONE) {
//                    $responses[$i] = [
//                        'error' => 'JSON decode error: ' . json_last_error_msg(),
//                        'duration' => $durationInMilliseconds . ' second',
//                    ];
//                } else {
//                    $responses[$i] = [
//                        'data' => $responseData,
//                        'duration' => $durationInMilliseconds . ' second',
//                    ];
//                }
//            }
//
//            curl_multi_remove_handle($mh, $curl);
//            curl_close($curl);
//        }
//
//        curl_multi_close($mh);
//
//        return $responses;
//
//
//
//    }
    public function QrHit(Request $request){
        $start = microtime(true);
        $userRef = $this->userRef();
        $ref = "RIS" . $userRef;
        $paramsList = [
            "apiId" => 20249,
            "bankId" => 3,
            "payeeVPA" => "ps1.sprintnxt@finobank",
            "mobile" => "9934464262",
            "txnReferance" => $ref,
            "txnNote" => $ref,
        ];

        $startTime = microtime(true);
        $AES_ENCRYPTION_IV = "1a60b5eee1de3a61";
        $AES_ENCRYPTION_KEY = "2049bcdf045983776914aa63b30b0ae3";
        $client_secret = "60029bdad4aa8a2182c31b5067f0a890a75b45d94c09e33a9515160e5f9bb437";
        $client_id = "SPR_NXT_prod_07966d9a8fe9d458";

        $err = array();
        $datapost = array('client_secret'=>$client_secret,'requestid'=>rand(1111111111,9999999999),'timestamp'=>time());
        $cipher  =   openssl_encrypt(json_encode($datapost,true), 'AES-256-CBC', $AES_ENCRYPTION_KEY, $options=OPENSSL_RAW_DATA, $AES_ENCRYPTION_IV);
        $finaldata = base64_encode($cipher);
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://api.sprintnxt.in/api/v2/UPIService/UPI',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($paramsList),
            CURLOPT_HTTPHEADER => array(
                'token:'.$finaldata.'',
                'client-id:U1BSX05YVF9wcm9kXzA3OTY2ZDlhOGZlOWQ0NTg=',
                'Content-Type: application/json'
            ),
            CURLOPT_NOSIGNAL => 1,
        ]);
        $response = curl_exec($curl);
        curl_close($curl);
        $end = microtime(true);
        return response()->json(["time"=>"This query took " . ($end - $start) . " seconds",'response'=>json_decode($response)]);


    }

    public static function hitapi($params){
        $curl = curl_multi_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://uatnxtgen.sprintnxt.in/api/v2/UPIService/UPI',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => array(
                'token: 5G00GuFFajKiFUD2tq9ltAPC6ocj3EoGgQo4V/nb9ztu4vGdwYk+1t0UoRUybc3UsH22EqSR4yiyr23JZ+F7v51AgGoPUkp3g6QsoAugPL8eNrMyKdO1/LeSIDA3cdySKL8YhWYSJLJIhKV8te7ZmZ7dPhthPvTaq+bNrgKP9Qr72OYOLn9usofnL7gvH+ev',
                'client-id: U1BSX05YVF91YXRfOTc3YThmYmJiY2VmNjU4Nw==',
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        echo $response;
    }

    public static function userRef()
    {
        $year = date('y');
        $info = getdate(strtotime(date('Y-m-d')));
        $julian =  $info['yday'] + 1;
        $yearlast = substr( $year, -1);
        $timestaamp = time();
        $random =rand(1111,9999);
        $str = $yearlast.$julian.$random.$timestaamp;
        return $str;
    }

}
