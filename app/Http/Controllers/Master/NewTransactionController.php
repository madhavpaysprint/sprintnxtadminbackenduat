<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\NewHeaderTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Transaction;
use App\Models\Payout;
use App\Models\User;
use App\Models\MerchantUser;
use Datetime;
use DB;

class NewTransactionController extends Controller
{
    use CommonTrait, NewHeaderTrait;

    public function allTransactions(Request $req) {
        try {
            $transaction_type = $req->transaction_type;
            $service = $req->service;
            $startdate = $req->startdate ?? date('Y-m-d'); 
            $enddate = $req->enddate ?? date('Y-m-d'); 
            $searchby = $req->searchby;
            $searchvalue = $req->searchvalue; 

            $query = DB::connection('pgsql')->table('transactions')
                       ->join('users', 'transactions.user_id', '=', 'users.id')
                       ->select('transactions.*', 'users.username as user_name', 'users.fullname')
                       ->where('transactions.wallet_type', 1)
                       ->orderBy('id', 'desc'); // newly added

            $query->when(!empty($startdate) && !empty($enddate), function ($q) use ($startdate, $enddate) {
                $q->whereDate('transactions.date', '>=', $startdate);
                $q->whereDate('transactions.date', '<=', $enddate);
            });


            if (!empty($transaction_type)) {
                $query->where('transactions.transaction_type', $transaction_type);
            }
            else {
                $query->whereIn('transactions.transaction_type', [1, 2]);
            }
            if (!empty($service)) {
                $query->where('transactions.service', $service);
            }

            if (!empty($searchvalue) && !empty($searchby)) {
                $query->where(function($q) use ($searchvalue, $searchby) {
                    switch ($searchby) {
                        case 'transaction_id':
                            $q->where('transactions.txn_id', $searchvalue);
                            break;
                        case 'service_name':
                            $q->where('transactions.service_name', $searchvalue);
                            break;
                        case 'username':
                            $q->where('users.username',$searchvalue);
                            break;
                        default:
                            break;
                    }
                });
            }

            

            $length = (!empty($req->length)) ? $req->length : 20;
            $start = (!empty($req->start)) ? $req->start : 0;
            $totalCount = $query->count();
            
            
            $trans = $query->skip($start)->take($length)->get();

            $trans->transform(function ($transaction) {
                $transaction->wallet_type = $transaction->wallet_type == 1 ? 'Prepaid' : $transaction->wallet_type;
                $transaction->transaction_type = $transaction->transaction_type == 1 ? 'Credit' : ($transaction->transaction_type == 2 ? 'Debit' : $transaction->transaction_type);
                $transaction->is_setteled = $transaction->is_setteled == 1 ? 'Yes' : ($transaction->is_setteled == 0 ? 'No' : $transaction->is_setteled);
                $transaction->date = Carbon::parse($transaction->date)->format('d-m-y'); // Format date
                $transaction->time = Carbon::parse($transaction->created_at)->format('h:i:s A'); // Format time
                
                return $transaction;
            });
            $count = $trans->count();
            $header = $this->transactions();

            $details = [
                'message' => "Data fetched successfully",
                'recordsFiltered' => $count,
                'recordsTotal' => $totalCount,
                'header' => $header,
                'data' => $trans,
            ];
            return $this->response('success', $details);
    
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }


    public function exportAllTransactions(Request $req){
        try {
            $transaction_type = $req->transaction_type;
            $service = $req->service;
            $startdate = $req->startdate ?? date('Y-m-d'); 
            $enddate = $req->enddate ?? date('Y-m-d'); 
            $searchby = $req->searchby;
            $searchvalue = $req->searchvalue;

            $query = DB::connection('pgsql')->table('transactions')
            ->join('users', 'transactions.user_id', '=', 'users.id')
            ->select('transactions.*', 'users.username as user_name', 'users.fullname')
            ->where('transactions.wallet_type', 1)
            ->orderBy('id', 'desc'); // newly added

            $query->when(!empty($startdate) && !empty($enddate), function ($q) use ($startdate, $enddate) {
            $q->whereDate('transactions.date', '>=', $startdate);
            $q->whereDate('transactions.date', '<=', $enddate);
            });

            if (!empty($transaction_type)) {
                $query->where('transactions.transaction_type', $transaction_type);
            } else {
                $query->whereIn('transactions.transaction_type', [1, 2]);
            }

            if (!empty($service)) {
                $query->where('transactions.service', $service);
            }

            if (!empty($searchvalue) && !empty($searchby)) {
                $query->where(function($q) use ($searchvalue, $searchby) {
                    switch ($searchby) {
                        case 'transaction_id':
                            $q->where('transactions.txn_id', $searchvalue);
                            break;
                        case 'service_name':
                            $q->where('transactions.service_name', $searchvalue);
                            break;
                        case 'username':
                            $q->where('users.username', $searchvalue);
                            break;
                        default:
                            break;
                    }
                });
            }

            // Get the data
            $trans = $query->get();

            if ($trans->isEmpty()) {
                return $this->response('noresult', ['message' => "No records found.", 'data' => []]);
            }

            // Transform the data
            $data = [];
            foreach ($trans as $transaction) {
                $txn_date = new DateTime($transaction->date);
                $sub_array = [
                    'TXN_ID' => $transaction->txn_id,
                    'BUSINESS_NAME' => $transaction->fullname,
                    'USER_NAME' => $transaction->user_name,
                    'SERVICE' => $transaction->service_name,
                    'AMOUNT' => $transaction->amount,
                    'DATE' => Carbon::parse($transaction->date)->format('d-m-y'),
                    'TIME' => Carbon::parse($transaction->created_at)->format('h:i:s A'),
                    'OPENING' => $transaction->opening,
                    'CLOSING' => $transaction->closing,
                    'WALLET_TYPE' => $transaction->wallet_type == 1 ? 'Prepaid' : 'Unknown',
                    'TRANSACTION_TYPE' => $transaction->transaction_type == 1 ? 'Credit' : ($transaction->transaction_type == 2 ? 'Debit' : 'Unknown'),
                    'IS_SETTLED' => $transaction->is_setteled == 1 ? 'Yes' : 'No'
                ];
                $data[] = $sub_array;
            }


            // Return the response
            return $this->response('success', [
                'message' => "Data exported successfully",
                'data' => $data
            ]);

        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }


    public function updatePayoutStatus(Request $req) {
        $validator = Validator::make($req->all(), [
            'transfer_id' => 'required', 
            'status' => 'required|in:1,4',
            'remarks' => 'required',
            'lat' => 'required',
            'lng' => 'required',
            'utr_rrn' => 'required_if:status,1',
            'transaction_ref_no' => 'required_if:status,1'
        ], [
            'utr_rrn.required_if' => 'UTR is required for successful transactions',
            'transaction_ref_no.required_if' => 'Transaction Ref no is required for successful transactions'
        ]);
        if ($validator->fails()) {
            $message = $this->validationResponse($validator->errors());
            return $this->response('validatorerrors', $message);
        }
        try {
                $transfer_id = $req->transfer_id;
                $status = $req->status;
                $remarks = $req->remarks; 
                $payout = Payout::where('transferId', $transfer_id)->first();
                $amount = $payout->amount ?? 0; 
                $charge = $payout->charge ?? 0;
                // if($payout->status == 2  || $payout->status == 3 || $payout->status == 6) { Initially was this
                if($payout->status == 3 || $payout->status == 6) {
                    $mode = $payout->mode;
                    $username = $payout->users->username;
                    $st = $status == 1 ? "Success" : "Failed";
                    $narration = $st.": Amount Rs." . $amount . " to " . $username;
                    $payout->status = $status;
                    $payout->updated_by = Auth::id();
                    $narration .= " Payout status updated by " . Auth::user()->email . " from this location: ". $req->lat . "/" . $req->lng;
                    $payout->narration = $narration;
                    if($status == 1) {
                        if($req->utr_rrn == "") {
                            return $this->response('notvalid', "UTR RRN can not be empty!");
                        }
                        if($req->transaction_ref_no == "") {
                            return $this->response('notvalid', "Transaction Ref no can not be empty!");
                        }
                        $payout->utr_rrn  = $req->utr_rrn;
                        $payout->transaction_ref_no  = $req->transaction_ref_no;
                    }
                    if($status == 4) {
                        // return $payout->userid."-".$charge."-".$remarks."-".$mode;
                        $transaction = $this->makeRefundTransaction($payout->userid, $charge, $remarks, $mode);
                        if(!$transaction) {
                            return $this->response('notvalid', ['message' => 'Unable to make transaction due to low balance or wallet mismatch!']);
                           }
                        $payout->refunded_txn_charge_id = $transaction->id;
                        $payout->is_refunded = 1;
                        $payout->refunded_datetime = Carbon::now();
                    }
                    $payout->remarks = $remarks;
                    $payout->save();
                    return $this->response('success', [
                        'message' => 'Status updated successfully.',
                    ]);
                }
                else {
                    return $this->response('notvalid');
                }
        }
        catch(\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function makeRefundTransaction($user_id, $amt, $remarks, $mode) {
        $balance = $this->checkBalance($user_id, 1, $amt );
        if($balance['status'] == false) {
            return false;
        }
        // return json_encode($balance);
        $merchant = MerchantUser::find($user_id);
        if($merchant->balance == $balance['opening']) {
            $transaction = new Transaction();
            $transaction->user_id = $user_id;
            $transaction->service = strtolower(str_replace(' ', '_', $mode));
            $transaction->service_name = $mode;
            $transaction->service_type = "Payout";
            $transaction->amount = $amt;
            $transaction->transaction_type = 1;
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
        // return  array("opening" => $wallet->balance, "closing" => $wallet->balance);
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

}