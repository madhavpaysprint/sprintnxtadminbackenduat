<?php

namespace App\Http\Middleware;

use Closure;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class LogMiddleware
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
        $response = $next($request);

        $loginfo = [
            'URI'          => $request->getUri(),
            'METHOD'       => $request->getMethod(),
            'REQUEST_BODY' => $request->all(),
            'RESPONSE'     => $response->getContent(),
        ];
        if($request->path() != "reports/exportUpiTransactionList"){
            $log = new Logger($request->getMethod());
            $log->pushHandler(new StreamHandler(storage_path().'/logs/logs-' . date('Y-m-d') . '.log', Logger::DEBUG));
            $log->info(json_encode($loginfo));
        }

        return $response;
    }

}
