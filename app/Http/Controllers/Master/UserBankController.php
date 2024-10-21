<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\NewHeaderTrait;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MerchantUser;
use App\Models\BankDetails;
use App\Models\BankDetailsData;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use DB;


class UserBankController extends Controller {
    use CommonTrait, NewHeaderTrait;
    
    public function userAccounts(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
            ]);
    
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }       
            $select = ["bd.id as bank_details_id","bd.bank_id",'bd.sender_name','bd.sender_email','bd.sender_phone', 'bd.account_type', 'bd.account_number',"bd.status as bd_status", "bl.name as bl_name", "bl.active_logo as bl_logo", "bl.suffix as bl_suffix"];
              $query =   DB::connection('pgsql')->table('bank_details as bd')->where('user_id', $request->user_id);
              $query->where('bd.status', 1);
              $query->select($select);
              if($request->bank_id !="") {
                $query->where('bd.bank_id', $request->bank_id);
              }
              $query->join('bank_lists as bl', 'bl.id', '=', 'bd.bank_id');
              $count = $query->count();
              $data = $query->get()->toArray();
            return response()->json(['count'=>$count,'details'=>$data]);



        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        } 
    }
    public function changeStatus(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'bank_details_id' => 'required',
                'status' => 'required|in:0,1'
            ]);
    
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $bankDetails = BankDetails::find($request->bank_details_id);
            $bankDetailsData = BankDetailsData::where('bank_details_id', $request->bank_details_id)->first();
            if($bankDetails && $bankDetailsData) {
                if($bankDetails->status == $bankDetailsData->status) {
                    $bankDetails->status = $request->status;
                    $bankDetailsData->status = $request->status;
                    $bankDetails->save();
                    $bankDetailsData->save();
                    return $this->response('success', ['message' => 'Status updated successfully!']);
                }
                else {
                    return $this->response('incorrectinfo',['message' => 'Status did not matched!']);
                }
            }
            else {
                return $this->response('incorrectinfo',['message' => 'Unable to change status']);
            }

        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        } 
    }
}