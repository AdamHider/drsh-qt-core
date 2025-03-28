<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;
use CodeIgniter\Events\Events;

class QuestModel extends Model
{
    use PermissionTrait;
    protected $table      = 'quests';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'code', 
        'value', 
        'pages', 
        'date_start', 
        'date_end', 
        'reward', 
        'owner_id', 
        'is_disabled', 
        'is_private'
    ];

    private $codes = [
        'lesson',
        'skill',
        'resource'
    ];
    public function getList ($data) 
    {
        $DescriptionModel = model('DescriptionModel');
        $QuestGroupModel = model('QuestGroupModel');
        $ResourceModel = model('ResourceModel');

        if($data['active_only']){
            $this->join('quests_usermap','quests_usermap.item_id = quests.id AND quests_usermap.user_id = '.session()->get('user_id'))
            ->select('quests.*, quests_usermap.status, COALESCE(quests_usermap.progress, 0) AS progress')
            ->where('IF(quests.date_end, quests.date_end > NOW(), 1)')->where('quests_usermap.status IN ("created", "active")');
        }

        $quests = $this->orderBy('group_id')->get()->getResultArray();

        if(empty($quests)){
            return 'not_found';
        }

        foreach($quests as &$quest){
            $quest = array_merge($quest, $DescriptionModel->getItem('quest', $quest['id']));
            $quest['group'] = $QuestGroupModel->getItem($quest['group_id']);
            
            $reward_config = json_decode($quest['reward_config'], true);
            $quest['reward'] = $ResourceModel->proccessItemReward($reward_config);
            
            $quest['pages'] = $this->composeItemPages($quest['pages']);

            $quest['is_completed'] = $quest['progress'] >= $quest['value'];
            
            if($quest['date_start']){
                $quest['date_start_humanized'] = Time::parse($quest['date_start'], Time::now()->getTimezone())->humanize();
            }
            if($quest['date_end']){
                $time = Time::parse($quest['date_end'], Time::now()->getTimezone());
                $quest['time_left'] = Time::now()->difference($time)->getDays();
                $quest['date_end_humanized'] = $time->humanize();
                $quest['time_left_humanized'] = Time::now()->difference($time)->humanize();
            }
        }

        return $quests;
    }

    private function composeItemPages($pages)
    {
        $result = json_decode($pages, true);
        if(!empty($result)){
            foreach($result as &$page){
                $page['image'] = base_url($page['image'] ?? '');
            }
        }
        return $result;
    }
    public function addActiveProgress($code, $target_id, $progress)
    {   
        $QuestsUsermapModel = model('QuestsUsermapModel');

        $quests = $this->join('quests_usermap','quests_usermap.item_id = quests.id AND quests_usermap.user_id = '.session()->get('user_id'))
        ->where('IF(quests.date_end, quests.date_end > NOW(), 1)')->where('quests_usermap.status = "active"')
        ->where('quests.code = "'.$code.'"')->where('find_in_set("'.$target_id.'", quests.target) <> 0')->get()->getResultArray();
        foreach($quests as $quest){
            $QuestsUsermapModel->set('progress', 'progress+'.$progress, false)->where(['item_id' => $quest['id'], 'user_id' => session()->get('user_id')])->update();
        }
    }
    private function checkItemOutdated($quest)
    {
        $is_outdated = false;
        if($quest['date_end']){
            $is_outdated = strtotime($quest['date_end']) <= strtotime('now');
        } 
        return $is_outdated;
    }
    public function getCompletedList($code)
    {
        $DescriptionModel = model('DescriptionModel');
        $QuestGroupModel = model('QuestGroupModel');

        $quests = $this->join('quests_usermap', 'quests.id = quests_usermap.item_id AND quests_usermap.user_id = '.session()->get('user_id'), 'left')
        ->where('quests_usermap.status = "active" AND quests_usermap.progress >= quests.value AND quests.code = "'.$code.'"')->get()->getResultArray();
        
        foreach($quests as &$quest){
            $quest = array_merge($quest, $DescriptionModel->getItem('quest', $quest['id']));
            $quest['group'] = $QuestGroupModel->getItem($quest['group_id']);
        }
        return $quests;
    }
    public function claimReward($quest_id)
    {
        $ResourceModel = model('ResourceModel');

        if(!$this->hasPermission($quest_id, 'r')){
            return 'forbidden';
        }
        $quest = $this->join('quests_usermap', 'quests.id = quests_usermap.item_id AND quests_usermap.user_id = '.session()->get('user_id'), 'left')
        ->where('quests_usermap.status = "active" AND quests_usermap.progress >= quests.value AND id = '.$quest_id)
        ->get()->getRowArray();

        if(empty($quest)) return 'not_found';

        $reward_config = json_decode($quest['reward_config'], true);

        $quest['is_completed'] = $quest['progress'] >= $quest['value'];
        $quest['is_outdated'] = $this->checkItemOutdated($quest);
        
        if($quest['is_completed'] && !$quest['is_outdated']){
            if($ResourceModel->enrollUserList(session()->get('user_id'), $reward_config)){
                $finished = $this->updateUserItem(['item_id' => $quest['id'], 'user_id' => session()->get('user_id'), 'status' => 'finished']);
                if($finished){
                    $this->linkItemToUser($quest['id'], session()->get('user_id'), 'next');
                    Events::trigger('invitationQuestClaimed', session()->get('user_id'));
                    return $ResourceModel->proccessItemReward($reward_config);
                }
            };
            return 'forbidden';
        } else {
            return 'forbidden';
        }
    }
    public function linkItemToUser ($quest_id, $user_id, $mode = 'exact') 
    {
        $field = 'id';
        if($mode == 'next'){
            $field = 'unblock_after';
        }    
        $quest = $this->where($field, $quest_id)->get()->getRowArray();
        if(!empty($quest)){
            $data = [
                'item_id' => $quest['id'],
                'user_id' => $user_id,
                'status' => 'created',
                'progress' => 0
            ];
            $this->createUserItem($data);
        }
    }

    public function updateUserItem($data)
    {
        $QuestsUsermapModel = model('QuestsUsermapModel');
        return $QuestsUsermapModel->set('status', $data['status'], null)->where(['item_id' => $data['item_id'], 'user_id' => $data['user_id']])->update();
    }
    public function createUserItem($data)
    {
        $QuestsUsermapModel = model('QuestsUsermapModel');
        return $QuestsUsermapModel->insert($data, true);
    }
    
    
}