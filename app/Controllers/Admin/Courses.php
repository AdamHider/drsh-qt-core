<?php 

namespace App\Controllers\Admin;

use App\Models\CourseModel;
use App\Models\CourseSectionModel;
use App\Models\LanguageModel;
use CodeIgniter\Controller;

class Courses extends Controller
{
    public function index()
    {
        $courseModel = new CourseModel();
        $sectionModel = new CourseSectionModel();
        $courses = $courseModel->findAll();

        $data['settings'] = [
            'layout' => 'admin',
            'title' => 'Courses',
            'path' => '/admin/courses'
        ];
        foreach ($courses as &$course) {
            $course['sections'] = $sectionModel->where('course_id', $course['id'])->findAll();
        }

        $data['courses'] = $courses;
        return view('admin/courses/index', $data);
    }

    public function form($id = null)
    {
        $LanguageModel = new LanguageModel();
        $model = new CourseModel();
        $data['settings'] = [
            'layout' => 'admin',
            'title' => 'Courses',
            'path' => '/admin/courses'
        ];
        $data['languages'] = $LanguageModel->findAll();
        $data['course'] = $id ? $model->find($id) : null;
        return view('admin/courses/form', $data);
    }

    public function save($id = null)
    {
        $model = new CourseModel();
        $courseData = $this->request->getPost();
        
        if ($id) {
            $model->update($id, $courseData);
        } else {
            $model->save($courseData);
        }

        return redirect()->to('/admin/courses');
    }

    public function delete($id)
    {
        $model = new CourseModel();
        $model->delete($id);
        return redirect()->to('/admin/courses');
    }

}
?>
