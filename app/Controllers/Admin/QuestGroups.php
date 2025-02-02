<?php 

namespace App\Controllers\Admin;

use App\Models\QuestGroupModel;
use App\Models\QuestModel;
use App\Models\LanguageModel;
use CodeIgniter\Controller;

class QuestGroups extends Controller
{
    public function index()
    {
        $QuestGroup = new QuestGroupModel();
        $sectionModel = new QuestModel();
        $Description = new DescriptionModel();
        $quest_groups = $QuestGroup->findAll();

        $data['settings'] = [
            'layout' => 'admin',
            'title' => 'Quests',
            'path' => '/admin/quest_groups'
        ];
        /*
        foreach ($quest_groups as &$quest_group) {
            $quest_group['sections'] = $sectionModel->where('course_id', $course['id'])->findAll();
        }*/

        $data['quest_groups'] = $quest_groups;
        return view('admin/quest_groups/index', $data);
    }

    public function form($id = null)
    {
        $LanguageModel = new LanguageModel();
        $model = new QuestGroupModel();
        $data['settings'] = [
            'layout' => 'admin',
            'title' => 'Quests',
            'path' => '/admin/courses'
        ];
        $data['languages'] = $LanguageModel->findAll();
        $data['course'] = $id ? $model->find($id) : null;
        return view('admin/quest_groups/form', $data);
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
