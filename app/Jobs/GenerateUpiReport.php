<?php

namespace App\Jobs;

use App\Exports\ExportUpiTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;

class GenerateUpiReport implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $count = 0;
        Log::info("GenerateUpiTransactionReport Job started");

        // Fetch all reports from the admin database that match the criteria
        $reports = DB::connection('mysql')
            ->table('report_download_schedulers')
            ->where('status',3)->get();

        Log::info("Fetched Reports Count: " . count($reports));

        foreach ($reports as $value) {
            $config = $this->reportConfig((array) $value);
            if (!empty($config)) {
                $fileName = "Report/transaction_report_" . rand(10, 1000) . "_" . strtotime("now") . ".xlsx";
                DB::connection('mysql')->table('report_download_schedulers')->where('id', $value->id)->update(['status' => 2]);

                Log::info("Report Config: " . json_encode($config));

                $output = Excel::store(new ExportUpiTransaction(
                    $config['table'],
                    $config['join'] ?? [],
                    $config['condition'],
                    $config['select'],
                    $config['column']
                ), $fileName);

                $msg['response'] = $output;
                $msg['data'] = $value;

                if ($output && Storage::exists($fileName)) {
                    $count++;
                    DB::connection('mysql')->table('report_download_schedulers')->where('id', $value->id)
                        ->update([
                            'status' => 1,
                            'download_link' => $fileName,
                            'expired_at' => date("Y-m-d h:i:s", strtotime("+3 months"))
                        ]);

                    $msg['message'] = "Excel generated successfully!";
                } else {
                    $msg['message'] = "Problem in excel generation. Please try again!";
                }

                // Log the message
                Log::info(json_encode($msg));
            } else {
                Log::info("No valid config for report ID: " . $value->id);
            }
        }

        Log::info("GenerateUpiTransactionReport Job completed. Total reports generated: " . $count);
    }

    /**
     * Generate report configuration based on given data.
     *
     * @param array $fetch
     * @return array
     */
    public function reportConfig($fetch)
    {
        $data = [];

        // Example configuration, adjust as per your actual requirements
        $data['table'] = 'merchant_upis';
        $data['column'] = ['TXNID', 'MERCHANT', 'BANK', 'REFID', 'AMOUNT', 'CHARGES', 'GST', 'SETTLEAMOUNT', 'INITATION-DATE', 'COMPLETION-DATE', 'QRTYPE', 'VPA', 'PAYER', 'PAYERVA', 'RRN', 'STATUS', 'CREATEDATE'];
        $data['select'] = ['merchant_upis.txnid', 'merchant_upis.merchant_code', 'merchant_upis.bank_id', 'merchant_upis.qr_refid', 'merchant_upis.payer_amount', 'merchant_upis.charges', 'merchant_upis.gst', 'merchant_upis.amount', 'merchant_upis.txn_init_date', 'merchant_upis.txn_completion_date', 'merchant_upis.qr_type', 'vpa.vpa', 'merchant_upis.payer_name', 'merchant_upis.payer_va', 'merchant_upis.original_bank_rrn', 'merchant_upis.status', 'merchant_upis.addeddate'];

        $data['condition']['order'] = 'merchant_upis.created_at';
        $data['condition']['orderby'] = 'ASC';

        // Always set the date range condition
        $data['condition']['whereDate'][0]['field'] = 'merchant_upis.addeddate';
        $data['condition']['whereDate'][0]['range'] = [
            date('Y-m-d', strtotime($fetch['from_date'])),
            date('Y-m-d', strtotime($fetch['to_date']))
        ];

        // Check if user_id is not 1 before adding the userid condition
        if ($fetch['user_id'] != 1) {
            $data['condition']['where'][1]['field'] = 'merchant_upis.userid';
            $data['condition']['where'][1]['operator'] = '=';
            $data['condition']['where'][1]['value'] = $fetch['user_id'];
        } else {
            // If user_id is 1, do not include the userid condition
            unset($data['condition']['where'][1]);
        }

        $data['join'] = [
            [
                'table' => 'merchant_vpas_copy as vpa',
                'local' => 'merchant_upis.merchant_code',
                'foreign' => 'vpa.merchantID'
            ]
        ];

        return $data;
    }



}
