<?php
namespace App\Models;
use CodeIgniter\Model;
class ResourceUsermapModel extends Model
{
    protected $table      = 'resources_usermap';
    protected $primaryKey = 'item_id';
    protected $allowedFields = [
        'item_id', 
        'user_id', 
        'quantity', 
        'consumed_at'
    ];
}