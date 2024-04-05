<?php
namespace App\Models;
use CodeIgniter\Model;
class SettingsUsermapModel extends Model
{
    protected $table      = 'settings_usermap';
    protected $primaryKey = 'item_id';
    protected $allowedFields = [
        'item_id', 
        'user_id', 
        'value'
    ];
}