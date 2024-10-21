<?php

namespace App\Http\Controllers\BussinessBanking;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Models\BankDetails;
use App\Models\BankForm;
use App\Models\AccountType;
use App\Models\BankDetailsData;
use App\Models\BankDetailsRemark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\HeaderTrait;
use App\Libraries\Common\Curl;
use App\Models\BankList;
use App\Models\MerchantUser;
use Carbon\Carbon;
use App\Libraries\Common\Emailtemplate;
use App\Libraries\Common\Email;
use Auth;

class PartnerRequests extends Controller
{
    use CommonTrait, HeaderTrait;
    public function __construct()
    {
        $this->type = "details";
        $this->status = ['0' => "Pending", '1' => "Approved", '2' => "Rejected"];
        $this->service = ['1' => "Payout", '2' => "Collection", '3' => "Both"];
    }
    public function cibRegistrations(Request $request)
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
            $accountType = $request->accountType;
            $status = $request->status;

            $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : date('Y-m-d', strtotime("-4 month"));
            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : date('Y-m-d');

            $searchColumn = ['bank_details.id', 'bank_lists.name', 'bank_details.holderName', 'bank_details.account_number','bank_details.pan'];
            $select = ['bank_details.id as cib_id', 'bank_details.cib_status as cib_status','bank_details.cbsedit as cbsedit', 'bank_lists.name as bank_name', 'bank_details.sender_phone',
                        'bank_details.sender_email', 'bank_details.holderName', 'bank_details.account_number', 'bank_details.ifsc as ifsc',
                        'account_types.type as account_type', 'bank_details.bankuserid', 'bank_details.pan', 'bank_details.gst', 'bank_details.corporateid',
                        'bank_details.corporate_name', 'bank_details.status as status', 'bank_details.created_at as created', 'users.fullname as partner'];

            $query = BankDetails::select($select)->join('users', 'users.id', '=', 'bank_details.user_id')
                ->join('bank_lists', 'bank_lists.id', '=', 'bank_details.bank_id')
                ->join('account_types', 'account_types.id', '=', 'bank_details.account_type');

            if (!empty($userid)) {
                $query->where('bank_details.user_id', '=', $userid);
            }

            if (!empty($bankid)) {
                $query->where('bank_details.bank_id', '=', $bankid);
            }

            if ($searchby === "cib_status" && !empty($searchvalue)) {
                $query->where('bank_details.cib_status', '=', $searchvalue);
            }

            if ($searchby === "sender_email" && !empty($searchvalue)) {
                $query->where('bank_details.sender_email', '=', $searchvalue);
            }
            if ($searchby === "account_number" && !empty($searchvalue)) {
                $query->where('bank_details.account_number', '=', $searchvalue);
            }
            if (!empty($accountType)) {
                $query->where('bank_details.account_type', '=', $accountType);
            }
            if ($searchby === "pan" && !empty($searchvalue)) {
                $query->where('bank_details.pan', '=', $searchvalue);
            }
            if (!empty($status) || $status === 0 || $status === "0" || $status === 1 || $status === "1") {
                $query->where('bank_details.status', '=', $status);
            }
            if ($searchby === "corporate_name" && !empty($searchvalue)) {
                $query->where('bank_details.corporate_name', '=', $searchvalue);
            }

