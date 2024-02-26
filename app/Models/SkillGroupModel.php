<?php
namespace App\Models;
use CodeIgniter\Model;
class SkillGroupModel extends Model
{
    protected $table      = 'skills_groups';
    protected $primaryKey = 'item_id';
    protected $allowedFields = [
        'item_id', 
        'user_id'
    ];
}