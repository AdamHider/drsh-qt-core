<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Events\Events;

class UserInvitationModel extends Model
{
    use PermissionTrait;
    protected $table      = 'user_invitations';
    protected $primaryKey = 'user_id';

    public function createItem()
    {
        
    }

    public function rewardItem($user_id)
    {
        $UserModel = model('UserModel');
        $ResourceModel = model('ResourceModel');
        $invited_user = $UserModel->where('id', $user_id)->get()->getRowArray();
        if(!empty($invited_user['invited_by'])){
            if($ResourceModel->enrollUserList($invited_user['invited_by'], ['rubidium' => 1])){
                return $invited_user;
            }
        }
        return false;
    }
}