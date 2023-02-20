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
        'image'
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getItem ($lesson_id) 
    {
        $DescriptionModel = model('DescriptionModel');
        $CourseSectionModel = model('CourseSectionModel');
        $ExerciseModel = model('ExerciseModel');

        $lesson = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.*, exercises.id as exercise_id')
        ->where('lessons.id', $lesson_id)->get()->getRowArray();

        if(!$lesson){
            return false;
        }
        if ($lesson) {
            $lesson['course_section'] = $CourseSectionModel->getItem($lesson['course_section_id']);
            $lesson['description'] = $DescriptionModel->getItem('lesson', $lesson['id']);
            $lesson['image'] = base_url('image/' . $lesson['image']);
            $lesson['exercise'] = $ExerciseModel->getItem($lesson['exercise_id']);
            $lesson['is_blocked'] = $this->checkBlocked($lesson['unblock_after']);
            if($lesson['parent_id']){
                $lesson['master_lesson'] = $DescriptionModel->getItem('lesson', $lesson['parent_id']);
            }
        }
        unset($lesson['pages']);
        return $lesson;
    }
    public function getList ($data) 
    {
        
        $this->useSharedOf('courses', 'course_id');
        $this->useSharedOf('course_sections', 'course_section_id');

        $DescriptionModel = model('DescriptionModel');
        $CourseSectionModel = model('CourseSectionModel');
        $ExerciseModel = model('ExerciseModel');

        $lessons = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.*, exercises.id as exercise_id')
        ->where('lessons.course_id', session()->get('user_data')['profile']['course_id'])
        ->where('lessons.parent_id IS NULL')
        ->whereHasPermission('r')
        ->limit($data['limit'], $data['offset'])->orderBy('id')->get()->getResultArray();
        die;
        foreach($lessons as $key => &$lesson){
            if(isset($data['offset'])){
                $lesson['order'] = $key + $data['offset'];
            }
            $lesson['course_section'] = $CourseSectionModel->getItem($lesson['course_section_id']);
            $lesson['description'] = $DescriptionModel->getItem('lesson', $lesson['id']);
            $lesson['satellites'] = $this->getSatellites($lesson['id'], 'lite');
            $lesson['image'] = base_url('image/' . $lesson['image']);
            $lesson['exercise'] = $ExerciseModel->getItem($lesson['exercise_id']);
            $lesson['is_blocked'] = $this->checkBlocked($lesson['unblock_after']);
            unset($lesson['pages']);
        }
        return $lessons;
    }
    public function getSatellites ($lesson_id, $mode) 
    {
        $DescriptionModel = model('DescriptionModel');
        $ExerciseModel = model('ExerciseModel');

        $result = [];
        $result['preview_total'] = 3;

        $satellites = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.*, exercises.id as exercise_id')
        ->where('lessons.parent_id', $lesson_id)->orderBy('id')->get()->getResultArray();

        foreach($satellites as $key => &$satellite){
            $satellite['image'] = base_url('image/' . $satellite['image']);
            $satellite['description'] = $DescriptionModel->getItem('lesson', $satellite['id']);
            if($mode == 'full'){
                $satellite['exercise'] = $ExerciseModel->getItem($satellite['exercise_id']);
                $satellite['is_blocked'] = $this->checkBlocked($satellite['unblock_after']);
            }
            unset($satellite['pages']);
        }
        $result['preview_list'] = $this->composeSatellitesPriview($satellites, $result['preview_total']);
        if ($mode == 'full') {
            $result['list'] = $satellites;

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
            $satellite['size'] = rand(15, 20);
            $satellite['distance'] = 2*$index;
            $satellite['duration'] = rand(15, 20);
            $satellite['delay'] = rand(1, 5);
            $result[] = $satellite;
        }
        
        return $result;
    }
        
    public function checkBlocked ($lesson_id) 
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

    public function composePages($pages, $lesson_type)
    {
        if($lesson_type == 'lexis'){
            return $this->parseLexis($pages);
        } else {
            return $pages;
        }
    }
    private function parseLexis($page_config)
    {
        $seed = rand(1, 10);
        $pages = [];
        
        $language_tag = 'ru-RU';
        
        $word_list = $this->seedShuffle($page_config['word_list'], $seed);
        $pages_scenery = [
            [ 'type' => 'read', 'referent_index' => '0'],
            [ 'type' => 'read', 'referent_index' => '1'],
            [ 'type' => 'quiz', 'referent_index' => '0'],
            [ 'type' => 'read', 'referent_index' => '2'],
            [ 'type' => 'quiz', 'referent_index' => '1'],
            [ 'type' => 'read', 'referent_index' => '3'],
            [ 'type' => 'quiz', 'referent_index' => '2'],
            [ 'type' => 'quiz', 'referent_index' => '3']
        ];
        $word_list_chunks = array_chunk($word_list, 4);
        $word_object = $word_list[0];
        foreach($word_list_chunks as $word_chunk){
            $word_chunk = $this->seedShuffle($word_chunk, $seed);
            foreach($pages_scenery as $key => $scenery_item){
                $word_object = $word_chunk[$scenery_item['referent_index']];
                if($scenery_item['type'] == 'read'){
                    $page = $page_config['template']['read_page'];
                    $page['template_config']['image'] = $word_object['image'];
                    $page['template_config']['text'] = $word_object['text'];
                    foreach($page['title'] as $lang_tag => &$title){
                        if(strpos($title, '%s') !== false){
                            $title = sprintf($title, "<b>".$word_object['translations'][$language_tag]."</b>");
                        }
                    }
                    $pages[] = $page;
                } else {
                    $page = $page_config['template']['quiz_page'];
                    $image_list = [];
                    $image_list[] = [
                        'image' => $word_object['image'],
                        'text' => $word_object['text']
                    ];
                    $word_list_randomized = $this->seedShuffle($word_list, $key);
                    foreach($word_list_randomized as $key => $word){
                        if($word['index'] !== $word_object['index']){
                            $image_list[] = [
                                'image' => $word['image'],
                                'text' => $word['text']
                            ];
                        }
                        if(count($image_list) == 4){
                            shuffle($image_list);
                            break;
                        }
                    }
                    foreach($page['title'] as $lang_tag => &$title){
                        if(strpos($title, '%s') !== false){
                            $title = sprintf($title, "<b>".$word_object['translations'][$language_tag]."</b>");
                        }
                    }

                    $input_object = $page_config['template']['input_object'];
                    $input_object['index'] = $word_object['index'];
                    $input_object['answer'] = $word_object['text'];
                    $input_object['variants'] = $image_list;
                    $page['template_config']["input_list"][] = $input_object;

                    $page['template_config']['text'] = "{{input".$word_object['index']."}}";
                    $pages[] = $page;
                }
            }
        }
        return $pages;
    }
    public function getUniqueRandomNumbersWithinRange($min, $max, $quantity, $excluded) {
        $numbers = range($min, $max);
        shuffle($numbers);
        unset($numbers[array_search($excluded, $numbers)]);
        return array_slice($numbers, 0, $quantity);
    }
    public function seedShuffle($array, $seed) {
        $tmp = array();
        for ($rest = $count = count($array);$count>0;$count--) {
            $seed %= $count;
            $t = array_splice($array,$seed,1);
            $tmp[] = $t[0];
            $seed = $seed*$seed + $rest;
        }
        return $tmp;
    }


}