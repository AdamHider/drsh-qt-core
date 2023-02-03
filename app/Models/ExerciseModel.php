<?php

namespace App\Models;

use CodeIgniter\Model;

class ExerciseModel extends Model
{
    protected $table      = 'exercises';
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

    public function getItem ($exercise_id) 
    {
        $exercise = $this->where('id', $exercise_id)->get()->getRow();
        return $exercise;
    }
    public function getList () 
    {
        $DescriptionModel = model('DescriptionModel');
        $CourseSectionModel = model('CourseSectionModel');
        
        $lessons = $this->get()->getResult();
        foreach($lessons as &$lesson){
            $lesson->parent_description = $CourseSectionModel->getItem($lesson->course_section_id);
            $lesson->description = $DescriptionModel->getItem('course', $lesson->id);
            $lesson->image = base_url('image/' . $lesson->image);
            $lesson->background_image = base_url('image/' . $lesson->background_image);
        }
        return $lessons;
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