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
    
    public function getItem ($quest_id) 
    {
        if(!$this->hasPermission($quest_id, 'r')){
            return 'forbidden';
        }
        
        $quest = $this->select('quests.*, lessons.title as lesson_title, lessons.image as lesson_image')
        ->where('quests.id', $quest_id)->get()->getRowArray();
        
        if(empty($quest)){
            return 'not_found';
        }
        $quest['title'] = lang('App.quest.title.'.$quest['code'], [$quest['value']]);
        $quest['image'] = base_url('image/' . $quest['image']);
        $quest['value'] = (int) $quest['value'];
        $quest['is_private'] = (bool) $quest['is_private'];
        $quest['is_disabled'] = (bool) $quest['is_disabled'];
        $quest['is_owner'] = $quest['owner_id'] == session()->get('user_id');
        $quest['reward'] = json_decode($quest['reward'], true);
        $quest['progress'] = $this->getProgress($quest);
        $quest['is_completed'] = $this->checkCompleted($quest);
        $quest['is_outdated'] = $this->checkOutdated($quest);
        $quest['is_rewarded'] = $this->checkRewarded($quest);
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
        if(isset($data['active_only'])){
            /*
            $this->join('user_resources_expenses', 'user_resources_expenses.item_id = quests.id AND user_resources_expenses.item_code = "quest" AND user_resources_expenses.user_id = '.session()->get('user_id'), 'left')
            ->where('user_resources_expenses.id', NULL);*/
            $this->where('IF(quests.date_end, quests.date_end > NOW(), 1)');
        }

        $this->whereHasPermission('r')->groupBy('quests.id');
        
        if(isset($data['limit'])){
            $this->limit($data['limit'], $data['offset']);
        }

        $quests = $this->orderBy('COALESCE(date_end, NOW()) DESC')->get()->getResultArray();

        if(empty($quests)){
            return 'not_found';
        }
        $result = [];
        foreach(array_group_by($quests, ['group_id']) as $group_id => &$quest_group){

            $groupObject = $DescriptionModel->getItem('quest_group', $group_id);

            foreach($quest_group as &$quest){
                $quest['title'] = lang('App.quest.title.'.$quest['code'], [$quest['value']]);
                $quest['image'] = base_url('image/' . $quest['image']);
                $quest['reward'] = json_decode($quest['reward'], true);
                $quest['progress'] = $this->getProgress($quest);
                $quest['is_completed'] = $this->checkCompleted($quest);
                $quest['is_outdated'] = $this->checkOutdated($quest);
                $quest['is_rewarded'] = $this->checkRewarded($quest);
                $quest['goal'] = [
                    'title' => lang('App.quest.goal.'.$quest['code'].'.title'),
                    'description' => lang('App.quest.goal.'.$quest['code'].'.description'),
                    'value' => lang('App.quest.goal.'.$quest['code'].'.value', [$quest['value']])
                ];
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
            $groupObject['list'] = $quest_group;
            $result[] = $groupObject;
        }
        return $result;
    }
    public function getTotal ($data) 
    {
        $quests = $this->get()->getResultArray();
        
        if(empty($quests)){
            return 0;
        }
        return count($quests);
    }
    public function getProgress($data)
    {
        $ExerciseModel = model('ExerciseModel');
        $current_total = 0;
        if($data['code'] == 'total_points' || $data['code'] == 'total_points_first'){
           // $current_total = $ExerciseModel->getTotal($data, 'sum');
        }
        if($data['code'] == 'lesson' || $data['code'] == 'total_lessons'){
            //$current_total = $ExerciseModel->getTotal($data, 'count');
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
    private function checkCompleted($quest)
    {
        return $quest['progress']['value'] >= $quest['value'];
    }
    private function checkOutdated($quest)
    {
        $is_outdated = false;
        if($quest['date_end']){
            $is_outdated = strtotime($quest['date_end']) <= strtotime('now');
        } 
        return $is_outdated;
    }
    private function checkRewarded($quest)
    {
        $is_rewarded = false;
        if(!empty($quest['reward'])){
            $UserResourcesExpensesModel = model('UserResourcesExpensesModel');
            foreach($quest['reward'] as $resource_title => $resource_quantity){
                $is_rewarded = false; //!empty($UserResourcesExpensesModel->getItem($resource_title, 'quest', $quest['id'], session()->get('user_id')));
            }
        }
        return $is_rewarded;
    }
    public function claimReward($quest_id)
    {
        if(!$this->hasPermission($quest_id, 'r')){
            return 'forbidden';
        }
        
        $quest = $this->where('id', $quest_id)->get()->getRowArray();

        if(empty($quest)){
            return 'not_found';
        }
        $quest['reward'] = json_decode($quest['reward'], true);
        $quest['progress'] = $this->getProgress($quest);
        $quest['is_completed'] = $this->checkCompleted($quest);
        $quest['is_outdated'] = $this->checkOutdated($quest);
        $quest['is_rewarded'] = $this->checkRewarded($quest);
        if($this->checkCompleted($quest) && !$this->checkOutdated($quest) && !$this->checkRewarded($quest)){
            $UserResourcesExpensesModel = model('UserResourcesExpensesModel');
            foreach($quest['reward'] as $resource_title => $resource_quantity){
                $data = [
                    'user_id' => session()->get('user_id'),
                    'code' => $resource_title,
                    'item_code' => 'quest',
                    'item_id' => $quest['id'],
                    'quantity' => $resource_quantity
                ];
                $UserResourcesExpensesModel->createItem($data);
            }
            return $quest;
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