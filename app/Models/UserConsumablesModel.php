<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class UserConsumablesModel extends Model
{
    protected $table      = 'user_consumables';
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

    public function getList ($user_id) 
    {
        $consumables = $this->where('user_id', $user_id)->get()->getResultArray();


        foreach($consumables as &$consumable){
            if((bool) $consumable['is_restorable']){
                $consumable = $this->checkRestoration($consumable);
            }
        }
        return $consumables;
    }
    public function checkRestoration ($consumable)
    {
        $restoration_minutes = 60;
        $max_quantity = 5;
        $consumed_at = Time::parse($consumable['consumed_at'], Time::now()->getTimezone());
        $date_difference = $consumed_at->difference(Time::now())->getSeconds();
        $result = [
            'restorated_quantity' => 0,
            'consumed_at' => ''
        ];
        if($restoration_minutes <= $date_difference/60){
            $restorated_quantity = floor($date_difference/60 / $restoration_minutes);
            $result['consumed_at'] = $consumed_at->addMinutes($restorated_quantity * $restoration_minutes);
            $next_consumed_at = $result['consumed_at']->addMinutes($restoration_minutes);

            $new_difference = Time::now()->difference($next_consumed_at);
            
            $minutes = floor(($new_difference->seconds / 60) % 60);
            $seconds = $new_difference->seconds % 60;
            print_r($minutes.':'.$seconds);
            die;
        }
        return $consumable;        
    }
    public function createItem ($user_id)
    {
        $this->transBegin();
        
        $data = [
            'user_id'       => $user_id,
            'character_id'  => getenv('user_consumables.character_id'),
            'classroom_id'  => NULL,
            'course_id'     => NULL
            
        ];
        $user_consumables_id = $this->insert($data, true);

        $this->transCommit();

        return $user_consumables_id;        
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