<?php

namespace App\Models;

use CodeIgniter\Model;

class UserLevelModel extends UserModel
{
    use PermissionTrait;
    protected $table      = 'user_levels';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = true;

    public function getItem($user_id)
    {
        $result = $this->join("user_resources", "user_resources.user_id = ".$user_id." AND user_resources.code = 'experience'")
        ->select("user_resources.quantity as experience, user_levels.id as level, user_levels.points_from, user_levels.points_to, user_levels.config as level_config")
        ->where('user_levels.points_from <= user_resources.quantity AND user_levels.points_to > user_resources.quantity')
        ->get()->getRowArray();
        if($result){
            $result['level_config'] = json_decode($result['level_config'], JSON_UNESCAPED_UNICODE);
            $result['percentage'] =  ceil($result['experience'] * 100 / $result['points_to']);
        }
        return $result;
    }
    

}