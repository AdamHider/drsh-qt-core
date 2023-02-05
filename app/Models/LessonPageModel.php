<?php

namespace App\Models;

use CodeIgniter\Model;
use stdClass;

class LessonPageModel extends Model
{
    protected $table      = 'lessons';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'image'
    ];
    
    public function getItem($filter) 
    {
        $ExerciseModel = $this->getModel('exercise');
        
        $exercise = $ExerciseModel->exerciseGet(['lesson_id' => $filter['lesson_id']]);
        
        $pages = $exercise['lesson_pages'];
        
        if(empty($pages)){
            return false;
        }
        $exercise['data']['total_pages'] = count($pages);
        if(empty($filter['action'])){
            $filter['action'] = 'current';
        }
        $index = $this->getIndexByAction($filter['action'], $exercise);
        if($index['available']){
            $exercise['data'] = $index['exercise_data'];
            $filter['page_index'] = $index['index'];
        } else {
            return $index;
        }
        
        $page_index = $filter['page_index'];
        
        if(!empty($pages[$page_index])){
            $page = [
                'exercise' => []
            ];
            $page_data = $pages[$page_index];
            $page['answers'] = [
                'is_finished' => false
            ];
            if(!empty($exercise['data']['answers'][$page_index])){
                $page['answers'] = $exercise['data']['answers'][$page_index];
            }
            /*
            $page_html = $this->renderPage($page_data, $page_index);
            if(!empty($page_data['template_config']['input_list'])){
                $page_html = $this->renderFields($page_data, $page_index, $page_html, $exercise['data']);
            }*/
            $page['exercise']         = $exercise;
            //$page['views']['content'] = $this->renderComponents($page_data, $page_index, $page_html);
            //$page['views']['actions'] = $this->renderPageActions($page_data, $exercise['data']);
            $page['data'] = $this->composeItemData($page_data);
            $page['fields'] = $this->composeItemFields($page_data, $exercise);
            unset($page_data['template_config']);
            $page['header'] = $page_data;
            return $page;
        } 
        if($exercise['data']['total_pages'] == $page_index && !$exercise['finished_at']){
            $Exercise->saveExercise(['id' => $exercise['id']], $exercise, 'finish');
        }
        
        return false;
    }

    public function getList ($data) 
    {
        $DescriptionModel = model('DescriptionModel');
        $CourseSectionModel = model('CourseSectionModel');
        $ExerciseModel = model('ExerciseModel');

        $lessons = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.*, exercises.id as exercise_id')
        ->where('lessons.course_id', session()->get('user_data')->profile->course_id)
        ->where('lessons.parent_id IS NULL')
        ->limit($data['limit'], $data['offset'])->orderBy('id')->get()->getResult();
        foreach($lessons as $key => &$lesson){
            if(isset($data['offset'])){
                $lesson->order = $key + $data['offset'];
            }
            $lesson->course_section = $CourseSectionModel->getItem($lesson->course_section_id);
            $lesson->description = $DescriptionModel->getItem('lesson', $lesson->id);
            $lesson->satellites = $this->getSatellites($lesson->id, 'lite');
            $lesson->image = base_url('image/' . $lesson->image);
            $lesson->background_image = base_url('image/' . $lesson->background_image);
            $lesson->exercise = $ExerciseModel->getItem($lesson->exercise_id);
            $lesson->is_blocked = $this->checkBlocked($lesson->unblock_after);
        }
        return $lessons;
    }
    public function getSatellites ($lesson_id, $mode) 
    {
        $DescriptionModel = model('DescriptionModel');
        $ExerciseModel = model('ExerciseModel');

        $result = new stdClass;
        $result->preview_total = 3;

        $satellites = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.*, exercises.id as exercise_id')
        ->where('lessons.parent_id', $lesson_id)->orderBy('id')->get()->getResult();

        foreach($satellites as $key => &$satellite){
            $satellite->image = base_url('image/' . $satellite->image);
            $satellite->description = $DescriptionModel->getItem('lesson', $satellite->id);
            if($mode == 'full'){
                $satellite->background_image = base_url('image/' . $satellite->background_image);
                $satellite->exercise = $ExerciseModel->getItem($satellite->exercise_id);
                $satellite->is_blocked = $this->checkBlocked($satellite->unblock_after);
            }
        }
        $result->preview_list = $this->composeSatellitesPriview($satellites, $result->preview_total);
        if ($mode == 'full') {
            $result->list = $satellites;

        }
        return $result;
    }

    /**
    * Method to beautify lesson object
    *
    * @return  assoc Array or false
    **/
    private function composeSatellitesPriview($satellites, $total)
    {
        $result = [];
        foreach($satellites as $index => $satellite){
            if($index == $total){
                break;
            }
            $satellite->size = rand(15, 20);
            $satellite->distance = 2*$index;
            $satellite->duration = rand(15, 20);
            $satellite->delay = rand(1, 5);
            $result[] = $satellite;
        }
        
        return $result;
    }
        
    private function checkBlocked ($lesson_id) 
    {
        if (!$lesson_id) {
            return false;
        }
        $exercise  = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'))
        ->where('lessons.id', $lesson_id)->where('exercises.finished_at IS NOT NULL')->get()->getResult();
        return empty($exercise);
    }
    public function itemCreate ($image)
    {
        $this->transBegin();
        $data = [
            'image' => $image
        ];
        $character_id = $this->insert($data, true);
        $this->transCommit();

        return $character_id;        
    }



}