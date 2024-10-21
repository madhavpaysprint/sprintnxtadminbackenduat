<?php
namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\BankKeys;
use App\Models\BankList;
use App\Models\DynamicForm;
use App\Models\PayoutBankdetailsDynamicForm;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Beneficiry;
use App\Libraries\Common\Guzzle;
use App\Libraries\Common\Curl;
use App\Libraries\Common\Charges;
use App\Models\Payout;
use App\Models\BankDetails;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\HeaderTrait;

class PayoutController extends Controller
{
    use CommonTrait, HeaderTrait;
    public function __construct()
    {
        $this->status = ['0' => 'Deactive', '1' => 'Active', '2' => 'Pending'];
        $this->Stmtstatus = ['0' => 'Refund', '1' => 'Success', '2' => 'Process', '3' => 'Pending', '4' => 'Failed'];
        $this->serviceType = ['1' => 'Penny', '2' => 'Payout'];
        $this->StmtstatusFinal = ['1' => 'Success', '2' => 'Initiated', '3' => 'Qr Generated', '4' => 'Qr Expired', '5' => 'Failed',  '6' => 'Pending/Dimmed'];
        $this->paymentStatus = [
            '0' => 'Initiated',
            '1' => 'Success',
            '2' => 'Pending',
            '3' => 'Send to bank',
            '4' => 'Failure',
            '6' => 'Processed'
        ];
        $this->type = ['1' => 'IMPS', '2' => 'NEFT', '3' => 'RTGS', '4' => 'UPI'];
        $this->types = ['1' => 'imps', '2' => 'neft', '3' => 'rtgs', '4' => 'upi'];
        $this->pstatus = ['0' => 'Refund', '1' => 'Success', '2' => 'Process', '3' => 'Pending'];
    }
    
    public function list(Request $request)
    {
        try {
            $search = $request->search;
            $orderby = $request->orderby;
            $order = $request->order;
            $startdate = $request->startdate;
            $enddate = $request->enddate;

            $userid = $request->user_id;
            $bankid = $request->bank_id;
            $searchby = $request->searchby;
            $searchvalue = $request->searchvalue;

            $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : date('Y-m-d', strtotime("-1 month"));
            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : date('Y-m-d');

            $searchColumn = ["B.id", "B.name", "B.accno", "B.mobile", "B.status", "users.fullname"];
            $select = ['users.fullname as partner', 'B.id', 'B.name', 'B.cpname', 'B.user_id', 'B.mobile', 'B.accno', 'B.status', 'B.email', 'B.bankname', 'B.ifsc', 'B.mode', 'B.remarks', 'B.created_at'];
            $query = DB::connection('pgsql')->table('beneficiries as B');
            if($userid){
                $query->where('B.user_id', $userid);
            }
            if($bankid){
                $query->where('B.user_id', $bankid);
            }
            $query->select($select);
            $query->join('users', 'users.id', '=', 'B.user_id');

            if (empty($userid) && empty($bankid)) {
                $query->whereDate('B.created_at', '>=', $startdate)
                    ->whereDate('B.created_at', '<=', $enddate);
            }

            if ($searchby === "partner" && !empty($searchvalue)) {
                $query->where('users.fullname', '=', $searchvalue);
            }
            if ($searchby === "name" && !empty($searchvalue)) {
                $query->where('B.name', '=', $searchvalue);
            }
            if ($searchby === "cpname" && !empty($searchvalue)) {
                $query->where('B.cpname', '=', $searchvalue);
            }
            if ($searchby === "mobile" && !empty($searchvalue)) {
                $query->where('B.mobile', '=', $searchvalue);
            }
            if ($searchby === "accno" && !empty($searchvalue)) {
                $query->where('B.accno', '=', $searchvalue);
            }
            if ($searchby === "email" && !empty($searchvalue)) {
                $query->where('B.email', '=', $searchvalue);
            }
            if ($searchby === "bankname" && !empty($searchvalue)) {
                $query->where('B.bankname', '=', $searchvalue);
            }
            if ($searchby === "ifsc" && !empty($searchvalue)) {
                $query->where('B.ifsc', '=', $searchvalue);
            }
            if ($searchby === "mode" && !empty($searchvalue)) {
                $query->where('B.mode', '=', $searchvalue);
            }

            if ($request->status || $request->status === 0 || $request->status === "0" || $request->status === 1 || $request->status === "1") {
                $query->where('B.status', '=', $request->status);
            }


            $totalCount = $query->count();
            if (!empty($search)) {
                $query->where(function ($query) use ($searchColumn, $search) {
                    foreach ($searchColumn as $column) {
                        $query->orwhere($column, 'like', '%' . trim($search) . '%');
                    }
                });
            }
            (!empty($orderby) && !empty($order)) ? $query->orderBy("B." . $orderby, $order) : $query->orderBy("B.id", "desc");
            $length = (!empty($request->length)) ? $request->length : 20;
            $start = (!empty($request->start)) ? $request->start : 0;
            $list = $query->skip($start)->take($length)->get();
            foreach ($list as $key => $val) {
                $list[$key]->status = $this->status[$val->status];
                $list[$key]->createdat = date('d-m-Y', strtotime($val->created_at));
                unset($list[$key]->created_at);
            }
            $count = count($list);
            $header = $this->beneficiarylist();
            $details = [
                "message" => "Beneficiary list.",
                "recordsFiltered" => $count,
                "recordsTotal" => $totalCount,
                "header" => $header,
                "data" => $list
            ];
            return $this->response('success', $details);
        } catch (\Exception $e) {
            return $this->response('internalservererror', ['message' => $e->getMessage()]);
        }
    }

