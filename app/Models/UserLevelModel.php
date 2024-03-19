<?php

namespace App\Models;

use CodeIgniter\Model;

class UserLevelModel extends UserModel
{
    use PermissionTrait;
    protected $table      = 'user_levels';
    protected $primaryKey = 'id';
    public function getItem($user_id)
    {
        $result = $this->join("resources_usermap", "resources_usermap.user_id = ".$user_id)
        ->join("resources", "resources_usermap.item_id = resources.id AND resources.code = 'experience'")
        ->select("resources_usermap.quantity as experience, user_levels.id as level, user_levels.points_from, user_levels.points_to, user_levels.config as level_config")
        ->where('user_levels.points_from <= resources_usermap.quantity AND user_levels.points_to > resources_usermap.quantity')
        ->get()->getRowArray();
        if($result){
            $result['level_config'] = json_decode($result['level_config'], JSON_UNESCAPED_UNICODE);
            $result['percentage'] =  ceil($result['experience'] * 100 / $result['points_to']);
        }
        return $result;
    }
    

}