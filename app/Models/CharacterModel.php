<?php

namespace App\Models;

use CodeIgniter\Model;

class CharacterModel extends Model
{
    protected $table      = 'characters';
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

    public function getItem ($character_id) 
    {
        $DescriptionModel = model('DescriptionModel');
        $character = $this->where('characters.id', $character_id)->get()->getRowArray();
        if ($character) {
            $character['avatar'] = base_url('image/' . $character['avatar']);
            $character['image'] = base_url('image/' . $character['image']);
            $character['description'] = $DescriptionModel->getItem('character', $character['id']);
        }
        return $character;
    }
        
    public function itemCreate ($image)
    {
        $this->transBegin();
        $data = [
            'image' => $image
        ];
        $character_id = $this->insert($data, true);
        $this->transCommit();

        return $character_id;        
    }



}