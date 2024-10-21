<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\CommonTrait;
use Illuminate\Http\Request;
use App\Models\Charge;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class ChargesController extends Controller
{
    use CommonTrait;

    public function getcharges(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'service' => 'required',
            ]);
    
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $charge = Charge::where('userid',Auth::user()->id)->orderBy('id','DESC')->get()->toArray();
             
            $charges['id'] = $charge[0]['id'];
            $charges['userid'] = $charge[0]['userid'];
            $commission = json_decode($charge[0]['commission'],true);

            if(array_key_exists(strtolower($request->service),$commission[strtolower($request->name)])){  
                $charges['commission'] = $commission[strtolower($request->name)][strtolower($request->service)];
            }else{
                $charges['commission'] = array();
            }
            $charges['type'] = $charge[0]['type'];

            return $this->response('success', ['form' => $charges]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        } 
    }

    public function savecharges(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'commission' => 'required',
            ]);

            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
           
            $details = new Charge();
            $details->userid = $request->userid;
            $details->commission = $request->commission;
            $details->type = $request->type;
            $data = $details->save();
           
            $details = json_decode($request->data);
            
           return $this->response('success', ['form' => $data]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }      
    }

    public function deletecharges(Request $request) {
        $id = $request->id;

        try{
            $validator = Validator::make($request->all(), [
                'id' => 'required|numeric',             
            ]);
            
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $charges = Charge::find($id);
           
            if(isset($charges) && !empty($charges)){
                $data = $charges->delete();
                } else{
                return response()->json(['message' => "Id is not Found"]);
            }

            return $this->response('success', ['form' => $data]);
            } catch (\Throwable $th) {
                return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }  

    public function filter(Request $request){
        try{
            $searchby      = trim(strip_tags($request->searchby));
            $searchvalue   = trim(strip_tags($request->search));
            $status        = trim(strip_tags($request->status));

            $query = User::select(
                'id',
                'fullname',
                'phone',
                'is_kyc',
                'status'
            );

                if ($searchby  != "" && $searchvalue != "") {
                            
                    if ($searchby == 'fullname') {
                        $val     = 'fullname';
                    } else if ($searchby == 'phone') {
                        $val     = 'phone';
                    } else if ($searchby == 'status'){
                        $val     = 'status';
                    } else {
                        $val     = '';
                    }
                    $query->where($val, $searchvalue);
                   
                } elseif ($searchvalue != "" && $searchby  == "") {
                    $query->where(function ($query) use ($searchvalue) {
                        $query->where('fullname', 'like',  trim($searchvalue) . '%')
                            ->orwhere('phone', 'like', trim($searchvalue) . '%');
                    });
                }
                           
            $recordsTotal = $query->count();

            $data = $query->get()->toArray();
            $recordsFiltered = $query->count();

            if( !empty($data)){
                $status = 'success';
                $datas =  ['statuscode' => 200, 'status' => true, 'responsecode' => 1, 'TotalRecords' => $recordsTotal, 'recordsFiltered' => $recordsFiltered, 'data' => $data, 'message'=>"Success"];
            
            }else {
                $code = 201;
                $output = [
                    'code'    => $code,
                    'responsecode'    => 2,
                    'recordsFiltered' => 0,
                    'recordsTotal'    => $recordsTotal,
                    'status'  => false,
                    'message' => 'Incorrect Info!',
                ];
                return response()->json($output,$code);               
            }       
            return $this->response($status, $datas);                
        } catch(\Throwable $th){
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
}
