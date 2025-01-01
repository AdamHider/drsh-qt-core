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
        'course_id', 'title', 'description', 'background_image', 'background_gradient', 'language_id', 'published', 'is_private'
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
            $course_section['background_image'] = base_url($course_section['background_image']);
        } else {
            return 'not_found';
        }
        return $course_section;
    }
}