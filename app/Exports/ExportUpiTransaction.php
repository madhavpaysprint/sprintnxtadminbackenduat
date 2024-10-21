<?php

namespace App\Exports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ExportUpiTransaction implements FromCollection, WithHeadings
{
    protected $table;
    protected $join;
    protected $condition;
    protected $select;
    protected $customColumn;

    public function __construct($table, $join = [], $condition, $select, $customColumn)
    {
        $this->table = $table;
        $this->join = $join;
        $this->condition = $condition;
        $this->select = $select;
        $this->customColumn = $customColumn;
    }

    public function headings(): array
    {
        return $this->customColumn;
    }

    public function collection()
    {
        $dataQuery = DB::connection('pgsql')->table($this->table);

        if (!empty($this->select)) {
            foreach ($this->select as $ex_c) {
                $dataQuery = $dataQuery->addSelect($ex_c);
            }
        }

        if (!empty($this->join)) {
            foreach ($this->join as $joins) {
                $dataQuery->leftJoin($joins['table'], $joins['local'], '=', $joins['foreign']);
            }
        }

        if (!empty($this->condition)) {
            if (isset($this->condition['where'])) {
                foreach ($this->condition['where'] as $where) {
                    $dataQuery->where($where['field'], $where['operator'], $where['value']);
                }
            }
            if (isset($this->condition['whereDate'])) {
                foreach ($this->condition['whereDate'] as $whereDate) {
                    $dataQuery->whereBetween($whereDate['field'], $whereDate['range']);
                }
            }
        }

        if (isset($this->condition['searchColumn'])) {
            $searchColumn = $this->condition['searchColumn'];
            $searchvalue = $this->condition['searchvalue'];
            $dataQuery->where(function ($query) use ($searchColumn, $searchvalue) {
                foreach ($searchColumn as $column) {
                    $query->orWhere($column, 'like', '%' . trim($searchvalue) . '%');
                }
            });
        }

        if (isset($this->condition['start'])) {
            $dataQuery->skip($this->condition['start'])->take($this->condition['length']);
        }

        $dataQuery->orderBy($this->condition['order'], $this->condition['orderby']);

        // Log the SQL query and bindings for debugging
        Log::info('SQL Query: ' . $dataQuery->toSql());
        Log::info('Bindings: ', $dataQuery->getBindings());

        $usersData = $dataQuery->get();

        // Log the fetched data for debugging
        Log::info('Fetched Data: ', $usersData->toArray());

        return collect($usersData);
    }
}
