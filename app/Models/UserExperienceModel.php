<?php

namespace App\Models;

use CodeIgniter\Model;

class UserExperienceModel extends UserModel
{
    use PermissionTrait;
    protected $table      = 'user_experience';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType     = 'array';
    protected $useSoftDeletes = true;

    public function getItem($user_id)
    {
        $result = $this->where('user_id', $user_id)
        ->join("user_levels", "user_levels.points_from <= user_experience.value AND user_levels.points_to > user_experience.value")
        ->select("user_experience.value as experience, user_levels.id as level, user_levels.points_from, user_levels.points_to, user_levels.config as level_config")
        ->get()->getRowArray();
        $result['level_config'] = json_decode($result['level_config'], JSON_UNESCAPED_UNICODE);
        $result['percentage'] =  ceil($result['experience'] * 100 / $result['points_to']);
        return $result;
    }
    

}