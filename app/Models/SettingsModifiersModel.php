<?php
namespace App\Models;
use CodeIgniter\Model;
class SettingsModifiersModel extends Model
{
    protected $table      = 'settings_modifiers';
    protected $primaryKey = 'item_id';
    protected $allowedFields = [
        'setting_id', 
        'user_id', 
        'source_id',
        'source_code',
        'value', 
        'operand',
        'expires_at'
    ];

    public function getList()
    {
        $DescriptionModel = model('DescriptionModel');
        $modifiers = $this->join('settings', 'settings.id = settings_modifiers.setting_id')
        ->where('settings_modifiers.user_id = '.session()->get('user_id'))->get()->getResultArray();
        foreach($modifiers as &$modifier){
            $modifier['is_positive'] = false;
            $modifier['setting'] = $DescriptionModel->getItem('setting', $modifier['setting_id']);
            $value = $modifier['value'];
            if($modifier['type'] == 'percentage' && $modifier['operand'] == 'multiply'){
                $value = round(($modifier['value'] - 1) * 100);
                $modifier['value'] = $value.'%';
            }
            if($modifier['operand'] == 'substract'){
                $value = '-'.$modifier['value'];
                $modifier['value'] = $value;
            } 
            if($value > 0) {
                $modifier['value'] = '+'.$modifier['value'];
                $modifier['is_positive'] = true;
            }
            if($modifier['is_decreasing']){
                $modifier['is_positive'] = !$modifier['is_positive'];
            }
            $modifier['title'] = lang('App.modifier.title.'.$modifier['source_code']);
        }
        return $modifiers;
    }

    public function createList ($user_id, $data, $source_code)
    {
        foreach($data as $item){
            $setting = $this->join('settings_usermap', 'settings_usermap.item_id = settings.id AND settings_usermap.user_id = '.$user_id, 'left')->where('settings.code', $item['code'])->get()->getRowArray();
            $this->createItem([
                'setting_id' => $setting['id'], 
                'user_id' => session()->get('user_id'), 
                'value' => $item['value'], 
                'source_code' => $source_code,
                'operand' => $item['operand'], 
                'expires_at' => $item['expires_at'] ?? null
            ]);
        }
        return true;        
    }
    public function createItem ($data)
    {
        $SettingsModifiersModel = model('SettingsModifiersModel');
        $SettingsModifiersModel->insert($data, true);
    }
}