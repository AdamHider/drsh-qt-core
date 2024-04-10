<?php

namespace App\Models;

use CodeIgniter\Model;

class UserGroupUsermapModel extends Model
{
    protected $table      = 'user_groups_usermap';
    protected $primaryKey = 'item_id';
    protected $allowedFields = [
        'item_id', 
        'user_id'
    ];
}