<?php

namespace App\Models;

use CodeIgniter\Model;

class LanguageModel extends Model
{
    protected $table      = 'languages';
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

    public function getItem ($language_id) 
    {
        $language = $this->where('id', $language_id)->get()->getRowArray();

        return $language;
    }
    public function getList () 
    {
        $languages = $this->get()->getResultArray();

        return $languages;
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