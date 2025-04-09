<?php

namespace App\Models;

use CodeIgniter\Model;

class CourseModel extends Model
{
    use PermissionTrait;
    protected $table      = 'courses';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'title', 'description', 'image', 'background_image', 'language_id', 'published', 'is_private'
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getItem ($course_id) 
    {
        if(!$this->hasPermission($course_id, 'r')){
            return 'forbidden';
        }
        $course = $this->where('id', $course_id)->where('published', 1)->get()->getRowArray();
        if(!empty($course)){
            $course['image'] = base_url('image/index.php'.$course['image']);
            $course['background_image'] = base_url('image/index.php'.$course['background_image']);
            $course['progress'] = $this->getProgress($course['id']);
            if($course['progress']['total_lessons'] > 0){
                $course['progress']['percentage'] = ceil($course['progress']['total_exercises'] * 100 / $course['progress']['total_lessons']);
            }
        }
        return $course;
    }
    public function getList () 
    {
        $result = [];
        $courses = $this->get()->getResultArray();
        foreach($courses as &$course){
            $course['image'] = base_url('image/index.php'.$course['image']);
            $course['background_image'] = base_url('image/index.php'.$course['background_image']);
            $course['progress'] = $this->getProgress($course['id']);
            if($course['progress']['total_lessons'] == 0){
                continue;
            }
            $course['progress']['percentage'] = ceil($course['progress']['total_exercises'] * 100 / $course['progress']['total_lessons']);
            $course['is_active'] = session()->get('user_data')['settings']['courseId']['value'] == $course['id'];
            $result[] = $course;
        }
        return $result;
    }
    private function getProgress($course_id)
    {
        $progress = $this->join('lessons', 'lessons.course_id = courses.id')
        ->join('exercises', 'exercises.lesson_id = lessons.id AND exercises.user_id = '.session()->get('user_id'), 'left')
        ->select("COALESCE(sum(exercises.points), 0) as total_points, COALESCE(COUNT(exercises.points), 0) as total_exercises, COALESCE(COUNT(lessons.id), 0) as total_lessons")
        ->where('courses.id', $course_id)
        ->get()->getRowArray();

        return $progress;
    }
    public function linkItem ($data) 
    {
        $SettingsModel = model('SettingsModel');
        return $SettingsModel->updateUserItem(session()->get('user_id'), ['code' => 'courseId', 'value' => $data['course_id']]);
    }
}