<?php
namespace App\Models;
use CodeIgniter\Model;
class QuestsUsermapModel extends Model
{
    protected $table      = 'quests_usermap';
    protected $primaryKey = 'item_id';
    protected $allowedFields = [
        'item_id', 
        'user_id',
        'status',
        'reward_config',
        'progress'
    ];

    private $statuses = [
        'created', 'active', 'completed'
    ];
}