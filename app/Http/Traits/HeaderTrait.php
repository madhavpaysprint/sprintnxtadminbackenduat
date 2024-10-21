<?php
namespace App\Http\Traits;

trait HeaderTrait
{
    static function bankslist(){
        $result = array();
        $result[] = ['name' => 'name', 'value' => 'Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'logo', 'value' => 'Logo', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Created At', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function accountTypeHeader(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'type', 'value' => 'Type', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Created At', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function bankform(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'label', 'value' => 'Label', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bank_name', 'value' => 'Bank', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'type', 'value' => 'Type', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'required', 'value' => 'Required', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Created At', 'is_show' => 1, 'issort' => 0];
        return $result;
    }
    static function bankformnew(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'zip', 'value' => 'ZIP', 'is_show' => 1, 'issort' => 0];
       return $result;
    }

    static function modules(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'name', 'value' => 'Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Created At', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function roles(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'name', 'value' => 'Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Created At', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function menuItems()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'name', 'value' => 'Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'type', 'value' => 'Type', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'urlapi', 'value' => 'URL API', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'icon', 'value' => 'Icon', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'menu', 'value' => 'Menu', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function notifications()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'title', 'value' => 'Title', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'start_date', 'value' => 'Start Date', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'end_date', 'value' => 'End Date', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Created At', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function users(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'fullname', 'value' => 'Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'username', 'value' => 'Username', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'email', 'value' => 'Email', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'phone', 'value' => 'Phone', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'role', 'value' => 'Role', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function cib(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'partner', 'value' => 'Partner Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bank_name', 'value' => 'Bank', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'corporate_name', 'value' => 'Company Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'sender_phone', 'value' => 'Phone', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'sender_email', 'value' => 'Email', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'holderName', 'value' => 'Holder Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'account_number', 'value' => 'Acc. no', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'ifsc', 'value' => 'IFSC', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'account_type', 'value' => 'Account Type', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bankuserid', 'value' => 'Bank U.ID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'pan', 'value' => 'Pan', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'gst', 'value' => 'GST', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'corporateid', 'value' => 'Bank C.ID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Created At', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
         return $result;
    }

    static function transactionshead()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'merchant', 'value' => 'Merchant', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bank', 'value' => 'Bank', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'txnid', 'value' => 'Txn. ID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'refid', 'value' => 'Ref. ID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'payer_amount', 'value' => 'Amount', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'charges', 'value' => 'Charges', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'gst', 'value' => 'gst', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'amount', 'value' => 'Settle Amount', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'txn_init_date', 'value' => 'TXN Init. Date', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'txn_completion_date', 'value' => 'TXN Completion Date', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'vpa', 'value' => 'VPA', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'rrn', 'value' => 'RRN', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'payer_name', 'value' => 'Payer Name', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'payer_va', 'value' => 'Payer VA', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'qr_type', 'value' => 'QR Type', 'is_show' => $isshow, 'issort' => 0];
        $result[] = ['name' => 'addeddate', 'value' => 'Added Date', 'is_show' => $isshow, 'issort' => 0];
       return $result;
    }

    static function vpastatement()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'partner', 'value' => 'Partner', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bank', 'value' => 'Bank', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'vpa', 'value' => 'VPA', 'is_show' => 1, 'issort' => 0];
//        $result[] = ['name' => 'charge', 'value' => 'Charges', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'mcc_code', 'value' => 'MCC Code', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'settelemt_acnn', 'value' => 'Settlement Account', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'settelemt_bank_name', 'value' => 'Settlement BankName', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'settelemt_bene_name', 'value' => 'Settlement BeneName', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'settelemt_ifsccode', 'value' => 'Settlement IFSC', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'merchantID', 'value' => 'Merchant Code', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created', 'value' => 'Create Time', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function beneficiarylist(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'partner', 'value' => 'Partner Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'name', 'value' => 'Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'cpname', 'value' => 'Contact Parson Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'mobile', 'value' => 'Mobile', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'email', 'value' => 'Email', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'accno', 'value' => 'Account Number', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'ifsc', 'value' => 'IFSC Code', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bankname', 'value' => 'Bank Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'remarks', 'value' => 'Remarks', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'createdat', 'value' => 'Create Time', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function payoutlist(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'username', 'value' => 'Partner Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'partner_code', 'value' => 'Partner Code', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'service_type', 'value' => 'Service Type', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'sender_bank', 'value' => 'Partner Bank', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bene_bank_name', 'value' => 'Bene Bank Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bene_acc_no', 'value' => 'Beneficiary A/c', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bene_acc_ifsc', 'value' => 'Bene IFSC Code', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'sender_acc_no', 'value' => 'Sender Account Number', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'mode', 'value' => 'Mode', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'amount', 'value' => 'Amount', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'charge', 'value' => 'Charges', 'is_show' => 1, 'issort' => 0];
//        $result[] = ['name' => 'remarks', 'value' => 'Remarks', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'utr_rrn', 'value' => 'UTR', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'transaction_ref_no', 'value' => 'TXN Ref Number', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'transferId', 'value' => 'Transfer ID', 'is_show' => 1, 'issort' => 0]; // added by @vinay on 30-09
        $result[] = ['name' => 'refunded_txn_charge_id', 'value' => 'Refunded ID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'createdat', 'value' => 'Initiate Time', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'success_time', 'value' => 'Success Time', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'is_refunded', 'value' => 'Is Refunded', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'refid', 'value' => 'Reference ID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'refunded_datetime', 'value' => 'Refunded Date', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function bankDetailsList(){
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'fullname', 'value' => 'Partner Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bank_name', 'value' => 'Bank Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'corp_id', 'value' => 'CorpID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'approver_id', 'value' => 'Approver ID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'maker_id', 'value' => 'MakerID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'checker_id', 'value' => 'CheckerID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'signature', 'value' => 'Signature', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'ldap_user_id', 'value' => 'Ldap UserID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'ldap_password', 'value' => 'Ldap Password', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'secret_id', 'value' => 'Secret ID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'client_id', 'value' => 'Client ID', 'is_show' => 1, 'issort' => 0];
         $result[] = ['name' => 'ssl_certificate', 'value' => 'SSL Certificate', 'is_show' => 1, 'issort' => 0];
         $result[] = ['name' => 'ssl_private_key', 'value' => 'SSL Private Key', 'is_show' => 1, 'issort' => 0];
        // $result[] = ['name' => 'ssl_public_key', 'value' => 'SSL Public Key', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    public static function maskString($string, $maskChar = '*', $showLast = 4) {
        $len = strlen($string);
        $maskLen = $len - $showLast;
        if ($maskLen <= 0) {
            return $string; // If not, return the original string
        }
        $maskedPart = str_repeat($maskChar, $maskLen);
        $lastPart = substr($string, -$showLast);
        return $maskedPart . $lastPart;
    }

    static function vastatement()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'partner', 'value' => 'Partner', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bank', 'value' => 'Bank', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'acc_no', 'value' => 'Account No.', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'name', 'value' => 'Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'email', 'value' => 'Email', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'phone', 'value' => 'Phone', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'pan', 'value' => 'Pan', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'charge', 'value' => 'Charges', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created_at', 'value' => 'Created at', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function vatransactionshead()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'merchant', 'value' => 'Merchant', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'bank', 'value' => 'Bank', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'created_at', 'value' => 'Create Time', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'account_name', 'value' => 'Account Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'acc_no', 'value' => 'Account No.', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'amount', 'value' => 'Amount', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'charge', 'value' => 'Charges', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'p_mode', 'value' => 'Payment Mode', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'remitter_name', 'value' => 'Remitter Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'remitter_ac_no', 'value' => 'Remitter Acc. no.', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'utr', 'value' => 'UTR', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'txn_date', 'value' => 'Transaction Date', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function userCredentialsHead()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'ip', 'value' => 'IP Address', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'client_id', 'value' => 'Client id', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'client_secret', 'value' => 'Client Secret', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'enciv', 'value' => 'Enc IV', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'enckey', 'value' => 'Enc Key', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'public_key', 'value' => 'Public Key', 'is_show' => 1, 'issort' => 0];
//        $result[] = ['name' => 'dbkey', 'value' => 'DB Key', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'callback', 'value' => 'Callback', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'previous_callback', 'value' => 'Previous Callback', 'is_show' => 1, 'issort' => 0];
        return $result;
    }


    static function partners()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'partner_name', 'value' => 'Partner Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'user_id', 'value' => 'User Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'phone', 'value' => 'Phone', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'balance', 'value' => 'Balance', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'email', 'value' => 'Email', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'createdat', 'value' => 'Added On', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'onboarding_status', 'value' => 'Onboarding Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'active_banks', 'value' => 'Active Banks', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        return $result;
    }

    static function allReportsHeader()
    {
        $isshow = 1;
        $issort = 1;
        $result[] = ['name' => 'fullname', 'value' => 'Full Name', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'user_id', 'value' => 'User ID', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'service', 'value' => 'Service', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'from_date', 'value' => 'From Date', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'to_date', 'value' => 'To Date', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'status', 'value' => 'Status', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'remarks', 'value' => 'Remarks', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'link', 'value' => 'Link', 'is_show' => 1, 'issort' => 0];
        $result[] = ['name' => 'generated_by', 'value' => 'Generated By', 'is_show' => 1, 'issort' => 0];
        return $result;
    }
}
