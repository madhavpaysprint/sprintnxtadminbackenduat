<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Models\AccountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\HeaderTrait;

class AccountController extends Controller
{
    use CommonTrait,HeaderTrait;
    public function __construct()
    {
        $this->status = ['0'=>'Deactive','1'=>'Active'];
    }
    public function accounttype(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $accountType = new AccountType();
            $accountType->type = $request->type;
            $accountType->save();

            return $this->response('success', ['message'=>"Account Type added successfully.",'data' => $accountType]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }

    }

    public function showaccount(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $accountType = AccountType::find($request->id);
            if ($accountType) {
                return $this->response('success', ['message'=>'Details fetched successfully.','data' => $accountType]);
            } else {
                return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }

    }

    public function updateaccount(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'type' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $account = AccountType::find($request->id);

            if (!empty($account)) {
                $account->type = $request->type;
                if($request->has('status')){
                    $account->status = $request->status;
                }
                $account->update();
                return $this->response('success', ['message'=>'Updated successfully.','data' => $account]);
            } else {
                return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    // public function deleteaccount(Request $request)
    // {

    //     try {
    //         $validator = Validator::make($request->all(), [
    //             'id' => 'required',
    //         ]);

    //         if ($validator->fails()) {
    //             $message = $this->validationResponse($validator->errors());
    //             return $this->response('validatorerrors', $message);
    //         }
    //         $id = $request->id;
    //         $account = AccountType::find($id);

    //         if (isset($account) && !empty($account)) {
    //             $data = $account->delete();
    //         } else {
    //             return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
    //         }

    //         return $this->response('success', ['form' => $data]);

    //     } catch (\Throwable $th) {
    //         return $this->response('internalservererror', ['message' => $th->getMessage()]);
    //     }
    // }

    public function list(Request $request)
    {
        try {
            $search = $request->search;
            $orderby= $request->orderby;
            $order  = $request->order;
            $searchColumn = ["id", "type"];
            $select = ['id','type','status',"created_at"];
            $query = AccountType::select($select);
            $totalCount = $query->count();
            if(!empty($search)){
                $query->where(function($query) use ($searchColumn, $search){
                    foreach($searchColumn as $column){
                        $query->orwhere($column, 'like', '%' .  trim($search) . '%');
                    }
                });                
            }
            (!empty($orderby) && !empty($order))? $query->orderBy($orderby, $order): $query->orderBy("id", "desc");
            $length = (!empty($request->length))? $request->length: 20;
            $start  = (!empty($request->start))? $request->start: 0;
            $list   = $query->skip($start)->take($length)->get();
            foreach($list as $key => $val){
                $list[$key]->status = $this->status[$val->status];
                $list[$key]->created_at = date('d-m-Y',strtotime($val->created_at));
            }
            $count  = count($list);
            $header = $this->accountTypeHeader();
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
}