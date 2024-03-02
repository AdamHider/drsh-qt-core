<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class Course extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $CourseModel = model('CourseModel');

        $course_id = $this->request->getVar('course_id');

        if( !$course_id && session()->get('user_data')){
            $course_id = session()->get('user_data')['settings']['courseId'];
        }

        $result = $CourseModel->getItem($course_id);

        if ($result == 'forbidden') {
            return $this->failForbidden();
        }
        if (!$result) {
            return $this->failNotFound('not_found');
        }

        return $this->respond($result);
    }
    public function getList()
    {
        $CourseModel = model('CourseModel');
        $result = $CourseModel->getList();
        return $this->respond($result, 200);
    }

}
