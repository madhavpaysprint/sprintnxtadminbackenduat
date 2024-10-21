<?php
namespace App\Http\Controllers\Master;
use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Models\AdminMenu;
use App\Models\AdminMenuPermission;
use App\Models\Module;
use App\Models\Role;
use App\Models\ModulePermission;
use App\Models\RoleModule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Traits\HeaderTrait;

use App\Models\Sample\Permission;
use App\Models\Sample\Module as AnotherModules;

class AdminConfigController extends Controller
{
    use CommonTrait,HeaderTrait;
    public function __construct()
    {
        $this->status = ['0'=>'Deactive','1'=>'Active'];
        $this->moduleStatus = ['Deactive'=>'0','Active'=>'1'];
	}

    ///Roles///////



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
            $role->name = $request->name;
            $role->status = 1;
            $role->save();
            // New Modules Functionality Start
            $modules = AnotherModules::where('status', 1)->get();
            foreach ($modules as $m) {
                $permission = new Permission();
                $permission->role_id = $role->id;
                $permission->module_id = $m->id;
                $permission->parent_id = $m->parent_id;
                $permission->status = 0;
                $permission->save();               
            }
            // New Modules Functionality End
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
                $role->name = $request->name;
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
            $searchColumn = ["id", "name"];
            $select = ['id','name','status',"created_at"];
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


    ///Roles end//

    //Modules start//

