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

        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');

        $data = [
            'limit' => $limit,
            'offset' => $offset
        ];

        $result = $LessonModel->getList($data);
        return $this->respond($result, 200);
    }
}
