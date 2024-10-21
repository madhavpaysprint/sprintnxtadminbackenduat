<?php

namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
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
        // Pre-Middleware Action

        // $response = $next($request);

        // Post-Middleware Action


        $headers = [
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Max-Age'           => '86400',
            'Access-Control-Allow-Headers'     => '*'
        ];

        $response = $next($request);


        if ($request->isMethod('OPTIONS')) {
            return response()->json('{"method":"OPTIONS"}', 200, $headers);
        }

        foreach ($headers as $key => $value) {
            $response->header($key, $value);
        }

        return $response;
    }
}
