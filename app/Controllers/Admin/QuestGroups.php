<?php 

namespace App\Controllers\Admin;

use App\Models\QuestGroupModel;
use App\Models\QuestModel;
use App\Models\LanguageModel;
use App\Models\DescriptionModel;
use CodeIgniter\Controller;

class QuestGroups extends Controller
{
    public function index()
    {
        $QuestGroupModel = new QuestGroupModel();
        $QuestModel = new QuestModel();
        $DescriptionModel = new DescriptionModel();
        $quest_groups = $QuestGroupModel->findAll();
        
        $data['settings'] = [
            'layout' => 'admin',
            'title' => 'Quests',
            'path' => '/admin/quest_groups'
        ];
        
        foreach ($quest_groups as &$quest_group) {
            $description = $DescriptionModel->where('code', 'quest_group')->where('item_id', $quest_group['id'])->where('language_id', 1)->get()->getRowArray();
            if(empty($description)){
                $description = [
                    'title' => '',
                    'description' => '',
                    'data' => [
                        'pages' => []
                    ]
                ];
            }
            $quest_group['description'] = $description;
            $quest_group['quests'] = $QuestModel->select('descriptions.title, descriptions.description, quests.id')
            ->join('descriptions', 'descriptions.code = "quest" AND  descriptions.item_id = quests.id AND descriptions.language_id = 1')->where('quests.group_id', $quest_group['id'])->get()->getResultArray();
        }

        $data['quest_groups'] = $quest_groups;
        return view('admin/quest_groups/index', $data);
    }

    public function form($id = null)
    {
        $LanguageModel = new LanguageModel();
        $QuestGroupModel = new QuestGroupModel();
        $DescriptionModel = new DescriptionModel();

        $data['settings'] = [
            'layout' => 'admin',
            'title' => 'Quests',
            'path' => '/admin/courses'
        ];
        $data['quest_group'] = $id ? $QuestGroupModel->find($id) : null;
        $description = [
            'title' => '',
            'description' => '',
            'data' => [
                'pages' => []
            ]
        ];
        $quest_groups = $QuestGroupModel->select('descriptions.title, quest_groups.id')
        ->join('descriptions', 'code = "quest_group" AND  item_id = quest_groups.id AND language_id = 1')->get()->getResultArray();
        if($id){
            $description = $DescriptionModel->where('code', 'quest_group')->where('item_id', $id)->where('language_id', 1)->get()->getRowArray();
        }
        $data['quest_group']['description'] = $description;
        $data['quest_group']['unblock_qeust_groups'] = $description;
        $data['unblock_quest_groups'] = array_filter($quest_groups, function($p) use ($id) {
            return $p['id'] !== $id;
        });
        return view('admin/quest_groups/form', $data);
    }

    public function save($id = null)
    {
        $QuestGroupModel = new QuestGroupModel();
        $DescriptionModel = new DescriptionModel();
        $data = $this->request->getPost();
        
        if ($id) {
            $QuestGroupModel->update($id, $data);
            $DescriptionModel->set([
                'title'         => $data['description']['title'],
                'description'   => $data['description']['description'],
                'data'          => $data['description']['data'] ?? null
            ])->where('item_id = '.$id.' AND language_id = 1 AND code = "quest_group"')->update();
        } else {
            $id = $QuestGroupModel->save($data);
            $DescriptionModel->set([
                'item_id'       => $QuestGroupModel->getInsertID(),
                'code'          => 'quest_group',
                'language_id'   => '1',
                'title'         => $data['description']['title'],
                'description'   => $data['description']['description'],
                'data'          => $data['description']['data'] ?? null
            ])->insert();
        }
        return redirect()->to('/admin/quest_groups');
    }

    public function delete($id)
    {
        $QuestGroupModel = new QuestGroupModel();
        $QuestGroupModel->delete($id);
        return redirect()->to('/admin/quest_groups');
    }

}
?>
