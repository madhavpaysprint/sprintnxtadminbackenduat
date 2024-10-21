<?php

namespace App\Http\Controllers;

use App\Models\MerchantUpi;
use App\Models\VaTransactions;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\CommonTrait;
use Illuminate\Support\Facades\DB;
class BusinessController extends Controller
{
    private $key = '';

    private $passphrase = "";
    private $encrypt_method = "";
    private $iv = "";

    public function __construct(){
        $this->passphrase = "my-passphrase";
        $this->encrypt_method = "aes-256-cbc";
        $this->iv = base64_decode('zGi3wV4UN4qdc+EeTH2e4A==');
        DB::statement("SET SQL_MODE=''");
        $this->new_date         = Carbon::now();
        $this->today            = Carbon::now()->toDateString();
        $this->end_date_mtd     = Carbon::now()->endOfMonth()->toDateString();
        $this->month_start_date = Carbon::now()->startOfMonth()->toDateString();
        $this->year_start_date  = Carbon::now()->startOfYear()->toDateString();
        $this->date_lwsd        = Carbon::now()->subDays(7)->toDateString();
        $this->end_date_lmtd    = Carbon::now()->subMonth()->toDateString();
    }
    public function BusinessTrend(Request $_request){
        try {

            $userid = $_request->user_id;
            $startdate = $_request->startdate;
            $enddate = $_request->enddate;

            $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : date('Y-m-d');
            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : date('Y-m-d');

            $startdateObj = new \DateTime($startdate);
            $enddateObj = new \DateTime($enddate);
            $dateDiff = $startdateObj->diff($enddateObj)->days;

            if ($dateDiff > 30) {
                return CommonTrait::response('invaliddaterange');
            }

            function applyCommonConditions($query, $userid, $status) {
                return $query->where('status', $status)
                    ->when($userid, function ($q) use ($userid) {
                        return $q->where('userid', $userid);
                    });
            }
            function applyDateRange($query, $columnName, $startdate, $enddate) {
                if (isset($startdate) && isset($enddate)) {
                    $query->whereDate($columnName, '>=', $startdate)
                        ->whereDate($columnName, '<=', $enddate);
                }
                return $query;
            }

            $sql = "select sum(payer_amount) as today_volume,count(id) as today_count from tbl_merchant_upis where status = 1
             and  addeddate >= '" . $startdate . "' and addeddate <= '" . $enddate . "' OR userid = '" . $userid . "'";
            $todayLeads =  DB::connection('pgsql')->select($sql);

            $sql2 = "select count(id) as today_expired from tbl_merchant_upis where status = 4 and addeddate >= '" . $startdate . "' and  addeddate <= '" . $enddate . "' OR userid = '" . $userid . "'";
            $todayexpired =  DB::connection('pgsql')->select($sql2);

             $sql3 = "select count(id) as today_pending from tbl_merchant_upis where status = 3 and  addeddate >= '" . $startdate . "' and addeddate <= '" . $enddate . "' OR userid = '" . $userid . "'";
               $todaypending =  DB::connection('pgsql')->select($sql3);
            $vaTransactionsQuery = applyCommonConditions(VaTransactions::query(), $userid, 1);
            $vaSuspendVol = applyCommonConditions(VaTransactions::query(), $userid, 4);
            //$merchantUpiQuery = applyCommonConditions(MerchantUpi::query(), $userid, 1);
            // $totalPending = applyCommonConditions(MerchantUpi::query(), $userid, 6);
            // $totalExpired = applyCommonConditions(MerchantUpi::query(), $userid, 4);

            $vaTransactionsQuery = applyDateRange($vaTransactionsQuery, 'txn_date', $startdate, $enddate);
            //$merchantUpiQuery = applyDateRange($merchantUpiQuery, 'addeddate', $startdate, $enddate);
            $vaSuspendVol = applyDateRange($vaSuspendVol, 'txn_date', $startdate, $enddate);
            // $totalPending = applyDateRange($totalPending, 'addeddate', $startdate, $enddate);
            // $totalExpired = applyDateRange($totalExpired, 'addeddate', $startdate, $enddate);

            $sql5 = "select sum(amount) as today_volume,count(id) as today_count from tbl_payouts where status = 1   and addeddate >= '" . $startdate . "' and addeddate <= '" . $enddate . "' OR userid = '" . $userid . "'";
            $todayPayouts =  DB::connection('pgsql')->select($sql5);

            $sql7 = "select count(id) as today_pending from tbl_payouts where status = 2  and addeddate >= '" . $startdate . "' and addeddate <= '" . $enddate . "' OR userid = '" . $userid . "'";
            $todayPendingPayout =  DB::connection('pgsql')->select($sql7);

            $sql9 = "select count(id) as today_hold from tbl_payouts where status = 6 and addeddate >= '" . $startdate . "' and addeddate <= '" . $enddate . "' OR userid = '" . $userid . "'";
            $todayHoldPayout =  DB::connection('pgsql')->select($sql9);

            $sql10 = "select count(id) as today_failed from tbl_payouts where status = 4 and addeddate >= '" . $startdate . "' and addeddate <= '" . $enddate . "' OR userid = '" . $userid . "'";

            $todayFailedPayout =DB::connection('pgsql')->select($sql10);

            $result = [
                "va" =>[
                    "total_transaction" => $vaTransactionsQuery->count(),
                    "total_volume" => $vaTransactionsQuery->sum('amount'),
                    "suspense_vol" => $vaSuspendVol->sum('amount'),
                    "suspense_txn" => $vaSuspendVol->count(),
                ],
                "qr"=>[
                      "total_transaction" =>$todayLeads[0]->today_count,
                      "total_volume" => $todayLeads[0]->today_volume,//$merchantUpiQuery->sum('payer_amount'),
                      "total_pending" =>$todaypending[0]->today_pending,//$totalPending->count(),
                      "total_expired" =>$todayexpired[0]->today_expired,//$totalExpired->count(),
                ],
                "payout"=>[
                    "total_transaction" =>$todayPayouts[0]->today_count,
                    "total_volume" => $todayPayouts[0]->today_volume,//$merchantUpiQuery->sum('payer_amount'),
                    "total_pending" =>$todayPendingPayout[0]->today_pending,//$totalPending->count(),
                    "total_hold" =>$todayHoldPayout[0]->today_hold,//$totalExpired->count(),
                    "total_failed"=>$todayFailedPayout[0]->today_failed
                ]
            ];
            return CommonTrait::response('success', $result);
        } catch (\Exception $e) {
            return CommonTrait::response('internalservererror', ['message' => $e->getMessage()]);
        }
    }
}