            if (empty($userid) && empty($bankid)) {
                $query->whereDate('bank_details.created_at', '>=', $startdate)
                    ->whereDate('bank_details.created_at', '<=', $enddate);
            }
            $totalCount = $query->count();
            if (!empty($search)) {
                $query->where(function ($query) use ($searchColumn, $search) {
                    foreach ($searchColumn as $column) {
                        $query->orwhere($column, 'like', '%' . trim($search) . '%');
                    }
                });
            }
            (!empty($orderby) && !empty($order)) ? $query->orderBy('bank_details.' . $orderby, $order) : $query->orderBy("bank_details.id", "desc");
            $length = (!empty($request->length)) ? $request->length : 20;
            $start = (!empty($request->start)) ? $request->start : 0;
            $list = $query->skip($start)->take($length)->get();
            foreach ($list as $key => $val) {
                $list[$key]->status = $this->status[$val->status];
                $list[$key]->created = date('d-m-Y', strtotime($val->created));
            }
            $count = count($list);
            $header = $this->cib();
            $details = [
                "message" => "List fetched",
                "recordsFiltered" => $count,
                "recordsTotal" => $totalCount,
                "header" => $header,
                "data" => $list
            ];
            return $this->response('success', $details);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }


    public function exportCibRegistrations(Request $request)
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
                    $searchColumn = ['bank_details.id', 'bank_lists.name', 'bank_details.holderName', 'bank_details.account_number'];
                    $select = ['bank_details.id as cib_id', 'bank_details.cib_status as cib_status', 'bank_lists.name as bank_name', 'bank_details.sender_phone', 'bank_details.sender_email', 'bank_details.holderName', 'bank_details.account_number', 'bank_details.ifsc as ifsc', 'account_types.type as account_type', 'bank_details.bankuserid', 'bank_details.pan', 'bank_details.gst', 'bank_details.corporateid', 'bank_details.corporate_name', 'bank_details.status as status', 'bank_details.created_at as created', 'users.fullname as partner'];
                    $query = BankDetails::select($select)->join('users', 'users.id', '=', 'bank_details.user_id')->join('bank_lists', 'bank_lists.id', '=', 'bank_details.bank_id')->join('account_types', 'account_types.id', '=', 'bank_details.account_type');
                    $query->whereDate('bank_details.created_at', '>=', $startdate);
                    $query->whereDate('bank_details.created_at', '<=', $enddate);
                    $totalCount = $query->count();
                    if (!empty($search)) {
                        $query->where(function ($query) use ($searchColumn, $searchvalue) {
                            foreach ($searchColumn as $column) {
                                $query->orwhere($column, 'like', '%' . trim($searchvalue) . '%');
                            }
                        });
                    }
                    $data = $query->get();
                    $headerdata = $this->cib();

                    if (!empty($data)) {
                        $data1 = [];
                        foreach ($data as $key => $val) {
                            $data[$key]->status = $this->status[$val->status];
                            $data[$key]->created = date('d-m-Y', strtotime($val->created));
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
            }
             else {
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
    public function getCib(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cib_id' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            if ($request->has('type') && $request->type != "") {
                $this->type = $request->type;
            }
            $cib_id = $request->cib_id;
            $bankAccount = BankDetails::where('id', $request->cib_id)->with('bank_id:id,name')->with('remarks', function ($query) use ($cib_id) {
                $query->where('field_id', 0);
                $query->orderBy('created_at', 'DESC');
                $query->select('bank_details_id', 'remark');
            })->first();

            if (!$bankAccount) {
                return $this->response('notvalid', ['message' => "Account Not found"]);
            }
            $bankAccount = $bankAccount->toArray();

            $formData = BankForm::select('id', 'label', 'fieldname', 'type', 'required', 'placeholder')->where('status', 1)->where('form_type', $this->type)->with('values', function ($query) use ($bankAccount) {
                $query->where('bank_details_id', '=', $bankAccount['id']);
                $query->select('id as document_id', 'field_id', 'value', 'status');
            })->with('options')->with('remarks', function ($query) use ($bankAccount) {
                $query->where('bank_details_id', '=', $bankAccount['id']);
                $query->orderBy('created_at', 'DESC');
                $query->select('field_id', 'remark');
            })->where('bank_id', $bankAccount['bank_id'])->orderBy('index', 'ASC')->get()->toArray();

            // dd($formData);
            $bankAccount['account_type'] = $this->service[$bankAccount['account_type']];
            $details = [
                'cib_id' => $bankAccount['id'],
                'account_type' => AccountType::where('id', $bankAccount['account_type'])->pluck('type')->first(),
                'status' => $bankAccount['status'],
                'bank' => $bankAccount['bank_id']['name'],
                'account_information' => $bankAccount,
                'form' => $formData,
            ];
            return $this->response('success', ['message' => 'fetched success.', 'data' => $details]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function updateCib(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cib_id' => 'required',
                'status' => 'required|numeric|in:1,2',
                'remarks' => 'required_if:status,==,2',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $bankAccount = BankDetails::find($request->cib_id);

            if (!$bankAccount) {
                return $this->response('notvalid', ['message' => "Account Not found"]);
            }
            if ($request->prefix) {
                $checkPrefixs = BankDetails::where('id', '!=', $request->cib_id)->where('prefix', $request->prefix)->get()->toArray();
                if (!empty($checkPrefixs)) {
                    return $this->response('notvalid', ['message' => "Prefix has already been taken."]);
                }
            }

            if ($request->status == 1) {
                $bankAccount->admin_approved = 1;
                $bankdetails = BankDetailsData::where('bank_details_id', $request->cib_id)->get()->pluck('status')->toArray();
                if (in_array(2, $bankdetails)) {
                    $request->status = 2;
                } elseif (in_array(0, $bankdetails)) {
                    $request->status = 0;
                }
            }

            $bankAccount->status = $request->status;

            if ($request->prefix) {
                $bankAccount->prefix = $request->prefix;
            } else {
                $bankAccount->prefix = '';
            }
            if ($request->va_ifsc) {
                $bankAccount->va_ifsc = $request->va_ifsc;
            } else {
                $bankAccount->va_ifsc = '';
            }

            if ($request->profile_id) {
                $bankAccount->profile_id = $request->profile_id;
            } else {
                $bankAccount->profile_id = '';
            }
            if ($request->vpa_id) {
                $bankAccount->vpa_id = $request->vpa_id;
            } else {
                $bankAccount->vpa_id = '';
            }
            if ($request->subvpa_id) {
                $bankAccount->subvpa_id = $request->subvpa_id;
            } else {
                $bankAccount->subvpa_id = '';
            }



            if ($request->bc_id) {
                $bankAccount->bc_id = $request->bc_id;
            } else {
                $bankAccount->bc_id = '';
            }

            if ($request->passcode) {
                $bankAccount->passcode = $request->passcode;
            } else {
                $bankAccount->passcode = '';
            }

             if ($request->Bank_Name) {
                $request->Bank_Name = $request->Bank_Name;
            } else {
                 $request->Bank_Name = '';
            }

            //new code added
            if ($bankAccount->bank_id == 3) {
                $bankAccount->mcc_code = $request->mcc_code;
                $bankAccount->mdr_percantage = $request->mdr_percantage;
                $bankAccount->minimum_charge = $request->minimum_charge;
                $bankAccount->firstname = $request->fname;
                $bankAccount->lastname = $request->lname;
                $bankAccount->settlement_bank_name = $request->Bank_Name;
            }

            $bankAccount->update();

            if ($request->has('remarks')) {
                $data = [
                    'field_id' => 0,
                    'bank_details_id' => $request->cib_id,
                    'remark' => $request->remarks,
                ];
                BankDetailsRemark::insert($data);
            } else {
                $data = [
                    'field_id' => 0,
                    'bank_details_id' => $request->cib_id,
                    'remark' => "Approved",
                ];
                BankDetailsRemark::insert($data);
            }
            $response = [
                'method' => 'success',
                'message' => json_encode($request->status)
            ];

            if ($request->status == 1) {
                $response = $this->cibregister($request->cib_id, $request);

            }
            return $this->response($response['method'], ['message' => $response['message']]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function cibregister($cib_id, $request = NULL)
    {


        $bankAccount = BankDetails::find($cib_id);

        $bankList = BankList::find($bankAccount->bank_id);

        //Default Notification set
        $NotificationData = $this->accountApprovedNotification($bankAccount->user_id);

        //Default Approved message
        $response = [
            'method' => 'success',
            'message' => 'Bank Account Approved'
        ];

        //no cib register in case of yes
        if ($bankAccount->bank_id == 2) {
            $bankAccount->status = 1;
            $bankAccount->cib_status = 2;
            $bankAccount->update();

            //add notification
            $this->addNotification($NotificationData);
            return $response;
        } elseif ($bankAccount->bank_id == 3) {
            $bankAccountData = BankDetails::select("id")->with('bankData:id,bank_details_id,name,value')->where(["id" => $cib_id])->first();
            $apiId="20241";

            $franchiseValue = $brandValue = "";
            // $name = explode(' ', $bankAccount->sender_name);

            if (isset($bankAccountData->bankData)) {
                foreach ($bankAccountData->bankData->toArray() as $key => $value) {
                    if ($value['name'] == "franchise") {
                        $franchiseValue = $value['value'];
                    } elseif ($value['name'] == "brandname") {
                        $brandValue = $value['value'];
                    };
                }
            }
            $para = [
                "apiId" => $apiId,
                "bank_id" => $bankAccount->bank_id,
                "user_id" => $bankAccount->user_id,
                "accountNumber" => $bankAccount->account_number,//settelment accno
                "ifsc" => $bankAccount->ifsc, //settelment ifsc
                "bankName" => $bankAccount->settlement_bank_name,
                "beneName" => $bankAccount->corporate_name,
                "mobileNumber" => $bankAccount->sender_phone,
                "firstName" => $request->fname,
                "lastName" =>  $request->lname,
                "franchiseName" => $franchiseValue,
                "brandName" => $brandValue,
                "mccCode" => $request->mcc_code,
                "vpa" => $request->vpa_id,
                "subvpa" => $request->subvpa_id,
                "mdr_per" => $request->mdr_percantage,
                "min_charge" => $request->minimum_charge,
            ];

            $merchant_register = Curl::merchantRegistraion($para);
  //dd($merchant_register);
            if (isset($merchant_register['status']) && $merchant_register['status']) {
            $bankAccount->status = 1;
            $bankAccount->cib_status = 2;
            $bankAccount->update();
            $response['message'] = $merchant_register['message'];
            }else{
                $bankAccount->status = 0;
                $bankAccount->update();
                $response['method'] = 'exception';
                $response['message'] = 'Exception Error!';
                if (!empty($merchant_register) && !$merchant_register['status']){
                    $response['message'] = $merchant_register['message'];
                    // if($merchant_register['status_code'] == 422){
                    //     $response['message'] = json_encode($merchant_register['errors']);
                    // }
                }

            }
            return $response;
        }
        //cib register in case of icici bank
        elseif ($bankAccount->bank_id == 1) {
            if ($bankAccount->cib_status == 0) {
                //register cib
                $cib_register = Curl::cibregister(array("accno" => $bankAccount->account_number, "urn" => $bankAccount->urn, "corpid" => $bankAccount->corporateid, "bank_userid" => $bankAccount->bankuserid));
                //check cib response
                if (!empty($cib_register) && isset($cib_register['statuscode']) && isset($cib_register['status']) && isset($cib_register['responsecode']) && $cib_register['responsecode'] == 1 && $cib_register['status'] && $cib_register['statuscode'] == "200") {
                    $bankAccount->status = 0;
                    $bankAccount->cib_status = 1; //applied but pending approval from partner side
                    $bankAccount->urn = $cib_register['data']->URN;
                    $bankAccount->update();
                    $response['message'] = $cib_register['data']->Message;
                    return $response;
                } else {
                    //check if cib already registered
                    if (!empty($cib_register) && isset($cib_register['data']) && isset($cib_register['data']->ErrorCode) && $cib_register['data']->ErrorCode == "995109") {
                        $bankAccount->cib_status = 1;
                    }
                    $bankAccount->status = 0;
                    $bankAccount->update();
                    $response['method'] = 'exception';
                    $response['message'] = 'Exception Error!';
                    if (!empty($cib_register) && isset($cib_register['data'])) {
                        $response['message'] = $cib_register['data']->Message;
                    }

                    return $response;
                }
            }
        }
        return $response;
    }

    public function accountApprovedNotification($user_id)
    {
        $NotificationData = [
            'title' => "Bank added - Start transacting",
            'content' => "You’re all set! Let’s make your first transaction with SprintNXT now?",
            'user_id' => $user_id
        ];
        return $NotificationData;
    }
    public function updateDocument(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cib_id' => 'required',
                'field_id' => 'required',
                'remark' => 'required',
                'status' => 'required|in:1,2'
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $bankAccount = BankDetails::find($request->cib_id);

            if (!$bankAccount) {
                return $this->response('notvalid', ['message' => "Account Not found"]);
            }

            $BankDetailsData = BankDetailsData::where('bank_details_id', $request->cib_id)->where('field_id', $request->field_id)->first();

            if (!$BankDetailsData) {
                return $this->response('notvalid', ['message' => "Document not refer to the account"]);
            }

            $BankDetailsData->status = $request->status;
            $BankDetailsData->update();
            $data = [
                'field_id' => $request->field_id,
                'bank_details_id' => $request->cib_id,
                'remark' => $request->remark,
            ];
            BankDetailsRemark::insert($data);

            if ($request->status == 1) {
                $bankdetails = BankDetailsData::where('bank_details_id', $request->cib_id)->get()->pluck('status')->toArray();
                if (in_array(2, $bankdetails)) {
                    $request->status = 2;
                } elseif (in_array(0, $bankdetails)) {
                    $request->status = 0;
                }
            } else {
                $request->status = 2;
            }

            $bankAccount = BankDetails::find($request->cib_id);


            if ($request->status == 1) {
                if ($bankAccount->admin_approved == 1) {
                    $bankAccount->status = $request->status;
                    $bankAccount->update();
                   // $response = $this->cibregister($request->cib_id);
                    return $this->response('success', ['message' => 'Success']);
                }
            } else {
                $bankAccount->status = $request->status;
                $bankAccount->update();
            }

            return $this->response('success', ['message' => "Updated Successfully"]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function getCibStatus()
    {
        $bankAccounts = BankDetails::where('cib_status', 1)->get()->toArray();

        if (!empty($bankAccounts)) {
            foreach ($bankAccounts as $bankAccount) {
                $cibStatus = [
                    'corpid' => $bankAccount['corporateid'],
                    'bank_userid' => $bankAccount['bankuserid'],
                    'urn' => $bankAccount['urn']
                ];
                $cib_register = Curl::cibstatus($cibStatus);
                if (!empty($cib_register)) {
                    if ($cib_register['data']->status == "Registered") {
                        $account = BankDetails::find($bankAccount['id']);
                        $account->status = 1;
                        $account->cib_status = 2;
                        $account->update();
                        echo 'Updated';
                        $NotificationData = $this->accountApprovedNotification($account->user_id);
                        $this->addNotification($NotificationData);
                    } else {
                        echo 'Not updated';
                    }
                } else {
                    echo 'Response incorrect';
                }
            }
        }
    }


    public function getSingleCibStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'cib_id' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            if ($request->has('type') && $request->type != "") {
                $this->type = $request->type;
            }
            $bankAccount = BankDetails::where('id', $request->cib_id)->first();

            if (!$bankAccount) {
                return $this->response('notvalid', ['message' => "Account Not found"]);
            }
            $bankAccount = $bankAccount->toArray();
            if ($bankAccount['cib_status'] == 0) {
                return $this->response('notvalid', ['message' => "CIB not registered"]);
            }
            if ($bankAccount['cib_status'] == 2) {
                return $this->response('notvalid', ['message' => "CIB already approved"]);
            }

            if ($bankAccount['cib_status'] == 1) {
                $cibStatus = [
                    'corpid' => $bankAccount['corporateid'],
                    'bank_userid' => $bankAccount['bankuserid'],
                    'urn' => $bankAccount['urn']
                ];
                $cib_register = Curl::cibstatus($cibStatus);
                if (!empty($cib_register)) {
                    if ($cib_register['data']->status == "Registered") {
                        $account = BankDetails::find($bankAccount['id']);
                        $account->status = 1;
                        $account->cib_status = 2;
                        $account->update();
                        $NotificationData = $this->accountApprovedNotification($account->user_id);
                        $this->addNotification($NotificationData);
                    }
                    return $this->response('success', ['message' => $cib_register['data']->status]);
                } else {
                    return $this->response('exception');
                }
            }
            return $this->response('exception');
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    // Added by @vinay on 09/10/24
    public function modifyCib(Request $request) {
        try {
            $validator = Validator::make($request->all(), [
                'cib_id' => 'required',
                'remarks' => 'required',
                'expiry_date' => 'required'
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $cib = BankDetails::find($request->cib_id);
            if($cib->cbsedit == 1) {
                return $this->response('notvalid', ['message' => "Request is already been processed!"]);
            }
            // Make permission and remarks phrase
            $permission_phrase = Carbon::now().": ".Auth::user()->fullname."(".Auth::user()->username.") requested to update the cib";
            $remarks_phrase = Carbon::now().": ".$request->remarks;
            $cib->cbsedit = 1;
            $cib->expiry_date = $request->expiry_date;
            $cib->cbsedit_permission_by = $cib->cbsedit_permission_by .$permission_phrase.">>>";
            $cib->cbsedit_remarks = $cib->cbsedit_remarks .$remarks_phrase.">>>";
            $cib->save();
            if($cib) {
                $usr = MerchantUser::find($cib->user_id);
                $send_username = $usr->fullname;
                $today_date = Carbon::now()->format('Y-m-d');
                $expiry_date = Carbon::parse($request->expiry_date)->format('Y-m-d');
                $reqData = array();
                // Where to send
                $reqData['expiry_date'] = $expiry_date;
                $reqData['send_username'] = $send_username;
                $reqData['today_date'] = $today_date;
                $req = [
                    "to" => $usr->email,
                    "subject"=> "SprintNXT || Important: SSL Certificate Expiry and Service Downtime on ".$expiry_date,
                    "template"=> Emailtemplate::cbsContent($reqData),
                ];
                $data = Email::sendemail($req);
                return $this->response('success', ['message' => "Request made successfully!"]);

            }
            else {
                return $this->response('internalservererror', ['message' => "Failed to request"]);
            }
        }
        catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }

    }

}
