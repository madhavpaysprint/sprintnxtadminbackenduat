<?php

namespace App\Http\Controllers;

use App\Models\MerchantUpi;
use App\Models\VaTransactions;
use App\Models\Payout;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Traits\CommonTrait;
use Illuminate\Database\Eloquent\Builder;

class DashboardController extends Controller
{
    private $passphrase;
    private $encrypt_method;
    private $iv;

    public function __construct() {
        $this->passphrase = "my-passphrase";
        $this->encrypt_method = "aes-256-cbc";
        $this->iv = base64_decode('zGi3wV4UN4qdc+EeTH2e4A==');
        $this->new_date = Carbon::now();
        $this->today = Carbon::now()->toDateString();
        $this->end_date_mtd = Carbon::now()->endOfMonth()->toDateString();
        $this->month_start_date = Carbon::now()->startOfMonth()->toDateString();
        $this->year_start_date = Carbon::now()->startOfYear()->toDateString();
        $this->date_lwsd = Carbon::now()->subDays(7)->toDateString();
        $this->end_date_lmtd = Carbon::now()->subMonth()->toDateString();
    }

    private function validateDateRange($startdate, $enddate) {
        $startdateObj = new \DateTime($startdate);
        $enddateObj = new \DateTime($enddate);
        $dateDiff = $startdateObj->diff($enddateObj)->days;

        if ($dateDiff > 30) {
            return false; 
        }
        return true;
    }

    public function getVaTransactions(Request $request) {
        try {
            $userid = $request->user_id;
            $startdate = $request->startdate ? date('Y-m-d', strtotime($request->startdate)) : $this->today;
            $enddate = $request->enddate ? date('Y-m-d', strtotime($request->enddate)) : $this->today;

            if (!$this->validateDateRange($startdate, $enddate)) {
                return CommonTrait::response('invaliddaterange');
            }

            $vaTransactionsQuery = VaTransactions::where('status', 1)
                ->whereBetween('txn_date', [$startdate, $enddate]);

            if ($userid) {
                $vaTransactionsQuery->where('userid', $userid);
            }

            $vaSuspendVol = VaTransactions::where('status', 4)
                ->whereBetween('txn_date', [$startdate, $enddate]);

            if ($userid) {
                $vaSuspendVol->where('userid', $userid);
            }

            $result = [
                "va_total_transaction" => $vaTransactionsQuery->count(),
                "va_total_volume" => $vaTransactionsQuery->sum('amount'),
                "va_suspense_vol" => $vaSuspendVol->sum('amount'),
                "va_suspense_txn" => $vaSuspendVol->count(),
            ];

            return CommonTrait::response('success', $result);
        } catch (\Exception $e) {
            return CommonTrait::response('internalservererror', ['message' => $e->getMessage()]);
        }
    }

    public function getQrTransactions(Request $request) {
        try {
            $userid = $request->user_id;
            $startdate = $request->startdate ? date('Y-m-d', strtotime($request->startdate)) : $this->today;
            $enddate = $request->enddate ? date('Y-m-d', strtotime($request->enddate)) : $this->today;

            if (!$this->validateDateRange($startdate, $enddate)) {
                return CommonTrait::response('invaliddaterange');
            }
            $merchantUpiQuery = MerchantUpi::where('status', 1)
                ->whereBetween('addeddate', [$startdate, $enddate]);

            if ($userid) {
                $merchantUpiQuery->where('userid', $userid);
            }

            $todayPending = MerchantUpi::where('status', 3)
                ->whereBetween('addeddate', [$startdate, $enddate])
                ->when($userid, function (Builder $query) use ($userid) {
                    return $query->where('userid', $userid);
                });

            $todayExpired = MerchantUpi::where('status', 4)
                ->whereBetween('addeddate', [$startdate, $enddate])
                ->when($userid, function (Builder $query) use ($userid) {
                    return $query->where('userid', $userid);
                });

            $result = [
                "qr_total_transaction" => $merchantUpiQuery->count(),
                "qr_total_volume" => $merchantUpiQuery->sum('payer_amount'),
                "qr_total_pending" => $todayPending->count(),
                "qr_total_expired" => $todayExpired->count(),
            ];

            return CommonTrait::response('success', $result);
        } catch (\Exception $e) {
            return CommonTrait::response('internalservererror', ['message' => $e->getMessage()]);
        }
    }

    public function getPayoutTransactions(Request $request) {
        try {
            $userid = $request->user_id;
            $startdate = $request->startdate ? date('Y-m-d', strtotime($request->startdate)) : $this->today;
            $enddate = $request->enddate ? date('Y-m-d', strtotime($request->enddate)) : $this->today;
            if (!$this->validateDateRange($startdate, $enddate)) {
                return CommonTrait::response('invaliddaterange');
            }
            $payoutQuery = Payout::where('status', 1)
                ->whereBetween('addeddate', [$startdate, $enddate]);

            if ($userid) {
                $payoutQuery->where('userid', $userid);
            }

            $todayPendingPayout = Payout::where('status', 2)
                ->whereBetween('addeddate', [$startdate, $enddate])
                ->when($userid, function (Builder $query) use ($userid) {
                    return $query->where('userid', $userid);
                });

            $todayHoldPayout = Payout::where('status', 6)
                ->whereBetween('addeddate', [$startdate, $enddate])
                ->when($userid, function (Builder $query) use ($userid) {
                    return $query->where('userid', $userid);
                });

            $todayFailedPayout = Payout::where('status', 4)
                ->whereBetween('addeddate', [$startdate, $enddate])
                ->when($userid, function (Builder $query) use ($userid) {
                    return $query->where('userid', $userid);
                });

            $result = [
                "po_total_transaction" => $payoutQuery->count(),
                "po_total_volume" => $payoutQuery->sum('amount'),
                "po_total_pending" => $todayPendingPayout->count(),
                "po_total_hold" => $todayHoldPayout->count(),
                "po_total_failed" => $todayFailedPayout->count()
            ];

            return CommonTrait::response('success', $result);
        } catch (\Exception $e) {
            return CommonTrait::response('internalservererror', ['message' => $e->getMessage()]);
        }
    }
}
