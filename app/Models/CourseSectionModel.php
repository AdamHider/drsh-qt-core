<?php

namespace App\Models;

use CodeIgniter\Model;

class CourseSectionModel extends Model
{
    protected $table      = 'course_sections';
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

    public function getItem ($course_section_id) 
    {
        if ($course_section_id == 0) {
            return 'not_found';
        }
        $course_section = $this->where('id', $course_section_id)->get()->getRowArray();
        if ($course_section) {
            $course_section['image'] = base_url('image/' . $course_section['image']);
            $course_section['background_image'] = base_url('image/' . $course_section['background_image']);
        } else {
            return 'not_found';
        }
        return $course_section;
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