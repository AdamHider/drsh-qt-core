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
            $character = array_merge($character, $DescriptionModel->getItem('character', $character['id']));
            $character['character_image'] = base_url('image/' . $character['character_image']);
            $character['planet_image'] = base_url('image/' . $character['planet_image']);
            $character['rocket_image'] = base_url('image/' . $character['rocket_image']);
            $character['background_image'] = base_url('image/' . $character['background_image']);
        }
        return $character;
    }
    public function getList($data)
    {
        $DescriptionModel = model('DescriptionModel');
        if(isset($data['limit'])){
            $this->limit($data['limit'], $data['offset']);
        }
        $characters = $this->get()->getResultArray();
        foreach($characters as &$character){
            $character = array_merge($character, $DescriptionModel->getItem('character', $character['id']));
            $character['character_image'] = base_url('image/' . $character['character_image']);
            $character['planet_image'] = base_url('image/' . $character['planet_image']);
            $character['rocket_image'] = base_url('image/' . $character['rocket_image']);
            $character['background_image'] = base_url('image/' . $character['background_image']);
        }
        return $characters;
    }
    public function linkItem ($data) 
    {
        $SettingsModel = model('SettingsModel');
        
        $character = $this->where('characters.id', $data['character_id'])->get()->getRowArray();
        $modifiersConfig = json_decode($character['modifiers_config'], true);
        $SettingsModel->updateUserItem($data['user_id'], ['code' => 'characterId', 'value' => $character['id']]);
        return $SettingsModel->createModifierList($data['user_id'], $modifiersConfig);
    }
}