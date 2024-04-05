<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingsModel extends Model
{
    protected $table      = 'settings';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'code', 
        'default_value'
    ];
    
    protected $useTimestamps = false;

    public function getList ($data) 
    {
        $DescriptionModel = model('DescriptionModel');
        $result = [];
        if(isset($data['user_id'])){
            $this->join('settings_usermap', 'settings_usermap.item_id = settings.id AND settings_usermap.user_id = '.$data['user_id']);
        }
        $settings = $this->get()->getResultArray();
        foreach($settings as $setting){
            $setting = array_merge($setting, $DescriptionModel->getItem('setting', $setting['id']));
            $result[$setting['code']] = [
                'value'         => $setting['value'],
                'title'         => $setting['title'],
                'description'   => $setting['description']
            ];
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
        
        $result = $this->set($data)->where(['user_id' => $data['user_id'], 'code' => $data['code']])->update();
       
        $this->transCommit();

        return $result;        
    }
    public function createList ($user_id, $settings)
    {
        $result = true;
        foreach($settings as $code => $value){
            $result = $this->createItem([
                'user_id' => $user_id, 
                'code' => $code, 
                'value' => $value
            ]);
        }
        return $result;        
    }
    public function updateList ($user_id, $settings)
    {
        $result = true;
        foreach($settings as $code => $value){
            $result = $this->updateItem([
                'user_id' => $user_id, 
                'code' => $code, 
                'value' => $value
            ]);
        }
        return $result;      
    }
    public function createUserList ($user_id)
    {
        $settings = $this->get()->getResultArray();
        foreach($settings as $setting){
            $this->createUserItem([
                'item_id' => $setting['id'], 
                'user_id' => $user_id, 
                'value' => $setting['default_value']
            ]);
        }
        return true;        
    }
    public function createUserItem($data)
    {
        $SettingsUsermapModel = model('SettingsUsermapModel');
        $SettingsUsermapModel->insert($data, true);
    }
    public function updateUserItem($user_id, $data)
    {
        $SettingsUsermapModel = model('SettingsUsermapModel');
        $setting = $this->where('code', $data['code'])->get()->getRowArray();
        $SettingsUsermapModel->set($data)->where(['item_id' => $setting['id'], 'user_id' => $user_id])->update();
    }
    


    public function createModifiersList ($user_id, $data)
    {
        foreach($data as $item){
            $setting = $this->join('settings_usermap', 'settings_usermap.item_id = settings.id AND settings_usermap.user_id = '.$user_id)->get()->getRowArray();
            $this->createModifierItem([
                'setting_id' => $setting['id'], 
                'user_id' => $user_id, 
                'value' => $item['value'], 
                'operand' => $item['operand'], 
                'expires_at' => $item['expires_at'] ?? null
            ]);
        }
        return true;        
    }
    public function createModifierItem($data)
    {
        $SettingsModifiersModel = model('SettingsModifiersModel');
        $SettingsModifiersModel->insert($data, true);
    }

    

}