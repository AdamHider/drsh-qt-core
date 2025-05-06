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
        $result = [];
        if(isset($data['user_id'])){
            $this->join('settings_usermap', 'settings_usermap.item_id = settings.id AND settings_usermap.user_id = '.$data['user_id'], 'left');
        }
        $settings = $this->get()->getResultArray();
        foreach($settings as $setting){
            if(empty($setting['item_id'])){
                $this->createUserItem(['item_id' => $setting['id'], 'user_id' => $data['user_id'], 'value' => $setting['default_value']]);
            }
            $result[$setting['code']] = [
                'value'         => $setting['value'],
                'type'          => $setting['type']
            ];
        }
        return $result;
    }
    public function processList ($data)
    {
        $DescriptionModel = model('DescriptionModel');
        foreach($data as &$item){
            $setting = $this->join('settings_usermap', 'settings_usermap.item_id = settings.id')->where('settings.code', $item['code'])->get()->getRowArray();
            $item = array_merge($item, $DescriptionModel->getItem('setting', $setting['id']));
            switch ($item['operand']){ 
                case 'multiply' :
                    if($item['value'] > 1){
                        $item['description'] = sprintf(lang('App.modifier.description.multiply.increase'), $item['description'], ($item['value']-1)*100);
                    } else {
                        $item['description'] = sprintf(lang('App.modifier.description.multiply.decrease'), $item['description'], (1-$item['value'])*100);
                    }
                    break;
                case 'add' :
                    $item['description'] = sprintf(lang('App.modifier.description.add'), $item['description'], $item['value']);
                    break;
                case 'substract' :
                    $item['description'] = sprintf(lang('App.modifier.description.substract'), $item['description'], $item['value']);
                    break;
                default;
            }
        }
        return $data;        
    }
    private function getItemValue ($user_id, $setting_id, $value) 
    {
        $SettingsModifiersModel = model('SettingsModifiersModel');
        $modifiers = $SettingsModifiersModel->where('settings_modifiers.setting_id = '.$setting_id.' AND settings_modifiers.user_id = '.$user_id)
        ->orderBy('FIELD(operand, "multiply", "divide", "add", "substract")')->get()->getResultArray();
        if(empty($modifiers)){
            return $value;
        }
        $result = (int) $value;
        foreach($modifiers as $modifier){
            switch ($modifier['operand']){ 
                case 'multiply' :
                    $result *= $modifier['value'];
                    break;
                case 'add' :
                    $result += $modifier['value'];
                    break;
                case 'substract' :
                    $result -= $modifier['value'];
                    break;
                default;
            }
        }
        return round($result, 2);
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
    public function createUserItem ($data)
    {
        $SettingsUsermapModel = model('SettingsUsermapModel');
        $SettingsUsermapModel->insert($data, true);
    }
    public function updateUserItem($user_id, $data, $force = false)
    {
        $SettingsUsermapModel = model('SettingsUsermapModel');
        $setting = $this->where('code', $data['code'])->get()->getRowArray();
        
        if(empty($setting)){
            return false;
        }
        if($setting['user_editable'] || $force){
            return $SettingsUsermapModel->set($data)->where(['item_id' => $setting['id'], 'user_id' => $user_id])->update();
        }
        return false;
    }
    
    

}