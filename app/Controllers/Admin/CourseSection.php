<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

use CodeIgniter\API\ResponseTrait;
class CourseSection extends BaseController
{
    use ResponseTrait;
    public function getItem()
    {
        
        $UserModel = model('UserModel');

        $user_id = $this->request->getVar('user_id');

        if( !$user_id ){
            $user_id = session()->get('user_id');
        }

        $user = $UserModel->getItem($user_id);

        if ($user == 'not_found') {
            return $this->failNotFound('not_found');
        }

        return $this->respond($user);
    }
    public function getList()
    {
        $CourseSectionModel = model('Admin/CourseSectionAdminModel');

        $filter = $this->request->getJSON(true);

        $result = $CourseSectionModel->getList($filter);
        return $this->respond($result, 200);
    }

}
