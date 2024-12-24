<?php 

namespace App\Controllers\Admin;

use App\Models\LessonModel;
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
        $data['settings'] = [
            'layout' => 'admin',
            'title' => 'Edit Lesson',
            'path' => '/admin/lessons'
        ];
        $model = new LessonModel();
        $data['lesson'] = $id ? $model->find($id) : null;
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
