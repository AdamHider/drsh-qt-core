<?php
namespace App\Models;
use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;
class QuestGroupsModel extends Model
{
    protected $table      = 'quest_groups';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'code',
        'level',
        'image',
        'unclock_after'
    ];

    public function getItem ($group_id) 
    {
        $DescriptionModel = model('DescriptionModel');
        $group = $this->where('quest_groups.id', $group_id)->get()->getRowArray();
        if ($group) {
            $group = array_merge($group, $DescriptionModel->getItem('quest_group', $group['id']));
            $group['image'] = base_url('image/' . $group['image']);
        }
        return $group;
    }
    public function getActiveList()
    {
        $this->whereIn('quest_groups.id',  static function (BaseBuilder $builder) {
            $builder->from('quest_groups q')->join('quest_groups_usermap', 'q.id = quest_groups_usermap.item_id', 'left')
            ->join('quest_groups q1', 'q1.unblock_after = quest_groups_usermap.item_id', 'left')
            ->select('COALESCE(q1.id, q.id)')->orderBy('q.level');
        });
        return $this->get()->getResultArray();
    }
}