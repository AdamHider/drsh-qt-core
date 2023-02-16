<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Lesson extends BaseController
{
    use ResponseTrait;

    public function getItem()
    {
        $LessonModel = model('LessonModel');

        $lesson_id = $this->request->getVar('lesson_id');
        
        $lesson = $LessonModel->getItem($lesson_id);
        
        if (!$lesson) {
            return $this->failNotFound('not_found');
        }
        return $this->respond($lesson);
    }
    public function getList()
    {
        $LessonModel = model('LessonModel');

        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');

        $data = [
            'limit' => $limit,
            'offset' => $offset
        ];

        $result = $LessonModel->getList($data);
        return $this->respond($result, 200);
    }
    public function getSatellites()
    {
        $LessonModel = model('LessonModel');

        $lesson_id = $this->request->getVar('lesson_id');
        
        $satellites = $LessonModel->getSatellites($lesson_id, 'full');
        
        if (empty($satellites)) {
            return $this->failNotFound('not_found');
        }
        return $this->respond($satellites);
    }
    public function getPage()
    {
        $LessonPageModel = model('LessonPageModel');

        $lesson_id = $this->request->getVar('lesson_id');
        $action = $this->request->getVar('action');
        
        $page = $LessonPageModel->getPage($lesson_id, $action);
        
        if (empty($page)) {
            return $this->failNotFound('not_found');
        }
        return $this->respond($page);
    }
}
