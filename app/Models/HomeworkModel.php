<?php

namespace App\Models;

use CodeIgniter\Model;

class HomeworkModel extends Model
{
    protected $table      = 'challenges';
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

    public function getList ($data) 
    {
        $DescriptionModel = model('DescriptionModel');
        
        if($data['user_id']){
            $this->join('achievements_to_users', 'achievements_to_users.achievement_id = achievements.id')
            ->where('achievements_to_users.user_id', $data['user_id']);
        }
        $achievements = $this->limit($data['limit'], $data['offset'])->orderBy('code, value')->get()->getResult();
        foreach($achievements as &$achievement){
            $achievement->description = $DescriptionModel->getItem('achievement', $achievement->id);
            $achievement->image = base_url('image/' . $achievement->image);
            $achievement->progress = $this->calculateProgress($achievement);
        }
        return $achievements;
    }
}