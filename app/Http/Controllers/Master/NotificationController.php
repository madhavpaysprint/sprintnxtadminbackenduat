<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\HeaderTrait;
use App\Models\Notification;
use App\Models\User;
use App\Models\NotificationStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request as Input;
class NotificationController extends Controller
{
    use CommonTrait,HeaderTrait;

    public function addNotification(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'title' => 'required',
                'content' => 'required',
                'user_type' => 'required|in:0,1',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $details = new Notification();
            $details->title = $request->title;
            $details->content = $request->content;
            $details->user_type = $request->user_type;
            if($request->has('start_date')){
                $details->start_date = $request->start_date;
            }else{
                $details->start_date = date('Y-m-d');
            }
            if($request->has('end_date')){
                $details->end_date = $request->end_date;
            }else{
                $details->end_date = date('Y-m-d');
            }
            $details->save();
            $notice = $details->id;
            if($notice){
                if ($request->user_type == 1) {
                    $users = $request->user;
                    $notifyUsers = explode(',',$users);
                    if(!empty($notifyUsers)){
                        foreach ($notifyUsers as $notifyUser) {
                            $data = array(
                                'user_id' => $notifyUser,
                                'notification_id' => $notice,
                                'status' => 0
                            );
                            NotificationStatus::insert($data);
                        }
                    }
                }
                $notificationDetails = [
                    'title' => $request->title,
                    'content' => $request->content,
                    'logo' => "",
                    'url' => "",
                ];
                return $this->response('success', ['message'=>"Notification Added Successfully.",'data' => $notificationDetails]);
            }else{
                return $this->response('apierror');
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function notificationList(Request $request)
    {
        try {
            $search = $request->search;
            $orderby= $request->orderby;
            $order  = $request->order;
            $searchColumn = ['id','title'];
            $select = ['id','title','start_date','end_date',"created_at"];
            $query = Notification::select($select);
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
                $list[$key]->created = date('d-m-Y',strtotime($val->created_at));
            }
            $count  = count($list);
            $header = $this->notifications();
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