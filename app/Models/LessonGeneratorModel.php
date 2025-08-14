<?php

namespace App\Models;

use CodeIgniter\Model;
use stdClass;

class LessonGeneratorModel extends LessonModel
{
    private $currentPage = 0;
    
    public function generatePages($lesson_id) 
    {
        $lesson = $this->where('lessons.id', $lesson_id)->get()->getRowArray();
        $lesson['pages'] = preg_replace('/image\/index.php/i', base_url('image/index.php'), $lesson['pages']);
        $pages = json_decode($lesson['pages'], true);
        
        if($lesson['type'] == 'common'){
            return $pages;
        } else 
        if($lesson['type'] == 'lexis' || $lesson['type'] == 'daily_lexis') {
            return $this->generateLexisItem($pages);
        } else 
        if($lesson['type'] == 'chat' || $lesson['type'] == 'daily_chat')   {
            return $this->generateChatItem($pages);
        } 
    }

    private $sceneries = [
        'standart' => [
            [ 'type' => 'read', 'referent_index' => '0'],
            [ 'type' => 'read', 'referent_index' => '1'],
            [ 'type' => 'quiz', 'referent_index' => '0'],
            [ 'type' => 'read', 'referent_index' => '2'],
            [ 'type' => 'quiz', 'referent_index' => '1'],
            [ 'type' => 'read', 'referent_index' => '3'],
            [ 'type' => 'quiz', 'referent_index' => '2'],
            [ 'type' => 'quiz', 'referent_index' => '3']
        ],
        'quiz_only' => [
            [ 'type' => 'quiz', 'referent_index' => '0'],
            [ 'type' => 'quiz', 'referent_index' => '1'],
            [ 'type' => 'quiz', 'referent_index' => '2'],
            [ 'type' => 'quiz', 'referent_index' => '3']
        ],
    ];
    private function generateLexisItem($page_config)
    {
        $seed = rand(1, 10);
        $pages = [];
        $word_list = $this->seedShuffle($page_config['word_list'], $seed);
        if(!isset($page_config['pattern'])) $page_config['pattern'] = 'random';
        if($page_config['pattern'] == 'quiz_only'){
            $pages_scenery = $this->generateQuizOnlyScenery(count($word_list));
        } else {
            $pages_scenery = $this->generateControlledRandomScenery(count($word_list));
        }
        foreach($pages_scenery as $key => $scenery_item){
            $word_object = $word_list[$scenery_item['referent_index']];
            if($scenery_item['type'] == 'read'){
                $page = $page_config['template']['read_page'];
                $page['title'] = sprintf($page['title'], "<b>".$word_object['label']."</b>");
                $page['template_config'] = [
                    'image' => $word_object['image'],
                    'text'  => $word_object['text']
                ];
                $pages[] = $page;
            } else {
                $page = $page_config['template']['quiz_page'];
                $variants = [];
                $variants[] = [
                    'text' => $word_object['text']
                ];
                $word_list_randomized = $this->seedShuffle($word_list, $key);
                foreach($word_list_randomized as $key => $word){
                    if($word['index'] !== $word_object['index']){
                        $variants[] = [
                            'text' => $word['text']
                        ];
                    }
                    if(count($variants) == 4){
                        shuffle($variants);
                        break;
                    }
                }
                $input_object = [
                    'index' => $word_object['index'],
                    'answer' => $word_object['text'],
                    'variants' => $variants,
                    'mode' => 'image',
                    'type' => 'input'
                ];
                $page['title'] = sprintf($page['title'], "<b>".$word_object['label']."</b>");
                $page['template_config'] = [
                    'input_list' => [$input_object],
                    'image' => $word_object['image'],
                    'text' => "{{input".$word_object['index']."}}",
                ];
                if (mt_rand(1, 100) <= 70 || $page_config['pattern'] == 'quiz_only') {
                    $page['timer'] = [
                        'mode' => 'standart',
                        'time' => 5
                    ];
                }
                $pages[] = $page;
            }
        }
        return $pages;
    }

    public function seedShuffle($array, $seed)
    {
        $tmp = array();
        for ($rest = $count = count($array);$count>0;$count--) {
            $seed %= $count;
            $t = array_splice($array,$seed,1);
            $tmp[] = $t[0];
            $seed = $seed*$seed + $rest;
        }
        return $tmp;
    }

    private function generateControlledRandomScenery(int $wordCount = 12) {
        $scenery = [];
        $readQueue = [];
        $quizQueue = [];

        for ($i = 0; $i < $wordCount; $i++) {
            $readQueue[] = ['type' => 'read', 'referent_index' => $i];
            $quizQueue[] = ['type' => 'quiz', 'referent_index' => $i];
        }

        shuffle($readQueue);

        foreach ($readQueue as $readPage) {
            $scenery[] = $readPage;
        }

        foreach ($quizQueue as $quizPage) {
            $ref = $quizPage['referent_index'];

            $readPos = array_search($ref, array_column($scenery, 'referent_index'));

            $minDistance = rand(1, 3);
            $insertPos = $readPos + $minDistance;

            while ($insertPos <= count($scenery)) {
                $prev = $scenery[$insertPos - 1] ?? null;
                if (!($prev['type'] === 'read' && $prev['referent_index'] === $ref)) {
                    break;
                }
                $insertPos++;
            }

            array_splice($scenery, $insertPos, 0, [$quizPage]);
        }

        return $scenery;
    }
    private function generateQuizOnlyScenery(int $wordCount = 12) {
        $scenery = [];
        for ($i = 0; $i < $wordCount; $i++) {
            $scenery[] = ['type' => 'quiz', 'referent_index' => $i];
        }
        return $scenery;
    }
    

    private function generateChatItem($page)
    {
        $UserModel = model('UserModel');
        $user = $UserModel->getItem();
        $pageFlat = $this->userMarkUp(json_encode($page), $user);

        $page = json_decode($pageFlat, true);
        return [$page];
    }
    private function userMarkUp($text, $user)
    {
        $text = preg_replace('/{{user\.name}}/i', $user['name'], $text);
        $text = preg_replace('/{{user\.character\.image}}/i', $user['character']['image'], $text);
        return $text;
    }
}