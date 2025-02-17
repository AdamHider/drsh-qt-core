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
        'source_id',
        'source_code',
        'value', 
        'operand',
        'expires_at'
    ];

    public function getList()
    {
        $result = $this->where('settings_modifiers.user_id = '.session()->get('user_id'))
        ->get()->getResultArray();
        return $result;
    }
}