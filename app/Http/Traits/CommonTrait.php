<?php

namespace App\Http\Traits;

use GuzzleHttp\Client;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\UserPasswordDetails as UserPassword;
use App\Models\Notification;
use App\Models\NotificationStatus;

trait CommonTrait
{
    public static function response($input = '', $params = array())
    {
        $statusResp = array(
            'success' => array(
                'statuscode' => 200,
                'status' => true,
                'message' => 'Success!',
            ),
            'invaliddaterange' => array(
                'statuscode' => 201,
                'status' => false,
                'message' => 'Date range should be not more than 30 days',
            ),
            'norecordfound' => array(
                'statuscode' => 201,
                'status' => false,
                'message' => 'IP and callback url not whitelisted',
            ),
            'noresult' => array(
                'statuscode' => 200,
                'status' => false,
                'message' => 'No Record Found!',
            ),
            'alreadyStore' => array(
                'statuscode' => 200,
                'status' => false,
                'message' => 'The Bank details has been already Stored !',
            ),
            'exception' => array(
                'statuscode' => 201,
                'status' => false,
                'message' => 'Exception Error!',
            ),
            'incorrectinfo' => array(
                'statuscode' => 201,
                'status' => false,
                'message' => 'The provided information is incorrect!',
            ),
            'updateError' => array(
                'statuscode' => 201,
                'status' => false,
                'message' => 'Error while Updating!',
            ),
            'notvalid' => array(
                'statuscode' => 201,
                'status' => false,
                'message' => 'The provided information is not Valid!',
            ),
            'notvalidatthemoment' => array(
                'statuscode' => 201,
                'status' => false,
                'message' => 'Your request for this date has been already initiated, Please wait !',
            ),
            'apierror' => array(
                'statuscode' => 201,
                'status' => false,
                'message' => 'API is not responding right now!',
            ),
            'validatorerrors' => array(
                'statuscode' => 422,
                'status' => false,
                'message' => 'Validation Error!',
            ),
            'oops' => array(
                'statuscode' => 404,
                'status' => false,
                'message' => 'something went wrong please try after some time!',
            ),
            'internalservererror' => array(
                'statuscode' => 500,
                'status' => false,
                'message' => 'HTTP INTERNAL SERVER ERROR!',
            ),
            'accessdenied' => array(
                'statuscode' => 403,
                'status' => false,
                'message' => 'Access denied!',
            ),
        );

        if (isset($statusResp[$input])) {
            $data = $statusResp[$input];
            $code = isset($params['code']) ? $params['code'] : $statusResp[$input]['statuscode'];
            if (!empty($params)) {
                $data = array_merge($data, $params);
            }
            return response()->json($data, $code);
        } else {
            return response()->json($params);
        }
    }

    public static function validationResponse($message)
    {
        $err = $message->toArray();
        $msg = $errors = [];
        foreach ($err as $key => $value) {
            $msg[] = $value[0];
            $errors[$key] = $value[0];
        }
        $message = ['message' => implode('<br/>', $errors), 'msg' => $msg];
        return $message;
    }

    public static function is_expired($date, $minute)
    {
        $date1 = new DateTime($date);
        $now = new DateTime();
        $difference_in_seconds = $now->format('U') - $date1->format('U');
        $counterTime = 60 * $minute;

        if ($difference_in_seconds > $counterTime) {
            return true;
        } else {
            return false;
        }
    }

    public static function is_locked($date, $minute)
    {
        $date1 = new DateTime($date);
        $now = new DateTime();
        $difference_in_seconds = $now->format('U') - $date1->format('U');
        $counterTime = 60 * $minute;

        if ($difference_in_seconds < $counterTime) {
            return true;
        } else {
            return false;
        }
    }


    function convertString($string, $flag)
    {
        // for email addresses: do not obfuscate beyond at symbol
        $clear = strpos($string, "@");
        if ($flag == 0) {
            if ($clear === false)
                $clear = max(0, strlen($string) - 0);
            $hide = max(0, min($clear - 1, 0));
            $result = substr($string, 0, $hide) .
                str_repeat("x", $clear - $hide) .
                substr($string, $clear);
        } else if ($flag == 1) {
            if ($clear === false)
                $clear = max(0, strlen($string) - 0);
            $hide = max(0, min($clear - 1, 1));
            $result = substr($string, 0, $hide) .
                str_repeat("x", $clear - $hide) .
                substr($string, $clear);
        }
        return $result;
    }

    static function random_no($length, $charTyp = "")
    {
        $token = "";
        if ($charTyp == "") {
            $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
            $codeAlphabet .= "abcdefghijklmnopqrstuvwxyz";
            $codeAlphabet .= "0123456789";
        } else if ($charTyp == "number") {
            $codeAlphabet = "0123456789";
        }
        $max = strlen($codeAlphabet); // edited

        for ($i = 0; $i < $length; $i++) {
            $token .= $codeAlphabet[CommonTrait::crypto_rand_secure(0, $max - 1)];
        }
        return $token;
    }

