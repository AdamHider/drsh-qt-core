<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;
use App\Libraries\DateProcessor;


class ExerciseStatisticModel extends Model
{
    protected $table      = 'exercises_rating';

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
            return 'not_found';
        }
        return $result;
    } 
    public function getTable($data)
    {
        $result = $this->select('place, GROUP_CONCAT(username) as `usernames`, COUNT(user_id) as total_students, points, COALESCE(is_active) as is_active')
        ->groupBy('place, username, user_id, points, is_active')->limit(5)->get()->getResultArray();

        foreach($result as &$row){
            if($row['total_students'] > 3){
                $usernames = explode(',', $row['usernames']);
                $row['usernames'] = implode(', ', [$usernames[0], $usernames[1], $usernames[2]]);
                $row['need_more'] = true;
                $row['need_more_total'] = $row['total_students'] - 3;
            }
            if(isset($row['finished_at'])){
                $finished_at = Time::parse($row['finished_at'], Time::now()->getTimezone());
                $row['finished_at'] = $finished_at->humanize();
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
        $list = $this->where("place BETWEEN '".$user_row['place'] - $offset."' AND '".$user_row['place'] + $offset."'")->get()->getResultArray();;

        $dates = $DateProcessor->getDates($data, $min_date, $max_date, 5);
        $result = [
            'data' => [],
            'labels' => $dates['labels']
        ];
        foreach($list as $index => $row){
            $student_row = [
                'name' => $row['username'],
                'fill' => false,
                'backgroundColor' => $this->chart_colors[$index],
                'borderColor' => $this->chart_colors[$index],
                'borderWidth' => 2,
                'tension' => '0',
                'data' => [],
                'animations' => ['y'=> ['duration' => 1500, 'delay' => 200]]
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
    
    public function createTempView($data){
        $this->query("SET @place=0");

        /* CLASSROOM FILTER SECTION */
        $classroom_filter = "";
        if(isset($data['by_classroom']) && $data['by_classroom']){
            $classroom_filter = " JOIN user_classrooms ON users.id = user_classrooms.user_id AND user_classrooms.classroom_id = '".session()->get('user_data')->profile->classroom_id."' ";
        }
        /* CLASSROOM FILTER SECTION END */
        
        /* DATE FILTER SECTION */
        $date_filter = " 1 ";
        if(isset($data['time_period'])){
            if($data['time_period'] == 'week'){
                $date_filter .= " AND exercises.finished_at > '".date('Y-m-d H:i.s', strtotime('-1 week'))."'";
            } else if($data['time_period'] == 'month'){
                $date_filter .= " AND exercises.finished_at > '".date('Y-m-d H:i.s', strtotime('-1 month'))."'";
            }
        }
        /* DATE FILTER SECTION END */

        if(isset($data['date_from'])){
            $date_filter .= " AND exercises.finished_at > '".$data['date_from']."'";
        }
        if(isset($data['date_to'])){
            $date_filter .= " AND exercises.finished_at < '".$data['date_to']."'";
        }
        /* ORDER BY SECTION */
        $order_by = "rating.points DESC, rating.finished_at DESC";
        if(isset($data['order_by'])){
            if($data['order_by'] == 'finished_at'){
                $order_by = "rating.finished_at ASC, rating.points DESC";
            }
        }
        /* ORDER BY SECTION END */
        $sql = "
            CREATE TEMPORARY TABLE IF NOT EXISTS exercises_rating
            SELECT 
                @place:=@place + 1 AS place,
                rating.*
            FROM (
                SELECT 
                    exercises.user_id, 
                    users.username,
                    SUM(exercises.points) as points,
                    MIN(exercises.created_at) AS created_at,
                    MAX(exercises.finished_at) AS finished_at,
                    IF('".session()->get('user_id')."' = users.id, 1, 0) AS is_active
                FROM
                    users 
                    $classroom_filter
                        LEFT JOIN
                    exercises ON exercises.user_id = users.id
                WHERE
                    $date_filter AND exercises.finished_at IS NOT NULL
                GROUP BY users.id) rating
                ORDER BY
                    $order_by,
                    rating.is_active DESC, 
                    rating.username ASC
                
        ";
        return $this->query($sql);
    }
    
    
    
}