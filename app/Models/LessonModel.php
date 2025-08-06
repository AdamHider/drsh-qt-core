<?php

namespace App\Models;

use CodeIgniter\Model;

class LessonModel extends Model
{
    use PermissionTrait;
    protected $table      = 'lessons';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';

    protected $allowedFields = [
        'course_id', 'course_section_id', 'language_id', 'title', 'description', 'type', 'pages', 'cost_config', 'reward_config', 'unblock_config', 'image', 'published', 'parent_id', 'is_private'
    ];
    

    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getItem ($lesson_id) 
    {
        $this->useSharedOf('courses', 'course_id');

        $CourseSectionModel = model('CourseSectionModel');
        $ExerciseModel = model('ExerciseModel');
        $ResourceModel = model('ResourceModel');
        $LessonUnblockUsermapModel = model('LessonUnblockUsermapModel');

        $lesson = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.id, lessons.course_id, lessons.course_section_id, lessons.title, lessons.description, lessons.image, lessons.unblock_config, lessons.parent_id, lessons.type, lessons.cost_config, lessons.reward_config, exercises.id as exercise_id')
        ->where('lessons.id', $lesson_id)->where('lessons.published', 1)->get()->getRowArray();

        if(empty($lesson)) return false;
        
        if ($lesson) {
            $lesson['course_section']   = $CourseSectionModel->getItem($lesson['course_section_id']);
            $lesson['image']            = base_url('image/index.php'.$lesson['image']);
            $lesson['exercise']         = $ExerciseModel->getItem($lesson['exercise_id']);
            $lesson['next_lessons']     = $this->getNextItems($lesson['id']);
            $lesson['progress']         = $this->getItemProgress($lesson['exercise']['data'] ?? []);
            $lesson['is_blocked']       = $LessonUnblockUsermapModel->checkBlocked($lesson['id'], json_decode($lesson['unblock_config'], true));
            $lesson['unblock']          = $this->getItemUnblock(json_decode($lesson['unblock_config'], true));
            $lesson['is_quest']         = $this->getItemQuest($lesson['id']);    
            if(!$lesson['is_blocked']){
                $reward_gradation       = $this->composeItemReward(json_decode($lesson['reward_config'], true));
                $lesson['reward']       = $ResourceModel->proccessItemGroupReward($reward_gradation);
                $lesson['cost']         = $ResourceModel->proccessItemCost(json_decode($lesson['cost_config'], true));
            }
        }
        unset($lesson['unblock_config']);
        unset($lesson['cost_config']);
        unset($lesson['reward_config']);
        unset($lesson['pages']);
        return $lesson;
    }
    public function getList () 
    {
        $this->useSharedOf('courses', 'course_id');

        $CourseSectionModel = model('CourseSectionModel');
        $ExerciseModel = model('ExerciseModel');
        $ResourceModel = model('ResourceModel');
        $LessonUnblockUsermapModel = model('LessonUnblockUsermapModel');
        
        $lessons = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.id, lessons.course_id, lessons.course_section_id, lessons.title, lessons.description, lessons.image, lessons.unblock_config, lessons.parent_id, lessons.type, lessons.cost_config, lessons.reward_config, exercises.id as exercise_id')
        ->where('lessons.course_id', session()->get('user_data')['settings']['courseId']['value'])
        ->where('lessons.parent_id IS NULL')->where('lessons.published', 1)->orderBy('lessons.order ASC')->get()->getResultArray();

        foreach($lessons as $key => &$lesson){
            $lesson['course_section']   = $CourseSectionModel->getItem($lesson['course_section_id']);
            $lesson['satellites']       = $this->getSatellites($lesson['id'], 'lite');
            $lesson['image']            = base_url('image/index.php'.$lesson['image']);
            $lesson['exercise']         = $ExerciseModel->getItem($lesson['exercise_id']);
            $lesson['progress']         = $this->getOverallProgress($lesson['id']);
            $lesson['is_blocked']       = $LessonUnblockUsermapModel->checkBlocked($lesson['id'], json_decode($lesson['unblock_config'], true), 'group');
            $lesson['unblock']          = $this->getItemUnblock(json_decode($lesson['unblock_config'], true));
            $lesson['is_quest']         = $this->getItemQuest($lesson['id']);
            if(!$lesson['is_blocked']){
                $reward_gradation       = $this->composeItemReward(json_decode($lesson['reward_config'], true));
                $lesson['reward']       = $ResourceModel->proccessItemGroupReward($reward_gradation);
                $lesson['cost']         = $ResourceModel->proccessItemCost(json_decode($lesson['cost_config'], true));
            }
            unset($lesson['unblock_config']);
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

        $satellites = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.id, lessons.title, lessons.description, lessons.image, lessons.unblock_config, lessons.parent_id, lessons.type, lessons.cost_config, lessons.reward_config, exercises.id as exercise_id')
        ->where('lessons.parent_id', $lesson_id)->where('lessons.published', 1)->orderBy('lessons.order ASC')->get()->getResultArray();
        
        foreach($satellites as $key => &$satellite){
            $satellite['image'] = base_url('image/index.php'.$satellite['image']);
            if($mode == 'full'){
                $satellite['exercise']      = $ExerciseModel->getItem($satellite['exercise_id']);
                $satellite['progress']      = $this->getItemProgress($satellite['exercise']['data'] ?? []);
                $satellite['is_blocked']    = $LessonUnblockUsermapModel->checkBlocked($satellite['id'], json_decode($satellite['unblock_config'], true));
                $satellite['unblock']       = $this->getItemUnblock(json_decode($satellite['unblock_config'], true));
                $satellite['is_quest']         = $this->getItemQuest($satellite['id']);
                if(!$satellite['is_blocked']){
                    $reward_gradation       = $this->composeItemReward(json_decode($satellite['reward_config'], true));
                    $satellite['reward']       = $ResourceModel->proccessItemGroupReward($reward_gradation);
                    $satellite['cost']      = $ResourceModel->proccessItemCost(json_decode($satellite['cost_config'], true));
                }
            }
            unset($satellite['unblock_config']);
            unset($satellite['cost_config']);
            unset($satellite['reward_config']);
            unset($satellite['pages']);
        }
        
        if ($mode == 'full') {
            $result = $satellites;
        } else {
            $result = $this->composeSatellitesPreview($satellites);
        }
        return $result;
    }

    /**
    * Method to compose sattelites for preview of Lesson Item
    *
    * @return array Array or false
    **/
    private function composeSatellitesPreview($satellites)
    {
        $total = getenv('lesson.satellites.preview_total');
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

    public function composeItemReward($reward_config)
    {
        $coefficients = [1 => 0.3, 2 => 0.6, 3 => 1]; 
        $reward_gradation = [];
    
        foreach ($coefficients as $stars => $coefficient) {
            $reward_gradation[$stars] = [];
            $resources = [];
            foreach ($reward_config as $resource => $quantity) {
                if ($stars !== 3) {
                    $calculatedAmount = floor($quantity * $coefficient); 
                } else {
                    $calculatedAmount = ceil($quantity * $coefficient);
                }
                if($calculatedAmount > 0){
                    $reward_gradation[$stars][$resource] = $calculatedAmount;
                }
                
            }
        }
        return $reward_gradation;
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
    public function getItemProgress($exercise = [])
    {
        if(isset($exercise['totals']) && $exercise['totals']['total'] > 0){
            return floor($exercise['totals']['points'] / $exercise['totals']['total'] * 100);
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
    private function getNextItems($lesson_id)
    {
        $lessons = $this->where('JSON_CONTAINS(JSON_EXTRACT(unblock_config, "$.lessons"),"'.$lesson_id.'","$")')->get()->getResultArray();
        foreach($lessons as &$lesson){
            $lesson['image'] = base_url('image/index.php'.$lesson['image']);
        }
        return $lessons;
    }
    private function getItemUnblock($unblock_config)
    {
        $result = [];
        $SkillModel = model('SkillModel');
        if(!empty($unblock_config['lessons'])){
            $result['lessons'] = $this->join('lesson_unblock_usermap', 'lesson_unblock_usermap.item_id = lessons.id AND lesson_unblock_usermap.user_id = '.session()->get('user_id'), 'left')
            ->select('lessons.title, lessons.description, lessons.image, lessons.id, IF(lesson_unblock_usermap.user_id, 1, 0) as unblocked, lessons.parent_id')
            ->whereIn('lessons.id', $unblock_config['lessons'])->get()->getResultArray();
        }
        if(!empty($unblock_config['skills'])){
            $result['skills'] = $SkillModel->join('skills_usermap', 'skills_usermap.item_id = skills.id AND skills_usermap.user_id = '.session()->get('user_id'), 'left')
            ->join('descriptions', 'descriptions.item_id = skills.id AND descriptions.language_id = 1 AND descriptions.code = "skill"')
            ->select('descriptions.title, descriptions.description, skills.image, skills.id, IF(skills_usermap.user_id, 1, 0) as unblocked')
            ->whereIn('skills.id', $unblock_config['skills'])->get()->getResultArray();
        }
        foreach($result as &$group){
            foreach($group as &$item){
                $item['image'] = base_url('image/index.php'.$item['image']);
            }
        }
        if(empty($result)) return null;
        return $result;
    }
    private function getItemQuest($lesson_id)
    {
        $QuestModel = model('QuestModel');
        $result = $QuestModel->join('quests_usermap', 'quests_usermap.item_id = quests.id')
        ->where('quests_usermap.user_id = '.session()->get('user_id').' AND quests.code = "lesson" AND quests.target = '.$lesson_id.' AND quests_usermap.status != "finished"')->get()->getResultArray();
        return !empty($result);
    }
}