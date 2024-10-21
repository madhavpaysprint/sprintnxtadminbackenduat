<?php
namespace App\Libraries\Common;

use App\Models\ChargesDefault;
use App\Models\Transaction;
use Illuminate\Support\Facades\Schema;
use App\Models\Charge;

class Charges{
    public function __construct()
    {
        $this->services=['balance_fetch','bank_statement','va_creation','vpa_creation','payout','imps','neft','rtgs','upi'];
        $this->service_types=['balance_fetch'=>'single','bank_statement'=>'single','va_creation'=>'single','vpa_creation'=>'single','payout'=>'single','imps'=>'slab','neft'=>'slab','rtgs'=>'slab','upi'=>'slab'];
        $this->service_names=['balance_fetch'=>'Balance Fetch','bank_statement'=>'Bank Statement','va_creation'=>'VA Creation','vpa_creation'=>'VPA Creation','payout'=>'Payout','imps'=>'IMPS','neft'=>'NEFT','rtgs'=>'RTGS','upi'=>'UPI'];
    }
    public static function get_charge_from_db($req){
        if(!empty($req)){
            $charges = Charge::where('bank', $req['bank'])->where('service', $req['service'])->get()->first();
            
            if($charges){
                return  array("status"=>true,"comm"=>$charges->charges);
            }
            else{

                return array("status"=>false,"message"=>"No Commission found");
            }
        }
        else{
            return array("status"=>false,"message"=>"No Commission found");
        }
    }

    public static function charge_from_db($req){
        if(!empty($req)){
            $Charge = Charge::where('userid', $req['userid'])->where('type', $req['type'])->first();
            if(!empty($Charge)){
                return  array("status"=>true,"charges"=>$Charge->commission);
            }
            else{
                $charges = '{"icici": {"ft": [{"max": 100, "min": 2, "charge": 2, "is_fixed": 1}, {"max": 200, "min": 101, "charge": 2, "is_fixed": 1}, {"max": 200000, "min": 201, "charge": 2, "is_fixed": 1}], "upi": [{"max": 100, "min": 1, "charge": 2, "is_fixed": 1}, {"max": 2000, "min": 101, "charge": 2, "is_fixed": 1}, {"max": 50000, "min": 2001, "charge": 2, "is_fixed": 1}], "imps": [{"max": 100, "min": 2, "charge": 2, "is_fixed": 1}, {"max": 200, "min": 101, "charge": 2, "is_fixed": 1}, {"max": 200000, "min": 201, "charge": 2, "is_fixed": 1}], "neft": [{"max": 100, "min": 2, "charge": 2, "is_fixed": 1}, {"max": 200, "min": 101, "charge": 2, "is_fixed": 1}, {"max": 2000000, "min": 201, "charge": 2, "is_fixed": 1}], "rtgs": [{"max": 1000000, "min": 200001, "charge": 2, "is_fixed": 1}, {"max": 2000000, "min": 1000001, "charge": 2, "is_fixed": 1}, {"max": 20000000, "min": 2000001, "charge": 2, "is_fixed": 1}], "penny": 3}}';
                return  array("status"=>true,"charges"=>$charges);
            }
        }
        else{
            return array("status"=>false,"message"=>"No Charge found");
        }
    }

    public static function payout($req) {
        $charged = 0;
        $amount = $req['amount'];
        $charge = self::charge_from_db(array("userid"=>$req['userid'],"type"=>$req["type"]));
        if($charge['status']){
            $array  = json_decode($charge['charges'],true);
            $arr = $array['icici'][strtolower($req['mode'])];
            if(isset($array['icici'][strtolower($req['mode'])])){
                foreach($array['icici'][strtolower($req['mode'])] as $value){
                    if ($req['amount'] >= $value['min'] && $req['amount'] <= $value['max']) {

                        if($value['is_fixed'] == 1){
                           $charged = $value['charge'];
                        }
                        else{
                            $charged  =  ($amount * $value['charge'])/100;
                        }
                    }
                }
                return array("status"=>true,"value"=>$charged,"message"=>"Charged fetched successful");
            }
            else{
                return array("status"=>false,"message"=>"Scheme not found");
            }
        }else{
            return array("status"=>false,"message"=>"Scheme not found");
        }
    }

    public static function upiCharges($req){
        if(!empty($req)){
            $Charge = Charge::where('userid', $req['userid'])->where('type', $req['type'])->first();
            //print_r($Charge);exit;
            if(!empty($Charge)){
                $charges = $Charge->toArray();
                return  array("status"=>true,"charges"=>$charges['commission']);
            }
            else{
                return array("status"=>false,"message"=>"No Charge found");
            }
        }
        else{
            return array("status"=>false,"message"=>"No Charge found");
        }

    }

