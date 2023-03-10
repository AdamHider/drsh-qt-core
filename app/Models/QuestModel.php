<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class QuestModel extends Model
{
    use PermissionTrait;
    protected $table      = 'quests';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'image'
    ];
    
    public function getItem ($quest_id) 
    {
        $this->useSharedOf('classrooms', 'classroom_id');
        if(!$this->hasPermission($quest_id, 'r')){
            return 'forbidden';
        }

        
        $quest = $this->join('lessons', 'lessons.id = quests.lesson_id', 'left')
        ->select('quests.*, lessons.title as lesson_title, lessons.image as lesson_image')
        ->where('quests.id', $quest_id)->get()->getRowArray();
        
        if(empty($quest)){
            return 'not_found';
        }
        $quest['title'] = lang('App.quest.title.'.$quest['code'], [$quest['value']]);

        $quest['image'] = base_url('image/' . $quest['image']);
        $quest['progress'] = $this->getProgress($quest);
        $quest['is_finished'] = $this->checkFinished($quest);
        if($quest['lesson_id']){
            $quest['title'] = lang('App.quest.title.'.$quest['code'], [$quest['lesson_title']]);
            $quest['image'] = base_url('image/' . $quest['lesson_image']);
        }
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
        
        $this->join('lessons', 'lessons.id = quests.lesson_id', 'left')
        ->select('quests.*, lessons.title as lesson_title, lessons.image as lesson_image');

        if(isset($data['classroom_id'])){
            $this->useSharedOf('classrooms', 'classroom_id');
            $this->where('quests.classroom_id', $data['classroom_id']);
        }
        if(isset($data['user_id'])){
            $this->join('classrooms', 'classrooms.id = quests.classroom_id')
            ->join('classrooms_usermap', 'classrooms_usermap.item_id = classrooms.id')
            ->where('classrooms_usermap.user_id', $data['user_id']);
        }

        $this->whereHasPermission('r')->groupBy('quests.id');
        
        if(isset($data['limit'])){
            $this->limit($data['limit'], $data['offset']);
        }

        $quests = $this->orderBy('date_end DESC')->get()->getResultArray();

        if(empty($quests)){
            return 'not_found';
        }
        
        foreach($quests as &$quest){
            $quest['title'] = lang('App.quest.title.'.$quest['code'], [$quest['value']]);
            $quest['image'] = base_url('image/' . $quest['image']);
            $quest['progress'] = $this->getProgress($quest);
            $quest['is_finished'] = $this->checkFinished($quest);
            if($quest['lesson_id']){
                $quest['title'] = lang('App.quest.title.'.$quest['code'], [$quest['lesson_title']]);
                $quest['image'] = base_url('image/' . $quest['lesson_image']);
            }
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
        return $quests;
    }
    public function getTotal ($data) 
    {
        if(isset($data['classroom_id'])){
            $this->useSharedOf('classrooms', 'classroom_id');
        }
        
        $quests = $this->where('quests.classroom_id', $data['classroom_id'])->whereHasPermission('r')
        ->groupBy('quests.id')->orderBy('date_end')->get()->getResultArray();

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
            $current_total = $ExerciseModel->getTotal($data, 'sum');
        }
        if($data['code'] == 'lesson' || $data['code'] == 'total_lessons'){
            $current_total = $ExerciseModel->getTotal($data, 'count');
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
    private function checkFinished($quest)
    {
        if($quest['code'] == 'total_points' || $quest['code'] == 'total_lessons'){
            return strtotime($quest['date_end']) <= strtotime('now');
        }
        if($quest['code'] == 'total_points_first'){
            return $quest['progress']['value'] >= $quest['value'];
        }
    }
    
}