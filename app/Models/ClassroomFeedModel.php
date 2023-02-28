<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class ClassroomFeedModel extends ClassroomModel
{
    use PermissionTrait;
    protected $table      = 'classroom_feed';
    protected $primaryKey = 'id';

    public function getFeed($data)
    {
        $ChallengeModel = model('ChallengeModel');
        $HomeworkModel = model('HomeworkModel');
        $this->createTempView();
        $result = $this->whereHasPermission('r')
        ->limit($data['limit'], $data['offset'])->get()->getResultArray();
        foreach($result as &$feed_item){
            if($feed_item['code'] == 'homework'){
                $feed_item['title'] = lang('App.classroom.feed.homework.title');
            } else
            if($feed_item['code'] == 'challenge'){
                $feed_item['title'] = lang('App.classroom.feed.challenge.title');
            } 
            $feed_item['classroom_image'] = base_url('image/' . $feed_item['classroom_image']);
            $date_start = Time::parse($feed_item['date_start'], Time::now()->getTimezone());
            $feed_item['date_start_humanized'] = $date_start->humanize();
        }
        return $result;
    } 
    
    public function createTempView(){
        $this->query("DROP TABLE IF EXISTS classroom_feed");

        /* HOMEWORKS QUERY SECTION */
        $homeworks_query = "
            SELECT 
                homeworks.id as item_id, 
                'homework' as code, 
                homeworks.date_start,
                homeworks.date_start as order_by,
                classrooms.id as classroom_id, 
                classrooms.title as classroom_title,
                classrooms.image as classroom_image
            FROM 
                homeworks 
                    JOIN 
                classrooms ON classrooms.id = homeworks.classroom_id 
                    JOIN 
                classrooms_usermap ON classrooms_usermap.item_id = classrooms.id 
            WHERE 
                user_id = ".session()->get('user_id')."
        ";
        /* HOMEWORKS QUERY SECTION END */
        
        /* CHALLENGES QUERY SECTION */
        $challenges_query = "
            SELECT 
                challenges.id as item_id, 
                'challenge' as code,
                challenges.date_start,
                challenges.date_start as order_by,
                classrooms.id as classroom_id, 
                classrooms.title as classroom_title,
                classrooms.image as classroom_image
            FROM 
                challenges 
                    JOIN 
                classrooms ON classrooms.id = challenges.classroom_id 
                    JOIN 
                classrooms_usermap ON classrooms_usermap.item_id = classrooms.id 
            WHERE 
                user_id = ".session()->get('user_id')."
        ";
        /* CHALLENGES QUERY SECTION END */
        
        $sql = "
            CREATE TEMPORARY TABLE IF NOT EXISTS classroom_feed
            SELECT 
                feed.*,
                0 as owner_id,
                '0' as is_private
            FROM 
            (
                $homeworks_query
                    UNION ALL
                $challenges_query
            ) feed
            ORDER BY feed.order_by DESC
        ";
        return $this->query($sql);
    }
    
    

}