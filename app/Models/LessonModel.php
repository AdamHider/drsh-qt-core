<?php

namespace App\Models;

use CodeIgniter\Model;
use stdClass;

class LessonModel extends Model
{
    use PermissionTrait;
    protected $table      = 'lessons';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'course_id', 'course_section_id', 'language_id', 'title', 'description', 'type', 'pages', 'cost_config', 'reward_config', 'image', 'published', 'parent_id', 'unblock_after', 'is_private'
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getItem ($lesson_id) 
    {
        $this->useSharedOf('courses', 'course_id');
        if(!$this->hasPermission($lesson_id, 'r')){
            return 'forbidden';
        }

        $CourseSectionModel = model('CourseSectionModel');
        $ExerciseModel = model('ExerciseModel');
        $ResourceModel = model('ResourceModel');
        $LessonUnblockUsermapModel = model('LessonUnblockUsermapModel');

        $lesson = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.*, exercises.id as exercise_id')
        ->where('lessons.id', $lesson_id)->where('lessons.published', 1)->get()->getRowArray();

        if(!$lesson){
            return false;
        }
        if ($lesson) {
            $lesson['course_section']   = $CourseSectionModel->getItem($lesson['course_section_id']);
            $lesson['image']            = base_url($lesson['image']);
            $lesson['exercise']         = $ExerciseModel->getItem($lesson['exercise_id']);
            $lesson['progress']         = $this->getProgress($lesson['exercise']['data'] ?? []);
            $lesson['is_blocked']       = $LessonUnblockUsermapModel->checkBlocked($lesson['id'], $lesson['unblock_after']);
            
            if($lesson['parent_id']){
                $lesson['master_lesson'] =  $this->select('title, description')->where('lessons.id', $lesson['parent_id'])->get()->getRowArray();
            }
            $lesson['cost']             = $ResourceModel->proccessItemCost(session()->get('user_id'), json_decode($lesson['cost_config'], true));
            $lesson['reward']           = $ResourceModel->proccessItemGroupReward(json_decode($lesson['reward_config'], true));
        }
        unset($lesson['cost_config']);
        unset($lesson['reward_config']);
        unset($lesson['pages']);
        return $lesson;
    }
    public function getList ($data) 
    {
        $this->useSharedOf('courses', 'course_id');

        $CourseSectionModel = model('CourseSectionModel');
        $ExerciseModel = model('ExerciseModel');
        $ResourceModel = model('ResourceModel');
        $LessonUnblockUsermapModel = model('LessonUnblockUsermapModel');
        
        $lessons = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.*, exercises.id as exercise_id')
        ->where('lessons.course_id', session()->get('user_data')['settings']['courseId']['value'])
        ->where('lessons.parent_id IS NULL')->where('lessons.published', 1)
        ->whereHasPermission('r')
        ->limit($data['limit'], $data['offset'])->orderBy('lessons.order ASC')->get()->getResultArray();

        foreach($lessons as $key => &$lesson){
            if(isset($data['offset'])){
                $lesson['order'] = $key + $data['offset'];
            }
            $lesson['course_section'] = $CourseSectionModel->getItem($lesson['course_section_id']);
            $lesson['satellites'] = $this->getSatellites($lesson['id'], 'lite');
            $lesson['image'] = base_url($lesson['image']);
            $lesson['exercise'] = $ExerciseModel->getItem($lesson['exercise_id']);
            $lesson['progress'] = $this->getOverallProgress($lesson['id']);
            $lesson['is_blocked'] = $LessonUnblockUsermapModel->checkBlocked($lesson['id'], $lesson['unblock_after'], 'group');
            $lesson['is_explored'] = isset($lesson['exercise']['id']);
            
            $lesson['cost'] = $ResourceModel->proccessItemCost(session()->get('user_id'), json_decode($lesson['cost_config'], true));
            $lesson['reward'] = $ResourceModel->proccessItemGroupReward(json_decode($lesson['reward_config'], true));
            unset($lesson['cost_config']);
            unset($lesson['reward_config']);
            unset($lesson['pages']);
        }
        return $lessons;
    }
    public function getSatellites ($lesson_id, $mode) 
    {
        $this->useSharedOf('courses', 'course_id');

        $ExerciseModel = model('ExerciseModel');
        $ResourceModel = model('ResourceModel');
        $LessonUnblockUsermapModel = model('LessonUnblockUsermapModel');

        $result = [];
        $result['preview_total'] = getenv('lesson.satellites.preview_total');

        $satellites = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.*, exercises.id as exercise_id')
        ->where('lessons.parent_id', $lesson_id)->where('lessons.published', 1)
        ->whereHasPermission('r')->orderBy('id')->get()->getResultArray();
        
        foreach($satellites as $key => &$satellite){
            $satellite['image'] = base_url($satellite['image']);
            if($mode == 'full'){
                $satellite['exercise']      = $ExerciseModel->getItem($satellite['exercise_id']);
                $satellite['progress']      = $this->getProgress($satellite['exercise']['data'] ?? []);
                $satellite['is_blocked']    = $LessonUnblockUsermapModel->checkBlocked($satellite['id'], $satellite['unblock_after']);
                $satellite['cost']          = $ResourceModel->proccessItemCost(session()->get('user_id'), json_decode($satellite['cost_config'], true));
                $satellite['reward']        = $ResourceModel->proccessItemGroupReward(json_decode($satellite['reward_config'], true));
            }
            unset($satellite['cost_config']);
            unset($satellite['reward_config']);
            unset($satellite['pages']);
        }
        $result['preview_list'] = $this->composeSatellitesPreview($satellites, $result['preview_total']);
        if ($mode == 'full') {
            $result['list'] = $satellites;
        }
        return $result;
    }

