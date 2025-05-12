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
                'value'         => $this->getItemValue($data['user_id'], $setting['id'], $setting['value']),
                'type'          => $setting['type']
            ];
        }
        return $result;
    }
    
    private function getItemValue ($user_id, $setting_id, $value) 
    {
        $SettingsModifiersModel = model('SettingsModifiersModel');
        $modifiers = $SettingsModifiersModel->where('settings_modifiers.setting_id = '.$setting_id.' AND settings_modifiers.user_id = '.$user_id)
        ->select('settings_modifiers.*, IF(operand = "multiply", ROUND(1 + SUM(-1 + value), 2), SUM(value)) AS multiplier')
        ->orderBy('FIELD(operand, "multiply", "divide", "add", "substract")')->groupBy('setting_id, operand')->get()->getResultArray();
        if(empty($modifiers)){
            return $value;
        }
        $result = (int) $value;
        foreach($modifiers as $modifier){
            switch ($modifier['operand']){ 
                case 'multiply' :
                    $result *= $modifier['multiplier'];
                    break;
                case 'add' :
                    $result += $modifier['multiplier'];
                    break;
                case 'substract' :
                    $result -= $modifier['multiplier'];
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