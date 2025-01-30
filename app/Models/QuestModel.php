<?php

namespace App\Models;

use CodeIgniter\BaseBuilder;
use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class QuestModel extends Model
{
    use PermissionTrait;
    protected $table      = 'quests';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'code', 
        'value', 
        'image', 
        'date_start', 
        'date_end', 
        'reward', 
        'owner_id', 
        'is_disabled', 
        'is_private'
    ];
    protected $validationRules    = [
        'code'     => [
            'label' =>'code',
            'rules' =>'required',
            'errors'=>[
                'required'=>'required'
            ]
        ],
        'value'     => [
            'label' =>'value',
            'rules' =>'required|greater_than[0]',
            'errors'=>[
                'required'=>'required',
                'greater_than'=>'greater_than'
            ]
        ]
    ];
    
    public function getItem ($data) 
    {
        $ResourceModel = model('ResourceModel');
        if(!$this->hasPermission($data['quest_id'], 'r')){
            return 'forbidden';
        }
        
        $quest = $this->where('quests.id', $data['quest_id'])->get()->getRowArray();
        
        if(empty($quest)){
            return 'not_found';
        }
        $quest['title'] = lang('App.quest.title.'.$quest['code'], [$quest['value']]);
        $quest['image'] = base_url('image/' . $quest['image']);
        $quest['value'] = (int) $quest['value'];
        $quest['is_private'] = (bool) $quest['is_private'];
        $quest['is_disabled'] = (bool) $quest['is_disabled'];
        $quest['is_owner'] = $quest['owner_id'] == $data['user_id'];

        $reward_config = json_decode($quest['reward_config'], true);
        $quest['reward'] = $ResourceModel->proccessItemReward($reward_config);
        
        $quest['progress'] = $this->getItemProgress($quest, $data['user_id']);
        $quest['is_completed'] = $this->checkItemCompleted($quest);
        $quest['goal'] = [
            'title' => lang('App.quest.goal.'.$quest['code'].'.title'),
            'description' => lang('App.quest.goal.'.$quest['code'].'.description'),
            'value' => lang('App.quest.goal.'.$quest['code'].'.value', [$quest['value']])
        ];
        if($quest['date_start']){
            $quest['date_start_humanized'] = Time::parse($quest['date_start'], Time::now()->getTimezone())->humanize();
        }
        if($quest['date_end']){
            $date_end = Time::parse($quest['date_end'], Time::now()->getTimezone());
            $quest['time_left'] = Time::now()->difference($date_end)->getDays();
            $quest['date_end_humanized'] = $date_end->humanize();
            $quest['time_left_humanized'] = Time::now()->difference($date_end)->humanize();
        }
        return $quest;
    }
    public function getList ($data) 
    {
        $DescriptionModel = model('DescriptionModel');
        $QuestGroupsModel = model('QuestGroupsModel');
        $ResourceModel = model('ResourceModel');

        if(isset($data['active_only'])){
            $this->join('quests_usermap','quests_usermap.item_id = quests.id AND quests_usermap.user_id = '.session()->get('user_id'))
            ->select('quests.*, quests_usermap.status')
            ->where('IF(quests.date_end, quests.date_end > NOW(), 1)')->where('quests_usermap.status IN ("created", "active")');
        }

        $quests = $this->orderBy('group_id')->get()->getResultArray();

        if(empty($quests)){
            return 'not_found';
        }

        foreach($quests as &$quest){
            $quest = array_merge($quest, $DescriptionModel->getItem('quest', $quest['id']));
            $quest['group'] = $QuestGroupsModel->getItem($quest['group_id']);
            $quest['image'] = base_url('image/' . $quest['image']);
            
            $reward_config = json_decode($quest['reward_config'], true);
            $quest['reward'] = $ResourceModel->proccessItemReward($reward_config);

            $quest['pages'] = json_decode($quest['pages'], true);
            if(!empty($quest['pages'])){
                foreach($quest['pages'] as &$page){
                    $page['image'] = base_url('image/' . $page['image']);
                }
            }

            $quest['progress'] = $this->getItemProgress($quest, $data['user_id']);
            $quest['is_completed'] = $this->checkItemCompleted($quest);
            
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

    private function getActiveIDs($user_id)
    {
        $QuestGroupsModel = model('QuestGroupsModel');
        $groups = $QuestGroupsModel->getActiveList();
        $groupsIds = array_map(function($group) { return $group['id'];}, $groups);
        $activeQuestIds = $this->select("SUBSTRING_INDEX(GROUP_CONCAT(id ORDER BY level SEPARATOR ','), ',', 1) AS id")
        ->join('quests_usermap', 'quests.id = quests_usermap.item_id AND quests_usermap.user_id = '.$user_id, 'left')
        ->where('quests_usermap.item_id IS NULL AND group_id IN ('.implode(',', $groupsIds).')')
        ->whereHasPermission('r')->groupBy('quests.group_id')->get()->getResultArray();
        if(!empty($activeQuestIds)){
            return array_map(function($quest) { return $quest['id'];}, $activeQuestIds);
        }
        return [0];
    }
    public function getTotal ($data) 
    {
        $quests = $this->get()->getResultArray();
        
        if(empty($quests)){
            return 0;
        }
        return count($quests);
    }
    public function getItemProgress($data, $user_id)
    {
        $ExerciseModel = model('ExerciseModel');
        $SkillUsermapModel = model('SkillUsermapModel');
        $current_total = 0;
        if($data['code'] == 'total_points' || $data['code'] == 'total_points_first'){
           // $current_total = $ExerciseModel->getTotal($data, 'sum');
        }
        if($data['code'] == 'lesson' || $data['code'] == 'total_lessons'){
            //$current_total = $ExerciseModel->getTotal($data, 'count');
        }
        if($data['code'] == 'skill'){
            $current_total = !empty($SkillUsermapModel->where('item_id', $data['value'])->where('user_id', $user_id)->get()->getResultArray());
            $data['value'] = 1;
        }
        if($data['code'] == 'skills_total'){
            $total_skills = $SkillUsermapModel->select('COUNT(*) as total')->where('item_id', $data['value'])->where('user_id', $user_id)->get()->getResultArray();
            if(!empty($total_skills['total'])){
                $current_total = $total_skills['total'];
            }

        }
        $result = [
            'value' => $current_total,
            'total' => $data['value'],
            'percentage' => 0
        ];
        if($result['total'] != 0){
            $result['percentage'] = ceil($result['value'] * 100 / $result['total']);
            if($result['percentage'] > 100){
                $result['percentage'] = 100;
            }
        }
        $result['percentage_text'] = lang('App.quest.progress.'.$data['code'].'.percentage_text', [$result['percentage'], $result['value'], $result['total']]);
        return $result;
    }
    private function checkItemCompleted($quest)
    {
        return $quest['progress']['percentage'] == 100;
    }
    private function checkItemOutdated($quest)
    {
        $is_outdated = false;
        if($quest['date_end']){
            $is_outdated = strtotime($quest['date_end']) <= strtotime('now');
        } 
        return $is_outdated;
    }

    public function claimReward($data)
    {
        $ResourceModel = model('ResourceModel');
        $QuestsUsermapModel = model('QuestsUsermapModel');

        if(!$this->hasPermission($data['quest_id'], 'r')){
            return 'forbidden';
        }
        
        $quest = $this->join('quests_usermap', 'quests.id = quests_usermap.item_id AND quests_usermap.user_id = '.$data['user_id'], 'left')
        ->where('quests_usermap.item_id IS NULL AND id = '.$data['quest_id'])->get()->getRowArray();

        if(empty($quest)){
            return 'not_found';
        }

        $reward_config = json_decode($quest['reward_config'], true);

        $quest['progress'] = $this->getItemProgress($quest, $data['user_id']);
        $quest['is_completed'] = $this->checkItemCompleted($quest);
        $quest['is_outdated'] = $this->checkItemOutdated($quest);
        
        if($this->checkItemCompleted($quest) && !$this->checkItemOutdated($quest)){
            if($ResourceModel->enrollUserList($data['user_id'], $reward_config)){
                $QuestsUsermapModel->insert(['item_id' => $quest['id'], 'user_id' => $data['user_id']], true);
                return $ResourceModel->proccessItemReward($reward_config);
            };
            return 'forbidden';
        } else {
            return 'forbidden';
        }
            
    }

    public function createItem ($data)
    {
        $ClassroomModel = model('ClassroomModel');
        
        $this->validationRules = [];
        $data = [
            'code' => NULL, 
            'value' => NULL, 
            'image' => NULL, 
            'date_start' => NULL,
            'date_end' => NULL, 
            'reward' => '{}', 
            'owner_id' => session()->get('user_id'), 
            'is_disabled' => false, 
            'is_private' => false
        ];
        $this->transBegin();
        $quest_id = $this->insert($data, true);

        $this->transCommit();

        return $quest_id;        
    }
    public function updateItem ($data)
    {
        if(!$this->hasPermission($data['id'], 'w')){
            return 'forbidden';
        }
        if(!empty($data['reward'])){
            $data['reward'] = json_encode($data['reward']);
        }
        $this->transBegin();
        
        $this->update(['id'=>$data['id']], $data);

        $this->transCommit();

        return true;        
    }





    public function getAvailableLessons ($data) 
    {
        $LessonModel = model('LessonModel');

        $lessons = $LessonModel->like('title', $data['title'])->limit(10)->orderBy('id')->get()->getResultArray();
        $result = [];
        foreach($lessons as $key => $lesson){
            $result[] = [
                'id'    => $lesson['id'],
                'title' => $lesson['title'],
                'image' => base_url('image/' . $lesson['image'])
            ];
        }
        return $result;
    }
    public function calculateReward ($data) 
    {
        $result = [
            'credits' => 0,
            'experience' => 0
        ];
        if($data['code'] === 'total_points' || $data['code'] === 'total_points_first'){
            $result['credits'] = ceil($data['value']*0.05); 
            $result['experience'] = ceil($data['value']*0.1); 
        } else 
        if($data['code'] === 'total_lessons'){
            $result['credits'] = ceil($data['value']*20); 
            $result['experience'] = ceil($data['value']*50); 
        } else 
        if($data['code'] === 'lesson'){
            $result['credits'] = ceil($data['value']*40); 
            $result['experience'] = ceil($data['value']*100); 
        }
        return $result;
    }
    public function getAvailableCodes ($data) 
    {
        $codes_conf = [
            [
                'label' => 'Total points',
                'value' => 'total_points'
            ],
            [
                'label' => 'Total points first',
                'value' => 'total_points_first'
            ],
            [
                'label' => 'Total lessons',
                'value' => 'total_lessons'
            ],
            [
                'label' => 'Lesson',
                'value' => 'lesson'
            ]
        ];
        $codes = $this->where('IF(quests.date_end, quests.date_end > NOW(), 1)')
        ->groupBy('code')->select('code')->get()->getResultArray('code');
        $result = [];
        foreach($codes_conf as $code){
            if(array_search($code['value'], array_column($codes, 'code')) === false){
                $result[] = $code;
            }
        }
        return $result;
    }
    
    
    
}