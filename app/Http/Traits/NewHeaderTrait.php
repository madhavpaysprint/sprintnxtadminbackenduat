<?php

namespace App\Http\Traits;

trait NewHeaderTrait
{
    public static function modules()
    {
        return [
            ['name' => 'parent_name', 'value' => 'Parent', 'is_show' => 1, 'issort' => 1],
            ['name' => 'name', 'value' => 'Module', 'is_show' => 1, 'issort' => 1],
            ['name' => 'parent_status', 'value' => 'Status', 'is_show' => 1, 'issort' => 1],
        ];
    }

    public static function transactions() 
    {
        return [
            ['name' => 'user_name', 'value' => 'Username', 'is_show' => 1, 'issort' => 1],
            ['name' => 'fullname', 'value' => 'Business Name', 'is_show' => 1, 'issort' => 1],
            ['name' => 'service', 'value' => 'Service', 'is_show' => 1, 'issort' => 1],
            ['name' => 'service_name', 'value' => 'Service Name', 'is_show' => 1, 'issort' => 1],
            ['name' => 'service_type', 'value' => 'Service Type', 'is_show' => 1, 'issort' => 1],
            ['name' => 'txn_id', 'value' => 'Transaction ID', 'is_show' => 1, 'issort' => 1],
            ['name' => 'utr', 'value' => 'UTR', 'is_show' => 1, 'issort' => 1],
            ['name' => 'amount', 'value' => 'Amount', 'is_show' => 1, 'issort' => 1],
            ['name' => 'transaction_type', 'value' => 'Transaction Type', 'is_show' => 1, 'issort' => 1],
            ['name' => 'wallet_type', 'value' => 'Wallet Type', 'is_show' => 1, 'issort' => 1],
            ['name' => 'opening', 'value' => 'Opening Balance', 'is_show' => 1, 'issort' => 1],
            ['name' => 'closing', 'value' => 'Closing Balance', 'is_show' => 1, 'issort' => 1],
            ['name' => 'remarks', 'value' => 'Remarks', 'is_show' => 1, 'issort' => 1],
            ['name' => 'is_settled', 'value' => 'Is Settled', 'is_show' => 1, 'issort' => 1],
            ['name' => 'date', 'value' => 'Date', 'is_show' => 1, 'issort' => 1],
            ['name' => 'time', 'value' => 'Time', 'is_show' => 1, 'issort' => 1],
        ];
    }

    public static function fundHistory()
    {
        return [
            ['name' => 'amount', 'value' => 'Amount', 'is_show' => 1, 'issort' => 1],
            ['name' => 'type', 'value' => 'Type', 'is_show' => 1, 'issort' => 1],
            ['name' => 'transaction_id', 'value' => 'Transaction Id', 'is_show' => 1, 'issort' => 1],
            ['name' => 'user_id', 'value' => 'User Id', 'is_show' => 1, 'issort' => 1],
            ['name' => 'username', 'value' => 'Username', 'is_show' => 1, 'issort' => 1],
            ['name' => 'fullname', 'value' => 'Full name', 'is_show' => 1, 'issort' => 1],
            ['name' => 'done_by_username', 'value' => 'Done by username', 'is_show' => 1, 'issort' => 1],
            ['name' => 'done_by_email', 'value' => 'Done by email', 'is_show' => 1, 'issort' => 1],
            ['name' => 'opening', 'value' => 'Opening', 'is_show' => 1, 'issort' => 1],
            ['name' => 'closing', 'value' => 'Closing', 'is_show' => 1, 'issort' => 1],
            ['name' => 'done_by', 'value' => 'Done by', 'is_show' => 1, 'issort' => 1],
            ['name' => 'remarks', 'value' => 'Remarks', 'is_show' => 1, 'issort' => 1],
            ['name' => 'narration', 'value' => 'Narration', 'is_show' => 1, 'issort' => 1],
        ];
    }
}