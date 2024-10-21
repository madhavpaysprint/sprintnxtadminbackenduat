<?php

namespace App\Http\Controllers\Reports;

use App\Models\AdminReportDownload;
use App\Models\ApiUser;
use App\Models\BankDetails;
use App\Models\BankList;
use App\Models\MerchantUpi;
use App\Models\MerchantVpa;
use App\Models\Report;
use App\Models\ReportDownload;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Traits\CommonTrait;
use App\Http\Traits\HeaderTrait;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use DateTime;
use DateTimeZone;
use Carbon\Carbon;

class UpiController extends Controller
{
    use CommonTrait, HeaderTrait;
    public function __construct()
    {
        $this->statuscode = "success";
        $this->response = [];
        $this->adm_status_array = ['1' => 'Success', '2' => 'In Process', '3' => 'Processing', '4' => 'Processed', '0' => 'Failed'];
        $this->status_array = ['1' => 'Success', '0' => 'Failed'];
        $this->qrtypes = ['1' => 'Static', '2' => 'Dynamic','3' => 'Initate Transfer','4' => 'Intent'];
        $this->TransactionStatus = ['1' => 'Success', '2' => 'Initited', '3' => 'Qr Generated', '4' => 'Qr Expired', '5' => 'Failed','6' => 'Pending'];
        $this->today = date('Y-m-d');
        $this->reportStatus = ['0'=> 'Processing', '1'=> 'Created', '2' => 'Failed', '3' => 'Requested'];
    }
    public function report(Request $request)
    {
        try {
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $searchvalue = $request->searchvalue;
            $orderby = $request->orderby;
            $order = $request->order;
            $status = $request->status;
            $start = $request->start;
            $length = $request->length;
            $searchby = $request->searchby;
            $qrType = $request->qrType;
            $isArchieve = $request->isArchieve;



            $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : date('Y-m-d');
            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : date('Y-m-d');

             // Check if startdate or enddate is more than 90 days back
            $ninetyDaysAgo = date('Y-m-d', strtotime('-90 days'));
            if (($startdate < $ninetyDaysAgo || $enddate < $ninetyDaysAgo) && !$isArchieve) {
                return $this->response('incorrectinfo', [
                    'status' => false,
                    'responsecode' => 0,
                    'message' => "Choose Archieve to view data older than 90 days"
                ]);
            }
            if (!($this->new_date_difference($startdate, $enddate))) {
                return $this->response('incorrectinfo', [
                    'status' => false,
                    'responsecode' => 0,
                    'message' => "You can only fetch data of " . $this->new_allowed_days() . " Days"
                ]);
            }

            $_table = "merchant_upis"; 
            if($isArchieve) {
                $_table = "merchant_upis_archieved";
            }
            

            $searchColumn = [
                'upi.merchant_code',
                'upi.payer_name',
                'upi.payer_va',
                'upi.txn_completion_date',
                'upi.addeddate',
                'upi.txnid',
                'upi.original_bank_rrn',
                'upi.qr_type',
                'upi.bank_refid',
                'vpa.acc_no',
                'vpa.vpa',
                'vpa.ifsccode',
                'user.fullname'
            ];

            $select = [
                'upi.created_at',
                'upi.updated_at',
                'upi.merchant_code',
                'upi.payer_name',
                'upi.payer_va',
                'upi.amount',
                'upi.txn_completion_date',
                'upi.txn_init_date',
                'upi.addeddate',
                'upi.status',
                'upi.txnid',
                'upi.original_bank_rrn',
                'upi.qr_type',
                'upi.qr_refid',
                'upi.client_txnReferance',
                'upi.bank_refid',
                'upi.charges',
                'upi.gst',
                'upi.receiverVpa',
                'upi.payer_amount',
                'vpa.acc_no as acc_no',
                'vpa.vpa as vpa',
                'vpa.ifsccode as ifsccode',
                'user.fullname as merchant',
                'bank.name as bank'
            ];    
             $query = DB::connection('pgsql')->table("$_table as upi"); 
            $query->leftJoin('merchant_vpas_copy as vpa', 'vpa.merchantID', '=', 'upi.merchant_code'); 
            $query->leftJoin('users as user', 'user.id', '=', 'upi.userid');
            $query->leftjoin('bank_lists as bank', 'bank.id', '=', 'upi.bank_id');
            $query->select($select); 

            $query->when(!empty($startdate) && !empty($enddate), function ($q) use ($startdate, $enddate) {
                $q->where('upi.addeddate', '>=', $startdate);
                $q->where('upi.addeddate', '<=', $enddate);
            });

            $query->when($request->user_id, function ($q) use ($request) {
                $q->where('upi.userid', $request->user_id);
            });

            $query->when($request->bank_id, function ($q) use ($request) {
                $q->where('upi.bank_id', $request->bank_id);
            });

            $query->when($request->merchantID, function ($q) use ($request) {
                $q->where('upi.merchant_code', $request->merchantID);
            });

            $query->when($qrType, function ($q) use ($qrType) {
                $q->where('upi.qr_type', $qrType);
            });

            $query->when($searchby === "txnid" && !empty($searchvalue), function ($q) use ($searchvalue) {
                $q->where('upi.txnid', $searchvalue);
            });

            $query->when($searchby === "vpa" && !empty($searchvalue), function ($q) use ($searchvalue) {
                $q->where('upi.receiverVpa', $searchvalue);
            });

            $query->when($searchby === "rrn" && !empty($searchvalue), function ($q) use ($searchvalue) {
                $q->where('upi.original_bank_rrn', $searchvalue);
            });

            $query->when($searchby === "refid" && !empty($searchvalue), function ($q) use ($searchvalue) {
                $q->where(function ($q) use ($searchvalue) {
                    $q->where('upi.qr_refid', $searchvalue)
                        ->orWhere('upi.client_txnReferance', $searchvalue);
                });
            });

            $query->when($status, function ($q) use ($status) {
                $q->where('upi.status', $status);
            }, function ($q) {
                $q->whereIn('upi.status', [1, 3, 4, 5, 6]);
            });

            $recordsTotal = $query->count();

            if (!empty($searchvalue)) {
                $query->where(function ($query) use ($searchColumn, $searchvalue) {
                    foreach ($searchColumn as $column) {
                        $query->orWhere($column, 'like', '%' . trim($searchvalue) . '%');
                    }
                });
            }

            $query->orderBy('upi.id', 'desc');

            $length = (!empty($length)) ? $length : 20;
            $start = (!empty($start)) ? $start : 0;

            $data = $query->skip($start)->take($length)->get();  
            $recordsFiltered = $data->count();
            $headdata = $this->transactionshead();

            $transactions = [];

            if ($data->isEmpty()) {
                return $this->response('noresult', ['statuscode' => 200, 'header' => $headdata]);
            }

            $total_payer_amount = 0;
            $totalcharge = 0;
            $totalgst = 0;
            $totalsettelmet = 0;

            foreach ($data as $singleTran) { 
                $txn_completion_dateF = isset($singleTran->txn_completion_date) ? new DateTime($singleTran->txn_completion_date) : '-';
                $txn_completion_date = ($txn_completion_dateF === '-') ? '-' : $txn_completion_dateF->format('d-m-Y g:i:s A');

                $txn_init_dateF = isset($singleTran->txn_init_date) ? new DateTime($singleTran->txn_init_date) : '-';
                $txn_init_date = ($txn_init_dateF === '-') ? '-' : $txn_init_dateF->format('d-m-Y g:i:s A');
   

                $transactions[] = [
                    'created_at' => date("d-m-Y H:i:s", strtotime($singleTran->created_at)),
                    'merchant' => $singleTran->merchant,
                    'bank' => $singleTran->bank,
                    'refid' => $singleTran->bank_refid,
                    'charges' => $singleTran->charges,
                    'amount' => $singleTran->amount,
                    'gst' => $singleTran->gst,
                    'addeddate' => date("d-m-Y", strtotime($singleTran->addeddate)),
                    'txn_init_date' => $txn_init_date,
                    'qr_type' => $this->qrtypes[$singleTran->qr_type],
                    'vpa' => $singleTran->vpa,
                    'payer_name' => $singleTran->payer_name,
                    'payer_va' => $singleTran->payer_va,
                    'payer_amount' => $singleTran->payer_amount,
                    'rrn' => $singleTran->original_bank_rrn,
                    'txnid' => $singleTran->txnid,
                    'txn_completion_date' => ($txn_completion_date === "") ? $txn_init_date : $txn_completion_date,
                    'status' => $this->TransactionStatus[$singleTran->status],
                ]; 
            }

            return $this->response('success', [
                'message' => "Success", 
                'recordsFiltered' => $recordsFiltered,
                'recordsTotal' => $recordsTotal,
                'header' => $headdata,
                'data' => $transactions
            ]);

        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    public function exportUpiTransactionList(Request $request)
    {
        try {
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $searchvalue = $request->search;
            $status = $request->status;

            $startdate = !empty($startdate)?date('Y-m-d',strtotime($startdate)):date('Y-m-d');
            $enddate = !empty($enddate)?date('Y-m-d',strtotime($enddate)):date('Y-m-d');
            if (empty($startdate) && empty($enddate)) {
                $startdate = date('Y-m-d');
                $enddate   = date('Y-m-d');
            }

            if (!($this->new_date_difference($startdate, $enddate))) {
                return $this->response('incorrectinfo', [
                    'status' => false,
                    'responsecode' => 0,
                    'message' => "You can only export data of " . $this->new_allowed_days() . " Days"
                ]);
            }
            if (empty($startdate) && empty($enddate)) {
                return $this->response('incorrectinfo', [
                    'status' => false,
                    'responsecode' => 0,
                    'message' => "Please add param.",
                ]);
            } 
            $searchColumn = [
                'upi.merchant_code',
                'upi.payer_name',
                'upi.payer_va',
                'upi.txn_completion_date',
                'upi.addeddate',
                'upi.txnid',
                'upi.original_bank_rrn',
                'upi.qr_type',
                'upi.bank_refid',
                'vpa.acc_no',
                'vpa.vpa',
                'vpa.ifsccode',
                'user.fullname'
            ];

            $select = [
                'upi.created_at',
                'upi.updated_at',
                'upi.merchant_code',
                'upi.payer_name',
                'upi.payer_va',
                'upi.amount',
                'upi.txn_completion_date',
                'upi.txn_init_date',
                'upi.addeddate',
                'upi.status',
                'upi.txnid',
                'upi.original_bank_rrn',
                'upi.qr_type',
                'upi.qr_refid',
                'upi.client_txnReferance',
                'upi.bank_refid',
                'upi.charges',
                'upi.gst',
                'upi.receiverVpa',
                'upi.payer_amount',
                'vpa.acc_no as acc_no',
                'vpa.vpa as vpa',
                'vpa.ifsccode as ifsccode',
                'user.fullname as merchant',
                'bank.name as bank'
            ];    
            $query = DB::connection('pgsql')->table('merchant_upis as upi'); 
            $query->leftJoin('merchant_vpas_copy as vpa', 'vpa.merchantID', '=', 'upi.merchant_code'); 
            $query->leftJoin('users as user', 'user.id', '=', 'upi.userid');
            $query->leftjoin('bank_lists as bank', 'bank.id', '=', 'upi.bank_id');
            $query->select($select);
                $query->when(!empty($startdate) && !empty($enddate), function ($q) use ($startdate, $enddate) {
                    $q->where('upi.addeddate', '>=', $startdate);
                    $q->where('upi.addeddate', '<=', $enddate);
                }); 
            if ($request->user_id) {
                $query->where('upi.userid',$request->user_id);
            }
            if ($request->bank_id) {
                $query->where('upi.bank_id',$request->bank_id);
            }
            if ($request->merchantID) {
                $query->where('upi.merchant_code',$request->merchantID);
            }
            $query->when($status, function ($q) use ($status) {
                $q->where('upi.status', $status);
            }, function ($q) {
                $q->whereIn('upi.status', [1, 3, 4, 5, 6]);
            });
            $recordsTotal = $query->count(); 
            // Added by @vinay on 10-10-2024
           if($recordsTotal > $this->export_limit()) {
            return $this->response('incorrectinfo', [
                'status' => false,
                'responsecode' => 0,
                'message' => "You can only export data of maximum " . $this->export_limit() . " rows"
            ]);
           }
            $data = $query->get();
            if (empty($data)) {
                return $this->response('noresult', ['message' => "No record found.", 'data' => '', 'recordsFiltered' => '', 'recordsTotal' => '']);
            } 
            foreach ($data as $singleTran) {

                $txn_completion_dateF = isset($singleTran->txn_completion_date) ? new DateTime($singleTran->txn_completion_date) : '-';
                $txn_completion_date = ($txn_completion_dateF === '-') ? '-' : $txn_completion_dateF->format('d-m-Y g:i:s A');

                $txn_init_dateF = isset($singleTran->created_at) ? new DateTime($singleTran->created_at) : '-';
                $txn_init_date = ($txn_init_dateF === '-') ? '-' : $txn_init_dateF->format('d-m-Y g:i:s A');
 
                $sub_array = [
                    'TXNID' => $singleTran->txnid,
                    'MERCHANT' => $singleTran->merchant,
                    'BANK' => $singleTran->bank,
                    'REFID' => $singleTran->bank_refid,
                    'AMOUNT' => $singleTran->payer_amount,
                    'CHARGES' => $singleTran->charges,
                    'GST' => $singleTran->gst,
                    'SETTLEAMOUNT' => $singleTran->amount,
                    'INITATION-DATE' => $txn_init_date,
                    'COMPLETION-DATE' => $txn_completion_date,
                    'QRTYPE' => $this->qrtypes[$singleTran->qr_type],
                    'VPA' => $singleTran->vpa,
                    'PAYER' => $singleTran->payer_name,
                    'PAYERVA' => $singleTran->payer_va,
                    'RRN' => $singleTran->original_bank_rrn,
                    'STATUS' => $this->TransactionStatus[$singleTran->status],
                   // 'CREATEDATE' => date("d-m-Y H:i:s", strtotime($singleTran->created_at)),
                ];
                $data1[] = $sub_array;
            }
            return $this->response('success', ['message' => "Success.", 'data' => $data1]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    /**
     * @Function AllReport
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @author Madhav <madhav.kumar@ciphersquare.tech>
     */
    public function AllReport(Request $request){
        try{
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $searchvalue = $request->search;
            $status = $request->status;
            $userid = $request->userid;
            $start = $request->start;
            $length = $request->length;

            $startdate = !empty($startdate) ? date('Y-m-d', strtotime($startdate)) : date('Y-m-d');
            $enddate = !empty($enddate) ? date('Y-m-d', strtotime($enddate)) : date('Y-m-d');

            if (empty($startdate) && empty($enddate)) {
                $startdate = date('Y-m-d');
                $enddate = date('Y-m-d');
            }

            // Fetch API users
                        $apiUsers = ApiUser::select('fullname', 'id')->get();
                        $userIds = $apiUsers->pluck('id')->toArray();

            // Base query for reports
                        $reportsQuery = DB::connection('mysql')
                            ->table('reports')
                            ->whereIn('userid', $userIds);

            // Apply date filtering condition if startdate and enddate are provided
                        if (!empty($startdate) && !empty($enddate)) {
                            $reportsQuery->whereDate('from_date', '>=', $startdate)
                                ->whereDate('to_date', '<=', $enddate);
                        }

            // Apply userid filter if provided
                        if ($userid) {
                            $reportsQuery->where('userid', $userid);
                        }

            // Apply status filter if provided
                        if ($status || $status === '0' || $status === 0) {
                            $reportsQuery->where('status', $status);
                        }

            // Clone the query for total count
                        $recordsTotalQuery = clone $reportsQuery;
                        $recordsTotal = $recordsTotalQuery->count();

            // Apply search filter if provided
                        if (!empty($searchvalue)) {
                            $reportsQuery->where(function ($query) use ($searchvalue) {
                                $query->where('service', 'like', "%{$searchvalue}%")
                                    ->orWhere('remarks', 'like', "%{$searchvalue}%");
                            });
                        }

            // Clone the query for filtered count after applying search filter
                        $recordsFilteredQuery = clone $reportsQuery;
                        $recordsFiltered = $recordsFilteredQuery->count();

            // Pagination
                        $data = $reportsQuery->skip($start)->take($length)->get();

                        $headdata = $this->allReportsHeader();

                        if ($data->isEmpty()) {
                            return $this->response('noresult', ['statuscode' => 200, 'header' => $headdata]);
                        }

            // Prepare the final response data
                        $responseData = $data->map(function ($report) use ($apiUsers) {
                            $user = $apiUsers->firstWhere('id', $report->userid);
                            $statusLabel = isset($this->reportStatus[$report->status]) ? $this->reportStatus[$report->status] : 'Unknown';
                            return [
                                'fullname' => $user->fullname,
                                'id' => $user->id,
                                'report_id' => $report->id,
                                'service' => $report->service,
                                'from_date' => $report->from_date,
                                'to_date' => $report->to_date,
                                'status' => $statusLabel,
                                'remarks' => $report->remarks,
                                'link' => $report->link,
                                'generated_by' => $report->generated_by,
                            ];
                        });

            // Return response
                        return $this->response('success', [
                            'message' => "Success.",
                            'header' => $headdata,
                            'data' => $responseData,
                            'recordsFiltered' => $recordsFiltered,
                            'recordsTotal' => $recordsTotal
                        ]); 

        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }

    /**
     * @Function exportUpiTransactionListTest
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @author Madhav <madhav.kumar@ciphersquare.tech>
     */
    public function exportUpiTransactionListTest(Request $request)
    {
        try {
            $startdate = $request->startdate;
            $enddate = $request->enddate;
            $searchvalue = $request->search;
            $status = $request->status;

            $startdate = !empty($startdate)?date('Y-m-d',strtotime($startdate)):date('Y-m-d');
            $enddate = !empty($enddate)?date('Y-m-d',strtotime($enddate)):date('Y-m-d');
            if (empty($startdate) && empty($enddate)) {
                $startdate = date('Y-m-d');
                $enddate   = date('Y-m-d');
            }

            $datetime1 = new DateTime($startdate);
            $datetime2 = new DateTime($enddate);

            $interval = $datetime1->diff($datetime2);
            $daysDifference = $interval->days;

            if ($daysDifference > 1) {
                return $this->response('incorrectinfo', [
                    'status' => false,
                    'responsecode' => 0,
                    'message' => "You can only export data of " . $this->ExportAllowedDays() . " Days"
                ]);
            }

            $adminReportDownload = AdminReportDownload::select('from_date', 'to_date', 'status')
                ->where(['from_date' => $startdate, 'to_date' => $enddate, 'status' => 3])->first();

            if($adminReportDownload){
                return $this->response('notvalidatthemoment');
            }

            if (empty($startdate) && empty($enddate)) {
                return $this->response('incorrectinfo', [
                    'status' => false,
                    'responsecode' => 0,
                    'message' => "Please add param.",
                ]);
            }

            $service_id = $request->serviceId;
            $currentDate = new DateTime();
            $currentDate->modify('+2 days');
            $expireDate = $currentDate->format('Y-m-d h:m:s'); 

            $reportDownload = new AdminReportDownload();
            $reportDownload->service_id = 2;
            $reportDownload->user_id = Auth::user()->id;
            $reportDownload->requested_email = Auth::user()->email;
            $reportDownload->requested_mobile = Auth::user()->phone;
            $reportDownload->from_date = $startdate;
            $reportDownload->to_date = $enddate;
            $reportDownload->status = 3;
            $reportDownload->download_link = '';
            $reportDownload->is_mail_sent = 0;
            $reportDownload->expired_at = $expireDate;
            $saveReportDownload = $reportDownload->save();

            return $this->response('success', ['message' => "Success."]); 
            $searchColumn = [
                'merchant_upis.merchant_code',
                'merchant_upis.payer_name',
                'merchant_upis.payer_va',
                'merchant_upis.txn_completion_date',
                'merchant_upis.addeddate',
                'merchant_upis.txnid',
                'merchant_upis.original_bank_rrn',
                'merchant_upis.qr_type',
                'merchant_upis.bank_refid',
                'vpa.acc_no',
                'vpa.vpa',
                'vpa.ifsccode',
                'user.fullname'
            ];

            $select = [
                'merchant_upis.created_at',
                'merchant_upis.merchant_code',
                'merchant_upis.payer_name',
                'merchant_upis.payer_va',
                'merchant_upis.amount',
                'merchant_upis.txn_completion_date',
                'merchant_upis.txn_init_date',
                'merchant_upis.addeddate',
                'merchant_upis.status',
                'merchant_upis.txnid',
                'merchant_upis.original_bank_rrn',
                'merchant_upis.qr_type',
                'merchant_upis.bank_refid',
                'merchant_upis.charges',
                'merchant_upis.gst',
                'merchant_upis.payer_amount',
                'vpa.acc_no as acc_no',
                'vpa.vpa as vpa',
                'vpa.ifsccode as ifsccode',
                'user.fullname as merchant',
                'bank.name as bank'
            ];
            $query = MerchantUpi::with(['vpa', 'user', 'bank'])
                ->select($select)
                ->leftJoin('merchant_vpas_copy as vpa', 'merchant_upis.merchant_code', '=', 'vpa.merchantID')
                ->leftJoin('users as user', 'merchant_upis.userid', '=', 'user.id')
                ->leftJoin('bank_lists as bank', 'merchant_upis.bank_id', '=', 'bank.id');

            $query->when(!empty($startdate) && !empty($enddate), function ($q) use ($startdate, $enddate) {
                $q->whereDate('merchant_upis.created_at', '>=', $startdate);
                $q->whereDate('merchant_upis.created_at', '<=', $enddate);
            });



            // $query = DB::connection('pgsql')->table('merchant_upis as U');

            // if($request->user_id){
            //     $query->where('U.userid', $request->user_id);
            // }
            // $query->join('merchant_vpas_copy as vpa', 'vpa.merchantID', '=', 'U.merchant_code');
            // $query->join('users', 'users.id', '=', 'U.userid');
            // $query->join('bank_lists', 'bank_lists.id', '=', 'U.bank_id');
            // $query->select($select);
            // $query->whereDate('U.created_a', '>=', $startdate);
            // $query->whereDate('U.created_at', '<=', $enddate);

            if ($request->user_id) {
                $query->where('U.userid',$request->user_id);
            }
            if ($request->bank_id) {
                $query->where('U.bank_id',$request->bank_id);
            }
            if ($request->merchantID) {
                $query->where('U.merchant_code',$request->merchantID);
            }
            $query->when($status, function ($q) use ($status) {
                $q->where('merchant_upis.status', $status);
            }, function ($q) {
                $q->whereIn('merchant_upis.status', [1, 3, 4, 5, 6]);
            });
            $recordsTotal = $query->count();

            // $query->when($searchby === "refid" && !empty($searchvalue), function ($q) use ($searchvalue) {
            //     $q->where(function ($q) use ($searchvalue) {
            //         $q->where('merchant_upis.qr_refid', $searchvalue)
            //             ->orWhere('merchant_upis.client_txnReferance', $searchvalue);
            //     });
            // });


            $data = $query->get();
            if (empty($data)) {
                return $this->response('noresult', ['message' => "No record found.", 'data' => '', 'recordsFiltered' => '', 'recordsTotal' => '']);
            }
            foreach ($data as $singleTran) {
                $txn_completion_dateF = isset($singleTran->txn_completion_date) ? new DateTime($singleTran->txn_completion_date) : '-';
                $txn_completion_date = ($txn_completion_dateF === '-') ? '-' : $txn_completion_dateF->format('d-m-Y g:i:s A');

                $txn_init_dateF = isset($singleTran->txn_init_date) ? new DateTime($singleTran->txn_init_date) : '-';
                $txn_init_date = ($txn_init_dateF === '-') ? '-' : $txn_init_dateF->format('d-m-Y g:i:s A');

                $sub_array = [
                    'TXNID' => $singleTran->txnid,
                    'MERCHANT' => $singleTran->merchant,
                    'BANK' => $singleTran->bank,
                    'REFID' => $singleTran->bank_refid,
                    'AMOUNT' => $singleTran->payer_amount,
                    'CHARGES' => $singleTran->charges,
                    'GST' => $singleTran->gst,
                    'SETTLEAMOUNT' => $singleTran->amount,
                    'INITATION-DATE' => $txn_init_date,
                    'COMPLETION-DATE' => $txn_completion_date,
                    'QRTYPE' => $this->qrtypes[$singleTran->qr_type],
                    'VPA' => $singleTran->vpa,
                    'PAYER' => $singleTran->payer_name,
                    'PAYERVA' => $singleTran->payer_va,
                    'RRN' => $singleTran->original_bank_rrn,
                    'STATUS' => $this->TransactionStatus[$singleTran->status],
                    'CREATEDATE' => date("d-m-Y H:i:s", strtotime($singleTran->created_at)),
                ];
                $data1[] = $sub_array;
            }
            return $this->response('success', ['message' => "Success.", 'data' => $data1]);
        } catch (\Throwable $th) {
            return $this->response('internalservererror', ['message' => $th->getMessage()]);
        }
    }
}
