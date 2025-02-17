<?php 

namespace App\Controllers\Admin;

use App\Models\QuestModel;
use App\Models\QuestGroupModel;
use App\Models\DescriptionModel;
use App\Models\LessonModel;
use App\Models\SkillModel;
use App\Models\ResourceModel;
use CodeIgniter\Controller;

class Quests extends Controller
{
    public function index($courseId)
    {
        $model = new CourseSectionModel();
        $data['settings'] = [
            'layout' => 'admin',
            'title' => 'Quest',
            'path' => '/admin/courses'
        ];
        $data['sections'] = $model->where('course_id', $courseId)->findAll();
        $data['course_id'] = $courseId;
        return view('admin/course_sections/index', $data);
    }

    public function form($questGroupId, $id = null)
    {
        $DescriptionModel = new DescriptionModel();
        $QuestModel = new QuestModel();
        $QuestGroupModel = new QuestGroupModel();
        $LessonModel = new LessonModel();
        $SkillModel = new SkillModel();
        $ResourceModel = new ResourceModel();
        $data['settings'] = [
            'layout' => 'admin',
            'title' => 'Quest',
            'path' => '/admin/quest_groups'
        ];
        $data['quest'] = $id ? $QuestModel->find($id) : null;
        
        $description = $DescriptionModel->where('code', 'quest')->where('item_id', $id)->where('language_id', 1)->get()->getRowArray();
        if(empty($description)){
            $description = [
                'title' => '',
                'description' => '',
                'data' => [
                    'pages' => []
                ]
            ];
        }
        $data['quest']['description'] = $description;
        $data['quest_group'] = $questGroupId ? $QuestGroupModel->find($questGroupId) : null;

        $data['lessons'] = $LessonModel->findAll();
        $data['skills'] = $SkillModel->select('descriptions.title, skills.id')->join('descriptions', 'descriptions.code = "skill" AND  item_id = skills.id AND language_id = 1')->findAll();
        $data['resources'] = $ResourceModel->select('descriptions.title, resources.id, resources.code')->join('descriptions', 'descriptions.code = "resource" AND  item_id = resources.id AND language_id = 1')->findAll();
        
        $quests = $QuestModel->select('descriptions.title, quests.id')
        ->join('descriptions', 'descriptions.code = "quest" AND  item_id = quests.id AND language_id = 1')->get()->getResultArray();

        $data['unblock_quests'] = array_filter($quests, function($p) use ($id) {
            return $p['id'] !== $id;
        });
        $data['quest_group_id'] = $questGroupId;
        return view('admin/quests/form', $data);
    }

    public function save($groupId, $id = null)
    {
        $model = new QuestModel();
        $questData = $this->request->getPost();
        $questData['group_id'] = $groupId;
        if ($id) {
            $model->update($id, $questData);
        } else {
            if (!$model->insert($questData)) {
                return redirect()->back()->withInput()->with('errors', $model->errors());
            }
            return redirect()->back()->withInput()->with('status', 'Course section saved successfully');
        }
        return redirect()->back()->withInput()->with('status', 'Course section saved successfully');
    }

    public function delete($courseId, $id)
    {
        $model = new QuestModel();
        $model->delete($id);
        return redirect()->back();
    }
}
?>
