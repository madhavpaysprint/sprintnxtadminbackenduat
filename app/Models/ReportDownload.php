<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReportDownload extends Model
{
    protected $connection = 'pgsql';
    protected $table='report_download_schedulers';
    protected $fillable=['service_id', 'user_id','requested_email','requested_mobile', 'from_date', 'to_date', 'status', 'download_link', 'bankid', 'is_mail_sent', 'expired_at', 'created_at', 'updated_at', 'deleted_at'];
}
