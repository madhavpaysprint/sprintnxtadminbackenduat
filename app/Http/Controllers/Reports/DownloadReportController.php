<?php
namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\HeaderTrait;
use App\Models\ReportDownload;
use Illuminate\Http\Request;

class DownloadReportController extends Controller
{
    use CommonTrait, HeaderTrait;

    public function __construct(){
        $this->status = ['1' => 'Success', '2' => 'Processing', '3' => 'Initiate', '4' => 'Failed'];
    }

    /**
     * @param Request $_request ["userid" => ""]
     * @function DownloadReport (for download the report)
     * @return JsonResponse
     * @author Madhav <madhav.kumar@ciphersquare.tech>
     */
    public function DownloadReport(Request $_request){
        try {
            $userid = $_request->user_id;

            function applyCommonConditions($query, $userid) {
                return $query->when($userid, function ($q) use ($userid) {
                        return $q->where('user_id', $userid);
                    });
            }

            // Apply filters and date ranges to the queries
            $reportDownload = applyCommonConditions(ReportDownload::query(), $userid)->get();
            if ($reportDownload->isEmpty()){
                return CommonTrait::response('noresult');
            }

            foreach ($reportDownload as $key => $report){
                $reportDownload[$key]->status = $this->status[$report->status];
                $reportDownload[$key]->download_link = storage_path() .'/logs/'. $report->download_link;
                unset($reportDownload[$key]->created_at);
                unset($reportDownload[$key]->updated_at);
                unset($reportDownload[$key]->deleted_at);
            }

            $final = [
                "data" => $reportDownload
            ];
            return CommonTrait::response('success', $final);
        }catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
}
