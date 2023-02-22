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
        'character_id', 
        'classroom_id', 
        'course_id'
    ];
    
    protected $useTimestamps = false;

    public function getItem ($user_id) 
    {
        $LanguageModel = model('LanguageModel');
        
        $settings = $this->where('user_id', $user_id)->get()->getRowArray();

        $settings['language'] = [
            'active' => $LanguageModel->getItem($settings['language_id']),
            'list' => $LanguageModel->getList()
        ];
        
        return $settings;
    }
        
    public function createItem ($user_id)
    {
        $this->transBegin();
        
        $data = [
            'user_id'       => $user_id,
            'character_id'  => getenv('user_settings.character_id'),
            'classroom_id'  => NULL,
            'course_id'     => NULL
            
        ];
        $user_settings_id = $this->insert($data, true);

        $this->transCommit();

        return $user_settings_id;        
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