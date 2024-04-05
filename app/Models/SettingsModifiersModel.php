<?php
namespace App\Models;
use CodeIgniter\Model;
class SettingsModifiersModel extends Model
{
    protected $table      = 'settings_modifiers';
    protected $primaryKey = 'item_id';
    protected $allowedFields = [
        'setting_id', 
        'user_id', 
        'value', 
        'operand',
        'expires_at'
    ];
}