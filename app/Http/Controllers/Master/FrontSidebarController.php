<?php
namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\HeaderTrait;
use App\Models\FrontMenu;
use App\Models\AccountType;
use App\Models\FrontMenuPermission;
use App\Models\FrontRole as Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class FrontSidebarController extends Controller
{
    use CommonTrait,HeaderTrait;

    public function accountTypes()
    {
        try {
            $accountTypes = AccountType::select('id', 'type')->where('status', 1)->get()->toArray();
            if (!empty($accountTypes)) {
                return $this->response('success', ['message' => 'List fetched successfully.', 'data' => $accountTypes]);
            } else {
                return $this->response('noresult');
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }

    }
    public function addMenuItem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'type' => 'required|in:item,group,collapse',
                'account_type' => 'numeric',
                'urlapi' => 'required',
                'icon' => 'required',
                'menu' => 'required',
                'parent' => 'numeric',
                'menu_order' => 'numeric',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            
            $fmenu = new FrontMenu();
            $fmenu->name = $request->name;
            $fmenu->type = $request->type;
            $fmenu->account_type = $request->account_type;
            if ($request->has('guard_name')) {
                $fmenu->guard_name = $request->guard_name;
            }
            $fmenu->urlapi = $request->urlapi;
            if($request->has('parent')){
                $fmenu->parent = $request->parent;
            }
            $fmenu->icon = $request->icon;
            if ($request->has('menu_order')) {
                $fmenu->menu_order = $request->menu_order;
            }
            $fmenu->menu = $request->menu;
            $fmenu->save();

            if ($fmenu->id) {
                return $this->response('success', ['message' => 'Menu Item added successfully.', 'data' => $fmenu]);
            } else {
                return $this->response('apierror');
            }

        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function listMenuItem(Request $request)
    {
        try {
            $search = $request->search;
            $orderby= $request->orderby;
            $order  = $request->order;
            $searchColumn = ["id", "name", "type"];
            $select = ["id", "name", "type", "guard_name", "urlapi", "parent", "icon", "menu_order", "menu","created_at"];
            $query = FrontMenu::select($select);
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
            $header = $this->menuItems();
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

    public function updateMenuItem(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'name' => 'required',
                'type' => 'required|in:item,group,collapse',
                'account_type' => 'numeric',
                'urlapi' => 'required',
                'icon' => 'required',
                'menu' => 'required',
                'parent' => 'numeric',
                'menu_order' => 'numeric',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            

            $menutype = FrontMenu::find($request->id);

            if ($menutype) {
                $menutype->name = $request->name;
                $menutype->type = $request->type;
                $menutype->account_type = $request->account_type;
                $menutype->guard_name = $request->guard_name;
                $menutype->urlapi = $request->urlapi;
                if($request->has('parent')){
                    $menutype->parent = $request->parent;
                }
                $menutype->icon = $request->icon;
                $menutype->menu_order = $request->menu_order;
                $menutype->menu = $request->menu;
                $data = $menutype->update();

            } else {
                return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
            }

            return $this->response('success', ['form' => $menutype]);

        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);

        }
    }

    public function getMenuItem(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $id = $request->id;
            $product = FrontMenu::find($id);
            if (isset($product) && !empty($product)) {
                $data = $product->get();
            } else {
                return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
            }

            return $this->response('success', ['data' => $product]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }

    }

    public function deletemenu(Request $request)
    {

        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $id = $request->id;
            $account = FrontMenu::find($id);

            if (isset($account) && !empty($account)) {
                $data = $account->delete();
            } else {
                return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
            }

            return $this->response('success', ['form' => $data]);

        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    
    ////////Roles//////

    ////Roles///////



    public function addRole(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $role = new Role();
            $role->role = $request->name;
            $role->status = 1;
            $role->save();
            if($role->id){
                return $this->response('success',['message'=>"New role added!"]);
            }else{
                return $this->response('apierror');
            }
        }catch(\Throwable $th){
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function getRole(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            $message = $this->validationResponse($validator->errors());
            return $this->response('validatorerrors', $message);
        }
        $role = Role::find($request->id);
        if ($role) {
            return $this->response('success', ['message'=>'Details fetched successfully.','data' => $role]);
        } else {
            return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
        }
    }

    public function updateRole(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'id' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $role = Role::find($request->id);

            if (!empty($role)) {
                $role->role = $request->name;
                if($request->has('status')){
                    $role->status = $request->status;
                }
                $role->update();
                return $this->response('success', ['message'=>'Updated successfully.','data' => $role]);
            } else {
                return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function listRole(Request $request)
    {
        try {
            $search = $request->search;
            $orderby= $request->orderby;
            $order  = $request->order;
            $searchColumn = ['id','role','status'];
            $select = ['id','role','status',"created_at"];
            $query = Role::select($select);
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
            $header = $this->roles();
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



    ///////////menu permission/////////

    public function getRoleItems(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'role_id' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
                $role = Role::find($request->role_id);
                if(!$role){
                    return $this->response('notvalid', ['message' => 'Invalid role']);
                }
                $menuItems = FrontMenu::get()->toArray();
                if(empty($menuItems)){
                    return $this->response('noresult', ['message' => 'no menu item found']);
                }
                $items = array();
                foreach($menuItems as $menuItem){
                    $isPermitted = FrontMenuPermission::where('role_id',$request->role_id)->where('menu_id',$menuItem['id'])->first();
                    $permission = 0;
                    if($isPermitted){
                        $permission = $isPermitted->status;
                    }
                    $items[] =[
                        'menu_id' => $menuItem['id'],
                        'role_id' =>$request->role_id,
                        'name' => $menuItem['name'],
                        'url' => $menuItem['urlapi'],
                        'created_at' => $menuItem['created_at'],
                        'isPermitted' =>$permission
                    ];
                }
                return $this->response('success', ['message'=>'list fetched successfully','data' => $items]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }

    }

    
    public function updateRoleItem(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'role_id' => 'required|numeric',
                'status' => 'required|numeric|in:0,1',
                'menu_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $data = FrontMenuPermission::updateOrInsert(
                ['role_id' => $request->role_id, 'menu_id'=>$request->menu_id],
                ['role_id' => $request->role_id, 'menu_id' => $request->menu_id, 'status' => $request->status]
            );
            if ($data) {
                return $this->response('success', ['message'=>'Updated Successfully']);
            } else {
                return $this->response('apierror');
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }

    }
    public function frontParentMenu()
    {
        try{
            $data = FrontMenu::select('id','name')->where('type','!=','item')->get()->toArray();
            if ($data) {
                return $this->response('success', ['message'=>'Fetched Successfully!','data'=>$data]);
            } else {
                return $this->response('noresult');
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    //// Menu permission End///


}