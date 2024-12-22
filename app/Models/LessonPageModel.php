<?php

namespace App\Models;

use CodeIgniter\Model;
use stdClass;

class LessonPageModel extends LessonModel
{
    private $currentPage = 0;
    public function getPage($lesson_id, $action = 'current')
    {
        $exercise = $this->select('exercises.*, COALESCE(exercises.exercise_pending, exercises.exercise_submitted) as data')
        ->where('lesson_id', $lesson_id)->get()->getRowArray();

        $checked = $this->checkIfAvailable($exercise, $action);

        if(!$checked['available']) return $checked;

        $exercise['data'] = $checked['exercise_data'];
        $this->currentPage = $exercise['data']['current_page'];

        $lesson['page'] = json_decode($lesson['page'], true);

        $exercise['data']['total_pages'] = $lesson['total_pages'];


        if($exercise['data']['total_pages'] == $this->currentPage && !$exercise['finished_at']){
            $ExerciseModel->updateItem($exercise, 'finish');
            return 'finish';
        }


        $lesson = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('exercises.*, COALESCE(exercises.exercise_pending, exercises.exercise_submitted) as data')
        ->select('JSON_EXTRACT(lessons.pages, "$[1]") as page, JSON_LENGTH(lessons.pages) as total_pages')
        ->where('lessons.id', $lesson_id)->get()->getRowArray();
        
       
        if(!empty($lesson['page'])){
            $page_data = $lesson['page'];
    
            $page['data']       = $this->composeItemData($lesson['page']);
            $page['fields']     = $this->composeItemFields($lesson['page'], $exercise);
            $page['actions']    = $this->composeItemActions($lesson['page'], $exercise['data']);
            $page['answer']     = $exercise['data']['answers'][$this->currentPage]['totals'] ?? [];
    
            unset($lesson['page']['template_config']);
            $page['header']     = $lesson['page'];
            return $page;
        } 
        
        return false;
    }

    private function composeItemData($page_data)
    {
        if(isset($page_data['form_template']) && $page_data['form_template'] == 'match'){
            $page_data['template_config']['match_variants'] = $this->composeMatchVariants($page_data['template_config']['input_list']);
        }
        unset($page_data['template_config']['input_list']);
        return $page_data['template_config'];
    }
    private function composeItemActions($page_data, $exercise)
    {
        $isStart    = $this->currentPage == 0;
        $isEnd      = $exercise['total_pages']-1 == $this->currentPage;
        $isAnswered = isset($exercise['answers'][$this->currentPage]) && $exercise['answers'][$this->currentPage]['is_finished'];
        $hasInput   = isset($page_data['template_config']['input_list']);

        if(!$isAnswered && $hasInput)   $exercise['actions']['main'] = 'confirm';
        if($isAnswered && $isEnd)       $exercise['actions']['main'] = 'finish';
        if($isStart)                    $exercise['actions']['back_attempts'] = 0;

        return $exercise['actions'];
    }
    private function composeItemFields($page_data, $exercise)
    {
        $result = [];
        if(empty($page_data['template_config']['input_list'])){
            return false;
        }
        $user_answers = false;
        if(!empty($exercise['data']['answers'][$this->currentPage])){
            $user_answers = $exercise['data']['answers'][$this->currentPage]['answers'];
        }
        foreach($page_data['template_config']['input_list'] as $key => $input){
            $field = [
                'index'     => $input['index'],
                'mode'      => $input['mode'],
            ];

            if(isset($input['variants']))   $field['variants'] = $input['variants'];
            if(isset($input['label']))      $field['label'] = $input['label'];
            if(isset($user_answers[$key]))  $field['answer'] = $user_answers[$key];

            $result[] = $field;
        }
        return $result;
    }

    private function composeMatchVariants($input_list)
    {
        $result = [];
        if(empty($input_list)) return false;
        foreach($input_list as $key => $input){
            $match_variant = [
                'index'     => $input['index'],
                'answer'    => $input['answer']
            ];
            $result[] = $match_variant;
        }
        shuffle($result);
        return $result;
    }
    

    private function checkIfAvailable($exercise, $action)
    {
        $ExerciseModel = model('ExerciseModel');
        $result = [
            'available' => true
        ];
        $current = $exercise['data']['current_page'];
        if($action == 'next'){
            $exercise['data']['current_page']++;
        }
        if($action == 'previous'){
            if($exercise['actions']['back_attempts'] == 0){               
                $result['available']  = false;
                $result['message']    = 'No back attempts left';
                return $result;
            }
            $exercise['data']['current_page']--;
            $exercise['actions']['back_attempts']--;
        }
        $result['exercise_data']  = $exercise['data'];
        $result['index']          = $exercise['data']['current_page'];
        $ExerciseModel->updateItem($exercise);
        return $result;
    }
    
}