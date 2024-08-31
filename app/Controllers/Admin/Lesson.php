<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\API\ResponseTrait;
class Lesson extends BaseController
{
    use ResponseTrait;

    public function getItem()
    {
        $LessonModel = model('Admin/LessonAdminModel');

        $lesson_id = $this->request->getVar('lesson_id');
        
        if (!$lesson_id) {
            return $this->failNotFound('not_found');
        }
        $lesson = $LessonModel->getItem($lesson_id);
        
        if (!$lesson) {
            return $this->failNotFound('not_found');
        }
        if ($lesson == 'forbidden') {
            return $this->failForbidden();
        }
        return $this->respond($lesson);
    }
    public function getList()
    {
        $LessonModel = model('Admin/LessonAdminModel');

        $limit = $this->request->getVar('limit');
        $offset = $this->request->getVar('offset');

        $data = [
            'limit' => $limit,
            'offset' => $offset
        ];

        $result = $LessonModel->getList($data);
        return $this->respond($result, 200);
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
    public function saveItem()
    {
        $LessonAdminModel = model('Admin/LessonAdminModel');
        $data = $this->request->getJSON(true);

        $result = $LessonAdminModel->updateItem($data);

        if ($result === 'forbidden') {
            return $this->failForbidden();
        }
        if($LessonAdminModel->errors()){
            return $this->failValidationErrors($LessonAdminModel->errors());
        }
        return $this->respond($result);
    }
}
