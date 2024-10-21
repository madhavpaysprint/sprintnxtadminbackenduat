<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Support\Facades\Auth;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    { 
        if($request->route()[0]){
         if(isset($request->route()[1]['as'])){
           $nickname = $request->route()[1]['as'];
           $roleid = $request->user()->role;
           $fetch = Permission::select('id','module_id','permission')
           ->with('RolePermission:id,role_id,permission_id')
           ->whereHas('RolePermission', function ($query) use ($roleid){
            $query->where('role_id', $roleid);
           }) ->where('permission', $nickname)->get()->first();
          
           if($fetch){
            $response = $next($request);
            return $response;
           }
         }
        }
        return response('Access denied!', 403);
    }
}
