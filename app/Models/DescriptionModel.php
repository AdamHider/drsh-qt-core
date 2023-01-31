<?php

namespace App\Models;

use CodeIgniter\Model;
use stdClass;

class DescriptionModel extends Model
{
    protected $table      = 'descriptions';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'image'
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getItem ($code, $item_id) 
    {
        $description = $this->select('title, description')->where('code', $code)->where('item_id', $item_id)->where('language_id', 1)->get()->getRow();
        if (empty($description)) {
            $description = new stdClass;
            $description->title = '';
            $description->description = '';
        }
        return $description;
    }

}