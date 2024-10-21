<?php

namespace App\Console\Commands;

use App\Exports\ExportTransaction;
use App\Models\ReportDownload;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Libraries\Common\Logs;

class sendReport extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transacrtion Report';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $count = 0;
        $report = ReportDownload::where('created_at', "<=", date('Y-m-d H:i:s', strtotime('-10 minutes')))
            ->where(['status' => 3])
            ->orderBy('created_at', 'ASC')
            ->get();

        if (!empty($report)) {
            foreach ($report as $key => $value) {
                $config = $this->reportConfig($value);
                if (!empty($config)) {
                    $fileName = "Report/transaction_report_" . rand(10, 1000) . "_" . strtotime("now") . ".xlsx";
                    ReportDownload::where('id', $value['id'])->update(['status' => 2]);
                    $output = Excel::store(new ExportTransaction($config['table'], isset($config['join']) ?? $config['join'], $config['condition'], $config['select'], $config['column']), $fileName);
                    $msg['response'] = $output;
                    $msg['data'] = $value;

                    if ($output && Storage::exists($fileName)) {
                        $count++;
                        ReportDownload::where('id', $value['id'])
                            ->update(
                                [
                                    'status' => 1,
                                    'download_link' => $fileName,
                                    'expired_at' => date("Y-m-d h:i:s", strtotime("+3 months"))
                                ]
                            );

                        $msg['message'] = "Excel generated successfully!";
                    } else {
                        $msg['message'] = "Problem in excel generation. Please try again!";
                    }
                                    Logs::writelogs(array("dir" => "Cron/ReportDownload", "type" => "REQUEST-" . rand(10, 1000), "data" => json_encode($msg)));
                }

            }
            $output = ["message" => "Scheduled Task Completed Successfully!. Total " . $count . " report generated successfully!"];
        } else {
            $output = ["message" => "No pending report download request found. Please try again!"];
        }
        echo json_encode($output);
    }

    //Configuration Function
    public function reportConfig($fetch = [])
    {
        $data = [];
        if ($fetch['service_id'] == 1) {

            $data['table'] = 'va_transactions';
            $data['column'] = ['User ID','Bank ID', 'Txn id', 'C. Code', 'Va No', 'Amount', 'Charge', 'Payment Mode', 'UTR', 'Remitter Name', 'Remitter Account No.', 'Sender IFSC',  'Txn date', 'Status', 'Remarks', 'Created Date'];
            $data['select'] = ['va_transactions.userid','va_transactions.bank_id', 'va_transactions.txnid', 'va_transactions.c_code', 'va_transactions.va_no', 'va_transactions.amount', 'va_transactions.charge', 'va_transactions.p_mode', 'va_transactions.utr', 'va_transactions.remitter_name', 'va_transactions.remitter_ac_no', 'va_transactions.sender_ifsc', 'va_transactions.txn_date', 'va_transactions.status', 'va_transactions.remarks', 'va_transactions.created_at'];

            //-------------------------Filter Start------------------//
            $data['condition']['order'] = 'va_transactions.created_at';
            $data['condition']['orderby'] = 'ASC';

            $data['condition']['whereDate'][0]['field'] = 'va_transactions.created_at';
            $data['condition']['whereDate'][0]['range'] = [date('Y-m-d', strtotime($fetch['from_date'] . ' - 1 day')), date('Y-m-d', strtotime($fetch['to_date'] . ' + 1 day'))];

            $data['condition']['where'][0]['field'] = 'va_transactions.bank_id';
            $data['condition']['where'][0]['operator'] = '=';
            $data['condition']['where'][0]['value'] = $fetch['bankid'];

            if ($fetch['user_id'] != "") {
                $data['condition']['where'][1]['field'] = 'va_transactions.userid';
                $data['condition']['where'][1]['operator'] = '=';
                $data['condition']['where'][1]['value'] = $fetch['user_id'];
            }

            //------------------Additional Filter uncomment if required--------------------//
            // $data['condition']['searchColumn']= ['va_transactions.bank_id','va_transactions.txnid', 'va_transactions.c_code']; 
            // $data['condition']['searchvalue']="1";

            // $data['join'][0]['table']='users';
            // $data['join'][0]['local']='users.id';
            // $data['join'][0]['foreign']='va_transactions.userid';

            // $data['condition']['start']= 1; 
            // $data['condition']['length']='10';
            //------------------Additional conditions uncomment if required--------------------//

        }
        return $data;
    }
}
