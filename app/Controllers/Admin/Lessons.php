<?php 

namespace App\Controllers\Admin;

use App\Models\LessonModel;
use App\Models\CourseSectionModel;
use App\Models\CourseModel;
use App\Models\LanguageModel;
use CodeIgniter\Controller;

class Lessons extends Controller
{
    public function index()
    {
        $data['settings'] = [
            'layout' => 'admin',
            'title' => 'Lessons',
            'path' => '/admin/lessons'
        ];
        $model = new LessonModel();
        $data['lessons'] = $model->findAll();
        return view('admin/lessons/index', $data);
    }


    public function form($id = null)
    {
        $LanguageModel = new LanguageModel();
        $CourseModel = new CourseModel();
        $CourseSectionModel = new CourseSectionModel();
        $LessonModel = new LessonModel();
        $data['settings'] = [
            'layout' => 'admin',
            'title' => 'Edit Lesson',
            'path' => '/admin/lessons'
        ];
        $data['languages'] = $LanguageModel->findAll();
        $data['lesson'] = $id ? $LessonModel->find($id) : null;
        $data['courses'] = $CourseModel->findAll();
        $lessons = $LessonModel->orderBy('created_at', 'ASC')->findAll();
        $data['parent_lessons'] = array_filter($lessons, function($p) use ($id) {
            return $p['id'] !== $id && !$p['parent_id'] && $p['type'] == 'common';
        });
        $data['unblock_lessons'] = array_filter($lessons, function($p) use ($id) {
            return $p['id'] !== $id;
        });
        $data['course_sections'] = $CourseSectionModel->findAll();
        return view('admin/lessons/form', $data);
    }

    public function save($id = null)
    {
        $model = new LessonModel();
        $lessonData = $this->request->getPost();
        if ($id) {
            $model->update($id, $lessonData);
        } else {
            $model->save($lessonData);
        }

        return redirect()->to('/admin/lessons');
    }

    public function delete($id)
    {
        $model = new LessonModel();
        $model->delete($id);
        return redirect()->to('/admin/lessons');
    }

}
?>
