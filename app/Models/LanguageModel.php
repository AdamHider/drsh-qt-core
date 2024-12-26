<?php

namespace App\Models;

use CodeIgniter\Model;

class LanguageModel extends Model
{
    protected $table      = 'languages';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';

    protected $allowedFields = [
        'code', 'title', 'image'
    ];
    
    protected $useTimestamps = false;

    public function getItem ($language_id) 
    {
        return $this->where('id', $language_id)->get()->getRowArray();
    }
    public function getList () 
    {
        return $this->get()->getResultArray();
    }
}