    static function crypto_rand_secure($min, $max)
    {
        $range = $max - $min;
        if ($range < 1)
            return $min; // not so random...
        $log = ceil(log($range, 2));
        $bytes = (int) ($log / 8) + 1; // length in bytes
        $bits = (int) $log + 1; // length in bits
        $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
        do {
            $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
            $rnd = $rnd & $filter; // discard irrelevant bits
        } while ($rnd > $range);
        return $min + $rnd;

    }

    static function getinputtypes()
    {
        $result = ['1' => 'file + button', '2' => 'file', '3' => 'input'];
        return $result;
    }

    static function passwordError($input = "")
    {
        $passResp = array(
            'match' => array(
                'message' => "Password matched",
                'statuscode' => 200
            ),
            'wrong' => array(
                'message' => "Password not match",
                'statuscode' => 201
            ),
            'expire' => array(
                'message' => "Password expired",
                'statuscode' => 201
            ),
            'otpsent' => array(
                'allwoTwostep' => true,
                'message' => "OTP send successfully.",
                'statuscode' => 201
            ),
            'locked' => array(
                'message' => "Account locked after 3 incorrect attempts. Try after some time",
                'statuscode' => 201
            ),
            'blocked' => array(
                'message' => "Account blocked 5 incorrect attempts. Please Contact to admin or reset your password",
                'statuscode' => 201
            )
        );

        if (isset($passResp[$input])) {
            $data = $passResp[$input];
            return $data;
        } else {
            $data = $passResp['wrong'];
            return $data;
        }
    }

    static function otpsent()
    {
        $response = [
            'allwoTwostep' => true,
            'message' => "OTP send successfully.",
            'statuscode' => 201
        ];
        return $response;
    }
    public function checkPasswordMatch($reqPass, $isPswdMatch)
    {
        if (Hash::check($reqPass, $isPswdMatch->password)) {
            $pswdExpDate = $isPswdMatch->expired_at;
            $today_date = date('Y-m-d');
            if ($isPswdMatch->status != 1 || $today_date > $pswdExpDate) {
                UserPassword::where('id', $isPswdMatch->id)->update(["status" => 0]);
                $res = $this->passwordError('expire');
                return $res;
            } else {
                UserPassword::where('id', $isPswdMatch->id)->update(["login_attempt" => 0]);
                $res = $this->passwordError('match');
                return $res;
            }
        } else {
            $attempt = $isPswdMatch->login_attempt + 1;
            if ($attempt > 4) {
                $status = 0;
            } else {
                $status = 1;
            }
            UserPassword::where('id', $isPswdMatch->id)->update(["login_attempt" => $attempt, 'status' => $status]);
            $res = $this->passwordError('wrong');
            return $res;
        }
    }