    public static function upi($req) {
        $amount = $req['amount'];
        $charge = self::upiCharges(array("userid"=>$req['userid'],"type"=>$req["type"]));
        if($charge['status']){
            $array  = json_decode($charge['charges'],true);
            $arr = $array['icici'][strtolower($req['mode'])];
            if(isset($array['icici'][strtolower($req['mode'])])){
                foreach($array['icici'][strtolower($req['mode'])] as $value){
                    if ($req['amount'] >= $value['min'] && $req['amount'] <= $value['max']) {

                        if($value['is_fixed'] == 1){
                           $charged = $value['charge'];
                        }
                        else{
                            $charged  =  ($amount * $value['charge'])/100;
                        }
                    }
                  }
                  return array("status"=>true,"value"=>$charged,"message"=>"Charged fetched successful");
            }
            else{
                return array("status"=>false,"message"=>"Scheme not found");
            }
        }else{
            return array("status"=>false,"message"=>"Scheme not found");
        }
    }

    public static function singleCharge($req) {
        $charge = self::getCharges($req);
        if($charge['status']){
            $value  = $charge['charges'];
            if($value){
                 return array("status"=>true,"value"=>$value,'service_type'=>$charge['service_type'],'charges'=>$value,'service'=>$charge['service'],'service_name'=>$charge['service_name'],'is_fixed'=>1,"message"=>"Charged fetched successful");
            }
            else{
                return array("status"=>false,"message"=>"Scheme not found");
            }
        }else{
            return array("status"=>false,"message"=>"Scheme not found");
        }
    }

    public static function slabCharge($req) {
        $amount = $req['amount'];
        $charge = self::getCharges($req);
        if($charge['status']){
            $array  = json_decode($charge['charges'],true);
            if(!empty($array)){
                foreach($array as $value){
                    if ($req['amount'] >= $value['min'] && $req['amount'] <= $value['max']) {

                        if($value['is_fixed'] == 1){
                           $charged = $value['charge'];
                        }
                        else{
                            $charged  =  ($amount * $value['charge'])/100;
                        }
                    }
                }
                if(isset($charged)){
                    return array("status"=>true,"value"=>$charged,'charges'=>$value['charge'],'service'=>$charge['service'],'service_type'=>$charge['service_type'],'service_name'=>$charge['service_name'],'is_fixed'=>$value['is_fixed'],"message"=>"Charged fetched successful");
                }else{
                    return array("status"=>false,"message"=>"Scheme not found");
                }
            }
            else{
                return array("status"=>false,"message"=>"Scheme not found");
            }
        }else{
            return array("status"=>false,"message"=>"Scheme not found");
        }
    }

    public static function getCharges($req){
        if(!empty($req)){
            $Charge = Charge::where('userid', $req['userid'])->where('bank_id', $req['bank_id'])->where('service', $req['service'])->first();
            if(!empty($Charge)){
                $charges = $Charge->toArray();
                return  array("status"=>true,"charges"=>$charges['commision'],"service"=>$charges['service'],"service_name"=>$charges['service_name'],"service_type"=>$req['service_type']);
            }
            else{
                $ChargeDefault = ChargesDefault::where('bank_id', $req['bank_id'])->where('service', $req['service'])->first();
                if(!empty($ChargeDefault)){
                    $charges = $ChargeDefault->toArray();
                    return  array("status"=>true,"charges"=>$charges['commision'],"service"=>$charges['service'],"service_name"=>$charges['service_name'],"service_type"=>$req['service_type']);
                }
                return array("status"=>false,"message"=>"No Charge found");
            }
        }
        else{
            return array("status"=>false,"message"=>"No Charge found");
        }

    }

    public static function createTransaction($req){
        if(!empty($req)){
            $transaction = new Transaction();
            $transaction->date = date('Y-m-d');
            $transaction->time = date("h:i:s");
            $transaction->user_id = $req['userid'];
            $transaction->service = $req['service'];
            $transaction->service_name = $req['service_name'];
            $transaction->service_type = $req['service_type'];
            $transaction->charges = $req['charges'];
            $transaction->is_fixed = $req['is_fixed'];
            if(isset($req['value'])){
                $transaction->value = $req['value'];
            }
            $transaction->is_setteled = 0;
            if(isset($req['txn_id'])){
                $txnid = preg_replace('/\s+/', '', $req['txn_id']);
                $txnid = str_replace(':', '', $txnid);
                $txnid = str_replace('-', '', $txnid);
                $transaction->txn_id = str_replace(':', '', $txnid);
            }
            $transaction->save();
            return array("status"=>true,"message"=>"Transaction Created");
        }else{
            return array("status"=>false,"message"=>"Bad Request");
        }

    }

}