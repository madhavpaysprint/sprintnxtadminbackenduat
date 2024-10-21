<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Models\AdminMenu;
use App\Models\AdminMenuPermission;
use App\Models\ModulePermission;
use App\Models\Otp;
use App\Models\RoleModule;
use Illuminate\Http\Request;
use App\Models\Userloginlog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use App\Libraries\Common\Crypt;
use App\Models\UserPasswordDetails as UserPassword;
class RegisterController extends Controller
{
    use CommonTrait;

    public function register(Request $request)
    {
        try {
                $validatorArray = [
                    'fullname'  => 'required',
                    "email"     => 'required|email|min:8|max:50|unique:users',
                    "phone"     => 'required|digits:10|unique:users',
                    "role"      => 'required|numeric|exists:roles,id', 
                ];
                $messagesArray = [
                    'email.required'=>'Email Id is Required!',
                    'email.unique'=>'Email Id has already been taken.',
                    'phone.required'=>'Mobile Number is Required!',
                    'phone.unique'=>'Mobile Number has already been taken.'
                ];
            $validator = Validator::make($request->all(), $validatorArray, $messagesArray);
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $password = $this->randomPassword();
            $user               = new User;
            $username = 'NXTADM' . rand(111, 999);
            $data = ["fullname"=>$request->fullname,"email"=>$request->email,"phone"=>$request->phone];
            $crypt = new Crypt();
            $passphrase = config('constant.crypt_key');
            $enc = $crypt->cryptAesEncrypt($passphrase,$data);   
            $user->fullname     = $request->fullname;
            $user->username     = $username;
            $user->email        = $request->email;
            $user->phone        = $request->phone;
            $user->role         = $request->role; 
            $user->status       = '1';
            $user->remember_token = base64_encode($enc);
            $user->ipaddress      = $request->ip();
            $user->save();

            $insertId = $user->id;
            if($insertId){
                $user_password              = new UserPassword;
                $user_password->user_id     = $insertId;
                $user_password->password    = Hash::make($password);
                $user_password->expired_at  = date('Y-m-d', strtotime("+30 days"));
                $user_password->status     = 1;
                $user_password->save();

                $getModules = RoleModule::where('role_id',$request->role)->first();
                if($getModules){
                ModulePermission::insert(['module_id' => $getModules->modules, 'user_id' => $insertId, 'status' => 1]);
                  $modules = explode(",",$getModules->modules);
                  foreach($modules as $module){
                    $menuItems = AdminMenu::where('module_id',$module)->get()->toArray();
                    if(!empty($menuItems)){
                        foreach($menuItems as $menuItem){
                            $menuPermission = new AdminMenuPermission();
                            $menuPermission->user_id = $insertId;
                            $menuPermission->menu_id = $menuItem['id'];
                            $menuPermission->status = 1;
                            $menuPermission->save();
                        }
                    }
                  }
                }
            }
            return $this->response('success', ['message' => 'user registered successfully']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    /*************************User Send Otp*************************** */
    public function userRegisterSendOtp(Request $request)
    {
        try {
            $validatorArray = [
                'fullname'  => 'required',
                "email"     => 'required|email|min:8|max:50|unique:users',
                "phone"     => 'required|digits:10|unique:users',
                "role"      => 'required|numeric|exists:roles,id',
                'lat'       => 'required',
                'lng'       => 'required'

            ];

            $validator = Validator::make($request->all(), $validatorArray);
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $username = 'NXTADM001' . rand(111, 999);
            $genOtp   =  rand(111, 9999);
            User::create(['fullname' => $request['fullname'], 'username' => $username, 'email' => $request['email'], 'phone' => $request['phone'], 'lat' => $request['lat'], 'lng' => $request['lng'], 'role' => 4]);

            Otp::create(['otp' => $genOtp, 'name' => $request['phone']]);
            // return  $this->response('success', ['message' => 'User  added successfully!']);
            return  $this->response('success', ['message' => 'OTP send successfully!', 'otp' => $genOtp, 'is_user' => '0']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    /*************************User Verify Otp*************************** */
    public function userVerifyOtp(Request $request)
    {
        try {
            $validatorArray = [
                'fullname'          => 'required',
                'email'             => 'required',
                'phone'             => 'required',
                'otp'               => 'required',
                'lat'               => 'required',
                'lng'               => 'required',
            ];
            $validator = Validator::make($request->all(), $validatorArray);
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $credentials['name'] = $request->phone;
            $credential['email'] = $request->email;
            $credential['phone'] = $request->phone;
            $Otp = $request->otp;
            $agent = $request->server('HTTP_USER_AGENT');
            $location = $request->lat . "," . $request->lng;
            $userdetails = User::select('id')->where($credential)->first();
            $token = Auth::login($userdetails);
            Userloginlog::create(['userid' => Auth::user()->id, 'ipaddress' => $request->ip(), "latlng" => $location, 'device_name' => $agent]);
            $geneOtp = Otp::select('id', 'name', 'otp', 'status', 'created_at')->where($credentials)->where('status', 0)->orderBy('created_at', 'desc')->first();

            if (!empty($geneOtp)) {
                if ($geneOtp->otp == $Otp) {
                    $otp = Otp::where('id', $geneOtp->id)->update(['status' => 1]);
                    if (CommonTrait::is_expired($geneOtp->created_at, 5)) {
                        return $this->response('incorrectinfo', ['message' => 'otp expired']);
                    } else {
                        return $this->response('success', ['message' => 'Otp verify successfully!!', 'is_user' => '1', 'authtoken' => $token]);
                    }
                } else {
                    return $this->response('incorrectinfo', ['message' => 'Invalid OTP!!']);
                }
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

     /*************************User Set Password*************************** */
    public function userSetPassword(Request $request)
    {
        try {
            $validatorArray = [
                "phone"     => 'required|exists:users,phone',
                'password'  => ['required', 'confirmed', Password::min(8), 'regex:/^(?=.*[a-z]){2,}(?=.*[A-Z])(?=.*\d)(?=.*(_|[^\w])).+$/']

            ];
            $messagesArray = [
                'password.regex' => 'Password must be of 8 characters Atleast 1 alphabets must be in upper case Atleast 1 letters must be in lower case Must be atleast 1 numeric.'
            ];
            $validator = Validator::make($request->all(), $validatorArray, $messagesArray);
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            User::where('phone', $request->phone)->update(["password" => Hash::make($request->password)]);
            return $this->response('success', ['message' => 'Password has been set!!','is_user'=>'2']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }


    
}
