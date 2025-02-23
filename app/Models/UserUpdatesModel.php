<?php
namespace App\Models;
use CodeIgniter\Model;
class UserUpdatesModel extends Model
{
    protected $table      = 'user_updates';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'code','data'
    ];
}