<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\HeaderTrait;
use App\Models\LogRequestResponseApi;
use App\Models\LogRequestResponseBank;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LogsController extends Controller
{
    use CommonTrait, HeaderTrait;

    public function requestLogApi(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required',
                'client_fullname' => 'required',
                'client_mobile' => 'required|numeric|digits:10',
                'client_email' => 'required|email',
                'service' => 'required',
                'api' => 'required',
                'uniquerefid' => 'required|numeric',
                'req_body' => 'required|json',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $logRequest = LogRequestResponseApi::updateOrCreate(
                [
                    'uniquerefid' => $request->uniquerefid,
                ],
                [
                    'username' => $request->username,
                    'client_fullname' => $request->client_fullname,
                    'client_mobile' => $request->client_mobile,
                    'client_email' => $request->client_email,
                    'service' => strtoupper($request->service),
                    'api' => $request->api,
                    'req_body' => $request->req_body,
                    'req_date' => date("Y-m-d"),
                    'req_time' => date("h:i:s"),
                ]
            );

            if ($logRequest) {
                return $this->response('success', ['message' => "APi Request Log Added Successfully.", 'data' => []]);
            } else {
                return $this->response('apierror');
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function responseLogApi(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'username' => 'required',
                'client_fullname' => 'required',
                'client_mobile' => 'required|numeric|digits:10',
                'client_email' => 'required|email',
                'api' => 'required',
                'uniquerefid' => 'required|numeric',
                'resp_body' => 'required|json',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $checkUniqueRef = LogRequestResponseApi::where(['uniquerefid' => $request->uniquerefid])->first();

            if ($checkUniqueRef == NULL) {
                return $this->response('incorrectinfo', ['message' => "Inavalid Log Detail!", 'data' => []]);
            }

            LogRequestResponseApi::where([
                'uniquerefid' => $request->uniquerefid
            ])->update([
                'username' => $request->username,
                'client_fullname' => $request->client_fullname,
                'client_mobile' => $request->client_mobile,
                'client_email' => $request->client_email,
                'service' => strtoupper($request->service),
                'service' => strtoupper($request->service),
                'api' => $request->api,
                'resp_body' => $request->resp_body,
                'resp_date' => date("Y-m-d"),
                'resp_time' => date("h:i:s"),
            ]);
            return $this->response('success', ['message' => "Api Log Updated Successfully.", 'data' => []]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function apiLogList(Request $request)
    {
        try {
            $search = $request->search;
            $orderby = $request->orderby;
            $order  = $request->order;
            $user_id  = $request->user_id;
            $searchColumn = ["uniquerefid", "service", "api"];
            $select = ["user_id", "service", "api", "uniquerefid", "req_body", "req_date", "req_time", "resp_body", "resp_date", "resp_time"];
            $query  = LogRequestResponseApi::select($select)->with('userlog:id,fullname');

            $totalCount = $query->count();

            if ($user_id != "") {
                $query->Where("user_id", $user_id);
            }

            if (!empty($search)) {
                $query->where(function ($query) use ($searchColumn, $search) {
                    foreach ($searchColumn as $column) {
                        $query->orwhere($column, 'like', '%' .  trim($search) . '%');
                    }
                });
            }
            // Date Filter
            if (!($request->from_date == "" && $request->to_date == "")) {
                $query->whereBetween('created_at', [$request->from_date . ' 00:00:00', $request->to_date . ' 23:59:59']);
            } else {
                $query->whereDate('created_at', Carbon::today());
            }
            (!empty($orderby) && !empty($order)) ? $query->orderBy($orderby, $order) : $query->orderBy("id", "desc");
            $length = (!empty($request->length)) ? $request->length : 20;
            $start  = (!empty($request->start)) ? $request->start : 0;
            $list   = $query->skip($start)->take($length)->get();
            foreach ($list as $key => $val) {
                $list[$key]->user_name = $val->userlog != null ? ucwords($val->userlog->fullname) : NULL;
                $list[$key]->req_date = date('d-m-Y', strtotime($val->req_date));
                $list[$key]->resp_date = ($val->resp_date != null) ?? date('d-m-Y', strtotime($val->resp_date));
                $list[$key]->req_time = date('H:i:s', strtotime($val->req_time));
                $list[$key]->resp_time = ($val->resp_time != null) ?? date('H:i:s', strtotime($val->resp_time));
            }
            $count  = count($list);
            $header = $this->apiLogHeader();
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

    public function requestLogBank(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|numeric|exists:users,id',
                'service' => 'required',
                'api' => 'required',
                'uniquerefid' => 'required|numeric',
                'req_body' => 'required|json',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $logRequest = LogRequestResponseBank::updateOrCreate(
                [
                    'user_id' => $request->user_id,
                    'uniquerefid' => $request->uniquerefid,
                ],
                [
                    'service' => strtoupper($request->service),
                    'api' => $request->api,
                    'req_body' => $request->req_body,
                    'req_date' => date("Y-m-d"),
                    'req_time' => date("h:i:s"),
                ]
            );

            if ($logRequest) {
                return $this->response('success', ['message' => "Bank Request Log Added Successfully.", 'data' => []]);
            } else {
                return $this->response('apierror');
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function responseLogBank(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|numeric|exists:users,id',
                'service' => 'required',
                'api' => 'required',
                'uniquerefid' => 'required|numeric',
                'resp_body' => 'required|json',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $checkUniqueRef = LogRequestResponseBank::where(['user_id' => $request->user_id, 'uniquerefid' => $request->uniquerefid])->first();
            if ($checkUniqueRef == NULL) {
                return $this->response('incorrectinfo', ['message' => "Inavalid Log Detail!", 'data' => []]);
            }

            LogRequestResponseBank::where([
                'user_id' => $request->user_id,
                'uniquerefid' => $request->uniquerefid
            ])->update([
                'service' => strtoupper($request->service),
                'api' => $request->api,
                'resp_body' => $request->resp_body,
                'resp_date' => date("Y-m-d"),
                'resp_time' => date("h:i:s"),
            ]);
            return $this->response('success', ['message' => "Bank Log Updated Successfully.", 'data' => []]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function bankLogList(Request $request)
    {
        try {
            $search = $request->search;
            $orderby = $request->orderby;
            $order  = $request->order;
            $user_id  = $request->user_id;
            $searchColumn = ["uniquerefid", "service", "api"];
            $select = ["user_id", "service", "api", "uniquerefid", "req_body", "req_date", "req_time", "resp_body", "resp_date", "resp_time"];
            $query  = LogRequestResponseBank::select($select)->with('userlog:id,fullname');

            $totalCount = $query->count();

            if ($user_id != "") {
                $query->Where("user_id", $user_id);
            }

            if (!empty($search)) {
                $query->where(function ($query) use ($searchColumn, $search) {
                    foreach ($searchColumn as $column) {
                        $query->orwhere($column, 'like', '%' .  trim($search) . '%');
                    }
                });
            }
            // Date Filter
            if (!($request->from_date == "" && $request->to_date == "")) {
                $query->whereBetween('created_at', [$request->from_date . ' 00:00:00', $request->to_date . ' 23:59:59']);
            } else {
                $query->whereDate('created_at', Carbon::today());
            }
            (!empty($orderby) && !empty($order)) ? $query->orderBy($orderby, $order) : $query->orderBy("id", "desc");
            $length = (!empty($request->length)) ? $request->length : 20;
            $start  = (!empty($request->start)) ? $request->start : 0;
            $list   = $query->skip($start)->take($length)->get();
            foreach ($list as $key => $val) {
                $list[$key]->user_name = $val->userlog != null ? ucwords($val->userlog->fullname) : NULL;
                $list[$key]->req_date = date('d-m-Y', strtotime($val->req_date));
                $list[$key]->resp_date = date('d-m-Y', strtotime($val->resp_date));
                $list[$key]->req_time = date('H:i:s', strtotime($val->req_time));
                $list[$key]->resp_time = date('H:i:s', strtotime($val->resp_time));
            }
            $count  = count($list);
            $header = $this->bankLogHeader();
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
