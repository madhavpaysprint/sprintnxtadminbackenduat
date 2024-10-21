<?php

namespace App\Http\Controllers\Master;



use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\HeaderTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\NewService;
use App\Models\NewDefaultCharge;
use App\Models\BankList;

class NewChargesController extends Controller
{
    use CommonTrait, HeaderTrait;

    public function addService(Request $req) {
        try {
            $validator = Validator::make($req->all(), [
                'service' => 'required',
                'name' => 'required',
                'type' => 'required'
            ]);

            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
            
            $service = new NewService();
            $service->service = $req->service;
            $service->name = $req->name;
            $service->type = $req->type;
            $service->save();

            if($service->id) {
                return $this->response('success', ['message' => "New service added!"]);
            }
            else {
                return $this->response('apierror');
            }
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function getAllServices() {
        $services = NewService::get();

        try {
            $services = NewService::get(['id', 'name', 'service', 'type']);
    
            return $this->response('success', [
                'message' => 'Services fetched successfully.',
                'data' => $services
            ]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function getDefaultCharges(Request $req) {
        try {
            $validator = Validator::make($req->all(), [
                'bank_id' => 'required|exists:pgsql.bank_lists,id'
            ]);
    
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
    
            $defaultCharges = NewDefaultCharge::where('bank_id', $req->bank_id)
                                             ->get();
    
            if ($defaultCharges->isEmpty()) {
                return $this->response('notfound', ['message' => 'No default charges found for the given bank.']);
            }
    
            $result = [];
            foreach ($defaultCharges as $charge) {
                $service = NewService::find($charge->service_id);
    
                if ($service->type == 'slab') {
                    $chargesData = [
                        'charges' => explode(',', $charge->charges),
                        'min' => explode(',', $charge->min),
                        'max' => explode(',', $charge->max),
                        'is_fixed' => explode(',', $charge->is_fixed),
                    ];
                } else {
                    $chargesData = [
                        'charges' => $charge->charges,
                        'min' => null,
                        'max' => null,
                        'is_fixed' => null,
                    ];
                }
    
                $result[] = [
                    'service_id' => $charge->service_id,
                    'service_name' => $service->name,
                    'type' => $service->type,
                    'charges' => $chargesData
                ];
            }
    
            return $this->response('success', [
                'message' => 'Default charges fetched successfully.',
                'data' => $result
            ]);
    
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
    
    public function setDefaultCharges(Request $req) {
        try {
            $validator = Validator::make($req->all(), [
                'bank_id' => 'required',
                'service_id' => 'required',
                'charges' => 'required',
                'min' => 'sometimes|array',
                'max' => 'sometimes|array',
                'is_fixed' => 'sometimes|array',
            ]);
    
            if ($validator->fails()) {
                $message = $this->validationResponse($validator->errors());
                return $this->response('validatorerrors', $message);
            }
    
            $service = NewService::find($req->service_id);
    
            if ($service->type == 'slab') {
                $charges = $req->input('charges');
                $min = $req->input('min');
                $max = $req->input('max');
                $is_fixed = $req->input('is_fixed');
    
                if (!is_array($charges) || !is_array($min) || !is_array($max) || !is_array($is_fixed)) {
                    return $this->response('validationerror', ['message' => 'Charges, min, max, and is_fixed must be arrays for slab type.']);
                }
    
                if (count($charges) != count($min) || count($min) != count($max) || count($max) != count($is_fixed)) {
                    return $this->response('validationerror', ['message' => 'The counts of charges, min, max, and is_fixed do not match.']);
                }
    
                $chargesStr = implode(',', array_map('floatval', $charges));
                $minStr = implode(',', array_map('intval', $min));
                $maxStr = implode(',', array_map('intval', $max));
                $isFixedStr = implode(',', array_map('intval', $is_fixed));
    
                NewDefaultCharge::updateOrCreate(
                    [
                        'bank_id' => $req->bank_id,
                        'service_id' => $req->service_id,
                    ],
                    [
                        'charges' => $chargesStr,
                        'min' => $minStr,
                        'max' => $maxStr,
                        'is_fixed' => $isFixedStr,
                    ]
                );
            } else {
                NewDefaultCharge::updateOrCreate(
                    [
                        'bank_id' => $req->bank_id,
                        'service_id' => $req->service_id,
                    ],
                    [
                        'charges' => (float)$req->charges,
                        'min' => null,
                        'max' => null,
                        'is_fixed' => null,
                    ]
                );
            }
    
            return $this->response('success', ['message' => "Default charges set successfully."]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }     
}
