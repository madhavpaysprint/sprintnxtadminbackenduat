<?php
namespace App\Http\Middleware;

use Closure;

class CheckIp
{

/**
 * The authentication guard factory instance.
 *
 * @var \Illuminate\Contracts\Auth\Factory
 */
    protected $auth;
/**
 * Create a new middleware instance.
 *
 * @param  \Illuminate\Contracts\Auth\Factory  $auth
 * @return void
 */
    public function __construct()
    {
        $this->ip = [
            "::1","127.0.0.1","122.176.75.222","49.205.179.22"
        ];
    }
/**
 * Handle an incoming request.
 *
 * @param  \Illuminate\Http\Request  $request
 * @param  \Closure  $next
 * @param  string|null  $guard
 * @return mixed
 */
    public function handle($request, Closure $next, $guard = null)
    {
        $ip = $request->ip();
        // if (!in_array($ip, $this->ip)) {
        //     return response()->json([
        //         'statuscode' => 520,
        //         'responsecode'=> 00,
        //         'status'  => false,
        //         'message' => 'Invalid Ip Address!'
        //     ], 401);
        // }
        return $next($request);
    }
}