    public function randomPassword()
    {
        $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $alphabet[$n];
        }
        return implode($pass); //turn the array into a string
    }
    static function upload_document(Request $request)
    {
        $validated = Validator::make($request->all(), [
            'document' => 'required|' . $request->validation,
            'path' => 'required'
        ]);
        if ($validated->fails()) {
            $error = CommonTrait::validationResponse($validated->errors());
            return ['status' => false, 'error' => $error];
        }
        $filePath = $request->path . '/';
        $fileName = 'rlm' . time() . '.' . $request->document->extension();
        $request->document->move(rootDir() . $filePath, $fileName);
        $url = rootDir() . $filePath . $fileName;
        return ['status' => true, 'url' => $url];
    }

    static function nostro_type()
    {
        return
            [
                ['id' => 1, 'nostro' => 'BEN'],
                ['id' => 2, 'nostro' => 'OUR']
            ];
    }

    public function getorderstage($stage)
    {
        $orderStages = array('1' => 'New', '2' => 'Pending', '3' => 'Verified', '4' => 'Confirmed', '5' => 'Completed');
        return $orderStages[$stage];
    }


    public function encryption_private_data($data)
    {
        $key = openssl_random_pseudo_bytes(32);
        $encrypteddata = openssl_encrypt(json_encode($data), 'aes-256-cbc', $key, OPENSSL_RAW_DATA);
        openssl_private_encrypt($key, $encryptedKey, self::privatekey());
        $encodedData = base64_encode($encrypteddata);
        $encodedKey = base64_encode($encryptedKey);

        return ['token' => $encodedData, 'key' => $encodedKey];
    }

    public function encryption_public_data($data)
    {
        $key = openssl_random_pseudo_bytes(32);
        openssl_public_decrypt($encryptedKey, $key, self::publickey());
        $decryptedData = openssl_decrypt($data, 'aes-256-cbc', $key, OPENSSL_RAW_DATA);

        return $decryptedData;
    }


    protected function privatekey()
    {
        return '-----BEGIN RSA PRIVATE KEY-----
        MIICXAIBAAKBgQDJJ3FNyqOa1XNblsByNQIDPMCSuTMBiAD78K34Rfi8FWF1P9zv
        a3bnrucr53eJzSSjfgEJ+1qtVQ5v2eSPwrfXI4x9QvpSqMutfHTSq3JudZMWSgcH
        kaAOJNG3jlqzkeUZQA07nBVKHQ8ZqaKqgnlM7pjbvKwgzUzinQ/0NZmARQIDAQAB
        AoGBALS9cfsZ5qMKw5o5/DUiF+rcvZOYQJJRp8C4Yzi/dl1ZQLZfaZ7einpmF2TF
        mA0DfLZCU6CqbrFryYsK12ms5g0bGX0mZr5fD238Uwe1dJZySdAalrmedZDaOKe3
        AZpWwxSVld2BsY8+BomI1O2AQnej6AvKEufyLSgj2U9Qv4qBAkEA5bjiJXqt/uYv
        FECMK9AtdOIbbeoXtIBxn2qxASPTSGT9RUIQAByRh5t3xlWtSj3bpQlexprMmyhk
        CdO7yWfd1QJBAOAp+og1vjfq6i4DsFrwy055PG8aZ+hBnqBMKLb+5LPUzJW5RZLh
        gri1vZeX6RYLZUuu8nY1SBr+BwSKBB+UoLECQBD6tVxn0OyCPwCUNMgYPwPgon5h
        SxdAVyWdUS/wYfF75Wx1EZGwiuEnEJdMRd6y68UrCCJN1smxFpPTXpHoZ3ECQFsf
        lXFjb3TpsNKNu1Xshqjazb9YW57ldecxrmddTHjx60x96RNhSrNtZanHHgBRF5dh
        gbydwjb+xrmIpU51K7ECQEPrUUVcjywtJcY8GoN1NwAm25By06XDwPY1MWqQnkfJ
        WPqmgzl5D09HNlKWmj4sqquFvusFCtt1tYEeAZC5R+0=
        -----END RSA PRIVATE KEY-----';
    }

    protected function publickey()
    {
        return '-----BEGIN PUBLIC KEY-----
        MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDJJ3FNyqOa1XNblsByNQIDPMCS
        uTMBiAD78K34Rfi8FWF1P9zva3bnrucr53eJzSSjfgEJ+1qtVQ5v2eSPwrfXI4x9
        QvpSqMutfHTSq3JudZMWSgcHkaAOJNG3jlqzkeUZQA07nBVKHQ8ZqaKqgnlM7pjb
        vKwgzUzinQ/0NZmARQIDAQAB
        -----END PUBLIC KEY-----';
    }

    public function addNotification($data)
    {
        $details = new Notification();
        $details->title = $data['title'];
        $details->content = $data['content'];
        $details->user_type = 1;
        if (isset($data['start_date'])) {
            $details->start_date = $data['start_date'];
        } else {
            $details->start_date = date('Y-m-d');
        }
        if (isset($data['end_date'])) {
            $details->end_date = $data['end_date'];
        }
        $details->save();
        $notice = $details->id;
        if ($notice) {
            $data = array(
                'user_id' => $data['user_id'],
                'notification_id' => $notice,
                'status' => 0
            );
            NotificationStatus::insert($data);
            return $this->response('success', ['message' => "Notification Added Successfully."]);
        } else {
            return $this->response('apierror');
        }
    }

    public static function date_difference($start_date, $end_date){
		$diff = date_diff(date_create($start_date), date_create($end_date));
		if ($diff->format("%a") <= self::allowed_days()) {
			return true;
		} else {
			return false;
		}
	}

    public static function allowed_days(){
		 $allowed_days = 60;
         return $allowed_days;
	}

    // Added by @vinay on 10-10-2024
    public static function new_date_difference($start_date, $end_date){
		$diff = date_diff(date_create($start_date), date_create($end_date));
		if ($diff->format("%a") <= self::new_allowed_days()) {
			return true;
		} else {
			return false;
		}
	}
    public static function new_allowed_days(){
        $allowed_days = 3;
        return $allowed_days;
   }


   public static function export_limit() {
        $limit = 10000;
        return $limit;
   }


    /**
     * @function ExportAllowedDays (for export allowed days)
     * @return int
     * @author Madhav <madhav.kumar@ciphersquare.tech>
     */
    public static function ExportAllowedDays(){
        $allowed_days = 1;
        return $allowed_days;
    }
}
