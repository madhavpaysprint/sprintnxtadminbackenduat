<?php
namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Models\MerchantUpi;
use App\Models\VaTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QrController extends Controller
{

    public function QrGraphData(Request $_request) {
        try {
            $userid = $_request->user_id;
            $startdate = $_request->startdate;
            $enddate = $_request->enddate;

            // Default to today's date if not provided
            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : date('Y-m-d');
            $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : date('Y-m-d', strtotime('-7 days', strtotime($enddate)));

            // Convert to DateTime objects
            $startdateObj = new \DateTime($startdate);
            $enddateObj = new \DateTime($enddate);
            $dateDiff = $startdateObj->diff($enddateObj)->days;

            // Ensure the date range does not exceed one month
            if ($dateDiff > 30) {
                return CommonTrait::response('invaliddaterange');
            }

            // Helper functions to apply conditions and date range filters
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

            // Apply filters and date ranges to the queries
            $vaTransactionsQuery = applyCommonConditions(VaTransactions::query(), $userid, 1);
            $merchantUpiQuery = applyCommonConditions(MerchantUpi::query(), $userid, 1);

            // Generate labels and initialize data arrays
            $labels = [];
            $vaVolumeData = [];
            $vaTransactionData = [];
            $merchantVolumeData = [];
            $merchantTransactionData = [];

            // Start date and end date for the loop
            $currentDate = clone $startdateObj;

            // Loop through each date in the range
            while ($currentDate <= $enddateObj) {
                $formattedDate = $currentDate->format('Y-m-d');
                $labels[] = $currentDate->format('j M');

                // Fetch data from VaTransactions for the current date
                $vaTransactionDataForDate = (clone $vaTransactionsQuery)->whereDate('txn_date', $formattedDate);
                $vaVolume = $vaTransactionDataForDate->sum('amount');
                $vaCount = $vaTransactionDataForDate->count();

                // Fetch data from MerchantUpi for the current date
                $merchantUpiDataForDate = (clone $merchantUpiQuery)->whereDate('date', $formattedDate);
                $merchantVolume = $merchantUpiDataForDate->sum('amount');
                $merchantCount = $merchantUpiDataForDate->count();

                // Add the calculated volume and transaction count to the respective arrays
                $vaVolumeData[] = (float)$vaVolume;
                $vaTransactionData[] = $vaCount;
                $merchantVolumeData[] = (float)$merchantVolume;
                $merchantTransactionData[] = $merchantCount;

                // Move to the next date
                $currentDate->modify('+1 day');
            }

            $result = [
                "series" => [
                    [
                        "name" => "VA Volume (In ₹)",
                        "data" => $vaVolumeData,
                    ],
                    [
                        "name" => "VA No. of Transactions",
                        "data" => $vaTransactionData,
                    ],
                    [
                        "name" => "Merchant Volume (In ₹)",
                        "data" => $merchantVolumeData,
                    ],
                    [
                        "name" => "Merchant No. of Transactions",
                        "data" => $merchantTransactionData,
                    ],
                ],
                "labels" => $labels,
            ];

            return CommonTrait::response('success', $result);
        } catch (\Exception $e) {
            return CommonTrait::response('internalservererror', ['message' => $e->getMessage()]);
        }
    }

}
