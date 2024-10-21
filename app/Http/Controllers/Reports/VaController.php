<?php

namespace App\Http\Controllers\Reports;

use App\Libraries\Common\Crypt;
use App\Models\ApiConfig;
use App\Models\ApiCredential;
use App\Models\ApiUser;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Traits\CommonTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\HeaderTrait;
use App\Models\VirtualAccount;
use App\Models\VaTransactions;
use Illuminate\Support\Facades\DB;

class VaController extends Controller
{
    use CommonTrait, HeaderTrait;

    public function __construct()
    {
        $this->status = ['0' => 'Deactive', '1' => 'Active'];
        $this->paymentmode = array('N' => "NEFT", 'R' => "RTGS", 'I' => "FT", 'O' => "IMPS", 'U' => "UPI");
        $this->TransactionStatus = array('0' => "Processing",'1' => "Success", '2' => "Processing", '3' => "Rejected",'4'=>"Suspense",'5'=>"Suspicious");
        $this->today = date('Y-m-d');
    }

    public function list(Request $request)
    {
        try {
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $searchvalue = $request->search;
            $orderby = $request->orderby;
            $order = $request->order;
            $status = $request->status;
            $start = $request->start;
            $length = $request->length;

            $userid = $request->user_id;
            $bankid = $request->bank_id;
            $searchby = $request->searchby;
            $searchvalue = $request->searchvalue;

            $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : date('Y-m-d', strtotime("-1 month"));
            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : date('Y-m-d');

            $searchColumn = [
                'va.acc_no',
                'va.prefix',
                'va.name',
                'va.email',
                'va.phone',
                'va.pan',
                'va.pincode',
                'va.type'
            ];

            $select = [
                'va.id',
                'va.charge',
                'va.acc_no',
                'va.name',
                'va.email',
                'va.phone',
                'va.pan',
                'va.status',
                'users.fullname as partner',
                'bank_lists.name as bank',
                'va.created_at'
            ];

            $query = DB::connection('pgsql')->table('va');
            if($userid){
                $query->where('va.userid', $userid);
            }
            $query->join('users', 'users.id', '=', 'va.userid');
            $query->join('bank_lists', 'bank_lists.id', '=', 'va.bank_id');
            $query->select($select);
            if (empty($userid) && empty($bankid)) {
                $query->whereDate('va.created_at', '>=', $startdate)
                        ->whereDate('va.created_at', '<=', $enddate);
            }

            if ($bankid) {
                $query->where('va.bank_id', $bankid);
            }
            if ($searchby === "acc_no" && !empty($searchvalue)) {
                $query->where('va.acc_no', '=', $searchvalue);
            }
            if ($searchby === "name" && !empty($searchvalue)) {
                $query->where('va.name', '=', $searchvalue);
            }
            if ($searchby === "email" && !empty($searchvalue)) {
                $query->where('va.email', '=', $searchvalue);
            }
            if ($searchby === "phone" && !empty($searchvalue)) {
                $query->where('va.phone', '=', $searchvalue);
            }
            if ($searchby === "pan" && !empty($searchvalue)) {
                $query->where('va.pan', '=', $searchvalue);
            }
            if ($searchby === "partner" && !empty($searchvalue)) {
                $query->where('users.fullname', '=', $searchvalue);
            }
            if ($status === "0" || $status === 0 || $status === "1" || $status === 1) {
                $query->where('va.status', $status);
            }

            $recordsTotal = $query->count();
            if (!empty($searchvalue)) {
                $query->where(function ($query) use ($searchColumn, $searchvalue) {
                    foreach ($searchColumn as $column) {
                        $query->orWhere($column, 'like', '%' . trim($searchvalue) . '%');
                    }
                });
            }

            (!empty($orderby) && !empty($order)) ? $query->orderBy('va.' . $orderby, $order) : $query->orderBy("va.id", "desc");
            $length = (!empty($request->length)) ? $request->length : 20;
            $start = (!empty($request->start)) ? $request->start : 0;
            $data = $query->skip($start)->take($length)->get();
            $recordsFiltered = count($data);
            foreach ($data as $key => $val) {
                $data[$key]->status = $this->status[$val->status];
                $data[$key]->created_at = date("d-m-Y H:i:s", strtotime($val->created_at));
            }
            $headerdata = $this->vastatement();
            if (!empty($data)) {
                return $this->response('success', ['message' => "List fetched successfully!", 'header' => $headerdata, 'data' => $data, 'recordsFiltered' => $recordsFiltered, 'recordsTotal' => $recordsTotal]);
            } else {
                return $this->response('noresult', ['message' => "No record found.", 'header' => $headerdata]);
            }

        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function exportVaList(Request $request)
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
                    $searchColumn = [
                        'va.acc_no',
                        'va.prefix',
                        'va.name',
                        'va.email',
                        'va.phone',
                        'va.pan',
                        'va.pincode',
                        'va.type'
                    ];

                    $select = [
                        'va.id',
                        'va.charge',
                        'va.acc_no',
                        'va.name',
                        'va.email',
                        'va.phone',
                        'va.pan',
                        'va.status',
                        'users.fullname as partner',
                        'bank_lists.name as bank',
                        'va.created_at'
                    ];

                    $query = DB::connection('pgsql')->table('va');
                    if($request->user_id){
                        $query->where('va.userid', $request->user_id);
                    }
                    $query->join('users', 'users.id', '=', 'va.userid');
                    $query->leftjoin('bank_lists', 'bank_lists.id', '=', 'va.bank_id');
                    $query->select($select);
                    $query->whereDate('va.created_at', '>=', $startdate);
                    $query->whereDate('va.created_at', '<=', $enddate);

                    if ($request->bank_id) {
                        $query->where('va.bank_id', $request->bank_id);
                    }
                    if ($status) {
                        $query->where('va.status', $status);
                    }

                    if (!empty($searchvalue)) {
                        $query->where(function ($query) use ($searchColumn, $searchvalue) {
                            foreach ($searchColumn as $column) {
                                $query->orWhere($column, 'like', '%' . trim($searchvalue) . '%');
                            }
                        });
                    }
                    $data = $query->get()->toArray();
                    $headerdata = $this->vastatement();

                    if (!empty($data)) {
                        foreach ($data as $key => $val) {
                            $data[$key]->status = $this->status[$val->status];
                            $data[$key]->created_at = date("d-m-Y H:i:s", strtotime($val->created_at));
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

    public function statement(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'va_no' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $VirtualAccount = VirtualAccount::select('id', 'userid', 'bank_id', 'prefix', 'acc_no', 'name', 'email', 'phone', 'pan', 'pincode', 'min_limit', 'max_limit', 'type', 'status', DB::raw('DATE(created_at) AS created'))->where('acc_no', $request->va_no)->first();

            if ($VirtualAccount) {
                $VirtualAccountData = $VirtualAccount->toArray();
                $VirtualAccountData['paymentss'] = VaTransactions::where('va_no', $request->va_no)->where('userid', Auth::user()->id)->count();
                $VirtualAccountData['amount_received'] = VaTransactions::where('va_no', $request->va_no)->where('userid', Auth::user()->id)->sum('amount');
                return $this->response('success', ['statuscode' => 200, 'message' => "Data fetched successfully!", 'data' => $VirtualAccountData]);
            } else {
                return $this->response('incorrectinfo', ['message' => "Incorrect Info!"]);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function transactions(Request $request)
    {

        try {
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $searchvalue = $request->search;
            $orderby = $request->orderby;
            $order = $request->order;
            $status = $request->status;
            $start = $request->start;
            $length = $request->length;

            $userid = $request->user_id;
            $bankid = $request->bank_id;
            $searchby = $request->searchby;
            $searchvalue = $request->searchvalue;

            $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : date('Y-m-d');
            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : date('Y-m-d');

            $searchColumn = [
                'vat.va_no',
                'vat.amount',
                'vat.p_mode',
                'vat.remitter_name',
                'vat.remitter_ac_no',
                'vat.txn_date',
                'vat.utr',
                'va.acc_no',
                'va.name'
            ];

            $select = [
                'vat.created_at',
                'vat.va_no',
                'vat.amount',
                'vat.charge',
                'vat.p_mode',
                'vat.remitter_name',
                'vat.remitter_ac_no',
                'vat.txn_date',
                'vat.utr',
                'vat.status',
                'va.acc_no as acc_no',
                'va.name as name',
                'users.fullname as merchant',
                'bank_lists.name as bank'
            ];

            $query = DB::connection('pgsql')->table('va_transactions as vat');
            if($request->user_id){
                $query->where('vat.userid', $request->user_id);
            }
            $query->join('va', 'va.acc_no', '=', 'vat.va_no');
            $query->join('users', 'users.id', '=', 'va.userid');
            $query->join('bank_lists', 'bank_lists.id', '=', 'va.bank_id');
            $query->select($select);

            if (empty($userid) && empty($bankid)) {
                $query->whereDate('vat.created_at', '>=', $startdate)
                        ->whereDate('vat.created_at', '<=', $enddate);
            }
            if (!empty($userid)) {
                $query->where('va.userid', '=', $userid);
            }
            if ($request->bank_id) {
                $query->where('vat.bank_id', $request->bank_id);
            }
            if ($searchby === "account_name" && !empty($searchvalue)) {
                $query->where('va.name', '=', $searchvalue);
            }
            if ($searchby === "merchant" && !empty($searchvalue)) {
                $query->where('users.fullname', '=', $searchvalue);
            }
            if ($searchby === "acc_no" && !empty($searchvalue)) {
                $query->where('va.acc_no', '=', $searchvalue);
            }
            if ($searchby === "remitter_name" && !empty($searchvalue)) {
                $query->where('vat.remitter_name', '=', $searchvalue);
            }
            if ($searchby === "remitter_ac_no" && !empty($searchvalue)) {
                $query->where('vat.remitter_ac_no', '=', $searchvalue);
            }
            if ($searchby === "utr" && !empty($searchvalue)) {
                $query->where('vat.utr', '=', $searchvalue);
            }
            if ($searchby === "txn_date" && !empty($searchvalue)) {
                $query->where('vat.txn_date', '=', $searchvalue);
            }
            if ($status) {
                $query->where('vat.status', $status);
            }

            $recordsTotal = $query->count();
            if (!empty($searchvalue)) {
                $query->where(function ($query) use ($searchColumn, $searchvalue) {
                    foreach ($searchColumn as $column) {
                        $query->orWhere($column, 'like', '%' . trim($searchvalue) . '%');
                    }
                });
            }

            (!empty($orderby) && !empty($order)) ? $query->orderBy('vat.' . $orderby, $order) : $query->orderBy("vat.id", "desc");
            $length = (!empty($request->length)) ? $request->length : 20;
            $start = (!empty($request->start)) ? $request->start : 0;
            $data = $query->skip($start)->take($length)->get();

            $recordsFiltered = count($data);
            $headdata = $this->vatransactionshead();
            $transactions = array();
            if (!empty($data)) {
                foreach ($data as $singleTran) {
                    $transactions[] = [
                        'created_at' => date("d-m-Y H:i:s", strtotime($singleTran->created_at)),
                        'account_name' => $singleTran->name,
                        'merchant' => $singleTran->merchant,
                        'bank' => $singleTran->bank,
                        'charge' => $singleTran->charge,
                        'acc_no' => $singleTran->acc_no,
                        'remitter_name' => $singleTran->remitter_name,
                        'remitter_ac_no' => $singleTran->remitter_ac_no,
                        'p_mode' => $singleTran->p_mode,
                        'amount' => $singleTran->amount,
                        'utr' => $singleTran->utr,
                        'txn_date' => date("d-m-Y", strtotime($singleTran->txn_date)),
                        'status' => $this->TransactionStatus[$singleTran->status],
                    ];
                }
                return $this->response('success', ['message' => "Success.", 'header' => $headdata, 'data' => $transactions, 'recordsFiltered' => $recordsFiltered, 'recordsTotal' => $recordsTotal]);
            } else {
                return $this->response('noresult', ['statuscode' => 200, 'header' => $headdata]);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function exportVaTransactionList(Request $request)
    {

        try {
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $searchvalue = $request->search;
            $status = $request->status;

           $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) :date('Y-m-d', strtotime('-7 days', strtotime($this->today)));
            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : date('Y-m-d');
            if ($this->date_difference($startdate, $enddate)) {
                if (!empty($startdate) && !empty($enddate)) {
                    $searchColumn = [
                        'vat.va_no',
                        'vat.amount',
                        'vat.p_mode',
                        'vat.remitter_name',
                        'vat.remitter_ac_no',
                        'vat.txn_date',
                        'vat.utr',
                        'va.acc_no',
                        'va.name'
                    ];

                    $select = [
                        'vat.created_at',
                        'vat.va_no',
                        'vat.amount',
                        'vat.charge',
                        'vat.p_mode',
                        'vat.remitter_name',
                        'vat.remitter_ac_no',
                        'vat.txn_date',
                        'vat.utr',
                        'vat.status',
                        'va.acc_no as acc_no',
                        'va.name as name',
                        'users.fullname as merchant',
                        'bank_lists.name as bank'
                    ];

                    $query = DB::connection('pgsql')->table('va_transactions as vat');
                    if($request->user_id){
                        $query->where('vat.userid', $request->user_id);
                    }
                    $query->join('va', 'va.acc_no', '=', 'vat.va_no');
                    $query->join('users', 'users.id', '=', 'va.userid');
                    $query->join('bank_lists', 'bank_lists.id', '=', 'va.bank_id');
                    $query->select($select);
                    $query->whereDate('vat.created_at', '>=', $startdate);
                    $query->whereDate('vat.created_at', '<=', $enddate);

                    if ($request->user_id) {
                        $query->where('vat.userid', $request->user_id);
                    }
                    if ($request->bank_id) {
                        $query->where('vat.bank_id', $request->bank_id);
                    }
                    if ($status) {
                        $query->where('vat.status', $status);
                    }

                    $recordsTotal = $query->count();
                    if (!empty($searchvalue)) {
                        $query->where(function ($query) use ($searchColumn, $searchvalue) {
                            foreach ($searchColumn as $column) {
                                $query->orWhere($column, 'like', '%' . trim($searchvalue) . '%');
                            }
                        });
                    }
                    $data = $query->get()->toArray();
                    $headerdata = $this->vastatement();

                    if (!empty($data)) {
                        foreach ($data as $singleTran) {
                            $sub_array = [
                                'created_at' => date("d-m-Y H:i:s", strtotime($singleTran->created_at)),
                                'account_name' => $singleTran->name,
                                'merchant' => $singleTran->merchant,
                                'bank' => $singleTran->bank,
                                'charge' => $singleTran->charge,
                                'acc_no' => $singleTran->acc_no,
                                'remitter_name' => $singleTran->remitter_name,
                                'remitter_ac_no' => $singleTran->remitter_ac_no,
                                'p_mode' => $singleTran->p_mode,
                                'amount' => $singleTran->amount,
                                'utr' => $singleTran->utr,
                                'txn_date' => date("d-m-Y", strtotime($singleTran->txn_date)),
                                'status' => $this->TransactionStatus[$singleTran->status],
                            ];
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

    public function updateVa(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'va_id' => 'required',
                'status' => 'required|in:0,1',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            VirtualAccount::where('id', $request->va_id)->update(array('status' => $request->status));

            return $this->response('success', ['message' => 'Updated Succesfully']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

}
