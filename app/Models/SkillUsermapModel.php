<?php
namespace App\Models;
use CodeIgniter\Model;
class SkillUsermapModel extends Model
{
    protected $table      = 'skills_usermap';
    protected $primaryKey = 'item_id';
    protected $allowedFields = [
        'item_id', 
        'user_id',
        'status'
    ];
}