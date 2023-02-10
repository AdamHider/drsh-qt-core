<?php

namespace App\Models;

use CodeIgniter\Model;

class ClassroomModel extends Model
{
    protected $table      = 'classrooms';
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

    public function getItem ($classroom_id) 
    {
        if ($classroom_id == 0) {
            return 'not_found';
        }
        $DescriptionModel = model('DescriptionModel');
        $classroom = $this->where('id', $classroom_id)->get()->getRow();
        if ($classroom) {
            $classroom->description = $DescriptionModel->getItem('classroom', $classroom->id);
            $classroom->image = base_url('image/' . $classroom->image);
            $classroom->background_image = base_url('image/' . $classroom->background_image);
        } else {
            return 'not_found';
        }
        return $classroom;
    }
    public function getList ($data) 
    {
        $DescriptionModel = model('DescriptionModel');
        
        if($data['user_id']){
            $this->join('user_classrooms', 'user_classrooms.classroom_id = classrooms.id')
            ->where('user_classrooms.user_id', $data['user_id']);
        }
        $classrooms = $this->get()->getResult();
        foreach($classrooms as &$classroom){
            $classroom->description = $DescriptionModel->getItem('classroom', $classroom->id);
            $classroom->image = base_url('image/' . $classroom->image);
            $classroom->background_image = base_url('image/' . $classroom->background_image);
            $classroom->is_active = session()->get('user_data')->profile->classroom_id == $classroom->id;
        }
        return $classrooms;
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
    public function checkIfExists($code)
    {
        $classroom = $this->where('code', $code)->get()->getRow();
        return $classroom && $classroom->id;
    }
    


}