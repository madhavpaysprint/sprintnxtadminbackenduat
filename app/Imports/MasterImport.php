<?php

namespace App\Imports;

use App\Models\BankMaster;
use App\Models\Cardinventory;
use GuzzleHttp\Client;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\ToModel;

class MasterImport implements ToModel, WithHeadingRow
{

    protected $import = 'bank';
    private $client;
    public $entryType;
    public function __construct($key)
    {
        $this->entryType = $key;
    }

    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        if ($this->entryType == 'bank') {
            return $this->bankMaster($row);
        }if ($this->entryType == 'cardinventory') {
            return $this->cardInventory($row);
        }
    }

    public function bankMaster($row)
    {
        return new BankMaster([
            'name'      => $row['name'],
            'ifsc'      => $row['ifsc'],
            'address'   => $row['address'],
        ]);
    }
    public function cardInventory($row)
    {
       // dd($row);
        return new Cardinventory([
            'branch_id'          => $row['branch_id'],
            'card_reference_no'  => $row['card_reference_no'],
            'title'              => $row['title'],
            'traveller_name'     => $row['traveller_name'],
            'passport'           => $row['passport'],
            'pan_no'             => $row['pan_no'],
        ]);
    }
}