    public function addModule(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), [
                'name' => 'required',
            ]);

            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $module = new Module();
            $module->name = $request->name;
            $module->save();
            if($module->id){
                return $this->response('success',['message'=>"New module added!"]);
            }else{
                return $this->response('apierror');
            }
        }catch(\Throwable $th){
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function getModule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
        ]);

        if ($validator->fails()) {
            $message = $this->validationResponse($validator->errors());
            return $this->response('validatorerrors', $message);
        }
        $module = Module::find($request->id);
        if ($module) {
            return $this->response('success', ['message'=>'Details fetched successfully.','data' => $module]);
        } else {
            return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
        }
    }

    public function updateModule(Request $request)
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

            $module = Module::find($request->id);


            if (!empty($module)) {
                $module->name = $request->name;
                if($request->has('status')){
                    if($request->status === "Active" || $request->status === "Deactive"){
                        $module->status = $this->moduleStatus[$request->status];
                    }else{
                        $module->status = $request->status;
                    }
                }
                $module->update();
                return $this->response('success', ['message'=>'Updated successfully.','data' => $module]);
            } else {
                return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function listModule(Request $request)
    {
        try {
           $search = $request->search;
            $orderby= $request->orderby;
            $order  = $request->order;
            $searchColumn = ["id", "name"];
            $select = ['id','name','status',"created_at"];
            $query = Module::select($select);
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
            $header = $this->modules();
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
    //// Module End///

    //Modules permission start//

    public function getModulePermission(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $message = $this->validationResponse($validator->errors());
            return $this->response('validatorerrors', $message);
        }
        $modulePermission = ModulePermission::where('user_id',$request->user_id)->first();
        if ($modulePermission) {
            $module_permission = explode(",",$modulePermission->module_id);
            $ids = array();
            foreach($module_permission as $id){
                $ids[] = [
                    'id' => $id
                ];
            }
            $ids_permission = json_encode($ids,true);
            $permission = [
                'id' => $modulePermission->id,
                'module_id' => $ids_permission,
                'user_id' => $modulePermission->user_id,
                'status' => $modulePermission->status,
            ];
            return $this->response('success', ['message'=>'Details fetched successfully.','data' => $permission]);
        } else {
            return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
        }
    }

    public function updateModulePermission(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'modules' => 'required',
                'user_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $modules_decode = json_decode($request->modules);
            if(!empty($modules_decode)){
                $ids = array();
                foreach($modules_decode as $id){
                    array_push($ids,$id->id);
                }
                $module_id = implode(",",$ids);

                $data = ModulePermission::updateOrInsert(
                    ['user_id' => $request->user_id],
                    ['user_id' => $request->user_id, 'module_id' => $module_id]
                );

                if($data){
                    return $this->response('success', ['message'=>'Success.']);
                }else{
                    return $this->response('apierror');
                }
            }else{
                return $this->response('incorrectinfo',['message'=>"Invalid format for modules."]);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    //// Module permission End///



    //////Role modules////


    //Modules permission start//

    public function getRoleModules(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'role_id' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            $message = $this->validationResponse($validator->errors());
            return $this->response('validatorerrors', $message);
        }
        $roleModules = RoleModule::where('role_id',$request->role_id)->first();
        if ($roleModules) {
            $module_permission = explode(",",$roleModules->modules);
            $ids = array();
            foreach($module_permission as $id){
                $ids[] = [
                    'id' => $id
                ];
            }
            $ids_permission = json_encode($ids,true);
            $permission = [
                'id' => $roleModules->id,
                'module_id' =>  $ids_permission,
                'role_id' => $roleModules->role_id,
                'status' => $roleModules->status,
            ];
            return $this->response('success', ['message'=>'Details fetched successfully.','data' => $permission]);
        } else {
            return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
        }
    }

    public function updateRoleModules(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'modules' => 'required',
                'role_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $modules_decode = json_decode($request->modules);
            if(!empty($modules_decode)){
                $ids = array();
                foreach($modules_decode as $id){
                    array_push($ids,$id->id);
                }
                $module_id = implode(",",$ids);

                $data = RoleModule::updateOrInsert(
                    ['role_id' => $request->role_id],
                    ['role_id' => $request->role_id, 'modules' => $module_id]
                );

                if($data){
                    return $this->response('success', ['message'=>'Success.']);
                }else{
                    return $this->response('apierror');
                }
            }else{
                return $this->response('incorrectinfo',['message'=>"Invalid format for modules."]);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    //// Module permission End///



    ///Role module ends//


    //Menu Start//

    public function addMenuItem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required',
                'type' => 'required|in:item,group,collapse',
                'module_id' => 'required',
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
            $module_id = Module::where('id', $request->module_id)->first();
            if($module_id){
                $fmenu = new AdminMenu();
                $fmenu->name = $request->name;
                $fmenu->type = $request->type;
                $fmenu->module_id = $request->module_id;
                if($request->has('guard_name')){
                    $fmenu->guard_name = $request->guard_name;
                }
                if($request->has('is_show')){
                    $fmenu->is_show = $request->is_show;
                }
                $fmenu->urlapi = $request->urlapi;
                if($request->has('parent')){
                    $fmenu->parent = $request->parent;
                }

                $fmenu->icon = $request->icon;
                if($request->has('menu_order')){
                    $fmenu->menu_order = $request->menu_order;
                }
                $fmenu->menu = $request->menu;
                $fmenu->save();
                return $this->response('success', ['message'=>'Menu Item added successfully.','data' => $fmenu]);
            }else{
                return $this->response('incorrectinfo');
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
            $select = ["id", "name", "type", "module_id", "guard_name", "urlapi", "parent", "icon", "menu_order", "menu","is_show","created_at"];
            $query = AdminMenu::select($select);
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
                $list[$key]->status = $this->status[$val->is_show];
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
                'module_id' => 'required',
                'urlapi' => 'required',
                'icon' => 'required',
                'menu' => 'required',
                'is_show' => 'required',
                'parent' => 'numeric',
                'menu_order' => 'numeric',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $menutype = AdminMenu::find($request->id);

            if ($menutype) {
                $menutype->name = $request->name;
                $menutype->type = $request->type;
                $menutype->module_id = $request->module_id;
                $menutype->guard_name = $request->guard_name;
                $menutype->urlapi = $request->urlapi;
                if($request->has('parent')){
                    $menutype->parent = $request->parent;
                }
                if($request->has('is_show')){
                    $menutype->is_show = $request->is_show;
                }
                $menutype->icon = $request->icon;
                $menutype->menu_order = $request->menu_order;
                $menutype->menu = $request->menu;
                $menutype->update();
                return $this->response('success', ['message'=>'Updated successfully.', 'data' => $menutype]);
            } else {
                return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
            }
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
            $menuItem = AdminMenu::find($id);
            if ($menuItem) {
                return $this->response('success', ['message'=>'Details fetched successfully','data' => $menuItem]);
            } else {
                return $this->response('incorrectinfo', ['message' => 'The provided information is incorrect!']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }

    }


    //// Menu End///


    //Menu permission Start//

    public function getModuleItem(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'user_id' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $modules = ModulePermission::where('user_id',$request->user_id)->first();
            if ($modules) {
                $modules_array = explode(',',$modules->module_id);
                $menuItems = AdminMenu::whereIn('module_id',$modules_array)->get()->toArray();
                if(empty($menuItems)){
                    return $this->response('noresult', ['message' => 'no menu item found']);
                }
                $items = array();
                foreach($menuItems as $menuItem){
                    $isPermitted = AdminMenuPermission::where('user_id',$request->user_id)->where('menu_id',$menuItem['id'])->first();
                    $permission = 0;
                    if($isPermitted){
                        $permission = $isPermitted->status;
                    }
                    $items[] =[
                        'menu_id' => $menuItem['id'],
                        'user_id' =>$request->user_id,
                        'name' => $menuItem['name'],
                        'url' => $menuItem['urlapi'],
                        'created_at' => $menuItem['created_at'],
                        'module' => Module::where('id',$menuItem['module_id'])->pluck('name')->first(),
                        'isPermitted' =>$permission
                    ];
                }
                return $this->response('success', ['message'=>'list fetched successfully','data' => $items]);
            } else {
                return $this->response('noresult', ['message' => 'no module assign']);
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }

    }


    public function updateModuleItem(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'user_id' => 'required|numeric',
                'status' => 'required|numeric|in:0,1',
                'menu_id' => 'required|numeric',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $data = AdminMenuPermission::updateOrInsert(
                ['user_id' => $request->user_id, 'menu_id'=>$request->menu_id],
                ['user_id' => $request->user_id, 'menu_id' => $request->menu_id, 'status' => $request->status]
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
    public function adminParentMenu()
    {
        try{
            $data = AdminMenu::select('id','name')->where('is_show',1)->where('type','!=','item')->get()->toArray();
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
