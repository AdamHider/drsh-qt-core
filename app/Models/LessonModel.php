<?php

namespace App\Models;

use CodeIgniter\Model;

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
    public function getList () 
    {
        $DescriptionModel = model('DescriptionModel');
        $CourseSectionModel = model('CourseSectionModel');
        $ExerciseModel = model('ExerciseModel');
        

        $lessons = $this->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id ='.session()->get('user_id'), 'left')
        ->select('lessons.*, exercises.id as exercise_id')
        ->where('lessons.course_id', session()->get('user_data')->profile->course_id)->get()->getResult();
        foreach($lessons as &$lesson){
            $lesson->parent_description = $CourseSectionModel->getItem($lesson->course_section_id);
            $lesson->description = $DescriptionModel->getItem('course', $lesson->id);
            $lesson->image = base_url('image/' . $lesson->image);
            $lesson->background_image = base_url('image/' . $lesson->background_image);
            $lesson->exercise = $ExerciseModel->getItem($lesson->exercise_id);
            $lesson->is_blocked = $this->checkBlocked($lesson->unblock_after);
        }
        return $lessons;
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