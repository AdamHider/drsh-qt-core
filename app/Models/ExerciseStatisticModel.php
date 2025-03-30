<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;
use App\Libraries\DateProcessor;


class ExerciseStatisticModel extends Model
{
    use PermissionTrait;
    protected $table      = 'exercise_statistic';

    public $limit = 10;
    public function getLeaderboard($data)
    {
        $SettingsModel = model('SettingsModel');

        $this->createTempView($data);

        $user_row = $this->where('user_id', session()->get('user_id'))->get()->getRowArray();
        if(empty($user_row['place'])) $user_row = [ 'place' => 0 ];

        $rows = $this->where("place BETWEEN ".($user_row['place']-5)." AND ".($user_row['place']+5))->limit(10)->get()->getResultArray();
        
        foreach($rows as &$row){
            $character = $SettingsModel->join('settings_usermap', 'settings_usermap.item_id = settings.id', 'left')
            ->join('characters', 'characters.id = settings_usermap.value', 'left')->where('settings.code = "characterId" AND settings_usermap.user_id = '.$row['user_id'])->select('characters.*')->get()->getRowArray();
            if(!empty($character)){
                $row['image'] = base_url('image/index.php'.$character['image']);
                $row['is_active'] = (bool) $row['is_active'];
            }
        }
        return $rows;
    }
    
    public function createTempView($data){
        $this->query("DROP TABLE IF EXISTS exercise_statistic");
        /* LESSON FILTER SECTION */
        $lesson_filter = "";
        if(isset($data['lesson_id'])){
            $lesson_filter = " AND exercises.lesson_id = '".$data['lesson_id']."' ";
        }
        /* LESSON FILTER SECTION END */
        
        /* DATE FILTER SECTION */
        $date_filter = "";
        if(isset($data['time_period'])){
            if($data['time_period'] == 'week'){
                $date_filter .= " AND exercises.finished_at > '".date('Y-m-d H:i.s', strtotime('-1 week'))."'";
            } else if($data['time_period'] == 'month'){
                $date_filter .= " AND exercises.finished_at > '".date('Y-m-d H:i.s', strtotime('-1 month'))."'";
            }
        }
        if(isset($data['date_start'])){
            $date_filter .= " AND exercises.finished_at > '".$data['date_start']."'";
        }
        if(isset($data['date_end'])){
            $date_filter .= " AND exercises.finished_at < '".$data['date_end']."'";
        }
        /* DATE FILTER SECTION END */
        
        /* ORDER BY SECTION */
        $order_by = "rating.points DESC, rating.finished_at DESC";
        /* ORDER BY SECTION END */

        $this->query("SET @place=0");
        $sql = "
            CREATE TEMPORARY TABLE IF NOT EXISTS exercise_statistic
            SELECT 
                @place:=@place + 1 AS place,
                rating.points,
                rating.user_id,
                rating.name,
                rating.created_at,
                rating.finished_at,
                rating.is_active
            FROM (
                SELECT 
                    users.id as user_id,
                    COALESCE(users.name, users.username) as name,
                    COALESCE(SUM(exercises.points), 0) as points,
                    MIN(exercises.created_at) AS created_at,
                    MAX(exercises.finished_at) AS finished_at,
                    IF('".session()->get('user_id')."' = users.id, 1, 0) AS is_active
                FROM
                    users 
                        LEFT JOIN
                    exercises ON exercises.user_id = users.id 
                        AND exercises.finished_at IS NOT NULL 
                        $lesson_filter 
                        $date_filter  
                GROUP BY users.id) rating
                ORDER BY
                    $order_by,
                    rating.is_active DESC, 
                    rating.name ASC
                
        ";
        return $this->query($sql);
    }
    
    
    
}