<?php
namespace App\Http\Controllers\Master;
use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\NewHeaderTrait;
use App\Models\Sample\ParentModule;
use App\Models\Sample\Module;
use App\Models\Sample\Permission;
use App\Models\Sample\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class NewPermissionController extends Controller
{
    use CommonTrait, NewHeaderTrait;
    public function __construct()
    {
        $this->status = ['0' => 'Deactive', '1' => 'Active'];
    }
    public function addParent(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'name' => 'required|string|max:255|unique:sample_parent_modules,name',
            'status' => 'required|in:0,1',
        ]);
        if ($validator->fails()) {
            $message = $this->validationResponse($validator->errors());
            return $this->response('validatorerrors', $message);
        }
        try {
            $parent = new ParentModule();
            $parent->name = $req->name;
            $parent->status = $req->status;
            $parent->icon = $req->icon;
            $parent->order = $req->order;
            $parent->save();
            if ($parent->id) {
                return $this->response('success', ['message' => "New parent module added!", 'parent_id' => $parent->id]);
            } else {
                return $this->response('apierror');
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    public function addModulePrev(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'parent_id' => 'required|exists:sample_parent_modules,id',
            'modules.*.name' => 'required',
            'modules.*.status' => 'required|in:0,1',
            'modules.*.url' => 'required',
            'modules.*.icon' => 'nullable|string',
        ]);
        if ($validator->fails()) {
            $message = $this->validationResponse($validator->errors());
            return $this->response('validatorerrors', $message);
        }
        try {
            $parentId = $req->input('parent_id');
            $modules = $req->input('modules');
            $savedModules = [];
            foreach ($modules as $moduleData) {
                $module = Module::where('name', $moduleData['name'])
                                ->where('parent_id', $parentId)
                                ->first();
                if ($module) {
                    $module->update([
                        'status' => $moduleData['status'],
                        'url' => $moduleData['url'],
                        'icon' => $moduleData['icon'] ?? null,
                    ]);
                } else {
                    $module = Module::create([
                        'name' => $moduleData['name'],
                        'parent_id' => $parentId,
                        'status' => $moduleData['status'],
                        'url' => $moduleData['url'],
                        'icon' => $moduleData['icon'] ?? null,
                    ]);
                }
                $savedModules[] = $module->id;
            }
            return $this->response('success', ['message' => "Modules processed!", 'module_ids' => $savedModules]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    public function addModule(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'parent_id' => 'required|exists:sample_parent_modules,id',
            'modules.*.name' => 'required',
            'modules.*.status' => 'required|in:0,1',
            'modules.*.url' => 'required',
            'modules.*.icon' => 'nullable|string',
        ]);
    
        if ($validator->fails()) {
            $message = $this->validationResponse($validator->errors());
            return $this->response('validatorerrors', $message);
        }
    
        try {
            $parentId = $req->input('parent_id');
            $modules = $req->input('modules');
            $savedModules = [];
            $existingModules = Module::where('parent_id', $parentId)->get()->keyBy('id'); // Key by module ID
    
            // Process each module in the request
            foreach ($modules as $moduleData) {
                $moduleId = $moduleData['module_id'] ?? null; // Default to null if not provided
                $moduleName = $moduleData['name'];
    
                if ($moduleId && isset($existingModules[$moduleId])) {
                    // Update the existing module's details
                    $existingModules[$moduleId]->update([
                        'name' => $moduleName,
                        'status' => $moduleData['status'],
                        'url' => $moduleData['url'],
                        'icon' => $moduleData['icon'] ?? null,
                    ]);
                    $savedModules[] = $moduleId;
    
                    $existingModules = $existingModules->forget($moduleId);
                } else {
                    // Create a new module if no valid module_id is provided
                    $module = Module::create([
                        'name' => $moduleName,
                        'parent_id' => $parentId,
                        'status' => $moduleData['status'],
                        'url' => $moduleData['url'],
                        'icon' => $moduleData['icon'] ?? null,
                    ]);
    
                    // Keep track of saved module IDs
                    $savedModules[] = $module->id;
                }
            }
    
            // Delete any remaining modules that were not in the request
            foreach ($existingModules as $module) {
                // Delete the module
                $module->delete();
    
                // Also delete related entries in the permissions table
                Permission::where('module_id', $module->id)->where('parent_id', $parentId)->delete();
            }
    
            return $this->response('success', ['message' => "Modules processed successfully!", 'module_ids' => $savedModules]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    
    
    public function addPermission(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), [
                'role_id' => 'required|exists:roles,id',
                'permissions.*.module_id' => 'required|exists:sample_modules,id',
                'permissions.*.status' => 'required|in:0,1',
            ]);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $role_id = $req->role_id;
            foreach ($req->permissions as $permissionData) {
                $module = Module::find($permissionData['module_id']);
                if (!$module) {
                    return $this->response('notfound', ['message' => 'Module not found.']);
                }
                $parent_id = $module->parent_id;
                Permission::updateOrCreate(
                    [
                        'role_id' => $role_id,
                        'module_id' => $permissionData['module_id'],
                        'parent_id' => $parent_id,
                    ],
                    [
                        'status' => $permissionData['status'],
                    ]
                );
            }
            return $this->response('success', ['message' => 'Permissions have been updated or created!']);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    public function getSideMenu(Request $req)
    {
        try {
            $roleId = Auth::user()->role;

            if (!$roleId) {
                return $this->response('validatorerrors', ['message' => 'User role ID not found.']);
            }
            $parentModules = ParentModule::with(['modules' => function($query) use ($roleId) {
                $query->with(['permissions' => function($q) use ($roleId) {
                    $q->where('role_id', $roleId);
                }]);
            }])->where('status', 1)->get();
            $filteredData = $parentModules->map(function($parent) use ($roleId) {
                $modules = $parent->modules->filter(function($module) use ($roleId) {
                    $hasActivePermission = $module->permissions->contains(function($permission) use ($roleId) {
                        return $permission->status === 1;
                    });
                    return $hasActivePermission;
                });
                if ($modules->isNotEmpty()) {
                    return [
                        'parent' => $parent->name,
                        'parent_id' => $parent->id,
                        'modules' => $modules->map(function($module) use ($roleId) {
                            $permission = $module->permissions->first();
                            return [
                                'module' => $module->name,
                                'module_id' => $module->id,
                                'status' => $permission ? $permission->status : 0,
                                'icon' => $module->icon,
                                'url' => $module->url,  
                            ];
                        }),
                    ];
                }
            })->filter();
            return $this->response('success', ['message' => 'Menu items fetched successfully.', 'data' => $filteredData]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    public function getRolePermissions(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), [
                'role_id' => 'required|exists:roles,id',
            ]);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $roleId = $req->role_id;
            $permissions = Permission::where('role_id', $roleId)
                ->with(['parent', 'module'])
                ->get();
            $groupedData = $permissions->groupBy('parent_id')->map(function ($group) {
                $parent = $group->first()->parent;
                return [
                    'parent_id' => $parent->id,
                    'parent_name' => $parent->name,
                    'modules' => $group->map(function ($permission) {
                        return [
                            'module_name' => $permission->module->name,
                            'module_id' => $permission->module_id,
                            'status' => $permission->status,
                        ];
                    })->values(),
                ];
            })->values();
            $modulesToUpdate = Module::where('added_to_roles', 0)
                ->where('status', 1)
                ->get();
            foreach ($modulesToUpdate as $module) {
                Permission::updateOrCreate(
                    [
                        'role_id' => $roleId,
                        'module_id' => $module->id,
                        'parent_id' => $module->parent_id,
                    ],
                    [
                        'status' => 0,
                    ]
                );
                $module->added_to_roles = 1;
                $module->save();
            }
            $permissions = Permission::where('role_id', $roleId)
                ->with(['parent', 'module'])
                ->get();
            $groupedData = $permissions->groupBy('parent_id')->map(function ($group) {
                $parent = $group->first()->parent;
                return [
                    'parent_id' => $parent->id,
                    'parent_name' => $parent->name,
                    'modules' => $group->map(function ($permission) {
                        return [
                            'module_name' => $permission->module->name,
                            'module_id' => $permission->module_id,
                            'status' => $permission->status,
                        ];
                    })->values(),
                ];
            })->values();
            return $this->response('success', [
                'message' => 'Details fetched successfully.',
                'data' => $groupedData,
            ]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', [
                'message' => $th->getMessage()
            ]);
        }
    }
    public function getParents(Request $req)
    {
        try {
            $activeParents = ParentModule::where('status', 1)->get(['id', 'name']);
            return $this->response('success', [
                'message' => 'Active parents fetched successfully.',
                'data' => $activeParents
            ]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    public function getModules(Request $req)
    {
        try {
            $validator = Validator::make($req->all(), [
                'parent_id' => 'required|exists:sample_parent_modules,id',
            ]);
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            $parentId = $req->parent_id;
            $parent = ParentModule::with('modules')->find($parentId);
            if (!$parent) {
                return $this->response('notfound', ['message' => 'Parent not found.']);
            }
            $modules = $parent->modules->map(function ($module) {
                return [
                    'module_id' => $module->id,
                    'name' => $module->name,
                    'url' => $module->url,
                ];
            });
            return $this->response('success', [
                'message' => 'Modules fetched successfully.',
                'data' => [
                    'parent_id' => $parent->id,
                    'parent_name' => $parent->name,
                    'modules' => $modules,
                ],
            ]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    public function getAllModules(Request $req)
    {
        try {
            $search = $req->search;
            $orderby = $req->orderby ?? 'id';
            $order = $req->order ?? 'desc';
            $length = $req->length ?? 20;
            $start = $req->start ?? 0;
            $parents = ParentModule::with(['modules' => function ($query) use ($search, $orderby, $order, $length, $start) {
                $searchColumn = ['id', 'name'];
                if (!empty($search)) {
                    $query->where(function ($query) use ($searchColumn, $search) {
                        foreach ($searchColumn as $column) {
                            $query->orWhere($column, 'like', '%' . trim($search) . '%');
                        }
                    });
                }
                $query->orderBy($orderby, $order)
                    ->skip($start)
                    ->take($length);
            }])->where('status', 1)->get();
            $totalCount = ParentModule::where('status', 1)->with('modules')->count();
            $count = $parents->map(function ($parent) {
                return $parent->modules->count();
            })->sum();
            $header = $this->modules();
            $data = $parents->map(function ($parent) {
                return [
                    'parent_id' => $parent->id,
                    'parent_name' => $parent->name,
                    'parent_status' => $parent->status,
                    'modules' => $parent->modules->map(function ($module) {
                        return [
                            'module_id' => $module->id,
                            'name' => $module->name,
                            'url' => $module->url,
                            'icon' => $module->icon,
                            'status'=> $module->status,
                            'added_to_roles' => $module->added_to_roles
                        ];
                    }),
                ];
            });
            $details = [
                'message' => 'All modules fetched successfully.',
                'recordsFiltered' => $count,
                'recordsTotal' => $totalCount,
                "header" => $header,
                'data' => $data,
            ];
            return $this->response('success', $details);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }



}