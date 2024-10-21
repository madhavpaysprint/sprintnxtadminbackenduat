<?php

namespace App\Http\Controllers\User;

use App\Models\ApiConfig;
use App\Models\ApiCredential;
use App\Models\ApiUser;
use App\Models\DynamicForm;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\HeaderTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use CommonTrait,HeaderTrait;
    public function __construct() {
        $this->status = ['0' => "Pending", '1' => "Active", '2' => "Deactive"];
    }
    public function listUsers(Request $request)
    {
        try {
            $search = $request->search;
            $orderby= $request->orderby;
            $order  = $request->order;
            $searchColumn = ['users.id', 'users.status', 'users.fullname', 'users.username', 'users.email', 'users.phone', 'users.role','roles.name'];
            $select = ['users.id as user_id', 'users.status as status', 'users.fullname', 'users.username', 'users.email', 'users.phone', 'users.role as role_id','users.created_at','roles.name as role'];
            $query = User::select($select)->join('roles', 'roles.id', '=', 'users.role');
            $totalCount = $query->count();
            if(!empty($search)){
                $query->where(function($query) use ($searchColumn, $search){
                    foreach($searchColumn as $column){
                        $query->orwhere($column, 'like', '%' .  trim($search) . '%');
                    }
                });
            }
            (!empty($orderby) && !empty($order))? $query->orderBy("users.".$orderby, $order): $query->orderBy("users.id", "desc");
            $length = (!empty($request->length))? $request->length: 20;
            $start  = (!empty($request->start))? $request->start: 0;
            $list   = $query->skip($start)->take($length)->get();
            foreach($list as $key => $val){
                $list[$key]->status = $this->status[$val->status];
                $list[$key]->created = date('d-m-Y',strtotime($val->created_at));
            }
            $count  = count($list);
            $header = $this->users();
            $details = [
                "message" => "List fetched",
                "recordsFiltered" => $count,
                "recordsTotal" => $totalCount,
                "header" => $header,
                "data" => $list
            ];
            return $this->response('success', $details);
        } catch (\Exception $e) {
            return  $this->response('internalservererror', ['message' => $e->getMessage()]);
        }
    }
    public function getUser(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'user_id'   => 'required',
            ]);
            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            }
            $user =  User::find($request->user_id);
            if($user){
                return $this->response('success',['data' => $user, 'message' => 'Details fetched successfully!']);
            }else{
                return $this->response('incorrectinfo');
            }

        } catch (\Exception $e) {
            return  $this->response('internalservererror', ['message' => $e->getMessage()]);
        }
    }

    public function updateUser(Request $request)
    {
        try {
            $validated = Validator::make($request->all(), [
                'user_id'   => 'required',
                'fullname'  => 'required',
                "email"     => 'required|email|min:8|max:50',
                "phone"     => 'required|digits:10',
                "role"      => 'required|numeric|exists:roles,id',
                "status"    => 'required'
            ]);
            if ($validated->fails()) {
                $message   = $this->validationResponse($validated->errors());
                return $this->response('validatorerrors', $message);
            }

            $userwithsameMobile =  User::select('id')->where("phone", $request['phone'])->first();
            if (!empty($userwithsameMobile) && $userwithsameMobile->id != $request['user_id']) {
                return  $this->response('notvalid', ['message' => 'Mobile number already exist !']);
            }

            $userwithsameEmail =  User::select('id')->where("email", $request['email'])->first();
            if (!empty($userwithsameEmail) && $userwithsameEmail->id != $request['user_id']) {
                return  $this->response('notvalid', ['message' => 'Email address already taken !']);
            }

            $user               = User::find($request['user_id']);
            $user->fullname     = $request->fullname;
            $user->email        = $request->email;
            $user->phone        = $request->phone;
            $user->role         = $request->role;
            $user->status       = $request->status;
            $result = $user->update();
            if ($result) {
                return $this->response('success', ['message' => 'user updated successfully']);
            } else {
                return  $this->response('apierror', ['message' => 'Something went wrong!']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function searchUser(Request $request)
    {
        try {
            $search = $request->input('searchvalue');
            if($search && strlen($search) > 3){
                $users = DB::connection('pgsql')->table('users')->select('id','fullname','email','phone','username')
                ->where(function($query) use ($search){
                    $query->where('fullname', 'like',  $search . '%');
                    $query->orWhere('email', 'like',  $search . '%');
                    $query->orWhere('phone', 'like',  $search . '%');
                    $query->orWhere('username', 'like',  $search . '%');
                })->where('status',1)->orderBy('id','ASC')->limit(5)->get();

                return $this->response('success', ['data' => $users]);
            }else{
                return $this->response('noresult');
            }


        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function userCredentials(Request $request){
        try {
            $userId = $request->user_id;

            $data = ApiUser::with(['apiConfig', 'apiCredential'])
                ->where(['id'=> $userId, 'status' => 1])->get();

            $finalData = $data->map(function ($user) {
                $userInfo = [
                    "ip" => null,
                    "client_id" => null,
                    "client_secret" => null,
                    "enciv" => null,
                    "enckey" => null,
                    "status" => null,
                    "callback" => null,
                    "previous_callback" => null,
                ];

                if (!is_null($user->apiConfig) && is_object($user->apiConfig)) {
                    $userInfo['ip'] = $user->apiConfig->ip;
                    $userInfo['status'] = $user->apiConfig->status;
                    $userInfo['callback'] = $user->apiConfig->callback;
                    $userInfo['previous_callback'] = $user->apiConfig->previous_callback;
                }

                if (!is_null($user->apiCredential) && is_object($user->apiCredential)) {
                    $userInfo['client_id'] = $user->apiCredential->client_id;
                    $userInfo['client_secret'] = $user->apiCredential->client_secret;
                    $userInfo['enciv'] = $user->apiCredential->enciv;
                    $userInfo['enckey'] = $user->apiCredential->enckey;
                    $userInfo['public_key'] = $user->apiCredential->public_key;
                }
                return $userInfo;
            });

            if(empty($finalData) || count($finalData) <= 0){
                return $this->response('norecordfound');
            }
            return $this->response('success', ['message' => "Success.", 'data' => $finalData[0]]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function userCredentialsUpdate(Request  $request){
        try{
            $validator = Validator::make($request->all(), [
                'user_id' => 'required'
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $userId = $request->user_id;
            $publicKey = $request->public_key;
            $callbackUrl = $request->callback_url;

            $apiCredentialCheck = ApiCredential::where('userid', $userId)->first();
            $apiConfigCheck = ApiConfig::where('userid', $userId)->first();

            if (!$apiCredentialCheck && !$apiConfigCheck) {
                return $this->response('notvalid', ['message' => "UserID is not Found"]);
            }

            $apiCredential = ApiCredential::where('userid', $userId)->update([
                "public_key" => $publicKey,
            ]);
            $apiConfig = ApiConfig::where('userid', $userId)->update([
                "callback" => $callbackUrl
            ]);

            if (!$apiCredential && !$apiConfig) {
                return $this->response('updateError');
            }
            return $this->response('success', ['message' => "Data updated Successfully."]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function DynamicForm(Request $request){
        try {
            $validator = Validator::make($request->all(), [
                'bank_id' => 'required|integer',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $data = $request->all();
            $bank_id = $data['bank_id'];

            unset($data['bank_id']);

            $common_json = json_encode($data['common_json']);

            if (!$bank_id) {
                return $this->response('updateError');
            }

            $dynamicFormConfig = DynamicForm::updateOrCreate(
                ['bank_id' => $bank_id],
                ['common_json' => $common_json]
            );
            return $this->response('success', ['message' => "Success."]);

        }catch (\Exception $e) {
            return $this->response('internalservererror', ['message' => $e->getMessage()]);
        }
    }
}
