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
        'code',
        'item_code', 
        'item_id', 
        'quantity' 
    ];
    
    protected $useTimestamps = false;
    private $config = [];

    public function getItem ($resource_code, $item_code, $item_id) 
    {
        return $this->where('user_resources_expenses.code', $resource_code)->where('user_resources_expenses.item_code', $item_code)->where('user_resources_expenses.item_id', $item_id)->get()->getRowArray();
    }
    
    public function createItem ($user_id)
    {
        $this->transBegin();
        
        $data = [
            'user_id'       => $user_id,
            'character_id'  => getenv('user_resources_expenses.character_id'),
            'classroom_id'  => NULL,
            'course_id'     => NULL
            
        ];
        $user_resources_expenses_id = $this->insert($data, true);

        $this->transCommit();

        return $user_resources_expenses_id;        
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