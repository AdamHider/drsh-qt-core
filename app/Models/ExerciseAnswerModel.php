<?php

namespace App\Models;

use CodeIgniter\Model;


class ExerciseAnswerModel extends ExerciseModel
{
    
    private $points_config = [
        'variant' => 100,
        'match' => 120,
        'simple' => 50,
        'image' => 70,
        'chat' => 140
    ];
    
    private $default_answers = [
        'is_finished'   => false,
        'answers'       => [],
        'totals'        => 
        [
            'answers'   => 0,
            'correct'   => 0,
            'total'       => 0
        ]
    ];
    
    /**
    * Method to compose lesson item by id
    *
    * @return  assoc Array or null
    *
    * @since   3.4
    **/
    public function saveAnswer($lesson_id, $data = [])
    {
        $LessonPageModel = model('LessonPageModel');
        $this->checkAnswers($lesson_id, $data);
        $page = $LessonPageModel->getPage($lesson_id);
        return $page;
    }
    
    /**
    * Method to compose lesson item by id
    *
    * @return  assoc Array or null
    *
    * @since   3.4
    **/
    public function checkAnswers($lesson_id, $income_answers)
    {
        $lesson = $this->join('lessons', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.*, exercises.id as exercise_id')
        ->where('lessons.id', $lesson_id)->get()->getRowArray();

        $exercise = $this->getItem($lesson['exercise_id']);
        
        $page_index = $exercise['data']['current_page'];
        $page = json_decode($lesson['pages'], true)[$page_index];
        $fields = $page['template_config']['input_list'];
        
        if(!empty($exercise['data']['answers'][$page_index])){
            $page_answers = $exercise['data']['answers'][$page_index];
        } else {
            $page_answers = $this->default_answers;
        }
        if(!isset($page_answers['is_finished'])){
            $page_answers['is_finished'] = false;
        }
        if($page_answers['is_finished'] == false){
            $total_fields = count($fields);
            foreach($fields as $input_index => $field){
                if(!empty($field) && isset($income_answers[$input_index])){
                    $user_input = $income_answers[$input_index];
                    $existing_answer = [];
                    if(!empty($page_answers['answers'][$input_index])){
                        $existing_answer = $page_answers['answers'][$input_index];
                    }
                    $page_answers['answers'][$input_index] = $this->composeAnswer($field, $user_input, $total_fields, $existing_answer);
                    if($page_answers['answers'][$input_index]['is_correct']){
                        $page_answers['totals']['correct']++;
                    }
                    $page_answers['totals']['answers']++;
                    $page_answers['totals']['total'] += $page_answers['answers'][$input_index]['points']; 
                }
            }
            if($this->checkFinished($fields, $page_answers)){
                $page_answers['is_finished'] = true;
            }
        }
        
        if($page_answers['is_finished'] == true){
            $exercise['data']['totals']['total'] = $exercise['data']['totals']['total'] + $page_answers['totals']['total'];
        }
        $exercise['data']['answers'][$page_index] = $page_answers;
        $this->updateItem($exercise);
    } 
    
    private function composeAnswer($field, $user_input, $total_fields, $existing_answer)
    {
        $points = $this->calculatePoints($field, $user_input, $total_fields);
        if($points > 0){
            $answer = [
                'value' => $user_input,
                'is_correct' => true,
                'points' => $points
            ];
        } else {
            $answer = [
                'value' => $user_input,
                'answer' => $field['answer'],
                'is_correct' => false,
                'points' => $points
            ];
        }  
        if($field['mode'] == 'chat'){
            if(empty($existing_answer['tmp_answer'])){
                $answer['tmp_answer'] = $user_input;
                $answer['is_temp'] = true;
                if($answer['is_correct']){
                    $answer['tmp_answer'] = "";
                    $answer['is_temp'] = false;
                }
            } else {
                $answer['tmp_answer'] = $existing_answer['tmp_answer'];
                $answer['is_temp'] = false;
            }
        }
        return $answer;
    }
    
    private function checkFinished($field_list, $page_answers)
    {
        $total_finished = [];
        foreach($page_answers['answers'] as $answer){
            if(empty($answer['is_temp']) || !$answer['is_temp']){
                $total_finished[] = $answer;
            }
        }
        return count($field_list) == count($total_finished);
    }
    
    
    private function calculatePoints($field, $user_input, $total_fields)
    {
        $points_default = $this->points_config[$field['mode']];
        $field_points = ceil($points_default/$total_fields);
        if($field['mode'] == 'variant' || $field['mode'] == 'match' || $field['mode'] == 'simple' || $field['mode'] == 'image'){
            $points = $this->calculateInputPoints($field, $user_input, $field_points);
        } else 
        if($field['mode'] == 'chat'){
            $points = $this->calculateChatPoints($field, $user_input, $field_points);
        }    
        return $points;
    }
    
    private function calculateInputPoints($field, $user_input, $field_points)
    {
        $points = (trim($field['answer']) == trim($user_input)) * $field_points;
        return $points;
    }
    
    public function calculateChatPoints($field, $user_input, $field_points)
    {
        $simplified_input = $this->simplifyUserInput($user_input);
        $points = 0;
        $field['variants'][] = $field['tip'];
        foreach($field['variants'] as $correct_answer){
            $simplified_answer = $this->simplifyUserInput($correct_answer);
            if ($simplified_input !== $simplified_answer && $this->simplifySpecialChars($simplified_input) == $this->simplifySpecialChars($simplified_answer)) {
                $points = 0;
            }
            $correctness = $this->chatCalculateCorrectness($simplified_input, $simplified_answer);
            if ($correctness*1 == 100) {
                $points = $field_points;
            }
        }
        return $points;
    }
    
    public function chatCalculateCorrectness($simplified_input, $simplified_answer)
    {
        $simplified_input_exploded = explode(' ', trim($simplified_input));
        $simplified_answer_exploded = explode(' ', trim($simplified_answer));
        $mistakes = 0;
        $total = 0;
        foreach ($simplified_answer_exploded as $key => $answer_item) {
            if ($answer_item == '{name}' || strpos($answer_item, '___') > -1 ) {
                continue;
            }
            if (!isset($simplified_input_exploded[$key])) {
                $mistakes++;
                $total++;
                continue;
            }
            if ($simplified_input_exploded[$key] !== $answer_item &&
                    $simplified_input_exploded[$key] === $this->simplifySpecialChars($answer_item)) {
                $total++;
                continue;
            }
            if ($answer_item !== $simplified_input_exploded[$key]) {
                $mistakes++;
            }
            $total++;
        }
        if($total == $mistakes){
            return 0;
        }
        return 100 / ($total / ($total - $mistakes));
    }
    
    public function refreshAnswer($exercise)
    {
        $exercise['data']['totals']['total'] = $exercise['data']['totals']['total'] - $exercise['data']['answers'][$exercise['data']['current_page']]['totals']['total'];
        unset($exercise['data']['answers'][$exercise['data']['current_page']]);
        $exercise['exercise_pending'] = $exercise['data'];
        return $exercise;
    } 
    
    public function simplifySpecialChars($str)
    {
        $str = mb_strtolower($str);
        $findArray = ['ı', 'ğ', 'ü', 'ş', 'ö', 'ç', 'â', 'ñ'];
        $replaceArray = ['i', 'g', 'u', 's', 'o', 'c', 'a', 'n'];
        return str_replace($findArray, $replaceArray, $str);
    }
    
    public function simplifyUserInput($str)
    {
        $str = mb_strtolower($str);
        $findArray = [',', '.', '!', '?', '-'];
        $replaceArray = '';
        $str = str_replace($findArray, $replaceArray, $str);
        $findArray = ['  ', '   ', '   '];
        $replaceArray = ' ';
        return str_replace($findArray, $replaceArray, $str);
    }

}