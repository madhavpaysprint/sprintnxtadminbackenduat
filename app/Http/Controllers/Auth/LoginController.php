<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Libraries\Common\Otps;
use App\Models\Otp;
use App\Models\Module;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Userloginlog;
use App\Models\ModulePermission;
use App\Models\AdminMenu;
use App\Models\AdminMenuPermission;
use App\Models\UserPasswordDetails as UserPassword;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

use Illuminate\Support\Facades\Hash;


class LoginController extends Controller
{
    use CommonTrait;

    public function index(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required|min:6|max:255',
                'lat' => 'required',
                'lng' => 'required'
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $location = $request->lat . "," . $request->lng;
            $credentials = $request->only('password');
            if (is_numeric($request->email)) {
                $logintype = "phone";
                $credentials['phone'] = $request->email;
            } else {
                $logintype = "email";
                $credentials['email'] = trim($request->email);
            }
            $credentials['status'] = 1;

            $verifiedUser = array();
            $isValidEmail = User::where('status', 1)->when($logintype == "email", function ($query) use ($request) {
                $query->where('email', $request->email);
            })->when($logintype == "phone", function ($query) use ($request) {
                $query->where('phone', $request->email);
            })->first();

            if ($isValidEmail) {
                $isPswdMatch = UserPassword::where('user_id', $isValidEmail->id)->orderBy('id', 'DESC')->first();
                if ($isPswdMatch) {
                    if($isPswdMatch->login_attempt == 3){
                        if ($this->is_locked($isPswdMatch->updated_at, 5)) {
                            $response = $this->passwordError('locked');
                            return $this->response('notvalid', $response);
                        }
                    }
                    if($isPswdMatch->login_attempt >= 5){
                        $response = $this->passwordError('blocked');
                        return $this->response('notvalid', $response);
                    }
                    $loginMessage = $this->checkPasswordMatch($request->password,$isPswdMatch);
                    if($loginMessage['statuscode'] == 200){
                        $verifiedUser = $isValidEmail;
                    }else{
                        return $this->response('notvalid', $loginMessage);
                    }
                } else {
                    return $this->response('notvalid',['message'=>'Password not created!']);
                }
            } else {
                return $this->response('notvalid');
            }
            if ($verifiedUser && !$token = Auth::login($verifiedUser)) {
                return $this->response('notvalid');
            } else {
                $validlogin = false;
                if (Auth::user()->allowip != '') {
                    $allowedip = explode(',', Auth::user()->allowip);
                    if (in_array($request->ip(), $allowedip)) {
                        $validlogin = true;
                    } else {
                        $validlogin = false;
                    }
                } else {
                    $validlogin = true;
                }

                if ($validlogin) {
                     if (Auth::user()->firstlogin == 1) {
                        $this->sendverificationotp(["name" => $request->email,'sendOnEmail' => Auth::user()->sendOnEmail,'sendOnPhone' => Auth::user()->sendOnPhone,'fullname'=>Auth::user()->fullname,'phone'=>Auth::user()->phone, "isSend" => 1, 'otptype' => 'login']);
                        $response = $this->passwordError('otpsent');
                        if ($logintype == "email") {
                            $response['data']['email'] = substr_replace($request->email, "XXXXXX", 0, 6);
                        } else {
                            $response['data']['phone'] = substr_replace($request->email, "XXXXXX", 0, 6);
                        }
                        return $this->response('success', $response);
                    } else {
                        $allwoTwostep = false;
                        $getLastLogin = Userloginlog::where(array("userid" => Auth::user()->id))->orderBy('id', 'DESC')->first();

                        if ($getLastLogin == null) {
                            $agent = $request->server('HTTP_USER_AGENT');
                            Userloginlog::create(['userid' => Auth::user()->id, 'ipaddress' => $request->ip(), "latlng" => $location, 'device_name' => $agent]);

                        }
                        $getLastLogin = Userloginlog::where(array("userid" => Auth::user()->id))->orderBy('id', 'DESC')->first();
                        if ($getLastLogin) {
                            if ($getLastLogin->ipaddress == $request->ip()) {
                                $allwoTwostep = false;
                            } else {
                                $allwoTwostep = true;
                            }
                        }

                        if ($allwoTwostep) {
                            $this->sendverificationotp(["name" => $request->email,'sendOnEmail' => Auth::user()->sendOnEmail,'sendOnPhone' => Auth::user()->sendOnPhone,'fullname'=>Auth::user()->fullname,'phone'=>Auth::user()->phone, "isSend" => 1, 'otptype' => 'login']);
                            $response = $this->passwordError('otpsent');
                            if ($logintype == "email") {
                                $response['data']['email'] = substr_replace($request->email, "XXXXXX", 0, 6);
                            } else {
                                $response['data']['phone'] = substr_replace($request->email, "XXXXXX", 0, 6);
                            }
                            return $this->response('success', $response);
                        } else {

                            $userData = User::where('id',Auth::user()->id)->first();
                            $isExist = $this->loginlogs(["userid" => Auth::user()->id, "ipaddress" => $request->ip(), 'latlng' => $location, 'device_name' => $request->server('HTTP_USER_AGENT')], $token);
                            User::where('id', Auth::user()->id)->update(['remember_token' => base64_encode($request->ip()), 'ipaddress' => $request->ip()]);
                            if ($isExist) {
                                User::where('id', Auth::user()->id)->update(['remember_token' => base64_encode($request->ip())]);
                                $response = [
                                    'message' => "User login successfully.",
                                    'data' => [
                                        'authtoken' => $token,
                                        'email' => $userData['email'],
                                        'phone' => $userData['phone'],
                                        'OnboardToken' => Auth::user()->onboard_token,
                                    ]
                                ];
                                return $this->response('success', $response);
                            } else {
                                return $this->response('notvalid');
                            }
                        }
                    }
                } else {
                    return $this->response('notvalid');
                }
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    protected function sendverificationotp($req)
    {
        $otp = Otps::generateOtp();
        Otp::create(['name' => $req['name'], 'status' => 1, 'otptype' => $req['otptype'], 'otp' => $otp]);
        Otps::otpsend(['email' => $req['name'],'name'=>$req['fullname'],'phone' => $req['phone'],'sendOnEmail' => $req['sendOnEmail'],'sendOnPhone' => $req['sendOnPhone'],'otp' => $otp]);
        if(env('APP_ENV') == "local"){
            $this->sendOtpDiscord($otp);
        }
        return true;
    }

    protected function sendOtpDiscord($otp){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://discordapp.com/api/webhooks/1219607373644304544/5G8Df_UduojWon8PI6qcGgEgmNSxs-8eS0H7GfgqUManB1Ql6x6G4DZRzCWQhklR-pWv',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS =>'{
    "content": "Login OTP IS: ' . $otp . '"
}',
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
                'Cookie: cfruid=27a748104c6596a8da23198db23d24b099c1eeb5-1710845154; dcfduid=38bb0296e5c511eeb2b0522d50d7bc42; __sdcfduid=38bb0296e5c511eeb2b0522d50d7bc42fbd7a5cbce84e1d85821b6def273fb3847e243c3428764d0a56b2e7f8ec4bad1; _cfuvid=JLvIwzPCvsukWIz5HD0uKyBms6BSGDtcQA1CmI7YXLQ-1710845154636-0.0.1.1-604800000'
            ),
        ));
        $response = curl_exec($curl);
        curl_close($curl);
    }


    public function verifyOtp(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required',
                'password' => 'required|min:6|max:255',
                'lat' => 'required',
                'lng' => 'required',
                'otp' => 'required|digits:4',
            ]);
            $otp = $request->otp;
            $location = $request->lat . "," . $request->lng;
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $credentials = $request->only('password');
            if (is_numeric($request->email)) {
                $logintype = "phone";
                $credentials['phone'] = $request->email;
            } else {
                $logintype = "email";
                $cd['email'] = $credentials['email'] = trim($request->email);
            }
            $credentials['status'] = 1;
            $credentials = $request->only('password');

            $verifiedUser = array();
            $isValidEmail = User::where('status', 1)->when($logintype == "email", function ($query) use ($request) {
                $query->where('email', $request->email);
            })->when($logintype == "phone", function ($query) use ($request) {
                $query->where('phone', $request->email);
            })->first();

            if ($isValidEmail) {
                //$password = Hash::make($request->password);
                $password = $request->password;
                $isPswdMatch = UserPassword::where('user_id', $isValidEmail->id)->orderBy('id', 'DESC')->first();
                if ($isPswdMatch && Hash::check($request->password, $isPswdMatch->password)) {
                    if ($isPswdMatch->status != 1) {
                        $response = [
                            'errors' => "expired!",
                            'message' => "Password expired",
                            'password_expired' => true
                        ];
                        return $this->response('notvalid', $response);
                    }
                    $pswdExp = UserPassword::where('id', $isPswdMatch->id)->first();
                    $pswdExpDate = $pswdExp->expired_at;
                    $today_date = date('Y-m-d');
                    if ($today_date <= $pswdExpDate) {
                        $verifiedUser = $isValidEmail;
                    } else {
                        if ($pswdExp->status) {
                            UserPassword::where('id', $isPswdMatch->id)->update(["status" => 0]);
                        }
                        $response = [
                            'errors' => "expired!",
                            'message' => "Password expired",
                            'password_expired' => true
                        ];
                        return $this->response('notvalid', $response);
                    }
                } else {
                    return $this->response('notvalid');
                }
            } else {
                return $this->response('notvalid');
            }


            if ($verifiedUser && !$token = Auth::login($verifiedUser)) {
                return $this->response('notvalid');
            }

            $agent = $request->server('HTTP_USER_AGENT');
            $getOtp = $this->validateOtp(["ipaddress" => $request->ip(), 'latlng' => $location, "otp" => $request->otp, "device_name" => $agent, 'userid' => Auth::user()->id, 'email' => $request->email]);
            if ($getOtp) {
                $otpstatus = $getOtp->status;
                if ($getOtp->otp == $otp && $otpstatus == 1) {
                    Otp::where('id', $getOtp->id)->update(['status' => 0]);
                    if (CommonTrait::is_expired($getOtp->created_at, 5)) {
                        $response = [
                            'errors' => "expired!",
                            'message' => "Otp expired"
                        ];
                        return $this->response('notvalid', $response);
                    } else {
                        Otp::where('id', $getOtp->id)->update(['status' => 0]);
                        User::where('id', Auth::user()->id)->update(['remember_token' => base64_encode($request->ip()), 'firstlogin' => 0]);

                        $response = [
                            'message' => "User login successfully.",
                            'data' => [
                                'authtoken' => $token,
                                'OnboardToken' => Auth::user()->onboard_token,
                                'allwoTwostep' => false,
                                'user_id' => Auth::user()->id
                            ]
                        ];
                        return $this->response('success', $response);
                    }
                } else {
                    $response = [
                        'errors' => "invalid!",
                        'message' => "Invalid OTP"
                    ];
                    return $this->response('notvalid', $response);
                }
            }
            return $this->response('notvalid');
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }


    protected function validateOtp($req)
    {
        Userloginlog::create(['userid' => $req['userid'], 'ipaddress' => $req['ipaddress'], "latlng" => $req['latlng'], 'device_name' => $req['device_name']]);
        $getOtp = Otp::select("*")->where('name', $req['email'])->where('otptype', 'login')->orderBy('created_at', 'desc')->first();
        return $getOtp;
    }

    public function logout()
    {
        try {
            auth()->logout();
            return $this->response('success');
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }


    public function loginlogs($req)
    {
        Userloginlog::create(['userid' => $req['userid'], 'ipaddress' => $req['ipaddress'], "latlng" => $req['latlng'], 'device_name' => $req['device_name']]);
        return true;
    }
    /************************** User Login Api Fun ******************************/
    public function userlogin(Request $request)
    {
        try {

            $validatorArray = [
                "phone" => 'required|digits:10',
                "password" => 'required_without:otp',
                "otp" => 'required_without:password|digits:4'
            ];

            $validator = Validator::make($request->all(), $validatorArray);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            if ($request->otp != '') {
                $credentials['name'] = $request->phone;
                $usercredentials['phone'] = $request->phone;
                $Otp = $request->otp;
                $geneOtp = Otp::select('id', 'name', 'otp', 'status', 'created_at')->where($credentials)->where('status', 0)->orderBy('created_at', 'desc')->first();
                if (!empty($geneOtp)) {
                    if ($geneOtp->otp == $Otp) {
                        Otp::where('id', $geneOtp->id)->update(['status' => 1]);
                        $userdetails = User::select('id')->where($usercredentials)->first();
                        $token = Auth::login($userdetails);
                        if (CommonTrait::is_expired($geneOtp->created_at, 5)) {
                            return $this->response('incorrectinfo', ['message' => 'Otp expired']);
                        } else {
                            return $this->response('success', ['message' => 'Otp Match', 'authtoken' => $token]);
                        }
                    } else {
                        return $this->response('incorrectinfo', ['message' => 'Invalid OTP!!']);
                    }
                }
            } else {
                $credentials = $request->only('password');
                $credentials['phone'] = $request->phone;
                $token = Auth::attempt($credentials);
                if ($token) {
                    return $this->response('success', ['Message' => 'Login Successfully!!', 'authtoken' => $token]);
                }
                return $this->response('success', ['Message' => 'Invalid credentials!!']);
            }

        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    /************************** User Send Otp Api Fun ******************************/
    public function userSendOtp(Request $request)
    {
        try {
            $validatorArray = [
                'phone' => 'required',
            ];
            $validator = Validator::make($request->all(), $validatorArray);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $credentials['phone'] = $request->phone;

            $chkUser = User::where($credentials)->first();
            //dd( $chkUser );
            if (empty($chkUser)) {
                return $this->response('notvalid');
            }
            $genOtp = Otps::generateOtp();
            $otp = new Otp;
            $otp->otp = $genOtp;
            $otp->name = $request->phone;
            $otp->save();
            if($otp->id){
                Otps::otpsend(['email' => $chkUser->email,'name'=>$chkUser->fullname,'sendOnEmail' => $chkUser->sendOnEmail,'sendOnPhone' => $chkUser->sendOnPhone,'phone' => $request->phone,'otp' => $genOtp]);
            }
            return $this->response('success', ['message' => 'OTP send successfully', 'otp' => $genOtp]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    public function verifyUserOtp(Request $request)
    {
        try {
            $validatorArray = [
                'phone' => 'required',
                'otp' => 'required',
            ];
            $validator = Validator::make($request->all(), $validatorArray);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $credentials['name'] = $request->phone;
            $usercredentials['phone'] = $request->phone;
            $Otp = $request->otp;
            // echo($Otp);
            $geneOtp = Otp::select('id', 'name', 'otp', 'status', 'created_at')->where($credentials)->where('status', 0)->orderBy('created_at', 'desc')->first();
            if (!empty($geneOtp)) {
                if ($geneOtp->otp == $Otp) {
                    $userdetails = User::select('id')->where($usercredentials)->first();
                    $token = Auth::login($userdetails);
                    if (CommonTrait::is_expired($geneOtp->created_at, 5)) {
                        return $this->response('incorrectinfo', ['message' => 'Otp expired']);
                    } else {
                        return $this->response('success', ['message' => 'Otp Match', 'authtoken' => $token]);
                    }
                } else {
                    return $this->response('incorrectinfo', ['message' => 'Invalid OTP!!']);
                }
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public static function encrypt($data)
    {
        $key = self::credential()['key'];
        $key = hex2bin($key);
        $iv_size = openssl_cipher_iv_length('AES-128-CBC');
        $iv = openssl_random_pseudo_bytes($iv_size);
        $blocksize = 16;
        $pad = $blocksize - (strlen($data) % $blocksize);
        $data = $data . str_repeat(chr($pad), $pad);
        return base64_encode($iv . openssl_encrypt(self::pad_zero($data), 'AES-128-CBC', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, $iv));
    }

    public static function decrypt($data)
    {
        $key = self::credential()['key'];
        $key = hex2bin($key);
        $ciphertext_dec = base64_decode($data);
        $iv_size = openssl_cipher_iv_length('AES-128-CBC');
        $iv_dec = substr($ciphertext_dec, 0, $iv_size);
        $ciphertext_dec = substr($ciphertext_dec, $iv_size);
        $dc = openssl_decrypt($ciphertext_dec, 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv_dec);
        return rtrim($dc, "\x00..\x1F");
    }


    public static function getUser(Request $request)
    {
        $data = User::all();
        dd($data);
    }

    public function getLeftPanel(Request $request)
    {
        $menu = array();
        $modulesPermission = ModulePermission::where('user_id', Auth::user()->id)->where('status', 1)->first();
        if(Auth::user()->role == 1){
            $all_modules = Module::where('status',1)->get();
            $modules = array();
            foreach($all_modules as $module){
                array_push($modules,$module->id);
            }
        }elseif ($modulesPermission){
            $modules = explode(",", $modulesPermission->module_id);
        }else{
            $modules = array();
        }

        if (empty($modules)) {
            $res = [
                'message' => 'success',
                'data' => $menu
            ];
        }else{
            $menuPermissions = AdminMenuPermission::where('user_id', Auth::user()->id)->where('status', 1)->pluck('menu_id');

            if(Auth::user()->role == 1){
                $group_menu = AdminMenu::whereIn('module_id', $modules)->where('parent', 0)->where('is_show', 1)->orderBy('menu_order', 'ASC')->get()->toArray();
            }else{
                $group_menu = AdminMenu::whereIn('id', $menuPermissions)->whereIn('module_id', $modules)->where('parent', 0)->where('is_show', 1)->orderBy('menu_order', 'ASC')->get()->toArray();
            }

            $menufinal = array();
            if (!empty($group_menu)) {

                foreach ($group_menu as $gm) {

                    if ($gm['type'] == "item") {
                        $item = [
                            'id' => $gm['menu'],
                            'title' => $gm['name'],
                            'type' => $gm['type'],
                            'icon' => $gm['icon'],
                            'url' => $gm['urlapi']
                        ];
                    } elseif ($gm['type'] == "collapse") {
                        $item = [
                            'id' => $gm['menu'],
                            'title' => $gm['name'],
                            'type' => $gm['type'],
                            'icon' => $gm['icon'],
                        ];
                        if(Auth::user()->role == 1){
                            $sub_menu = AdminMenu::whereIn('module_id', $modules)->where('is_show', 1)->where('parent', $gm['id'])->orderBy('menu_order', 'ASC')->get()->toArray();
                        }else{
                            $sub_menu = AdminMenu::whereIn('id', $menuPermissions)->whereIn('module_id', $modules)->where('is_show', 1)->where('parent', $gm['id'])->orderBy('menu_order', 'ASC')->get()->toArray();
                        }

                        $item['children'] = array();
                        foreach ($sub_menu as $sm) {
                            $submenuchild = [
                                'id' => $sm['menu'],
                                'title' => $sm['name'],
                                'type' => $sm['type'],
                                'icon' => $sm['icon'],
                                'url' => $sm['urlapi']
                            ];
                            array_push($item['children'], $submenuchild);
                        }
                    } else {
                        $item = [
                            'id' => $gm['menu'],
                            'title' => $gm['name'],
                            'type' => $gm['type'],
                            'icon' => $gm['icon'],
                        ];
                        $item['children'] = array();
                        if(Auth::user()->role == 1){
                            $parent_menu = AdminMenu::whereIn('module_id', $modules)->where('is_show', 1)->where('parent', $gm['id'])->orderBy('menu_order', 'ASC')->get()->toArray();
                        }else{
                            $parent_menu = AdminMenu::whereIn('id', $menuPermissions)->whereIn('module_id', $modules)->where('is_show', 1)->where('parent', $gm['id'])->orderBy('menu_order', 'ASC')->get()->toArray();
                        }

                        foreach ($parent_menu as $pm) {
                            if($pm['type'] == "item"){
                                $children = [
                                    'id' => $pm['menu'],
                                    'title' => $pm['name'],
                                    'type' => $pm['type'],
                                    'icon' => $pm['icon'],
                                    'url' => $pm['urlapi']
                                ];
                            }else{
                                if(Auth::user()->role == 1){
                                    $sub_menu = AdminMenu::whereIn('module_id', $modules)->where('is_show', 1)->where('parent', $pm['id'])->orderBy('menu_order', 'ASC')->get()->toArray();
                                }else{
                                    $sub_menu = AdminMenu::whereIn('id', $menuPermissions)->whereIn('module_id', $modules)->where('is_show', 1)->where('parent', $pm['id'])->orderBy('menu_order', 'ASC')->get()->toArray();
                                }

                                $children = [
                                    'id' => $pm['menu'],
                                    'title' => $pm['name'],
                                    'type' => $pm['type'],
                                    'icon' => $pm['icon'],
                                ];
                                $children['children'] = array();
                                foreach ($sub_menu as $sm) {
                                    $submenuchild = [
                                        'id' => $sm['menu'],
                                        'title' => $sm['name'],
                                        'type' => $sm['type'],
                                        'icon' => $sm['icon'],
                                        'url' => $sm['urlapi']
                                    ];
                                    array_push($children['children'], $submenuchild);
                                }
                            }
                            array_push($item['children'], $children);
                        }
                    }
                    array_push($menufinal, $item);
                }
            }
            $res = [
                'message' => 'success',
                'data' => $menufinal,
            ];
        }
        return $this->response('success', $res);
    }


}
