<?php
namespace App\Models;
use CodeIgniter\Model;
class SkillGroupModel extends Model
{
    protected $table      = 'skill_groups';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'item_id', 
        'user_id'
    ];
}