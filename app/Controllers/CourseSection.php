<?php

namespace App\Controllers;

use CodeIgniter\API\ResponseTrait;
class CourseSection extends BaseController
{
    use ResponseTrait;
    
    public function getList()
    {
        $UserModel = model('UserModel');
        $result = $UserModel->getList();
        return $this->respond($result, 200);
    }

}
