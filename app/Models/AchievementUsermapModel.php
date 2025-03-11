<?php
namespace App\Models;
use CodeIgniter\Model;
class AchievementUsermapModel extends Model
{
    protected $table      = 'achievements_usermap';
    protected $primaryKey = 'item_id';
    protected $allowedFields = [
        'item_id', 
        'user_id'
    ];
}