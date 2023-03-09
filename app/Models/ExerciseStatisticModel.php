<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;
use App\Libraries\DateProcessor;


class ExerciseStatisticModel extends Model
{
    use PermissionTrait;
    protected $table      = 'exercise_statistic';

    public $limit = 6;
    public $chart_colors = [
        '#4dc9f6',
        '#f67019',
        '#f53794',
        '#537bc4',
        '#acc236',
        '#166a8f',
        '#00a950',
        '#58595b',
        '#8549ba'
    ];
    public function getLeaderboard($mode, $data)
    {
        $this->createTempView($data);
        $result = [];
        if($mode == 'table'){
            $result = $this->getTable($data);
        }
        if($mode == 'chart'){
            $result = $this->getChart($data);
        }
        if(empty($result)){
            return false;
        }
        return $result;
    } 
    public function getTable($data)
    {
        
        $user_row = $this->where('user_id', session()->get('user_id'))->get()->getRowArray();
        $offset = ceil($this->limit/2);
        if(empty($user_row['place'])){
            $user_row = [
                'place' => 0
            ];
        }
        if($data['classroom_id']){
            $this->useSharedOf('classrooms', 'classroom_id');
        }
        $result = $this->select('place, COALESCE(points, 0) as points, GROUP_CONCAT(is_active) as is_active, MIN(finished_at) as finished_at, is_winner')
        ->where("place BETWEEN '".$user_row['place'] - $offset."' AND '".$user_row['place'] + $offset."'")->whereHasPermission('r')
        ->groupBy('place, points, is_winner')->get()->getResultArray();
        
        
        foreach($result as &$row){
            $row['data'] = $this->where("place", $row['place'])->get()->getResultArray();
            $row['is_active'] = (bool) $row['is_active'];
            $row['is_winner'] = (bool) $row['is_winner'];
            if($row['finished_at']){
                $row['finished_at_humanized'] = Time::parse($row['finished_at'], Time::now()->getTimezone())->toLocalizedString('d MMM yyyy');
            }
            foreach($row['data'] as &$user){
                $user['avatar'] = base_url('image/' . $user['avatar']);
            }
        }
        return $result;
    }
    public function getChart($data)
    {
        $DateProcessor = new DateProcessor();
        $user_row = $this->where('user_id', session()->get('user_id'))->get()->getRowArray();
        if(empty($user_row)){
            $user_row = [
                'place' => 0
            ];
        }
        $min_date = $this->select('COALESCE(MIN(created_at), DATE_SUB(NOW(),INTERVAL 1 YEAR)) as min_date')->get()->getRow()->min_date;
        $max_date = $this->select('COALESCE(MAX(finished_at), NOW()) as max_date')->get()->getRow()->max_date;

        $offset = ceil($this->limit/2);
        if($data['classroom_id']){
            $this->useSharedOf('classrooms', 'classroom_id');
        }
        $list = $this->where("place BETWEEN '".$user_row['place'] - $offset."' AND '".$user_row['place'] + $offset."'")->whereHasPermission('r')->get()->getResultArray();
        if(empty($list)){
            return false;
        }
        $dates = $DateProcessor->getDates($data, $min_date, $max_date, 5);
        $result = [
            'data' => [],
            'labels' => $dates['labels']
        ];
        foreach($list as $index => $row){
            $student_row = [
                'name' => $row['username'],
            ];
            $start_date = $dates['start_dates'][0];
            foreach($dates['start_dates'] as $date_key => $date){
                $student_row['data'][] = $this->from('exercises')->where('exercises.user_id', $row['user_id'])
                ->where("exercises.finished_at >= '".$start_date."'") 
                ->where("exercises.finished_at <= '".$dates['end_dates'][$date_key]."'")
                ->select('COALESCE(SUM(exercises.points), 0) as total_points')->get()->getRow()->total_points;
            }
            $result['data'][] = $student_row;
        }
        return $result;
    }

    public function checkUserPlace($data)
    {
        $this->createTempView($data);
        $result = $this->where('user_id', session()->get('user_id'))->get()->getRowArray();
        $result['avatar'] = base_url('image/' . $result['avatar']);
        return $result;
    } 

    
    public function createTempView($data){
        $this->query("DROP TABLE IF EXISTS exercise_statistic");

        $is_private = 0;
        /* CLASSROOM FILTER SECTION */
        $classroom_filter = "";
        $classroom_id = "0";
        if(isset($data['classroom_id']) && $data['classroom_id']){
            $ClassroomModel = model('ClassroomModel');
            $classroom_filter = " JOIN classrooms_usermap ON users.id = classrooms_usermap.user_id AND classrooms_usermap.item_id = '".$data['classroom_id']."' ";
            $classroom_id = $data['classroom_id'];
            $is_private = $ClassroomModel->where('id', $data['classroom_id'])->get()->getRow('is_private');
        }
        /* CLASSROOM FILTER SECTION END */

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
        /* DATE FILTER SECTION END */

        if(isset($data['date_start'])){
            $date_filter .= " AND exercises.finished_at > '".$data['date_start']."'";
        }
        if(isset($data['date_end'])){
            $date_filter .= " AND exercises.finished_at < '".$data['date_end']."'";
        }
        
        /* ORDER BY SECTION */
        $place_counter_condition = "rating.points != @points";
        $order_by = "rating.points DESC, rating.finished_at DESC";
        if(isset($data['order_by'])){
            if($data['order_by'] == 'finished_at'){
                $order_by = "IF(rating.created_at, 1, 0) DESC, rating.finished_at ASC, rating.points DESC";
                $place_counter_condition .= " OR rating.finished_at != @finished_at";
            }
        }
        /* ORDER BY SECTION END */

        /* WINNER LIMIT SECTION */
        $winner_limit = 3;
        if(isset($data['winner_limit'])){
            $winner_limit = $data['winner_limit'];
        }
        /* WINNER LIMIT SECTION END */

        $this->query("SET @place=0");
        $this->query("SET @points=0");
        $this->query("SET @finished_at='0000-00-00 00:00:00'");
        $this->query("SET @winner_limit=".$winner_limit);
        
        $sql = "
            CREATE TEMPORARY TABLE IF NOT EXISTS exercise_statistic
            SELECT 
                IF($place_counter_condition, @place:=@place + 1, @place) AS place,
                @points:=rating.points as points,
                rating.user_id,
                rating.username,
                rating.avatar,
                rating.created_at,
                @finished_at:=rating.finished_at as finished_at,
                0 as owner_id,
                rating.is_active,
                $is_private  as is_private,
                IF(@winner_limit > 0 AND rating.points > 0, 1, 0) as is_winner,
                @winner_limit:=@winner_limit - 1 as winner_limit,
                $classroom_id as classroom_id
            FROM (
                SELECT 
                    users.id as user_id, 
                    users.username,
                    (SELECT characters.avatar FROM characters JOIN user_settings ON users.id = user_settings.user_id) AS avatar,
                    COALESCE(SUM(exercises.points), 0) as points,
                    MIN(exercises.created_at) AS created_at,
                    MAX(exercises.finished_at) AS finished_at,
                    IF('".session()->get('user_id')."' = users.id, 1, 0) AS is_active
                FROM
                    users 
                    $classroom_filter
                        LEFT JOIN
                    exercises ON exercises.user_id = users.id 
                        AND exercises.finished_at IS NOT NULL 
                        $lesson_filter 
                        $date_filter  
                GROUP BY users.id) rating
                ORDER BY
                    $order_by,
                    rating.is_active DESC, 
                    rating.username ASC
                
        ";
        return $this->query($sql);
    }
    
    
    
}