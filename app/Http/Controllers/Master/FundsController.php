<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\NewHeaderTrait;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MerchantUser;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use App\Libraries\Common\Otps;
use App\Models\ManualFund;
use App\Models\Otp;
use Carbon\Carbon;

class FundsController extends Controller
{
    use CommonTrait, NewHeaderTrait;

    public function manualFundingInitial(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'amount' => 'required',
                'type' => 'required|in:1,2',
                'remarks' => 'required'
            ]);
    
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $user = MerchantUser::find($request->user_id);
            // return json_encode($user);
            $user_balance = $user->balance;
            if(($user->balance < $request->amount) && $request->type == 2) {
                return $this->response('incorrectinfo',['message' => 'Not enough balance to perform this operation']);
            }
            $sendOtp = $this->sendOtp();
            if($sendOtp) {
                return $this->response('success', ['message' => 'OTP send successfully']);
            }    
            else {
                return $this->response('internalservererror', ['message' => "Unknown error occured!"]);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        } 
    }

    


    public function manualFundingFinal(Request $request) {
        // Validate OTP
        try {
            $validatorArray = [
                'otp' => 'required',
                'user_id' => 'required',
                'amount' => 'required',
                'type' => 'required|in:1,2',
                'remarks' => 'required'
            ];
            $validator = Validator::make($request->all(), $validatorArray);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            
            $checkOtp = Otp::where('name', Auth::user()->email)->where('status', 1)->where('otptype', 'funding')->where('otp', $request->otp)->orderBy('created_at', 'desc')->first();
            if ($checkOtp) {
               $txn = $this->makeTransaction($request->user_id, $request->amount, $request->remarks, $request->type);
               if(!$txn) {
                    return $this->response('notvalid', ['message' => 'Unable to make transaction due to low balance or wallet mismatch!']);
                }
                $checkOtp->status = 0;
                $checkOtp->save();

                $manualFund = new ManualFund();
                $manualFund->user_id = $request->user_id;
                $manualFund->transaction_id = $txn->id;
                $manualFund->amount = $request->amount;
                $manualFund->type = $request->type;
                $manualFund->opening = $txn->opening;
                $manualFund->done_by = Auth::id();
                $manualFund->narration = Auth::user()->email.  ' has done manual funding of amount ' . $request->amount;
                $manualFund->closing = $txn->closing;
                $manualFund->remarks = $request->remarks;
                $manualFund->save();
                return $this->response('success', ['message' => 'Funds updated successfully']);

            }
            else {
                return $this->response('incorrectinfo', ['message' => 'Invalid OTP!!']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    public function makeTransaction($user_id, $amt, $remarks, $type) {
        $balance = $this->checkBalance($user_id, $type, $amt );
        if($balance['status'] == false) {
           return false;
        }
        $merchant = MerchantUser::find($user_id);
        if($merchant->balance == $balance['opening']) {
            $transaction = new Transaction();
            $transaction->user_id = $user_id;
            $transaction->service = "Funding";
            $transaction->service_name = "Manual Funding";
            $transaction->service_type = "Funding";
            $transaction->amount = $amt;
            $transaction->transaction_type = $type;
            $transaction->wallet_type = $balance['wallet_type'];
            $transaction->opening = $balance['opening'];
            $transaction->closing = $balance['closing'];
            $transaction->remarks = $remarks;
            $transaction->date = date('Y-m-d');
            $transaction->time = date("h:i:s");
            $transaction->save();  
            $merchant->balance = $balance['closing'];
            $merchant->save();
            return $transaction;
        }
        else {
            return array("status" => false, "message" => "Wallet mismatch!");
        }
    }


    public static function checkBalance($user_id, $txn_type, $value )
    {
        $wallet = MerchantUser::select('balance', 'wallet_type')->where('id', $user_id)->first();
        $chargevaluevalue = $value;
        if ($wallet->wallet_type == 1) {
            $wallet_balance = $wallet->balance;
            $lastTransaction = Transaction::where('user_id',$user_id)->where('wallet_type', 1)->orderBy('id', 'DESC')->first();

            if ($lastTransaction && $lastTransaction->closing != $wallet_balance) {
                return array("status" => false, "message" => "Wallet mismatch!");
            } elseif ($chargevaluevalue > $wallet_balance-100 && ($txn_type == 2)) {
                return array("status" => false, "message" => "Wallet balance is low. Kindly add funds in wallet to continue.");
            } else {
                $opening = $wallet_balance;
                
                if ($txn_type == 1) {
                    $closing = $opening + $chargevaluevalue;
                } else {
                    $closing = $opening - $chargevaluevalue;
                }
            }
        } elseif ($wallet->wallet_type == 2) {
            $transaction = Transaction::where('user_id',$user_id)->whereYear('created_at', date('Y'))
                ->whereMonth('created_at', date('m'))
                ->where('wallet_type', 2)
                ->orderBy('id', 'DESC')->first();
            if ($transaction) {
                $opening = $transaction->closing;
            } else {
                $opening = 0.00;
            }
            if ($txn_type == 1) {
                $closing = $opening - $chargevaluevalue;
            } else {
                $closing = $opening + $chargevaluevalue;
            }
        } else {
            return array("status" => false, "message" => "Kindly set your wallet first!");
        }
        $wallet = [
            'status' => true,
            'wallet_type' =>$wallet->wallet_type,
            'opening' => $opening,
            'closing' => $closing,
        ];
        return $wallet;
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



    
    private function sendOtp() {
        // send otp to user
        $otp = $this->sendverificationotp(["name" => Auth::user()->email,'sendOnEmail' => Auth::user()->sendOnEmail,'sendOnPhone' => Auth::user()->sendOnPhone,'fullname'=>Auth::user()->fullname,'phone'=>Auth::user()->phone, "isSend" => 1, 'otptype' => 'funding']);
        return $otp;
       
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


    public function manualFundingHistory(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
            ]);
    
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
    
            $funds = ManualFund::where('user_id', $request->user_id)->get();
    
            $merchantUsers = MerchantUser::whereIn('id', $funds->pluck('user_id'))->get()->keyBy('id');
            $adminUsers = User::whereIn('id', $funds->pluck('done_by'))->get()->keyBy('id');
    
            $funds->transform(function ($fund) use ($merchantUsers, $adminUsers) {
                $merchantUser = $merchantUsers[$fund->user_id] ?? null;
                $doneByUser = $adminUsers[$fund->done_by] ?? null;
    
                $fund->type = $fund->type == 1 ? 'Credit' : 'Debit'; 
                $fund->created_at = Carbon::parse($fund->created_at)->format('d-m-Y'); 
                $fund->username = $merchantUser->username ?? null;
                $fund->fullname = $merchantUser->fullname ?? null;
    
                $fund->done_by_username = $doneByUser->username ?? null;
                $fund->done_by_email = $doneByUser->email ?? null;
    
                return $fund;
            });
    
            $count = $funds->count();
            $header = $this->fundHistory();
    
            $details = [
                'message' => "Data fetched successfully",
                'recordsFiltered' => $count,
                'recordsTotal' => $count,
                'header' => $header,
                'data' => $funds,
            ];
    
            return $this->response('success', $details);
    
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    
    
    
}
