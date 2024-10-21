<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Http\Traits\CommonTrait;
use App\Models\Permission;
use App\Models\RolePermission;

class PermissionController extends Controller
{

    use CommonTrait;
    
    /**
     * Module List
     * @param Request $request
     * @return Module LIST 
     */
    public function moduleList(Request $request)
    {
        try {
            $module = Module::select("id", "module")->orderBy('id', 'DESC')->get();
            return $this->response('success', ['message' => 'Module fetched successfully', 'data' => $module]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    /**
     * Module Add
     * @param Request $request
     * @return response 
     */
    public function addModule(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'module' => 'required|unique:modules,module',
            ]);
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $module = Module::create(['module' => strtoupper(trim($request->module))]);
            $permission = ["0" => "add", "1" => "update", "2" => "list", "3" => "delete", "4" => "view"];
            $permissionIds = [];
            foreach ($permission as $value) {
                $permission = Permission::create(['module_id' => $module->id,
                 'permission' => strtolower($request->module) ."-". $value,'label'=>ucwords(strtolower($request->module) ." ". $value)]);
                array_push($permissionIds, $permission->id);
            }

            $permissionArray[] = ["module_id" => $module->id, "permission" => $permissionIds];
            $privilegedUser = Role::select("id")->whereIn("role", $this->privilegedRole())->get();
            if (!$privilegedUser->isEmpty()) {
                foreach ($privilegedUser as $key => $value) {
                    $this->syncPermissions(["role_id" => $value->id, "permission" => $permissionArray]);
                }
            }
            return $this->response('success', ['message' => 'Module created successfully']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    /**
     * Module Delete
     * @param Request $request
     * @return Response
     */
    public function deleteModule(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:modules,id',
            ]);
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            Module::find($request->id)->delete();
            Permission::where("module_id", $request->id)->delete();
            RolePermission::where("module_id", $request->id)->delete();

            return $this->response('success', ['message' => 'Module deleted successfully']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    /**
     * Permissions Add
     * @param Request $request
     * @return response 
     */
    public function addCustomPermission(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'module_id' => 'required|exists:modules,id',
                'permission' => 'required'
            ]);

            $moduleObj = Module::find($request->module_id);
            $customPermission=strtolower($moduleObj->module . "-" . trim($request->permission));
           
            $validator->after(function($validator) use ($customPermission,$request){
                $obj=Permission::select()->where(['permission' => $customPermission, "module_id" => $request->module_id])->first();
                if ($obj) {
                    $validator->errors()->add('permission', 'This permission is already taken!');
                    $message   = $this->validationResponse($validator->errors());
                    return $this->response('validatorerrors', $message);
                }
            });
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $label= $request->has('label') ? $request->label:ucwords(str_replace("-"," ",$customPermission));
            $perObj= Permission::create(['permission' =>$customPermission,"label"=>$label, "module_id" => $request->module_id]);
            $permissionArray[] = ["module_id" => $request->module_id, "permission" =>[$perObj->id]];
            $privilegedUser = Role::select("id")->whereIn("role", $this->privilegedRole())->get();
            if (!$privilegedUser->isEmpty()) {
                foreach ($privilegedUser as $key => $value) {
                    $this->syncPermissions(["role_id" => $value->id, "permission" => $permissionArray]);
                }
            }
            return $this->response('success', ['message' => 'Permissions created successfully']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    /**
     * Permission Delete
     * @param Request $request
     * @return response 
     */
    public function deletePermission(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:permissions,id'
            ]);
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            Permission::find($request->id)->delete();
            RolePermission::where("permission_id", $request->id)->delete();

            return $this->response('success', ['message' => 'Permission deleted successfully']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    /**
     * Permission List
     * @param Request $request
     * @return Module LIST 
     */
    public function permissionList(Request $request)
    {
        try {
            $permission = Module::select("id", "module")->with('permission:id,module_id,permission,label')->get();
            return $this->response('success', ['message' => 'Permission fetched successfully', 'data' => $permission]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    /**
     * Role Add
     * @param Request $request
     * @return response 
     */
    public function addRole(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'role' => 'required|unique:roles,role',
                'permission' => 'required|json',
            ]);

            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            
            $permission = $request->permission;
            $permission = json_decode($permission, true);
            
            $role = Role::create(['role' => strtoupper(trim($request->role))]);
            $this->syncPermissions(["role_id" => $role->id, "permission" => $permission]);

            return $this->response('success', ['message' => 'Role added successfully']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

      /**
     * Update Role
     * @param Request $request
     * @return response
     */
    public function updateRole(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:roles,id',
                'permission' => 'required|json',
            ]);

            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $permission = $request->permission;
            $permission = json_decode($permission, true);

            RolePermission::where(["role_id" =>  $permission['role_id']])->delete();
            $this->syncPermissions(["role_id" => $request->id, "permission" => $permission]);

            return $this->response('success', ['message' => 'Role permission updated successfully']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

        /**
     * Role 
     * @param Request $request
     * @return response 
     */
    public function getRoleById(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:roles,id',
            ]);

            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            
            $fetch =$this->getRoleDetail($request->id);

            return $this->response('success', ['message' => 'Role fetched successfully','data'=>$fetch]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    /**
     * Role Delete
     * @param Request $request
     * @return Response
     */
    public function deleteRole(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:roles,id',
            ]);
            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            Role::find($request->id)->delete();
            RolePermission::where(['role_id' =>  $request->id])->delete();

            return $this->response('success', ['message' => 'Role deleted successfully']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    /**
     * Role List
     * @param Request $request
     * @return Module LIST 
     */
    public function roleList(Request $request)
    {
        try {
            $role = Role::select("id", "role")->orderBy('id', 'DESC')->get();
            return $this->response('success', ['message' => 'Role fetched successfully', 'data' => $role]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    /**
     * Get Static Role Detail
     * @return response 
     */
    static function getRoleDetail($id){

      
        $data=$result=$temp=[];
        $permission_id=RolePermission::where(["role_id"=>$id])->pluck("permission_id");
        $fetch = Permission::select("id", "module_id","permission","label")->with('module:id,module')
                ->whereIn("id",$permission_id)->get();

        foreach($fetch as $key=>$value){
            $temp['module_id']=$value['module_id'];
            $temp['permission_id']=$value['id'];
            $temp['permission']=$value['permission'];
            $temp['label']=ucwords($value['label']);
            $data[$value['module']['module']][]=$temp;
            $temp=[];
        }
        //create final array
        foreach ($data as $key => $value) {
            $temp['module'] = $key;
            $temp['permission'] = $value;
            $result[] = $temp;
            $temp=[];
        }
         return $result;
    }

     /**
     * Sync Permission
     * @param Permission
     */
    public function syncPermissions($permission)
    {
        foreach ($permission['permission'] as $key => $value) {
            foreach ($value['permission'] as $value1) {
                RolePermission::create(["module_id" =>  $value['module_id'], 'role_id' =>  $permission['role_id'], 'permission_id' => $value1]);
            }
        }
        return true;
    }

    /**
     * Privileged Role Array
     */
    public static function privilegedRole()
    {
        return ["SUPERADMIN", "ADMIN"];
    }


    /**
     * Permission Update
     * @param Request $request
     * @return response 
     */
    public function updatePermission(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:permissions,id',
                'label' => 'required',
            ]);

            if ($validator->fails()) {
                $message   = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }

            $input = $request->except(['id']);
            Permission::where("id",$request->id)->update($input); 

            return $this->response('success', ['message' => 'Permission updated successfully']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
}
