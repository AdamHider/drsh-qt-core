<?php
namespace App\Models;
use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;
class QuestGroupModel extends Model
{
    protected $table      = 'quest_groups';
    protected $primaryKey = 'id';

    public function getItem ($group_id) 
    {
        $DescriptionModel = model('DescriptionModel');
        $group = $this->where('quest_groups.id', $group_id)->get()->getRowArray();
        if ($group) {
            $group = array_merge($group, $DescriptionModel->getItem('quest_group', $group['id']));
            $group['image_avatar'] = base_url('image/index.php'.$group['image_avatar'] ?? '');
            $group['image_full'] = base_url('image/index.php'.$group['image_full'] ?? '');
            $group['is_primary'] = (bool) $group['is_primary'];
        }
        return $group;
    }
}