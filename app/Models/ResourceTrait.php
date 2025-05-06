<?php

namespace App\Models;
trait ResourceTrait
{
    private $incalculatable = ['energy'];
    private $XP_MOD = 0.3;
    private $CUR_XP = 0;
    private $USR_MOD = 0;
    public function recalculateResources ($data) 
    {
        $this->CUR_XP = $this->getCurrentExperience();
        foreach($data as $code => &$value){
            if(array_search($code, $this->incalculatable) !== false){
                continue;
            }
            $value = $this->recalculateValue($value);
        }
        return $data;
    }
    public function recalculateResourcesGroup ($data) 
    {
        $this->CUR_XP = $this->getCurrentExperience();
        foreach($data as &$group){
            foreach($group as $code => &$value){
                if(array_search($code, $this->incalculatable) !== false){
                    continue;
                }
                $value = $this->recalculateValue($code, $value);
            }
        }
        return $data;
    }
    
    
    private function recalculateValue ($code, $value)
    {
        //ROUND(BASE * (1 + XP_MOD * LOG(CUR_XP)) * USR_MOD)
        //$result = $value * (1 + $this->XP_MOD * log10($this->CUR_XP)) * $this->USR_MOD;
        $SettingsModel = model('SettingsModel');
        $modifiers = $SettingsModel->join('settings_modifiers', 'settings_modifiers.setting_id = settings.id AND settings_modifiers.user_id = '.session()->get('user_id'))
        ->where('settings.code', $code.'GainModifier')->orderBy('FIELD(settings_modifiers.operand, "multiply", "add", "substract"), `value` DESC')->get()->getResultArray();
        
        $result = $value;
        foreach($modifiers as $modifier){
            if($modifier['operand'] == 'multiply'){
                $result *= $modifier['value'];
            } else 
            if($modifier['operand'] == 'add'){
                $result += $modifier['value'];
            } else 
            if($modifier['operand'] == 'substract'){
                $result -= $modifier['value'];
            } 
        }

        //ROUND
        /*
        if($result < 10){
            $result = round($result);
        } elseif($result < 100) {
            $result = round($result / 5) * 5;
        } else {
            $result = round($result / 10) * 10;
        }*/
        //$result = ceil( $result / 5 ) * 5;
        return round($result);
    }

    private function getCurrentExperience ()
    {   
        if(!session()->get('user_id')){
            return 1;
        }
        $ResourceModel = model('ResourceModel');
        $resource = $ResourceModel->join('resources_usermap', 'resources_usermap.item_id = resources.id AND resources_usermap.user_id = '.session()->get('user_id'))
        ->select('resources_usermap.quantity')->where('resources.code', 'experience')->get()->getRowArray();
        if(!empty($resource)){
            return $resource['quantity']+1;
        }
        return 1;
    }

}
