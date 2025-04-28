<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Events\Events;

class UserInvitationModel extends Model
{
    use PermissionTrait;
    protected $table      = 'user_invitations';
    protected $primaryKey = 'user_id';
    protected $allowedFields = [
        'user_id',
        'hash', 
        'status', 
        'count'
    ];
    

    public function getItem()
    {
        $invitation = $this->where('user_id', session()->get('user_id'))->get()->getRowArray();
        if(empty($invitation)){
            $this->createItem();
            $invitation = $this->where('user_id', session()->get('user_id'))->get()->getRowArray();
        }
        return $invitation;
    }
    public function createItem()
    {
        return $this->insert([
            'user_id' => session()->get('user_id'), 
            'hash' => md5(session()->get('user_id').rand()), 
            'status' => 'created', 
            'count' => 0
        ]);
    }

    public function rewardItem($user_id)
    {
        $UserModel = model('UserModel');
        $ResourceModel = model('ResourceModel');
        $QuestModel = model('QuestModel');
        
        $invited_user = $UserModel->where('id', $user_id)->get()->getRowArray();
        if(!empty($invited_user['invited_by'])){
            $QuestModel->addActiveProgress('invitation', 0, 1, $invited_user['invited_by']);
            if($ResourceModel->enrollUserList($invited_user['invited_by'], ['isonit' => 1])){
                $this->set('count', 'count+1', false)->where('user_id', $invited_user['invited_by'])->update();
                return $invited_user;
            }
        }
        return false;
    }
}