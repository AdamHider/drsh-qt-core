<?php 

namespace App\Controllers\Admin;

use App\Models\LessonModel;
use App\Models\CourseSectionModel;
use App\Models\CourseModel;
use App\Models\ResourceModel;
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
        $ResourceModel = new ResourceModel();
        $CourseSectionModel = new CourseSectionModel();
        $LessonModel = new LessonModel();
        $data['settings'] = [
            'layout' => 'admin',
            'title' => 'Edit Lesson',
            'path' => '/admin/lessons'
        ];
        $data['lesson'] = $id ? $LessonModel->find($id) : null;
        if(empty($data['lesson'])){
            $data['lesson'] = [
                'course_id'         => null, 
                'course_section_id' => null, 
                'language_id'       => null, 
                'title'             => 'Новый урок', 
                'description'       => '', 
                'type'              => 'common', 
                'pages'             => '[]', 
                'cost_config'       => '{}', 
                'reward_config'     => '{}', 
                'image'             => '', 
                'published'         => false, 
                'parent_id'         => null, 
                'unblock_after'     => null, 
                'is_private'        => false
            ];
        }
        $data['languages'] = $LanguageModel->findAll();
        $data['courses'] = $CourseModel->findAll();
        $data['resources'] = $ResourceModel->findAll();
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
            if (!$model->save($lessonData)) {
                return redirect()->back()->withInput()->with('errors', $model->errors());
            }
        }
        return redirect()->back()->withInput()->with('status', 'Lesson saved successfully');
    }

    public function delete($id)
    {
        $model = new LessonModel();
        $model->delete($id);
        return redirect()->to('/admin/lessons');
    }

}
?>
