<?php

namespace App\Http\Middleware;

use App\Libraries\Common\Crypt;
use Closure;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

class CryptMiddleware
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
        $environment = App::environment();
        $crypt = new Crypt();
        $passphrase = config('constant.crypt_key');
        if($environment != 'local' && $request->has('key') && $request->key !=''){
            if(Auth::check()){
            $tkn = $request->bearerToken();
            $passphrase = $crypt->key($tkn);
            }
            
            $dec = $crypt->cryptAesDecrypt($passphrase,$request->key);
            if($dec){
             $request->merge($dec);
            }else{
                return response('Unauthorized.', 401);
            } 
        }
        // Pre-Middleware Action
        $response = $next($request);
        // Post-Middleware Action
        if($environment != 'local'){
            $enc = $crypt->cryptAesEncrypt($passphrase,$response->getOriginalContent());
            $response->setContent($enc);
        }
        return $response;
    }
}
