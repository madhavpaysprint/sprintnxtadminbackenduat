<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use Illuminate\Support\Facades\Validator;
use App\Models\ReportDownload;
use Illuminate\Http\Request;

class ReportDownloadController extends Controller
{
    use CommonTrait;

    public function reportDownload(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'service_id' => 'required|numeric',
                'user_id' => 'nullable|numeric',
                'bankid' => 'required|numeric',
                'requested_email' => 'required|email',
                'requested_mobile' => 'required|numeric|digits:10',
                'bankid' => 'required|numeric',
                'from_date' => 'required|date_format:Y-m-d',
                'to_date' => 'required|date_format:Y-m-d',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $checkReport = ReportDownload::where(
                [
                    'service_id' => $request->service_id,
                    'user_id' => $request->user_id,
                    'bankid' => $request->bankid,
                    'from_date' => $request->from_date,
                    'to_date' => $request->to_date,
                ]
            )->where('status','!=',1)->first();
            
            if ($checkReport == null) {
                $insert=[
                    'service_id' => $request->service_id,
                    'bankid' => $request->bankid,
                    'from_date' => $request->from_date,
                    'to_date' => $request->to_date,
                    'requested_email' => $request->requested_email,
                    'requested_mobile' => $request->requested_mobile,
                    'status' => 3
                ];

                if($request->user_id !=""){
                    $insert['user_id']=$request->user_id;
                }

                $request = ReportDownload::insert($insert);
            }else{
                return $this->response('success', ['message' => "Your report generation request has been submitted. Please wait for 10 minutes or try again after 10 minutes."]);
            }

            if ($request) {
                return $this->response('success', ['message' => "Report generation request successfully added!"]);
            } else {
                return $this->response('apierror');
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
}
