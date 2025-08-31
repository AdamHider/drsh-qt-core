<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;
use CodeIgniter\Events\Events;

class QuestModel extends Model
{
    use ResourceTrait;
    protected $table      = 'quests';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'code', 
        'value', 
        'date_start', 
        'date_end', 
        'owner_id', 
        'published', 
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
        $ResourceModel = model('ResourceModel');

        if($data['active_only']){
            $this->join('quests_usermap','quests_usermap.item_id = quests.id AND quests_usermap.user_id = '.session()->get('user_id'))
            ->select('quests.*,  quests_usermap.status, COALESCE(quests_usermap.progress, 0) AS progress')
            ->where('IF(quests.date_end, quests.date_end > NOW(), 1)')->where('quests_usermap.status IN ("created", "active")');
        }

        $quests = $this->orderBy('group_id')->get()->getResultArray();

        if(empty($quests)){
            return 'not_found';
        }

        foreach($quests as &$quest){
            $quest = array_merge($quest, $DescriptionModel->getItem('quest', $quest['id']));
            $reward_config = json_decode($quest['reward_config'], true);
            $quest['group']     = $QuestGroupModel->getItem($quest['group_id']);
            $quest['reward']    = $ResourceModel->proccessItemReward($reward_config);
            $quest['target']    = $this->composeItemTarget($quest['code'], $quest['target']);
            $quest['data']      = $this->composeItemPages($quest['data']);

            $quest['is_completed'] = $quest['progress'] >= $quest['value'];
            
            if($quest['date_start']){
                $quest['date_start_humanized'] = Time::parse($quest['date_start'], Time::now()->getTimezone())->humanize();
            }
            if($quest['date_end']){
                $time = Time::parse($quest['date_end'], Time::now()->getTimezone());
                $quest['time_left'] = Time::now()->difference($time)->getSeconds();
                $quest['date_end_humanized'] = $time->humanize();
                $quest['time_left_humanized'] = Time::now()->difference($time)->humanize();
            }
            unset($quest['reward_config']);
        }
        return $quests;
    }

    private function composeItemPages($pages)
    {
        $result = json_decode($pages, true);
        if(!empty($result)){
            foreach($result as &$section){
                if(!empty($section['dialogue'])){
                    foreach($section['dialogue'] as &$page){
                        if(isset($page['image'])){
                            $page['image'] = base_url('image/index.php'.$page['image']);
                        }
                    }
                }
            }
        }
        return $result;
    }

    private function composeItemTarget($code, $target_id)
    {
        $result = [];
        $LessonModel = model('LessonModel');
        $SkillModel = model('SkillModel');
        $DescriptionModel = model('DescriptionModel');
        
        if($code == 'lesson'){
            $result = $LessonModel->where('id', $target_id)->select('id, parent_id, title, description')->get()->getRowArray();
            $result['code'] = 'lesson';
        } else if($code == 'total_lessons' || $code == 'resource' || $code == 'resource_invitation'){
            $result['code'] = 'lesson';
        } else if($code == 'skill'){
            $result = $SkillModel->select('skills.id')->get()->getRowArray();
            $result = array_merge($result, $DescriptionModel->getItem('skill', $result['id']));
            $result['code'] = 'skill';
        } else if($code == 'skills_total'){
            $result['code'] = 'skill';
        }
        return $result;
    }
    public function addActiveProgress($code, $target_id, $progress, $user_id = 0)
    {   
        if($user_id == 0){
            $user_id = session()->get('user_id');
        }
        $QuestsUsermapModel = model('QuestsUsermapModel');
        if($code == 'lesson' || $code == 'resource' || $code == 'skill'|| $code == 'resource_invitation' ){
            $quests = $this->join('quests_usermap','quests_usermap.item_id = quests.id AND quests_usermap.user_id = '.$user_id)
            ->where('IF(quests.date_end, quests.date_end > NOW(), 1)')->where('quests_usermap.status = "active"')
            ->where('quests.code = "'.$code.'"')->where('find_in_set("'.$target_id.'", quests.target) <> 0')->get()->getResultArray();
        } else {
            $quests = $this->join('quests_usermap','quests_usermap.item_id = quests.id AND quests_usermap.user_id = '.$user_id)
            ->where('IF(quests.date_end, quests.date_end > NOW(), 1)')->where('quests_usermap.status = "active"')
            ->where('quests.code = "'.$code.'"')->get()->getResultArray();
        }
        foreach($quests as $quest){
            $QuestsUsermapModel->set('progress', 'progress+'.$progress, false)->where(['item_id' => $quest['id'], 'user_id' => $user_id])->update();
        }
    }
    private function checkItemCompleted($quest)
    {
        $ExerciseModel = model('ExerciseModel');
        $SkillUsermapModel = model('SkillUsermapModel');
        if($quest['code'] == 'lesson'){
            return $ExerciseModel->where('lesson_id', $quest['target'])->where('user_id', session()->get('user_id'))->where('finished_at IS NOT NULL')->countAllResults() >= 1;
        } else
        if ($quest['code'] == 'skill'){
            return $SkillUsermapModel->where('item_id', $quest['target'])->where('user_id', session()->get('user_id'))->countAllResults() >= 1;
        } 
        return false;
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
        ->where('quests_usermap.status = "active" AND quests_usermap.progress >= quests.value')->where('find_in_set(quests.code, "'.$code.'") <> 0')->get()->getResultArray();
        
        foreach($quests as &$quest){
            $quest = array_merge($quest, $DescriptionModel->getItem('quest', $quest['id']));
            $quest['group'] = $QuestGroupModel->getItem($quest['group_id']);
        }
        return $quests;
    }
    public function claimReward($quest_id)
    {
        $ResourceModel = model('ResourceModel');
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
                    $this->linkItem($quest['id'], session()->get('user_id'), 'next');
                    Events::trigger('invitationQuestClaimed', session()->get('user_id'));
                    return $ResourceModel->proccessItemReward($reward_config);
                }
            };
            return 'forbidden';
        } else {
            return 'forbidden';
        }
    }
    public function linkItem($quest_id, $user_id, $mode = 'exact') 
    {
        $QuestGroupModel = model('QuestGroupModel');
        $QuestsUsermapModel = model('QuestsUsermapModel');
        $field = 'id';
        if($mode == 'next'){
            $field = 'unblock_after';
        }
        $status = 'created';  
        $progress = 0;  
        $quests = $this->where($field, $quest_id)->get()->getResultArray();
        if(!empty($quests)){
            foreach($quests as $quest){
                if($this->checkItemCompleted($quest)){
                    $quest['group'] = $QuestGroupModel->getItem($quest['group_id']);
                    if($quest['group']['is_primary']){
                        $status = 'active';
                    } else {
                        $status = 'finished';
                    }
                    $progress = 1;
                }
                $data = [
                    'item_id' => $quest['id'],
                    'user_id' => $user_id,
                    'status' => $status,
                    'progress' => $progress
                ];
                $QuestsUsermapModel->insert($data, true);
            }
        }
    }

    public function updateUserItem($data)
    {
        $QuestsUsermapModel = model('QuestsUsermapModel');
        return $QuestsUsermapModel->set('status', $data['status'], null)->where(['item_id' => $data['item_id'], 'user_id' => session()->get('user_id')])->update();
    }
    
    public function linkDailyList()
    {
        $QuestsUsermapModel = model('QuestsUsermapModel');
        $UserModel = model('UserModel');

        $initials = parse_ini_file(ROOTPATH.'/defaults.ini')['initials'];
        $daily_quest_group_id = $initials['daily_quest_group_id'];

        $daily_quests = $this->select('GROUP_CONCAT(id) as ids')->where('group_id', $daily_quest_group_id)->get()->getRowArray();
        if(!empty($daily_quests['ids'])){
            $QuestsUsermapModel->whereIn('item_id', explode(',', $daily_quests['ids']))->delete();
        }
        
        $active_quest = $this->where('group_id', $daily_quest_group_id)->orderBy('id', 'RANDOM')->limit(1)->get()->getRowArray();
        
        $active_quest['date_start'] = date('Y-m-d H:i:s');
        $nextDayTimestamp = strtotime('+23 hours', strtotime($active_quest['date_start']));
        $active_quest['date_end'] = date('Y-m-d H:i:s', $nextDayTimestamp);


        $this->set(['date_start' => $active_quest['date_start'], 'date_end' => $active_quest['date_end']])->where('id', $active_quest['id'])->update();

        $users = $UserModel->select('users.*')->join('settings_modifiers', 'users.id = settings_modifiers.user_id')->join('settings', 'settings.id = settings_modifiers.setting_id')
        ->where('settings_modifiers.value >= 1 AND settings.code = "lessonAccessDailyLevel"')->groupBy('id')->get()->getResultArray();

        foreach($users as $user){
            $data = [
                'item_id' => $active_quest['id'],
                'user_id' => $user['id'],
                'status' => 'created',
                'progress' => 0
            ];
            $QuestsUsermapModel->insert($data, true);
        }
    }   
}