<?php
namespace App\Models;
use CodeIgniter\Model;
class UserEmailVerificationModel extends Model
{
    protected $table      = 'user_email_verification';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'email', 'code'
    ];

}