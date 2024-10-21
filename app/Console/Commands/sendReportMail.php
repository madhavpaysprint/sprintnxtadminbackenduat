<?php

namespace App\Console\Commands;

use App\Libraries\Common\Email;
use App\Libraries\Common\Emailtemplate;
use App\Models\ReportDownload;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Libraries\Common\Logs;

class sendReportMail extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send report to user';

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
        $fetch = ReportDownload::select('id','requested_email', 'from_date', 'to_date', 'download_link')->where(['is_mail_sent' => 0,'status' => 1])->orderBy('created_at', 'ASC')->get();
        foreach ($fetch as $key => $value) {
            if ($value->requested_email != "") {
                $reqData = array();
                $reqData['from_date'] = date('d-m-Y', strtotime($value['from_date']));
                $reqData['to_date'] = date('d-m-Y', strtotime($value['to_date']));
                $req = [
                    "to" => $value->requested_email,
                    "subject" => "SprintNXT || Transaction Report(" . $reqData['from_date'] . " to " . $reqData['to_date'] . ")",
                    "attachement" => [env('BASEURL') . "storage/app/" . $value->download_link],
                    "template" => Emailtemplate::transactionReport($reqData),
                ];

                    $eoutput = Email::sendemail($req);
                    $eresponse=json_decode($eoutput,true);
                    if (isset($eresponse['status']) && $eresponse['status']) {
                        $count++;
                        ReportDownload::where('id', $value->id)->update(['is_mail_sent' => 1]);
                        $response = ["message" => "Mail Send successfully!", "data"=>["request"=>$value,"mailbody"=>$req]];
                        Logs::writelogs(array("dir" => "Cron/MailReport", "type" => "REQUEST-RESPONSE" . rand(10, 1000), "data" => json_encode($response)));
                    }else{
                        $response = ["message" => "Mail sending failed!", "data"=>["request"=>$value,"mailbody"=>$req,"error"=>$eresponse]];
                        Logs::writelogs(array("dir" => "Cron/MailReport", "type" => "REQUEST-RESPONSE" . rand(10, 1000), "data" => json_encode($response)));
                    }

            }
        }
        $output = ["message" => "Scheduled Task Completed Successfully!. Total " . $count . " Mail Send successfully!"];
        echo json_encode($output);
    }
}
