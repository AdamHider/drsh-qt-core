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
            $course_id = session()->get('user_data')['settings']['courseId']['value'];
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
    public function linkItem()
    {
        
        $CourseModel = model('CourseModel');

        $course_id = $this->request->getVar('id');

        $data = [
            'course_id' => $course_id
        ];
        
        $result = $CourseModel->linkItem($data);

        if ($result === 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respondCreated(['result' => $result]);
    }

}
