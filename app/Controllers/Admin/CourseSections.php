<?php 

namespace App\Controllers\Admin;

use App\Models\CourseSectionModel;
use App\Models\CourseModel;
use App\Models\LanguageModel;
use CodeIgniter\Controller;

class CourseSections extends Controller
{
    public function index($courseId)
    {
        $model = new CourseSectionModel();
        $data['settings'] = [
            'layout' => 'admin',
            'title' => 'Courses',
            'path' => '/admin/courses'
        ];
        $data['sections'] = $model->where('course_id', $courseId)->findAll();
        $data['course_id'] = $courseId;
        return view('admin/course_sections/index', $data);
    }

    public function form($courseId, $id = null)
    {
        $LanguageModel = new LanguageModel();
        $CourseModel = new CourseModel();
        $model = new CourseSectionModel();
        $data['settings'] = [
            'layout' => 'admin',
            'title' => 'Courses',
            'path' => '/admin/courses'
        ];
        $data['section'] = $id ? $model->find($id) : null;
        $data['languages'] = $LanguageModel->findAll();
        $data['course_id'] = $courseId;
        $data['course'] = $courseId ? $CourseModel->find($courseId) : null;
        return view('admin/course_sections/form', $data);
    }

    public function save($courseId, $id = null)
    {
        $model = new CourseSectionModel();
        $sectionData = $this->request->getPost();
        $sectionData['course_id'] = $courseId;
        if ($id) {
            $model->update($id, $sectionData);
        } else {
            if (!$model->insert($sectionData)) {
                return redirect()->back()->withInput()->with('errors', $model->errors());
            }
            return redirect()->to('/admin/course_sections/form/'.$courseId.'/'. $model->insertID());
        }


        return redirect()->back()->withInput()->with('status', 'Course section saved successfully');
    }

    public function delete($courseId, $id)
    {
        $model = new CourseSectionModel();
        $model->delete($id);
        return redirect()->back();
    }
}
?>
