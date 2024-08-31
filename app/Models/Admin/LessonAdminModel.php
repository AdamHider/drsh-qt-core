<?php

namespace App\Models\Admin;

use App\Models\PermissionTrait;
use CodeIgniter\Model;
use stdClass;

class LessonAdminModel extends Model
{
    use PermissionTrait;
    protected $table      = 'lessons';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'course_id',
        'course_section_id',
        'title',
        'description',
        'image'
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getItem ($lesson_id) 
    {
        //$this->useSharedOf('courses', 'course_id');
        if(!$this->hasPermission($lesson_id, 'r')){
            return 'forbidden';
        }

        $CourseSectionModel = model('CourseSectionModel');
        $ExerciseModel = model('ExerciseModel');
        $ResourceModel = model('ResourceModel');

        $lesson = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.*, exercises.id as exercise_id')->where('lessons.id', $lesson_id)->get()->getRowArray();

        if(!$lesson){
            return false;
        }
        if ($lesson) {
            $lesson['course_section'] = $CourseSectionModel->getItem($lesson['course_section_id']);
            $lesson['image'] = base_url('image/' . $lesson['image']);
            $lesson['exercise'] = $ExerciseModel->getItem($lesson['exercise_id']);
            $lesson['is_blocked'] = $this->checkBlocked($lesson['unblock_after']);
            if($lesson['parent_id']){
                $lesson['master_lesson'] =  $this->select('title, description')->where('lessons.id', $lesson['parent_id'])->get()->getRowArray();
            }
            $cost_config = json_decode($lesson['cost_config'], true);
            $lesson['cost'] = $ResourceModel->proccessItemCost(session()->get('user_id'), $cost_config);
            $lesson['reward_config'] = json_decode($lesson['reward_config']);
        }
        unset($lesson['pages']);
        return $lesson;
    }
    public function getList ($data) 
    {
        //$this->useSharedOf('courses', 'course_id');

        $CourseSectionModel = model('CourseSectionModel');
        
        $lessons = $this->join('courses', 'courses.id = lessons.course_id')->join('course_sections', 'course_sections.id = lessons.course_section_id')
        ->select('lessons.*, courses.title as course_title, course_sections.title as course_section_title')
        ->where('lessons.parent_id IS NULL')->limit($data['limit'], $data['offset'])->orderBy('id')->get()->getResultArray();

        foreach($lessons as $key => &$lesson){
            if(isset($data['offset'])){
                $lesson['order'] = $key + $data['offset'];
            }
            $lesson['satellites'] = $this->getSatellites($lesson['id']);
            $lesson['image'] = base_url('image/' . $lesson['image']);
            unset($lesson['pages']);
        }
        return $lessons;
    }
    public function getSatellites ($lesson_id) 
    {
        //$this->useSharedOf('courses', 'course_id');
        $satellites = $this->where('lessons.parent_id', $lesson_id)->orderBy('id')->get()->getResultArray();
        
        foreach($satellites as $key => &$satellite){
            $satellite['image'] = base_url('image/' . $satellite['image']);
            unset($satellite['pages']);
        }
        return $satellites;
    }
    public function updateItem ($data)
    {
        $this->transBegin();
        
        $result = $this->set($data)->where(['id' => $data['lesson_id']])->update();
       
        $this->transCommit();

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
        ->where('lessons.id', $lesson_id)->where('exercises.exercise_submitted IS NOT NULL')->get()->getResult();
        return empty($exercise);
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