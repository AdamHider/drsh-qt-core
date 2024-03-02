<?php

namespace App\Models;

use CodeIgniter\Model;

class UserSettingsModel extends Model
{
    protected $table      = 'user_settings';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'user_id',
        'code', 
        'value'
    ];
    
    protected $useTimestamps = false;

    public function getList ($data) 
    {
        $result = [];
        $settings = $this->where('user_id', ['user_id' => $data['user_id']])->get()->getResultArray();
        foreach($settings as $parameter){
            $result[$parameter['code']] = $parameter['value'];
        }
        return $result;
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
        $this->where(['user_id' => $data['user_id'], 'code' => $data['code']]);
        $result = $this->update();

        $this->transCommit();

        return $result;        
    }
    public function createList ($user_id, $settings)
    {
        foreach($settings as $code => $value){
            $this->createItem([
                'user_id' => $user_id, 
                'code' => $code, 
                'value' => $value
            ]);
        }
        return;        
    }

}