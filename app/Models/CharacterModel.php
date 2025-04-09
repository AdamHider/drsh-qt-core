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
            $character['image'] = base_url('image/index.php' . $character['image']);
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
            $character['image'] = base_url('image/index.php' . $character['character_image']);
        }
        return $characters;
    }
    public function linkItem ($item_id, $user_id) 
    {
        $SettingsModel = model('SettingsModel');
        $character = $this->where('characters.id', $item_id)->get()->getRowArray();
        $modifiersConfig = json_decode($character['modifiers_config'], true);
        $SettingsModel->updateUserItem($user_id, ['code' => 'characterId', 'value' => $character['id']], true);
        if(!empty($modifiersConfig)){
            return $SettingsModel->createModifierList($user_id, $modifiersConfig);
        }
        return false;
    }
}