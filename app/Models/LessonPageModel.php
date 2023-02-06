<?php

namespace App\Models;

use CodeIgniter\Model;
use stdClass;

class LessonPageModel extends LessonModel
{
    
    public function getPage($lesson_id, $index, $action = 'current')
    {
        $exercise = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('exercises.*, COALESCE(exercises.exercise_pending, exercises.exercise_submitted) as data')
        ->where('lessons.id', $lesson_id)->get()->getRow();

        $exercise->data = json_decode($exercise->data, false, JSON_UNESCAPED_UNICODE);
        $exercise->lesson_pages = (array) json_decode($exercise->lesson_pages, false, JSON_UNESCAPED_UNICODE);
        
        $checked_index = $this->checkPageIndex($action, $exercise);
        print_r($checked_index);
        die;
        if($checked_index['available']){
            $exercise['data'] = $checked_index['exercise_data'];
            $index = $checked_index['index'];
        } else {
            return $checked_index;
        }
        
        if(!empty($exercise->lesson_pages[$index])){
            return $this->composePage($exercise);
        } 
        if($exercise['data']['total_pages'] == $page_index && !$exercise['finished_at']){
            $ExerciseModel = model('ExerciseModel');
            $ExerciseModel->updateItem($exercise, 'finish');
        }
        
        return false;
    }

    private function composePage($exercise)
    {
        $page = [];
        $page_data = $exercise->lesson_pages[$page_index];
        $page['answers'] = [
            'is_finished' => false
        ];
        if(!empty($exercise['data']['answers'][$page_index])){
            $page['answers'] = $exercise['data']['answers'][$page_index];
        }
        $page['exercise'] = $exercise;
        $page['data'] = $this->composeItemData($page_data);
        $page['fields'] = $this->composeItemFields($page_data, $exercise);
        unset($page_data['template_config']);
        $page['header'] = $page_data;
        return $page;
    }

    private function composeItemData($page_data)
    {
        if(isset($page_data['form_template']) && $page_data['form_template'] == 'match'){
            $page_data['template_config']['match_variants'] = $this->composeMatchVariants($page_data['template_config']['input_list']);
        }
        unset($page_data['template_config']['input_list']);
        return $page_data['template_config'];
    }

    private function composeItemFields($page_data, $exercise)
    {
        $result = [];
        if(empty($page_data['template_config']['input_list'])){
            return false;
        }
        $user_answers = false;
        if(!empty($exercise['data']['answers'][$exercise['data']['current_page']])){
            $user_answers = $exercise['data']['answers'][$exercise['data']['current_page']]['answers'];
        }
        foreach($page_data['template_config']['input_list'] as $key => $input){
            $field = [
                'index'     => $input['index'],
                'mode'      => $input['mode'],
            ];
            if(isset($input['variants'])){
                $field['variants'] = $input['variants'];
            }
            if(isset($input['label'])){
                $field['label'] = $input['label'];
            }
            if(isset($user_answers[$key])){
                $field['answer'] = $user_answers[$key];
            }
            $result[] = $field;
        }
        return $result;
    }

    private function composeMatchVariants($input_list)
    {
        $result = [];
        if(empty($input_list)){
            return false;
        }
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
    

    private function checkPageIndex($action, $exercise)
    {
        $ExerciseModel = model('ExerciseModel');
        $result = new stdClass;
        $result->available  = true;
        if($action == 'next'){
            if($exercise->data->current_page == $exercise->data->total_pages){            
                $result->available = false;
                return $result;
            }
            $exercise->data->current_page++;
        }
        if($action == 'previous'){
            if($exercise->data->back_attempts == 0){               
                $result->available  = false;
                $result->message    = 'No back attempts left';
                return $result;
            }
            $exercise->data->current_page--;
            $exercise->data->back_attempts--;
        }
        if($action == 'again'){
            if($exercise->data->again_attempts == 0){             
                $result->available  = false;
                $result->message    = 'No again attempts left';
                return $result;
            }
            $exercise->data->again_attempts--;
        }
        $result->exercise_data  = $exercise->data;
        $result->index          = $exercise->data->current_page;
        $ExerciseModel->updateItem($exercise);
        return $result;
    }
    
}