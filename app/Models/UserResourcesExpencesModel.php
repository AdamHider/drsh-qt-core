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
        'quantity', 
        'is_restorable', 
        'consumed_at'
    ];
    
    protected $useTimestamps = false;
    private $config = [];

    public function getItem ($code, $item_id) 
    {
        $this->getLevelConfig($user_id);   
        $this->checkRestoration($user_id);
        $consumables = $this->where('user_id', $user_id)->get()->getResultArray();

        return $consumables;
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