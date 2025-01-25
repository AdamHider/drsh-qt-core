<?php
namespace App\Models;
use CodeIgniter\Model;
class LessonUnblockUsermapModel extends Model
{
    protected $table      = 'lesson_unblock_usermap';
    protected $primaryKey = 'item_id';
    protected $allowedFields = [
        'item_id', 
        'user_id'
    ];

    public function checkBlocked ($lesson_id, $unblock_after, $mode = 'single') 
    {
        $LessonModel = model('LessonModel');
        $result = true;
        
        if (!$unblock_after) {
            return false;
        }

        if($mode == 'group'){
            $lessons = $LessonModel->join('lesson_unblock_usermap', 'lesson_unblock_usermap.item_id = lessons.id AND AND user_id ='.session()->get('user_id'), 'left')
            ->where('lessons.published', 1)->where('(lessons.parent_id = '. $lesson_id.' OR lessons.id = '.$lesson_id.')')
            ->having('lessons.unblock_after IS NULL OR lesson_unblock_usermap.item_id IS NOT NULL')->get()->getResultArray();
            $result = empty($lessons);
        } else {
            $result = $this->where('item_id = '.$lesson_id.' AND user_id ='.session()->get('user_id'))->get()->getResult();
            $result = empty($result);
        }
        
        return $result;
    }
    public function unblockNext ($lesson_id) 
    {
        $LessonModel = model('LessonModel');
        $data = $LessonModel->where('unblock_after', $lesson_id)->select('id as item_id, '.session()->get('user_id').' as user_id')->get()->getResultArray();
        return $this->ignore(true)->insertBatch($data);
    }
    
}