<?php
namespace App\Models;
use CodeIgniter\Model;
class ChestUsermapModel extends Model
{
    protected $table      = 'chests_usermap';
    protected $primaryKey = 'item_id';
    protected $allowedFields = [
        'item_id', 
        'user_id',
        'created_at',
        'updated_at'
    ];
}