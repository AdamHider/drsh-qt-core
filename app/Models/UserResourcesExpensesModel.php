<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class UserResourcesExpensesModel extends Model
{
    protected $table      = 'user_resources_expenses';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'user_id',
        'code',
        'item_code', 
        'item_id', 
        'quantity' 
    ];
    
    protected $useTimestamps = false;
    private $config = [];

    public function getItem ($resource_code, $item_code, $item_id, $user_id) 
    {
        return $this->where('user_resources_expenses.code', $resource_code)->where('user_resources_expenses.item_code', $item_code)
        ->where('user_resources_expenses.item_id', $item_id)->where('user_resources_expenses.user_id', $user_id)->get()->getRowArray();
    }
    
    public function createItem ($data)
    {
        $this->transBegin();
        
        $result = $this->insert($data, true);

        $this->transCommit();

        return $result;        
    }
    public function updateItem ($data)
    {
        $this->transBegin();

        $this->set($data);
        $this->where('user_id', $data['user_id']);
        $result = $this->update();

        $this->transCommit();

        return $result;        
    }


}