    public function exportBeneList(Request $request)
    {

        try {
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $searchvalue = $request->search;
            $status = $request->status;

            $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : date('Y-m-d');
            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : date('Y-m-d');
            if ($this->date_difference($startdate, $enddate)) {
                if (!empty($startdate) && !empty($enddate)) {
                    $searchColumn = ["B.id", "B.name", "B.accno", "B.mobile", "B.status", "users.fullname"];
                    $select = ['users.fullname as partner', 'B.id', 'B.name', 'B.cpname', 'B.user_id', 'B.mobile', 'B.accno', 'B.status', 'B.email', 'B.bankname', 'B.ifsc', 'B.mode', 'B.remarks', 'B.created_at'];
                    $query = DB::connection('pgsql')->table('beneficiries as B');
                    if($request->user_id){
                        $query->where('B.user_id', $request->user_id);
                    }
                    $query->select($select);
                    $query->join('users', 'users.id', '=', 'B.user_id');
                    $query->whereDate('B.created_at', '>=', $startdate);
                    $query->whereDate('B.created_at', '<=', $enddate);
                    if (!empty($search)) {
                        $query->where(function ($query) use ($searchColumn, $searchvalue) {
                            foreach ($searchColumn as $column) {
                                $query->orwhere($column, 'like', '%' . trim($searchvalue) . '%');
                            }
                        });
                    }
                    $data = $query->get()->toArray();
                    $headerdata = $this->beneficiarylist();

                    if (!empty($data)) {
                        foreach ($data as $key => $val) {
                            $data[$key]->status = $this->status[$val->status];
                            $data[$key]->createdat = date('d-m-Y', strtotime($val->created_at));
                            unset($data[$key]->created_at);
                            foreach ($headerdata as $head) {
                                $value = $head['name'];
                                $sub_array[$head['value']] = $val->$value;
                            }
                            $data1[] = $sub_array;
                        }
                        return $this->response('success', ['message' => "Success.", 'data' => $data1]);
                    } else {
                        return $this->response('noresult', ['message' => "No record found.", 'data' => '', 'recordsFiltered' => '', 'recordsTotal' => '']);
                    }
                } else {
                    return $this->response('incorrectinfo', [
                        'status' => false,
                        'responsecode' => 0,
                        'message' => "Please add param.",
                    ]);
                }
            } else {
                return $this->response('incorrectinfo', [
                    'status' => false,
                    'responsecode' => 0,
                    'message' => "You can only export data of " . $this->allowed_days() . " Days"
                ]);

            }
            // return $this->response($status, $data);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    ///statement
    #Payout statement method.
    public function statement(Request $request)
    {
        try {
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $search = $request->search;
            $orderby = $request->orderby;
            $order = $request->order;

            $userid = $request->user_id;
            $bankid = $request->bank_id;
            $searchby = $request->searchby;
            $searchvalue = $request->searchvalue;
            $sd = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : null;
            $ed = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : null;
            $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : date('Y-m-d');
            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : date('Y-m-d');



            $searchColumn = ["P.id", "P.bene_acc_no", "P.amount", "P.mode", "P.bank_urn", "P.utr_rrn", 'users.fullname'];

            $select = ["users.fullname as partner", "users.username as partner_code", 'P.id', 'P.transferId', 'P.refid', 'P.userid', 'P.bene_acc_no', 'P.urn', 'P.amount', 'P.charge', 'P.mode', 'P.status',
                'P.remarks', 'P.bankname', 'P.bene_acc_ifsc', 'P.bank_urn', 'P.utr_rrn', 'P.sender_acc_no', 'P.transaction_ref_no', 'P.is_refunded',
                'P.refunded_datetime', 'P.refunded_txn_charge_id', 'P.updated_at as success_time', 'P.created_at as initiated_time', 'P.created_at', 'bank_lists.name as sender_bank'];

            $query = DB::connection('pgsql')->table('payouts as P');
            if($userid){
                $query->where('P.userid', $userid);
            }
            if($bankid){
                $query->where('P.bank_id', $bankid);
            }
            $query->select($select);
            $query->join('users', 'users.id', '=', 'P.userid');
            $query->join('bank_lists', 'bank_lists.id', '=', 'P.bank_id');

            if ($searchby === "partner" && !empty($searchvalue)) {
                $query->where('users.fullname', '=', $searchvalue);
            }
            if ($searchby === "refid" && !empty($searchvalue)) {
                $query->where('P.refid', '=', $searchvalue);
            }
            if ($searchby === "transfer_id" && !empty($searchvalue)) {
                $query->where('P.transferId', '=', $searchvalue);
            }
            if ($searchby === "bene_acc_no" && !empty($searchvalue)) {
                $query->where('P.bene_acc_no', '=', $searchvalue);
            }
            if ($searchby === "urn" && !empty($searchvalue)) {
                $query->where('P.utr_rrn', '=', $searchvalue);
            }
            if ($request->mode) {
                $query->where('P.mode', '=', $request->mode);
            }
            if ($searchby === "bankname" && !empty($searchvalue)) {
                $query->where('P.bankname', '=', $searchvalue);
            }
            if ($request->status || $request->status === 0 || $request->status === "0" || $request->status === 1 || $request->status === "1") {
                $query->where('P.status', '=', $request->status);
            }

            // if ((!isset($userid) || empty($userid)) && (!isset($bankid) || empty($bankid))) {
            //     $query->whereDate('P.addeddate', '>=', $startdate)
            //         ->whereDate('P.addeddate', '<=', $enddate);
            // }
            // dd($sd,  $ed, $userid, $bankid, $searchby, $searchvalue);

            if (($sd!=null && $ed!=null) || ( empty($userid) && empty($bankid) && empty($searchby) && empty($searchvalue))) {
                $query->whereDate('P.addeddate', '>=', $startdate)
                      ->whereDate('P.addeddate', '<=', $enddate);
            }

            //$username = "RMY001823";
            $totalCount = $query->count();
            if (!empty($search)) {
                $query->where(function ($query) use ($searchColumn, $search) {
                    foreach ($searchColumn as $column) {
                        $query->orWhere($column, 'like', '%' . trim($search) . '%');
                    }
                });
            }
            (!empty($orderby) && !empty($order)) ? $query->orderBy("P." . $orderby, $order) : $query->orderBy("P.id", "desc");
            $length = (!empty($request->length)) ? $request->length : 20;
            $start = (!empty($request->start)) ? $request->start : 0;
            $list = $query->skip($start)->take($length)->get();
            
            foreach ($list as $key => $val) {
                $list[$key]->status_val = $val->status;
                $list[$key]->status = $this->paymentStatus[$val->status]; // Existing line for status description
                $list[$key]->bene_bank_name = $val->bankname;
                $list[$key]->username = $val->partner;
                $list[$key]->service_type = "Payout";

                $list[$key]->createdat = date("d-m-Y H:i:s", strtotime($val->created_at));

                unset($val->created_at);
                unset($val->users);
                unset($val->bankname);
                unset($val->partner);
                unset($val->bank_urn);
            }
            $count = count($list);
            $header = $this->payoutlist();
            $details = [
                "message" => "Payout list.",
                "recordsFiltered" => $count,
                "recordsTotal" => $totalCount,
                "header" => $header,
                "data" => $list
            ];
            return $this->response('success', $details);
        } catch (\Exception $e) {
            return $this->response('internalservererror', ['message' => $e->getMessage()]);
        }


    }
    public function exportBeneTransactionList(Request $request)
    {
        try {
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $searchvalue = $request->search;
            $status = $request->status;

            $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : date('Y-m-d');
            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : date('Y-m-d');
            if ($this->date_difference($startdate, $enddate)) {
                if (!empty($startdate) && !empty($enddate)) {
                    $searchColumn = ["P.id", "P.bene_acc_no", "P.amount", "P.mode", "P.bank_urn", "P.utr_rrn", 'users.fullname'];

//                    $select = ["users.fullname as partner", 'P.id', 'P.refid', 'P.userid', 'P.bene_acc_no', 'P.urn', 'P.amount', 'P.charge', 'P.mode', 'P.status', 'P.remarks', 'P.bankname', 'P.bene_acc_ifsc', 'P.bank_urn', 'P.utr_rrn', 'P.addeddate', 'P.created_at'];
//                    $query = DB::connection('pgsql')->table('payouts as P');

                    $select = ["users.fullname as username", "users.username as partner_code", 'P.id', 'P.refid', 'P.userid', 'P.bene_acc_no', 'P.urn', 'P.amount', 'P.charge', 'P.mode', 'P.status',
                        'P.remarks', 'P.bankname as bene_bank_name', 'P.bene_acc_ifsc', 'P.bank_urn', 'P.utr_rrn', 'P.sender_acc_no', 'P.transaction_ref_no', 'P.is_refunded',
                        'P.refunded_datetime', 'P.refunded_txn_charge_id','P.type as service_type', 'P.updated_at as success_time', 'P.created_at', 'P.created_at as initiated_time',
                        'bank_lists.name as sender_bank'];
                    $query = DB::connection('pgsql')->table('payouts as P');

                    if($request->user_id){
                        $query->where('B.userid', $request->user_id);
                    }
                    $query->select($select);
                    $query->join('users', 'users.id', '=', 'P.userid');
                    $query->join('bank_lists', 'bank_lists.id', '=', 'P.bank_id');

                    $query->whereDate('P.addeddate', '>=', $startdate);
                    $query->whereDate('P.addeddate', '<=', $enddate);

                    //$username = "RMY001823";
                    $totalCount = $query->count();
                    if (!empty($search)) {
                        $query->where(function ($query) use ($searchColumn, $searchvalue) {
                            foreach ($searchColumn as $column) {
                                $query->orWhere($column, 'like', '%' . trim($searchvalue) . '%');
                            }
                        });
                    }
                    $data = $query->get()->toArray();
                    $headerdata = $this->payoutlist();

                    if (!empty($data)) {
                        foreach ($data as $key => $val) {
                            $data[$key]->status = $this->paymentStatus[$val->status];
             
                            $data[$key]->service_type = $this->serviceType[$val->service_type];
                            $data[$key]->createdat = date("d-m-Y H:i:s", strtotime($val->created_at));
                            unset($val->created_at);
                            unset($val->users);
                            foreach ($headerdata as $head) {
                                $value = $head['name'];
                                $sub_array[$head['value']] = $val->$value;
                            }
                            $data1[] = $sub_array;
                        }
                        return $this->response('success', ['message' => "Success.", 'data' => $data1]);
                    } else {
                        return $this->response('noresult', ['message' => "No record found.", 'data' => '', 'recordsFiltered' => '', 'recordsTotal' => '']);
                    }
                } else {
                    return $this->response('incorrectinfo', [
                        'status' => false,
                        'responsecode' => 0,
                        'message' => "Please add param.",
                    ]);
                }
            } else {
                return $this->response('incorrectinfo', [
                    'status' => false,
                    'responsecode' => 0,
                    'message' => "You can only export data of " . $this->allowed_days() . " Days"
                ]);

            }
            // return $this->response($status, $data);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function updatePayout(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'bene_id' => 'required',
                'status' => 'required|in:0,1',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            Beneficiry::where('id', $request->bene_id)->update(array('status' => $request->status));

            return $this->response('success', ['message' => 'Updated Succesfully']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function getPayoutStatus(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payout_id' => 'required|numeric'
        ]);

        if ($validator->fails()) {
            $message = $this->validationResponse($validator->errors());
            return $this->response('validatorerrors', $message);
        }
        $payout = Payout::where(["id" => $request->payout_id])->first();
        if ($payout) {
            $beneacc = Beneficiry::where('id', $payout->beneid)->first()->toArray();
            $bankdetail = BankDetails::where('id', $beneacc['acc_id'])->first()->toArray();
            $data = [
                "refid" => $payout->id,
                "corp_id" => $bankdetail["corporateid"],
                "corp_userid" => $bankdetail["corporate_name"],
                "profile_id" => $bankdetail["profile_id"],
                "bene_name" => $beneacc["name"],
                "payout_bank" => $beneacc["bankname"],
                "sender_name" => $bankdetail["sender_name"],
                "sender_phone" => $bankdetail["sender_phone"],
                "amount" => $payout->amount,
                "mode" => $payout->mode,
                "remarks" => $payout->remarks,
                "resp" => "success"
            ];
            if ($payout->mode == "UPI") {
                $data["date"] = date('YmdHsi', strtotime("+30 seconds"));
                $data["mobile"] = $bankdetail["sender_phone"];
                $data["payee_vpa"] = $beneacc["vpaid"];
                $data["payer_vpa"] = $bankdetail["vpa_id"];
                $data["device_id"] = $payout->device_id;
            } else {
                $data["bene_acc_no"] = $beneacc["accno"];
                $data["bene_acc_ifsc"] = $beneacc["ifsc"];
                if ($payout->mode == "IMPS") {
                    $data["bcID"] = $bankdetail["bc_id"];
                    $data["passCode"] = $bankdetail["passcode"];
                    $data["localTxnDtTime"] = date('YmdHsi', strtotime("+30 seconds"));
                } elseif ($payout->mode == "RTGS" || $payout->mode == "NEFT") {
                    $data["sender_acc_no"] = $bankdetail["account_number"];
                    $data["urn"] = $bankdetail["urn"];
                }
            }
            $resp = Guzzle::getPayoutStatus($data);
            if ($resp->status_code == 200 && $resp->response_code == 1 && $resp->status == true) {

                $payout = Payout::find($payout->id);
                $payout->refid = "PS" . $payout->id;
                $payout->utr_rrn = $resp->data->utr_rrn;
                $payout->bank_urn = $resp->data->bank_urn;
                $payout->remarks = $payout->remarks . "-" . $resp->data->remarks;
                $payout->status = 1;
                $payout->save();

                $message = "Payment Successfully!";
                $response = "success";
                $Charge['userid'] = $payout->userid;
                $Charge['txn_id'] = $payout->refid;
                Charges::createTransaction($Charge);
            } elseif ($resp->status_code == 422 && $resp->response_code == 2 && $resp->status == 0) {
                $message = "Validation errors bank!!";
                $response = "exception";
                return $this->response('exception', ['message' => 'Validation errors!!', "data" => [], "errors" => $resp->errors]);
            } else {
                $payout = Payout::find($payout->id);
                $payout->refid = "PS" . $payout->id;
                if (!empty($resp->data)) {
                    $payout->utr_rrn = $resp->data->utr_rrn;
                    $payout->bank_urn = $resp->data->bank_urn;
                    $payout->remarks = $payout->remarks . "-" . $resp->data->remarks;
                }
                $payout->status = 3;
                $payout->save();
                $message = "Payment failed!";
                $response = "exception";
            }
            $payout = Payout::find($payout->id);
            $details = [
                "name" => $payout->bene_name,
                "refid" => $payout->refid,
                "utr_rrn" => $payout->utr_rrn,
                "bank_urn" => $payout->bank_urn,
                "status" => $this->pstatus[$payout->status],
                "remarks" => $payout->remarks
            ];
            return $this->response($response, ['message' => $message, 'details' => $details]);
        } else {
            return $this->response('notvalid', ['message' => 'Some issue!', 'details' => []]);
        }
    }

    /**
     * @function BankDetailsStore (payout bank details like their public key, private key, ssl cert store )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @author Madhav <madhav.kumar@ciphersquare.tech>
     */
    public function BankDetailsStore(Request  $request){
        try {
            $validator = Validator::make($request->all(), [
                'bank_id' => 'required|integer',
                'user_id' => 'required|integer',
                'corpid' => 'string',
                'approverid' => 'string',
                'makerid' => 'string',
                'checkerid' => 'string',
                'signature' => 'string',
                'ldapuserid' => 'string',
                'ldappassword' => 'string',
                'secret_id' => 'string',
                'clientid' => 'string',
                'ssl_certificate' => '',
                'ssl_private_key' => '',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $bankId = $request->bank_id;
            $userId = $request->user_id;
            $id = $request->id;
            $newCorpId = $request->corpid;

            $bankKeyss = BankKeys::select('id', 'ssl_certificate', 'ssl_private_key')->where(['user_id'=> $userId, 'bank_id' => $bankId])->first();

            if($bankKeyss){
                $bankKeys = BankKeys::where('user_id', $userId)
                    ->where('bank_id', $bankId)
                    ->first();

                if (!$bankKeys) {
                    return $this->response('noresult');
                }

                $oldCorpId = $bankKeys->corp_id;
                $certificatePath = $bankKeys->ssl_certificate;
                $privatePath = $bankKeys->ssl_private_key;

                // If corp_id changes and files exist, rename them
                if ($oldCorpId !== $newCorpId) {
                    if ($certificatePath) {
                        $newCertificatePath = 'uploads/' . $newCorpId . '_publickey.' . pathinfo($certificatePath, PATHINFO_EXTENSION);
//                        Storage::disk('public')->move($certificatePath, $newCertificatePath);
                        $certificatePath = $newCertificatePath;
                    }

                    if ($privatePath) {
                        $newPrivatePath = 'uploads/' . $newCorpId . '_privatekey.' . pathinfo($privatePath, PATHINFO_EXTENSION);
//                        Storage::disk('public')->move($privatePath, $newPrivatePath);
                        $privatePath = $newPrivatePath;
                    }
                }

                if ($request->hasFile('ssl_certificate') && $request->hasFile('ssl_private_key')) {
                    $sslCertificate = $request->file('ssl_certificate');
                    $sslPrivateKey = $request->file('ssl_private_key');

                    $certificateFilename = $newCorpId . '_publickey.' . $sslCertificate->getClientOriginalExtension();
                    $privateKeyFilename = $newCorpId . '_privatekey.' . $sslPrivateKey->getClientOriginalExtension();

                    $certificatePath = $sslCertificate->storeAs('uploads', $certificateFilename, 'public');
                    $privatePath = $sslPrivateKey->storeAs('uploads', $privateKeyFilename, 'public');

                    $oldCertificatePath = $bankKeys->ssl_certificate;
                    $oldPrivatePath = $bankKeys->ssl_private_key;

                    $this->sendFilesToAnotherProject($certificatePath, $privatePath, $oldCertificatePath, $oldPrivatePath);
                } else {
                    $certificatePath = $bankKeys->ssl_certificate;
                    $privatePath = $bankKeys->ssl_private_key;

                    if ($certificatePath) {
                        $parts = explode('_', $certificatePath);
                        $certificatePath = $newCorpId . "_" . $parts[1];
                    }
//                    else {
//                        $certificateFilename = null; // Handle this case as needed
//                    }

                    if ($privatePath) {
                        $parts = explode('_', $privatePath);
                        $privatePath = $newCorpId . "_" . $parts[1];
                    }
//                    else {
//                        $privatePath = null; // Handle this case as needed
//                    }

                    $oldCertificatePath = $bankKeys->ssl_certificate;
                    $oldPrivatePath = $bankKeys->ssl_private_key;

                    $this->sendFilesToAnotherProject($certificatePath, $privatePath, $oldCertificatePath, $oldPrivatePath);
                }

                $bankKeys->update([
                    'corp_id' => $newCorpId,
                    'approver_id' => $request->approverid,
                    'maker_id' => $request->makerid,
                    'checker_id' => $request->checkerid,
                    'signature' => $request->signature,
                    'ldap_user_id' => $request->ldapuserid,
                    'ldap_password' => $request->ldappassword,
                    'secret_id' => $request->secret_id,
                    'client_id' => $request->clientid,
                    'ssl_certificate' => $certificatePath,
                    'ssl_private_key' => $privatePath,
                    'status' => 1
                ]);

                return $this->response('success', ['message' => "Success."]);
            }

            $certificatePath = '';
            $privatePath = '';

            if ($request->hasFile('ssl_certificate') && $request->hasFile('ssl_private_key')) {
                $sslCertificate = $request->file('ssl_certificate');
                $sslPrivateKey = $request->file('ssl_private_key');

                $corpId = $request->corpid;
                $certificateFilename = $corpId . '_publickey.' . $sslCertificate->getClientOriginalExtension();
                $privateKeyFilename = $corpId . '_privatekey.' . $sslPrivateKey->getClientOriginalExtension();

                $certificatePath = $sslCertificate->storeAs('uploads', $certificateFilename, 'public');
                $privatePath = $sslPrivateKey->storeAs('uploads', $privateKeyFilename, 'public');

                $this->sendFilesToAnotherProject($certificatePath, $privatePath);
            }

            $bankKey = BankKeys::create([
                'user_id' => $userId,
                'bank_id' => $bankId,
                'corp_id' => $request->corpid,
                'approver_id' => $request->approverid,
                'maker_id' => $request->makerid,
                'checker_id' => $request->checkerid,
                'signature' => $request->signature,
                'ldap_user_id' => $request->ldapuserid,
                'ldap_password' => $request->ldappassword,
                'secret_id' => $request->secret_id,
                'client_id' => $request->clientid,
                'ssl_certificate' => $certificatePath,
                'ssl_private_key' => $privatePath,
                'status' => 1
            ]);

            if (!$bankKey) {
                return $this->response('updateError');
            }

            return $this->response('success', ['message' => "Success."]);

        }catch (\Exception $e) {
            return $this->response('internalservererror', ['message' => $e->getMessage()]);
        }
    }

    private function sendFilesToAnotherProject($certificatePath, $privatePath, $oldCertificatePath = null, $oldPrivatePath = null)
    {
        $url = 'https://va-callback.sprintnxt.in/rblbank/ssl-store';

        $postData = [
            'ssl_certificate' => new \CURLFile(storage_path('app/public/' . $certificatePath)),
            'ssl_private_key' => new \CURLFile(storage_path('app/public/' . $privatePath)),
        ];

        if ($oldCertificatePath) {
            $filename = substr($oldCertificatePath, strlen("uploads/"));
            $postData['old_ssl_certificate'] = $filename;
        }
        if ($oldPrivatePath) {
            $filename1 = substr($oldPrivatePath, strlen("uploads/"));
            $postData['old_ssl_private_key'] = $filename1;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            // Handle cURL error
            Log::error('cURL error: ' . curl_error($ch));
        }

        curl_close($ch);

        return $response;
    }
    /**
     * @function GetBankDetails
     * @param Request $request ["search": "", "start": 0, "length": 10, "orderby": "", "order": "asc", "startdate": "", "enddate": "", "user_id": "", "status": "", "accountType": "", "searchvalue": "", "searchby": "" ]
     * @return \Illuminate\Http\JsonResponse
     * @author Madhav <madhav.kumar@ciphersquare.tech>
     */

//    public function GetBankDetails(Request $request)
//    {
//        try {
//            // Extract request parameters
//            $startdate = $request->startdate;
//            $enddate = $request->enddate;
//            $search = $request->search;
//            $orderby = $request->orderby;
//            $order = $request->order;
//            $userid = $request->user_id;
//            $bankid = $request->bank_id;
//            $searchby = $request->searchby;
//            $searchvalue = $request->searchvalue;
//
//            // Set default dates if not provided
//            $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : date('Y-m-d');
//            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : date('Y-m-d');
//
//            // Define searchable columns
//            $searchColumn = [
//                "id", "user_id", "bank_id", "corp_id", "approver_id", "maker_id", "checker_id",
//                "signature", "ldap_user_id", "ldap_password", "secret_id", "client_id",
//                "ssl_certificate", "ssl_private_key", "ssl_public_key", "status"
//            ];
//
//            // Build the query
//            $query = BankKeys::leftJoin('bank_lists', 'bank_keys.bank_id', '=', 'bank_lists.id')
//                ->select('bank_keys.*', 'bank_lists.name as bank_name');
//
//            // Apply filters based on request parameters
//            if ($userid) {
//                $query->where('bank_keys.user_id', $userid);
//            }
//            if ($bankid) {
//                $query->where('bank_keys.bank_id', $bankid);
//            }
//            if (!empty($searchvalue) && !empty($searchby)) {
//                $query->where('bank_keys.' . $searchby, '=', $searchvalue);
//            }
//            if ($request->status || $request->status === 0 || $request->status === "0" || $request->status === 1 || $request->status === "1") {
//                $query->where('bank_keys.status', '=', $request->status);
//            }
//
//            // Count total records
//            $totalCount = $query->count();
//
//            // Apply search filter
//            if (!empty($search)) {
//                $query->where(function ($query) use ($searchColumn, $search) {
//                    foreach ($searchColumn as $column) {
//                        $query->orWhere('bank_keys.' . $column, 'like', '%' . trim($search) . '%');
//                    }
//                });
//            }
//
//            // Apply ordering
//            if (!empty($orderby) && !empty($order)) {
//                $query->orderBy($orderby, $order);
//            } else {
//                $query->orderBy("bank_keys.id", "desc");
//            }
//
//            // Pagination
//            $length = (!empty($request->length)) ? $request->length : 20;
//            $start = (!empty($request->start)) ? $request->start : 0;
//            $list = $query->skip($start)->take($length)->get();
//
//            foreach ($list as $key => $val) {
//                $val->createdat = date("d-m-Y H:i:s", strtotime($val->created_at));
//                $val->bank_name = $val->bank_name; // Ensuring bank_name is accessible
//                unset($val->created_at); // Remove created_at if not needed
//                $list[$key] = $val;
//            }
//
//            // Prepare response
//            $count = count($list);
//            $header = $this->bankDetailsList();
//            $details = [
//                "message" => "Bank Details list.",
//                "recordsFiltered" => $count,
//                "recordsTotal" => $totalCount,
//                "header" => $header,
//                "data" => $list
//            ];
//
//            return $this->response('success', $details);
//        } catch (\Exception $e) {
//            return $this->response('internalservererror', ['message' => $e->getMessage()]);
//        }
//    }
    public function GetBankDetails(Request $request)
    {
        try {
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $search = $request->search;
            $orderby = $request->orderby;
            $order = $request->order;
            $userid = $request->user_id;
            $bankid = $request->bank_id;
            $searchby = $request->searchby;
            $searchvalue = $request->searchvalue;

            // Set default dates if not provided
            $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : null;
            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : null;

            // Define searchable columns
            $searchColumn = [
                "id", "user_id", "bank_id", "corp_id", "approver_id", "maker_id", "checker_id",
                "signature", "ldap_user_id", "ldap_password", "secret_id", "client_id",
                "ssl_certificate", "ssl_private_key", "ssl_public_key", "status"
            ];

            // Build the query
            $query = BankKeys::leftJoin('bank_lists', 'bank_keys.bank_id', '=', 'bank_lists.id')
                ->leftJoin('users as user', 'bank_keys.user_id', '=', 'user.id')
                ->select('bank_keys.*', 'bank_lists.name as bank_name','user.fullname');

            if (!empty($startdate) && !empty($enddate)) {
                $query->whereDate('bank_keys.created_at', '>=', $startdate)
                    ->whereDate('bank_keys.created_at', '<=', $enddate);
            }

            // Apply filters based on request parameters
            if ($userid) {
                $query->where('bank_keys.user_id', $userid);
            }
            if ($bankid) {
                $query->where('bank_keys.bank_id', $bankid);
            }
//            if (!empty($searchvalue) && !empty($searchby)) {
//                $query->where('bank_keys.' . $searchby, '=', $searchvalue);
//            }
            if ($request->status || $request->status === 0 || $request->status === "0" || $request->status === 1 || $request->status === "1") {
                $query->where('bank_keys.status', '=', $request->status);
            }

            // Count total records
            $totalCount = $query->count();

//            if(!empty($searchvalue)){
//                $query->where(function($query) use ($searchColumn, $searchvalue){
//                    foreach($searchColumn as $column){
//                        $query->orWhere($column, 'like', '%' .  trim($searchvalue) . '%');
//                    }
//                });
//            }

            // Apply search filter
            if (!empty($search)) {
                $query->where(function ($query) use ($searchColumn, $search) {
                    foreach ($searchColumn as $column) {
                        $query->orWhere('bank_keys.' . $column, 'like', '%' . trim($search) . '%');
                    }
                });
            }

            // Apply ordering
            if (!empty($orderby) && !empty($order)) {
                $query->orderBy($orderby, $order);
            } else {
                $query->orderBy("bank_keys.id", "desc");
            }

            // Pagination
            $length = (!empty($request->length)) ? $request->length : 20;
            $start = (!empty($request->start)) ? $request->start : 0;
            $list = $query->skip($start)->take($length)->get();

            $transactions = [];
            foreach ($list as $key => $val) {
                //$val->createdat = date("d-m-Y H:i:s", strtotime($val->created_at));
                $val->bank_name = $val->bank_name; // Ensuring bank_name is accessible
                unset($val->created_at); // Remove created_at if not needed
                $list[$key] = $val;

                $transactions[] = [
                    'created_at' => date("d-m-Y H:i:s", strtotime($val->created_at)),
                    'bank_name' => $val->bank_name,
                    'bank_id' => $val->bank_id,
                    'user_id' => $val->user_id,
                    'approver_id' => $val->approver_id,
                    'checker_id' => $val->checker_id,
                    'client_id' =>$this->maskString($val->client_id,'#',10),
                    'full_client_id' => $val->client_id,
                    'corp_id' => $val->corp_id,
                    'fullname' => $val->fullname,
                    'signature' => $val->signature,
                    'ldap_password' =>$this->maskString($val->ldap_password,'#',3),
                    'full_ldap_password' => $val->ldap_password,
                    'ldap_user_id' =>$val->ldap_user_id,
                    'maker_id' => $val->maker_id,
                    'secret_id' => $this->maskString($val->secret_id),
                    'full_secret_id' => $val->secret_id,
                    'status' => $this->status[$val->status],
                    'ssl_certificate' => "https://va-callback.sprintnxt.in/rblbank/". $val->ssl_certificate,
                    'ssl_private_key' => "https://va-callback.sprintnxt.in/rblbank/". $val->ssl_private_key,
                ];
            }

            // Prepare response
            $count = count($transactions);
            $header = $this->bankDetailsList();
            $details = [
                "message" => "Bank Details list.",
                "recordsFiltered" => $count,
                "recordsTotal" => $totalCount,
                "header" => $header,
                "data" => $transactions
            ];

            return $this->response('success', $details);
        } catch (\Exception $e) {
            return $this->response('internalservererror', ['message' => $e->getMessage()]);
        }
    }


    /**
     * @function BankDetailsUpdate (payout bank details like their public key, private key, ssl cert update )
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @author Madhav <madhav.kumar@ciphersquare.tech>
     */
//    public function BankDetailsUpdate(Request $request) {
//        try {
//            $validator = Validator::make($request->all(), [
//                'id' => 'required|integer',
//                'bank_id' => 'required|integer',
//                'user_id' => 'required|integer',
//                'corpid' => 'string',
//                'approverid' => 'string',
//                'makerid' => 'string',
//                'checkerid' => 'string',
//                'signature' => 'string',
//                'ldapuserid' => 'string',
//                'ldappassword' => 'string',
//                'secret_id' => 'string',
//                'clientid' => 'string',
//                'ssl_certificate' => 'file',
//                'ssl_private_key' => 'file',
//            ]);
//
//            if ($validator->fails()) {
//                $message = $this->validationResponse($validator->errors());
//                return $this->response('validatorerrors', $message);
//            }
//
//            $id = $request->id;
//            $bankId = $request->bank_id;
//            $userId = $request->user_id;
//            $newCorpId = $request->corpid;
//
//            $bankKeys = BankKeys::where('id', $id)
//                ->where('user_id', $userId)
//                ->where('bank_id', $bankId)
//                ->first();
//
//            if (!$bankKeys) {
//                return $this->response('noresult');
//            }
//
//            $oldCorpId = $bankKeys->corp_id;
//            $certificatePath = $bankKeys->ssl_certificate;
//            $privatePath = $bankKeys->ssl_private_key;
//
//            // If corp_id changes and files exist, rename them
//            if ($oldCorpId !== $newCorpId) {
//                if ($certificatePath) {
//                    $newCertificatePath = 'uploads/' . $newCorpId . '_publickey.' . pathinfo($certificatePath, PATHINFO_EXTENSION);
//                    Storage::disk('public')->move($certificatePath, $newCertificatePath);
//                    $certificatePath = $newCertificatePath;
//                }
//
//                if ($privatePath) {
//                    $newPrivatePath = 'uploads/' . $newCorpId . '_privatekey.' . pathinfo($privatePath, PATHINFO_EXTENSION);
//                    Storage::disk('public')->move($privatePath, $newPrivatePath);
//                    $privatePath = $newPrivatePath;
//                }
//            }
//
//            if ($request->hasFile('ssl_certificate') && $request->hasFile('ssl_private_key')) {
//                $sslCertificate = $request->file('ssl_certificate');
//                $sslPrivateKey = $request->file('ssl_private_key');
//
//                $certificateFilename = $newCorpId . '_publickey.' . $sslCertificate->getClientOriginalExtension();
//                $privateKeyFilename = $newCorpId . '_privatekey.' . $sslPrivateKey->getClientOriginalExtension();
//
//                $certificatePath = $sslCertificate->storeAs('uploads', $certificateFilename, 'public');
//                $privatePath = $sslPrivateKey->storeAs('uploads', $privateKeyFilename, 'public');
//            }
//
//            $bankKeys->update([
//                'corp_id' => $newCorpId,
//                'approver_id' => $request->approverid,
//                'maker_id' => $request->makerid,
//                'checker_id' => $request->checkerid,
//                'signature' => $request->signature,
//                'ldap_user_id' => $request->ldapuserid,
//                'ldap_password' => $request->ldappassword,
//                'secret_id' => $request->secret_id,
//                'client_id' => $request->clientid,
//                'ssl_certificate' => $certificatePath,
//                'ssl_private_key' => $privatePath,
//                'status' => $request->status
//            ]);
//
//            return $this->response('success', ['message' => "Success."]);
//        } catch (\Exception $e) {
//            return $this->response('internalservererror', ['message' => $e->getMessage()]);
//        }
//    }



}
