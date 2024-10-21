<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Http\Traits\CommonTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfigurationController extends Controller
{
    use CommonTrait;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function front(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required',
                'bintohex' => 'required',
                'encrypt' => 'required',
                'ip' => 'required',
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }


        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    
}
