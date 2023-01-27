<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
use App\Models\LessonModel;
class Lesson extends BaseController
{
    use ResponseTrait;

    public function index()
    {
        echo "Lesson Introduction";
        //return view('welcome_message');
    }
    public function getItem()
    {
        $LessonModel = new LessonModel();
        $result = $LessonModel->getItem(1);
        return $this->respond($result, 200);
    }
    public function getList()
    {
        $LessonModel = new LessonModel();
        $result = $LessonModel->getList();
        return $this->respond($result, 200);
    }
}
