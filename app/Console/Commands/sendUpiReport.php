<?php

namespace App\Console\Commands;

use App\Exports\ExportUpiTransaction;
use App\Jobs\GenerateUpiReport;
use App\Libraries\Common\Logs;
use App\Models\AdminReportDownload;
use App\Models\ApiUser;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class sendUpiReport extends Command
{
    protected $signature = 'upitransaction:report';
    protected $description = 'Upi Transaction Report';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        dispatch(new GenerateUpiReport());
        $this->info('Upi Transaction Report job dispatched successfully!');
    }

//    public function handle()
//    {
//        $count = 0;
//        $report = AdminReportDownload::where(['status' => 3])
//            ->orderBy('created_at', 'ASC')
//            ->get();
//
//        if (!$report->isEmpty()) {
//            foreach ($report as $value) {
//                $config = $this->reportConfig($value);
//                if (!empty($config)) {
//                    $fileName = "Report/transaction_report_" . rand(10, 1000) . "_" . strtotime("now") . ".xlsx";
//                    AdminReportDownload::where('id', $value['id'])->update(['status' => 2]);
//
//                    Log::info("Report Config: ", $config);
//
//                    $output = Excel::store(new ExportUpiTransaction(
//                        $config['table'],
//                        $config['join'] ?? [],
//                        $config['condition'],
//                        $config['select'],
//                        $config['column']
//                    ), $fileName);
//
//                    $msg['response'] = $output;
//                    $msg['data'] = $value;
//
//                    if ($output && Storage::exists($fileName)) {
//                        $count++;
//                        AdminReportDownload::where('id', $value['id'])
//                            ->update([
//                                'status' => 1,
//                                'download_link' => $fileName,
//                                'expired_at' => date("Y-m-d h:i:s", strtotime("+3 months"))
//                            ]);
//
//                        $msg['message'] = "Excel generated successfully!";
//                    } else {
//                        $msg['message'] = "Problem in excel generation. Please try again!";
//                    }
//
//                    Logs::writelogs(["dir" => "Cron/AdminReportDownload", "type" => "REQUEST-" . rand(10, 1000), "data" => json_encode($msg)]);
//                }
//            }
//
//            $output = ["message" => "Scheduled Task Completed Successfully!. Total " . $count . " report generated successfully!"];
//        } else {
//            $output = ["message" => "No pending report download request found. Please try again!"];
//        }
//
//        echo json_encode($output);
//    }

//    public function reportConfig($fetch = [])
//    {
//        $data = [];
//        if ($fetch['service_id'] == 2) {
//            $data['table'] = 'merchant_upis';
//            $data['column'] = ['TXNID', 'MERCHANT', 'BANK', 'REFID', 'AMOUNT', 'CHARGES', 'GST', 'SETTLEAMOUNT', 'INITATION-DATE', 'COMPLETION-DATE', 'QRTYPE', 'VPA', 'PAYER', 'PAYERVA', 'RRN', 'STATUS', 'CREATEDATE'];
//            $data['select'] = ['merchant_upis.txnid', 'merchant_upis.merchant_code', 'merchant_upis.bank_id', 'merchant_upis.qr_refid', 'merchant_upis.payer_amount', 'merchant_upis.charges', 'merchant_upis.gst', 'merchant_upis.amount', 'merchant_upis.txn_init_date', 'merchant_upis.txn_completion_date', 'merchant_upis.qr_type', 'vpa.vpa', 'merchant_upis.payer_name', 'merchant_upis.payer_va', 'merchant_upis.original_bank_rrn', 'merchant_upis.status', 'merchant_upis.addeddate'];
//
//            $data['condition']['order'] = 'merchant_upis.created_at';
//            $data['condition']['orderby'] = 'ASC';
//
//            $data['condition']['whereDate'][0]['field'] = 'merchant_upis.addeddate';
//            $data['condition']['whereDate'][0]['range'] = [
//                date('Y-m-d', strtotime($fetch['from_date'])),
//                date('Y-m-d', strtotime($fetch['to_date']))
//            ];
//
////            $data['condition']['where'][0]['field'] = 'merchant_upis.bank_id';
////            $data['condition']['where'][0]['operator'] = '=';
////            $data['condition']['where'][0]['value'] = $fetch['bankid'];
//
//            if (!empty($fetch['user_id'])) {
//                $data['condition']['where'][1]['field'] = 'merchant_upis.userid';
//                $data['condition']['where'][1]['operator'] = '=';
//                $data['condition']['where'][1]['value'] = $fetch['user_id'];
//            }
//
//            // Add the left join condition for merchant_vpas_copy with alias vpa
//            $data['join'] = [
//                [
//                    'table' => 'merchant_vpas_copy as vpa',
//                    'local' => 'merchant_upis.merchant_code',
//                    'foreign' => 'vpa.merchantID'
//                ]
//            ];
//        }
//        return $data;
//    }
}
