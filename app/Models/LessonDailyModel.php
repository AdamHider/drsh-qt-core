<?php

namespace App\Models;

use CodeIgniter\Model;

class LessonDailyModel extends LessonModel
{
    private $currentPage = 0;
    private $daily_lesson = [
        'language_id' => 1, 
        'title' => 'Daily', 
        'description' => 'Daily', 
        'type' => 'daily_lexis', 
        'pages' => '[]',
        'cost_config' => '[]', 
        'reward_config' => '[]', 
        'image' => '/wormholes/wormhole.jpg', 
        'published' => 1, 
        'order' => 0, 
        'owner_id' => 0, 
        'is_private' => 0
    ];
    private $daily_lesson_pages = [
        'template' => [
            'quiz_page' => [
                "title" => 'Где здесь "%s"?',
                'form_template' => 'image',
                'page_template' => 'imageSelection'
            ],
            'read_page' => [
                "title" => 'Выучите слово "%s"',
                "page_template" => 'imageSelection'
            ]
        ],
        'pattern' => 'quiz_only',
        'word_list' => []
    ];
    
    public function getList()
    {
        $ExerciseModel = model('ExerciseModel');
        $ResourceModel = model('ResourceModel');

        $lessons = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.*, exercises.id as exercise_id')
        ->where('type', 'daily_lexis')->where('published', 1)->where('is_private', 0)->get()->getResultArray();
        foreach($lessons as $key => &$lesson){
            $lesson['satellites']       = $this->getSatellites($lesson['id'], 'lite');
            $lesson['image']            = base_url('image/index.php'.$lesson['image']);
            $lesson['exercise']         = $ExerciseModel->getItem($lesson['exercise_id']);
            $lesson['progress']         = $this->getOverallProgress($lesson['id']);
            $lesson['is_explored']      = isset($lesson['exercise']['id']);
            
            $lesson['cost']             = $ResourceModel->proccessItemCost(json_decode($lesson['cost_config'], true));
            $lesson['reward']           = $ResourceModel->proccessItemGroupReward(json_decode($lesson['reward_config'], true));
            unset($lesson['unblock_config']);
            unset($lesson['cost_config']);
            unset($lesson['reward_config']);
            unset($lesson['pages']);
        }
        return $lessons;

    }
    public function createItem()
    {
        $this->daily_lesson['title'] .= time();

        $this->daily_lesson['pages'] = json_encode($this->compileItemPages(), JSON_UNESCAPED_SLASHES);

        $this->where('type', 'daily_lexis')->delete();
        $this->insert($this->daily_lesson);
    }

    private function compileItemPages()
    {
        $LessonWordsModel = model('LessonWordsModel');

        $result = $this->daily_lesson_pages;
        $word_list = $LessonWordsModel->where('language_id', 1)->orderBy('id', 'RANDOM')->limit(12)->get()->getResultArray();
        foreach($word_list as $key => $word){
            $result['word_list'][] = [
                'text' => $word['word'],
                'image' => $word['image'],
                'index' => $key+1,
                'label' => $word['translation']
            ];
        }
        return $result;
    }
}