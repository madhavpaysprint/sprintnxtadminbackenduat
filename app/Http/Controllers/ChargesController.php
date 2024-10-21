<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Charge;
use App\Models\BankList;
use App\Models\BankDetails;
use App\Models\ChargesDefault;
use App\Http\Traits\CommonTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ChargesController extends Controller
{
    use CommonTrait;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct() {
        $this->services=['sound_box', 'transaction', 'balance_fetch','bank_statement','va_creation','vpa_creation','payout','imps','neft','rtgs','upi','va_transactions','upi_transactions'];
        $this->service_types=['sound_box' => 'single','transaction' => 'single','balance_fetch'=>'single','bank_statement'=>'single','va_creation'=>'single','vpa_creation'=>'single','payout'=>'single','imps'=>'slab','neft'=>'slab','rtgs'=>'slab','upi'=>'slab','va_transactions'=>'slab','upi_transactions'=>'slab'];
        $this->service_names=['sound_box' => 'Sound Box','transaction' => 'Transaction','balance_fetch'=>'Balance Fetch','bank_statement'=>'Bank Statement','va_creation'=>'VA Creation','vpa_creation'=>'VPA Creation','payout'=>'Bene Creation','imps'=>'IMPS','neft'=>'NEFT','rtgs'=>'RTGS','upi'=>'UPI','va_transactions'=>'VA Trancation','upi_transactions'=>'UPI Trancation'];
    }
    public function defaultCharges(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'bank_id' => 'required',
            ]);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $results = array();
            foreach ($this->services as $service) {
                $charges = ChargesDefault::where('bank_id', $request->bank_id)
                                         ->where('service', $service)
                                         ->first();
    
                if ($this->service_types[$service] == 'single') {
                    $commission = !empty($charges) ? $charges->commision : "";
                    $chargeType = !empty($charges) ? $charges->single_charge_type : "Flat";
                    
                    $results["comm"][$service] = $commission;
                    $results["comm"][$service . '_chargeType'] = $chargeType;
                } else {
                    $commission = !empty($charges) ? json_decode($charges->commision, true) : array();
                    $results["comm"][$service] = $commission;
                }
            }
            $details = [
                'data'    => $results,
                'message' => "Charges Fetched successfully",
            ];
            return $this->response('success', $details);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    public function updateDefaultCharges(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'bank_id' => 'required',
            ]);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            foreach ($this->services as $service) {
                if (isset($request->$service)) {
                    $commision = $request->$service;
    
                    if (in_array($service, ['sound_box', 'transaction']) && $request->bank_id == 2) {
                        $single_charge_duration = 'Month';
                    }
                    else {
                        $single_charge_duration = null;
                    }
                    if ($this->service_types[$service] == 'single') {
                        $chargeTypeKey = $service.'_chargeType';
                        $chargeType = $request->$chargeTypeKey; 
                    }
                    ChargesDefault::updateOrInsert(
                        ['service' => $service, 'bank_id' => $request->bank_id],
                        [
                            'service_name' => $this->service_names[$service],
                            'type' => $this->service_types[$service],
                            'commision' => $commision,
                            'single_charge_type' => $chargeType,
                            'single_charge_duration' => $single_charge_duration ,
                        ]
                    );
                }
            }
            return $this->response('success', ['message' => "Charges Updated"]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    public function getcharges(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'bank_id' => 'required',
                'user_id' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $results = array();
            foreach($this->services as $service){
                $charges = Charge::where('bank_id',$request->bank_id)->where('userid',$request->user_id)->where('service',$service)->first();
                if(!$charges){
                    $charges = ChargesDefault::where('bank_id',$request->bank_id)->where('service',$service)->first();
                }
                if($this->service_types[$service] == 'single'){
                    $commission = !empty($charges)?$charges->commision:"";
                }else{
                    $commission = !empty($charges)?json_decode($charges->commision, true):array();
                }
                $results["comm"][$service] = $commission;
            }
            $details   = [
                'data'         => $results,
                'message'      => "Charges Fetched successfully",
            ];
            return $this->response('success', $details);
        } catch (\Throwable $th) {
            return  $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    } 
    public function updateCharges(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'bank_id' => 'required',
                'user_id' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            foreach($this->services as $service){
                if(isset($request->$service)){
                    if($this->service_types[$service] == 'single'){
                        $commision = $request->$service;
                    }else{
                        $commision = $request->$service;
                    }
                    Charge::updateOrInsert(
                        ['service' => $service, 'bank_id' => $request->bank_id,'userid' => $request->user_id],
                        ['service_name' => $this->service_names[$service],'type'=>$this->service_types[$service],'commision'=> $commision]
                    );
                }
            } 
            return $this->response('success', ['message'=>"Charges Updated"]);
        } catch (\Throwable $th) {
            return  $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    public function searchUser(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'searchvalue' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
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

    public function activeBanks(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $user = DB::connection('pgsql')->table('users')->where('id',$request->user_id)->first();
            if($user){
                $banks = array();
                $linkedaccounts = BankDetails::select('bank_id','user_id')->where('user_id',$request->user_id)->groupBy('bank_id')->get();
                foreach($linkedaccounts as $linkedaccount){
                    $bankDetails = BankList::select('id','name','logo')->where('id',$linkedaccount->bank_id)->first();
                    $banks[] = $bankDetails->toArray();
                }
    
                return $this->response('success', ['data' => $banks]);
            }else{
                return $this->response('incorrectinfo');
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
}
