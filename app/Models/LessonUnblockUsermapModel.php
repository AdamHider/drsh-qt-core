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

    public function checkBlocked ($lesson_id, $unblock_config, $mode = 'single') 
    {
        $LessonModel = model('LessonModel');
        $result = true;
        
        if (!$unblock_config) {
            return false;
        }
        if($mode == 'group'){
            $lessons = $LessonModel->join('lesson_unblock_usermap', 'lesson_unblock_usermap.item_id = lessons.id AND AND user_id ='.session()->get('user_id'), 'left')
            ->where('lessons.published', 1)->where('(lessons.parent_id = '. $lesson_id.' OR lessons.id = '.$lesson_id.')')
            ->having('lessons.unblock_config IS NULL OR lesson_unblock_usermap.item_id IS NOT NULL')->get()->getResultArray();
            $result = empty($lessons);
        } else {
            $result = $this->where('item_id = '.$lesson_id.' AND user_id ='.session()->get('user_id'))->get()->getResult();
            $result = empty($result);
        }
        
        return $result;
    }
    public function unblockNext ($code, $item_id) 
    {
        $LessonModel = model('LessonModel');
        $SkillModel = model('SkillModel');
        $lessons = $LessonModel->where('JSON_CONTAINS(JSON_EXTRACT(unblock_config, "$.'.$code.'"),"'.$item_id.'","$")')->get()->getResultArray();
       
        foreach($lessons as $lesson){
            $unblock_config = json_decode($lesson['unblock_config'], true);
            if(!empty($unblock_config['lessons'])){
                $total_lessons = $LessonModel->join('lesson_unblock_usermap', 'lesson_unblock_usermap.item_id = lessons.id AND lesson_unblock_usermap.user_id = '.session()->get('user_id'))
                ->whereIn('lessons.id', $unblock_config['lessons'])->get()->getNumRows();
            } else {
                $unblock_config['lessons'] = [];
                $total_lessons = 0;
            }
            
            if(!empty($unblock_config['lessons'])){
                $total_skills = $SkillModel->join('skills_usermap', 'skills_usermap.item_id = skills.id AND skills_usermap.user_id = '.session()->get('user_id'))
                ->whereIn('skills.id', $unblock_config['skills'])->get()->getNumRows();
            } else {
                $unblock_config['skills'] = [];
                $total_skills = 0;
            }

            if(count($unblock_config['lessons']) == $total_lessons && count($unblock_config['skills']) == $total_skills){
                $data[] = [
                    'item_id' => $lesson['id'],
                    'user_id' => session()->get('user_id')
                ];
            }
        }
        if(!empty($data)){
            return $this->ignore(true)->insertBatch($data);
        }
        return false;
    }
    
}