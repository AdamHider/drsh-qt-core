<?php

namespace App\Models;

use CodeIgniter\Model;


class ExerciseAnswerModel extends ExerciseModel
{
    
    public $points_config = [
        'none' => 20,
        'variant' => 100,
        'puzzle' => 100,
        'match' => 120,
        'simple' => 50,
        'radio' => 100,
        'checkbox' => 100,
        'image' => 20,
        'chat' => 140
    ];

    private $empty_answer = [
        "mode" => "none", 
        "type" => "input", 
        "index" => 0, 
        "answer" => ''
    ];
    
    private $default_answers = [
        'answers'       => [],
        'totals'        => 
        [
            'quantity'  => 0,
            'correct'   => 0,
            'points'    => 0
        ]
    ];
    public function saveAnswer($lesson_id, $income_answers = [])
    {
        $LessonPageModel = model('LessonPageModel');

        $exercise = $this->getItemByLesson($lesson_id);
        $page_index = $exercise['data']['current_page'];

        $fields = $exercise['pages'][$page_index]['template_config']['input_list'];
        
        $answers = $this->checkPageAnswers($fields, $income_answers);

        $exercise['data']['totals']['points'] += $answers['totals']['points'];
        $exercise['data']['answers'][$page_index] = $answers;
        $this->updateItem($exercise);

        return $LessonPageModel->getPage($lesson_id, $page_index);
    }

    public function checkPageAnswers($fields, $income_answers)
    {
        $answers = $this->default_answers;
        
        $total_fields = count($fields);
        foreach($fields as $input_index => $field){
            if(isset($income_answers[$input_index])){
                $user_input = $income_answers[$input_index]->text ?? '';
                
                $answer = $this->composeAnswer($field, $user_input, $total_fields);
                
                if($answer['is_correct']) $answers['totals']['correct']++;

                $answers['totals']['quantity']++;
                $answers['totals']['points'] += $answer['points']; 
                $answers['answers'][$input_index] = $answer;
            } 
        }
        return $answers;
    } 
    
    private function composeAnswer($field, $user_input, $total_fields)
    {
        $answer = [];
        $answer['value']        = $user_input;
        $answer['answer']       = $field['answer'];
        $answer['points']       = $this->calculatePoints($field, $user_input, $total_fields);
        $answer['is_correct']   = $answer['points'] > 0;
        return $answer;
    }
    
    private function calculatePoints($field, $user_input, $total_fields)
    {
        $points_config  = $this->points_config[$field['mode']];
        $field_points   = ceil($points_config/$total_fields);
        return (trim($field['answer']) == trim($user_input)) * $field_points;
    }
}