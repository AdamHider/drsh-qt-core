<?php

namespace App\Models;

use CodeIgniter\Model;

class CourseModel extends Model
{
    protected $table      = 'courses';
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

    public function getItem ($character_id) 
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
        
        $courses = $this->get()->getResult();
        foreach($courses as &$course){
            $course->description = $DescriptionModel->getItem('course', $course->id);
            $course->image = base_url('image/' . $course->image);
            $course->background_image = base_url('image/' . $course->background_image);
        }
        return $courses;
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