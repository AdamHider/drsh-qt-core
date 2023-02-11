<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class ChallengeModel extends Model
{
    protected $table      = 'homeworks';
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

    public function getList ($data) 
    {
        $CourseSectionModel = model('CourseSectionModel');
        $DescriptionModel = model('DescriptionModel');
        $LessonModel = model('LessonModel');
        $ExerciseModel = model('ExerciseModel');
        
        
        $homeworks = $this->join('lessons', 'lessons.id = homeworks.lesson_id')
        ->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id = '.session()->get('user_id'), 'left')
        ->select("homeworks.*, exercises.finished_at, exercises.id as exercise_id, lessons.image as image, lessons.course_section_id, lessons.unblock_after")
        ->where('homeworks.classroom_id', session()->get('user_data')->profile->classroom_id)
        ->limit($data['limit'], $data['offset'])->orderBy('date_end')
        ->get()->getResultArray();

        foreach($homeworks as &$homework){
            $homework['course_section'] = $CourseSectionModel->getItem($homework['course_section_id']);
            $homework['description'] = $DescriptionModel->getItem('lesson', $homework['lesson_id']);
            $homework['image'] = base_url('image/' . $homework['image']);
            $homework['exercise'] = $ExerciseModel->getItem($homework['exercise_id']);
            $homework['is_blocked'] = $LessonModel->checkBlocked($homework['unblock_after']);
            if($homework['date_end']){
                $time = Time::parse($homework['date_end'], Time::now()->getTimezone());
                $homework['time_left'] = Time::now()->difference($time)->getDays();
                $homework['time_left_humanized'] = Time::now()->difference($time)->humanize();
            }
        }
        return $homeworks;
    }
}