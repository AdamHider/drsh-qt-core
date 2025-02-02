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
        'item_id',
        'code',
        'language_id',
        'title',
        'description',
        'data'
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getItem ($code, $item_id) 
    {
        $description = $this->select('title, description')->where('code', $code)->where('item_id', $item_id)->where('language_id', 1)->get()->getRowArray();
        if (empty($description)) {
            $description = [
                'title' => '',
                'description' => ''
            ];
        }
        return $description;
    }
    public function getList ($code, $item_id) 
    {
        $descriptions = $this->join('languages', 'languages.id = descriptions.language_id', 'left')
        ->select('languages.code as language_code, languages.title as language_title, descriptions.language_id, descriptions.title, descriptions.description')
        ->where('descriptions.code', $code)->where('descriptions.item_id', $item_id)->get()->getResultArray();
        
        $LanguageModel = model('LanguageModel');
        $languages = $LanguageModel->getList();
        $result = [];
        foreach($languages as $language){
            $description = [
                'title' => '',
                'description' => ''
            ];
            $index = array_search($language['code'], array_column($descriptions, 'language_code'));
            if($index !== false){
                $description['title'] = $descriptions[$index]['title'];
                $description['description'] = $descriptions[$index]['description'];
            }
            $result[$language['code']] = $description;
        }
        return $result;
    }

}