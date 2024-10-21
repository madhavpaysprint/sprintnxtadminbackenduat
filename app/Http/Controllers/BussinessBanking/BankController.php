<?php

namespace App\Http\Controllers\BussinessBanking;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Models\BankList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\HeaderTrait;
use Illuminate\Support\Facades\DB;
use App\Models\BankForm;
use App\Models\BankFormDropdowns;

class BankController extends Controller
{
    use CommonTrait,HeaderTrait;
    public function __construct()
    {
        $this->status = ['0'=>'Deactive','1'=>'Active'];
	}
    public function bankList(Request $request)
    {
        try {
            $search = $request->search;
            $orderby= $request->orderby;
            $order  = $request->order;
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $type = $request->type;
            $searchColumn = ['id','name'];
            $select = ['id','name','logo','status',"created_at"];
            $query = BankList::select($select);
            if($startdate){
                $query->whereDate('created_at', '>=', $startdate);
            }
            if($enddate){
                $query->whereDate('created_at', '<=', $enddate);
            }
            if($type){
                $query->where('payout_cred_status', '=', 1);
            }
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
                $list[$key]->created = date('d-m-Y',strtotime($val->created_at));
            }
            $count  = count($list);
            $header = $this->bankslist();
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


    /* Bank List */
    public function addBank(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'logo' => 'required',
                'active_logo' => 'required',
                'suffix' => 'required',
                'class' => 'required',
                'colorcode' => 'required',
                'singlecard' => 'required',
                'singleback' => 'required',
                'maincard' => 'required',
                'service_type' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $bankList = new BankList();
            $bankList->name = $request->name;
            $bankList->suffix = $request->suffix;
            $bankList->active_logo = $request->active_logo;
            $bankList->logo = $request->logo;
            $bankList->ifsc = $request->ifsc;
            $bankList->class = $request->class;
            $bankList->remarks = $request->remarks;
            $bankList->service_type = $request->service_type;
            if ($request->has('singleback')) {
                $bankList->singleback = $request->singleback;
            }
            if ($request->has('singlecard')) {
                $bankList->singlecard = $request->singlecard;
            }
            if ($request->has('maincard')) {
                $bankList->maincard = $request->maincard;
            }
            if ($request->has('colorcode')) {
                $bankList->colorcode = $request->colorcode;
            }
            $bankList->save();
            if($bankList->id){
                return $this->response('success', ['message'=>'Added Succesfully','data' => $bankList]);
            }else{
                return $this->response('exception');
            }

        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }


    }