    /**
    * Method to compose sattelites for preview of Lesson Item
    *
    * @return array Array or false
    **/
    private function composeSatellitesPreview($satellites, $total)
    {
        $result = [];
        foreach($satellites as $index => $satellite){
            if($index == $total){
                break;
            }
            $satellite['size'] = rand(15, 20);
            $satellite['distance'] = 2*$index;
            $satellite['duration'] = rand(15, 20);
            $satellite['delay'] = rand(1, 5);
            $result[] = $satellite;
        }
        return $result;
    }
    private function composeSatellitesProgress($satellites)
    {
        if(empty($satellites)) return 0;
        $totalPoints = array_reduce($satellites, function($points, $item){
            return $points + $item['progress'] ?? 0;
        });
        
        return ceil($totalPoints / (count($satellites)*100) * 100);
    }
    
    public function checkExplored ($lesson_id) 
    {
        if (!$lesson_id) {
            return false;
        }
        $exercise  = $this->join('user_resources_expenses', 'user_resources_expenses.item_id = lessons.id AND user_resources_expenses.code = "lesson_explored"')
        ->join('user_resources', 'user_resources.user_id = '.session()->get('user_id'))
        ->where('lessons.id', $lesson_id)->get()->getResult();
        return !empty($exercise);
    }
    public function composePages($pages, $lesson_type)
    {
        if($lesson_type == 'lexis'){
            return $this->parseLexis($pages);
        } else {
            return $pages;
        }
    }
    public function getProgress($exercise = [])
    {
        if(isset($exercise['totals']) && $exercise['totals']['total'] > 0){
            return ceil($exercise['totals']['points'] / $exercise['totals']['total'] * 100);
        }
        return 0;
    }
    public function getOverallProgress($lesson_id)
    {
        $exercises  = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('COALESCE(exercises.exercise_pending, exercises.exercise_submitted) as data')
        ->where('(lessons.id = '.$lesson_id.' OR lessons.parent_id = '.$lesson_id.') AND lessons.published = 1')->get()->getResultArray();
        $overal_points = 0;
        if(!empty($exercises)){
            foreach($exercises as $exercise){
                $exercise = json_decode($exercise['data'], true);
                if(!empty($exercise)){
                    $overal_points += ceil($exercise['totals']['points'] / $exercise['totals']['total'] * 100);
                }
            }
            return floor($overal_points / count($exercises));
        }
        return 0;
    }
}