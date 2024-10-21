<?php

namespace App\Http\Controllers\BussinessBanking;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Models\BankList;
use App\Models\User;
use App\Models\BankDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\HeaderTrait;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller
{
    use CommonTrait,HeaderTrait;
    public function __construct()
    {
        $this->status = ['0'=>'Deactive','1'=>'Active'];
        $this->kycstatus = ['0'=>'Pending','1'=>'In Process','2'=>'Completed','3'=>'Rejected'];
    }
    public function partnerList(Request $request)
    {
        try {
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $orderby= $request->orderby;
            $order  = $request->order;
            $status        = $request->status;
            $start         = $request->start;
            $length        = $request->length;

            $userid = $request->user_id;
            $bankid = $request->bank_id;
            $searchby = $request->searchby;
            $searchvalue = $request->searchvalue;
            $onboardingStatus = $request->OnboardingStatus;

            // $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) :  null;
            // $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : null;


            $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : date('Y-m-d');
            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : date('Y-m-d');

            $searchColumn = ['users.fullname','users.username','users.email','users.phone','users.status','users.created_at'];

            $select = ['users.id','users.fullname as partner_name','users.username as user_id','users.email',
                'users.phone','users.status','users.is_kyc','users.created_at','users.balance'];

            $query = DB::connection('pgsql')->table('users');
            $query->select($select);

            if (!empty($startdate) && !empty($enddate)) {
                $query->whereDate('users.created_at', '>=', $startdate)
                    ->whereDate('users.created_at', '<=', $enddate);
            }
            if($userid) {
                $query->where('users.id', $userid);
            }

            if (!empty($bankid)) {
                $query->join('bank_details', 'users.id', '=', 'bank_details.user_id')
                      ->where('bank_details.bank_id', $bankid)
                      ->where('bank_details.status', 1);
            }

  
            if ($status) {
                $query->where('users.status', $status);
            }
            if (!empty($onboardingStatus) || $onboardingStatus === "0" || $onboardingStatus === 0 || $onboardingStatus === "1" || $onboardingStatus === 1) {
                $query->where('users.is_kyc', $onboardingStatus);
            }

            // if ($searchby === "partner_name" && !empty($searchvalue)) {
            //     $query->where('users.fullname', '=', $searchvalue);
            // }
            if ($searchby === "email" && !empty($searchvalue)) {
                $query->where('users.email', '=', $searchvalue);
            }
            if ($searchby === "phone" && !empty($searchvalue)) {
                $query->where('users.phone', '=', $searchvalue);
            }
            // if ($searchby === "is_kyc" && !empty($searchvalue)) {
            //     $query->where('users.is_kyc', '=', $searchvalue);
            // }

            $recordsTotal = $query->count();
            if(!empty($searchvalue)){
                $query->where(function($query) use ($searchColumn, $searchvalue){
                    foreach($searchColumn as $column){
                        $query->orWhere($column, 'like', '%' .  trim($searchvalue) . '%');
                    }
                });
            }

            (!empty($orderby) && !empty($order))? $query->orderBy('users.'.$orderby, $order): $query->orderBy("users.id", "desc");
            $length = (!empty($request->length))? $request->length: 20;
            $start  = (!empty($request->start))? $request->start: 0;
            $data   = $query->skip($start)->take($length)->get();
            $recordsFiltered  = count($data);

            foreach($data as $key => $val){
                $linkedaccounts = BankDetails::select('bank_id','user_id')->where('user_id',$val->id)->where('status',1)->groupBy('bank_id')->get();
                $banks = array();
                foreach($linkedaccounts as $linkedaccount){
                    $bankDetails = BankList::select('name')->where('id',$linkedaccount->bank_id)->first();
                    $banks[] = $bankDetails->name;
                }
                $data[$key]->status = $this->status[$val->status];
                $data[$key]->onboarding_status = $this->kycstatus[$val->is_kyc];
                $data[$key]->balance = $val->balance;
                $data[$key]->active_banks = implode(', ', $banks);
                $data[$key]->createdat = date("d-m-Y",strtotime($val->created_at));
                unset($data[$key]->created_at);
            }


            $headerdata = $this->partners();
            if(!empty($data)){
                return $this->response('success', ['message' => "List fetched successfully!",'recordsFiltered' => $recordsFiltered,'recordsTotal'    => $recordsTotal,'header' => $headerdata,'data'=>$data]);
            }else{
                return $this->response('noresult', ['message' => "No record found.",'header' => $headerdata]);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function exportpartnerList(Request $request)
    {
        try {
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $searchvalue = $request->search;
            $status = $request->status;
            $userId = $request->user_id;
            $bankid = $request->bank_id;
            $onboardingStatus = $request->OnboardingStatus;


            $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : date('Y-m-d');
            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : date('Y-m-d');

            if ($this->date_difference($startdate, $enddate)) {
                $searchColumn = ['users.fullname', 'users.username', 'users.email', 'users.phone', 'users.status', 'users.created_at'];

                $select = ['users.id', 'users.fullname as partner_name', 'users.username as user_id', 'users.email', 'users.phone', 'users.balance', 'users.status', 'users.is_kyc', 'users.created_at'];

                $query = DB::connection('pgsql')->table('users');
                $query->select($select);
                $query->whereDate('users.created_at', '>=', $startdate);
                $query->whereDate('users.created_at', '<=', $enddate);

                if ($status) {
                    $query->where('users.status', $status);
                }
                if (!empty($onboardingStatus) || $onboardingStatus === "0" || $onboardingStatus === 0 || $onboardingStatus === "1" || $onboardingStatus === 1) {
                    $query->where('users.is_kyc', $onboardingStatus);
                }
                if ($userId) {
                    $query->where('users.id', $userId);
                }
                if (!empty($bankid)) {
                    $query->join('bank_details', 'users.id', '=', 'bank_details.user_id')
                          ->where('bank_details.bank_id', $bankid)
                          ->where('bank_details.status', 1);
                }
                if (!empty($searchvalue)) {
                    $query->where(function($query) use ($searchColumn, $searchvalue) {
                        foreach ($searchColumn as $column) {
                            $query->orWhere($column, 'like', '%' . trim($searchvalue) . '%');
                        }
                    });
                }
                $data = $query->get()->toArray();
                $headerdata = $this->partners();
                if (!empty($data)) {
                    $data1 = [];
                    foreach ($data as $key => $val) {
                        $linkedaccounts = BankDetails::select('bank_id', 'user_id')
                            ->where('user_id', $val->id)
                            ->where('status', 1)
                            ->groupBy('bank_id')
                            ->get();

                        $banks = [];
                        foreach ($linkedaccounts as $linkedaccount) {
                            $bankDetails = BankList::select('name')
                                ->where('id', $linkedaccount->bank_id)
                                ->first();
                            if ($bankDetails) {
                                $banks[] = $bankDetails->name;
                            }
                        }
                        $data[$key]->status = $this->status[$val->status];
                        $data[$key]->onboarding_status = $this->kycstatus[$val->is_kyc];
                        $data[$key]->active_banks = implode(', ', $banks);
                        $data[$key]->createdat = date("d-m-Y", strtotime($val->created_at));

                        $data[$key]->balance = $val->balance ?? '0.00';
                        unset($data[$key]->created_at);
                        $sub_array = [];
                        foreach ($headerdata as $head) {
                            $value = $head['name'];
                            $sub_array[$head['value']] = property_exists($val, $value) ? $val->$value : '';
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
                    'message' => "You can only export data of " . $this->allowed_days() . " Days"
                ]);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function updatePartner(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
                'status' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $user = DB::connection('pgsql')->table('users')->where('id', $request->user_id)->update(array('status'=>$request->status));

            return $this->response('success', ['message'=>'Updated Succesfully']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
}