    public function viewBank(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $bankList = BankList::find($request->id);
            if ($bankList) {
                return $this->response('success', ['message' => 'Details fetched successfully','data' => $bankList]);
            } else {
                return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'name' => 'required',
                'logo' => 'required',
                'active_logo' => 'required',
                'suffix' => 'required',
                'colorcode' => 'required',
                'class' => 'required',
                'singlecard' => 'required',
                'singleback' => 'required',
                'maincard' => 'required',
                'status' => 'required|in:0,1',
                'service_type' => 'required|numeric'
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $id = $request->id;
            $bankList = BankList::find($id);

            if ($bankList) {
                $bankList->name = $request->name;
                $bankList->logo = $request->logo;
                $bankList->suffix = $request->suffix;
                $bankList->active_logo = $request->active_logo;
                $bankList->service_type = $request->service_type;
                $bankList->status = $request->status;
                // dd($images);
                $bankList->ifsc = $request->ifsc;
                if ($request->has('singleback')) {
                    $bankList->singleback = $request->singleback;
                }
                if ($request->has('singlecard')) {
                    $bankList->singlecard = $request->singlecard;
                }
                if ($request->has('maincard')) {
                    $bankList->maincard = $request->maincard;
                }
                if ($request->has('colorcode')) {
                    $bankList->colorcode = $request->colorcode;
                }
                $bankList->update();

                return $this->response('success', ['message'=>'Updated Successfully.','data' => $bankList]);

            } else {
                return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    // public function destroy(Request $request)
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

    //         $product = BankList::find($id);
    //         if (isset($product) && !empty($product)) {
    //             $data = $product->delete();

    //         } else {
    //             return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
    //         }
    //         return $this->response('success', ['form' => $data]);

    //     } catch (\Throwable $th) {
    //         return $this->response('internalservererror', ['message' => $th->getMessage()]);
    //     }

    // }
    /* Bank Form */


    public function add(Request $request)
    {

        try {
            $bankdropdownlist = array();
            $validator = Validator::make($request->all(), [
                'bank_id' => 'required',
                'type' => 'required|in:file,text,select,radio,checkbox',
                'label' => 'required',
                'required' => 'required|in:Y,N',
                'sample' => 'required_if:type,==,file',
                'fillable_form' => 'required_if:type,==,file',
                'options' => 'required_if:type,==,select|required_if:type,==,radio|required_if:type,==,checkbox',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $banklist = BankList::find($request->bank_id);
            if (!empty($banklist)) {
                $options = "";
                if (strtolower($request->type) == 'select' || strtolower($request->type) == 'radio' || strtolower($request->type) == 'checkbox') {
                    // $optionsValues = json_decode($request->data);
                    // if(!empty($optionsValues)){
                    //      foreach ($optionsValues as $optionsValue) {
                    //          $data = array('field_id' => $bankFormAdditionID, 'name' => $optionsValue->name, 'value' => $optionsValue->val);
                    //          BankFormDropdowns::insert($data);
                    //      }
                    // }
                    if ($request->has('options')) {
                        $options = $request->options;
                    }
                }
                $result = substr($request->label, 0, 10);
                $fieldname = str_replace(' ', '', $result);
                $fieldname = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $fieldname));

                $bankform = new BankForm();
                $bankform->bank_id = $banklist->id;
                $bankform->type = $request->type;
                $bankform->fieldname = $fieldname;
                $bankform->label = $request->label;
                $bankform->required = $request->required;
                $bankform->placeholder = $request->placeholder;
                $bankform->form_type = "details";
                $bankform->options =  $options;
                if ($request->has('sample')) {
                    $bankform->sample = $request->sample;
                }
                if ($request->has('fillable_form')) {
                    $bankform->fillable_form = $request->fillable_form;
                }
                if ($request->has('index')) {
                    $bankform->index = $request->index;
                }else{
                    $bankform->index = 0;
                }
                $bankform->save();
                $bankFormAdditionID = $bankform->id;
                if($bankFormAdditionID){
                    return $this->response('success', ['message'=>'Bank form added successfully!','data' => $bankform]);
                }else{
                    return $this->response('exception');
                }

            } else {
                return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function showBankForm(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $form = BankForm::find($request->id);
            if (!empty($form)) {
                return $this->response('success', ['message' => 'Details fetched successfully','data' => $form]);
            } else {
                return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }

    }

    public function listBankForm(Request $request)
    {
        try {
            $search = $request->search;
            $orderby= $request->orderby;
            $order  = $request->order;
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $startdate = !empty($startdate)?date('Y-m-d',strtotime($startdate)):date("Y-m-d", strtotime ( '-12 month' , strtotime ( date('Y-m-d') ) ));
            $enddate = !empty($enddate)?date('Y-m-d',strtotime($enddate)):date('Y-m-d');
            $searchColumn = ['bank_forms.id','bank_forms.bank_id','bank_lists.name','bank_forms.status'];
            $select = ['bank_forms.id as form_id','bank_forms.bank_id as bank_id','bank_lists.name as bank_name','bank_forms.label','bank_forms.type','bank_forms.required','bank_forms.status as status','bank_forms.created_at'];
            $query = BankForm::select($select)->join('bank_lists', 'bank_lists.id', '=', 'bank_forms.bank_id');
            $query->whereDate('bank_forms.created_at', '>=', $startdate);
            $query->whereDate('bank_forms.created_at', '<=', $enddate);
            $totalCount = $query->count();
            if(!empty($search)){
                $query->where(function($query) use ($searchColumn, $search){
                    foreach($searchColumn as $column){
                        $query->orwhere($column, 'like', '%' .  trim($search) . '%');
                    }
                });
            }
            (!empty($orderby) && !empty($order))? $query->orderBy("bank_forms.".$orderby, $order): $query->orderBy("bank_forms.id", "desc");
            $length = (!empty($request->length))? $request->length: 20;
            $start  = (!empty($request->start))? $request->start: 0;
            $list   = $query->skip($start)->take($length)->get();
            foreach($list as $key => $val){
                $list[$key]->status = $this->status[$val->status];
                $list[$key]->created = date('d-m-Y',strtotime($val->created_at));
            }
            $count  = count($list);
            $header = $this->bankform();
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

    public function updatebankform(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'type' => 'required|in:file,text,select,radio,checkbox',
                'label' => 'required',
                'required' => 'required|in:Y,N',
                'status' => 'required|in:0,1',
                'sample' => 'required_if:type,==,file',
                'fillable_form' => 'required_if:type,==,file',
                'options' => 'required_if:type,==,select|required_if:type,==,radio|required_if:type,==,checkbox',

            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $id = $request->id;

            $formdata = BankForm::find($id);
            if (!empty($formdata)) {
                $options = "";
                if (strtolower($request->type) == 'select' || strtolower($request->type) == 'radio' || strtolower($request->type) == 'checkbox') {
                    if ($request->has('options')) {
                        $options = $request->options;
                    }
                }
                $formdata->placeholder = $request->placeholder;
                if ($request->has('index')) {
                    $formdata->index = $request->index;
                }else{
                    $formdata->index = 0;
                }
                $formdata->label = $request->label;
                $formdata->type = $request->type;
                $formdata->required = $request->required;
                $formdata->options = $options;
                if ($request->has('sample')) {
                    $formdata->sample = $request->sample;
                }
                if ($request->has('fillable_form')) {
                    $formdata->fillable_form = $request->fillable_form;
                }
                $formdata->status = $request->status;
                $formdata->update();

                return $this->response('success', ['message'=>'Updated Succesfully','data' => $formdata]);
            } else {
                return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
}
