<?php

namespace App\Models;

use CodeIgniter\Model;
use stdClass;

class LessonModel extends Model
{
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
        $character = $this->join('descriptions', 'descriptions.code = "character" AND descriptions.item_id = characters.id AND descriptions.language_id = 1')
        ->where('characters.id', $character_id)->get()->getRow();
        if ($character) {
            $character->image = base_url('image/' . $character->image);
        }
        return $character;
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
            $lesson->description = $DescriptionModel->getItem('course', $lesson->id);
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
            $satellite->description = $DescriptionModel->getItem('course', $satellite->id);
            if($mode == 'full'){
                $satellite->background_image = base_url('image/' . $satellite->background_image);
                $satellite->exercise = $ExerciseModel->getItem($satellite->exercise_id);
                $satellite->is_blocked = $this->checkBlocked($satellite->unblock_after);
            }
        }
        $result->preview_list = $this->composeSatellitesPriview($satellites, $result->preview_total);
        if ($mode == 'full') {
            $result->list = $satellite;

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