<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;
use App\Libraries\DateProcessor;


class ExerciseStatisticModel extends ExerciseModel
{
    protected $table      = 'exercises_leaderboard';

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
        '#8549ba',
        '#f67019',
        '#f53794',
        '#537bc4',
        '#acc236',
        '#166a8f',
        '#00a950',
        '#58595b',
        '#8549ba'
    ];
    public function getLeaderboard($data)
    {
        $this->createTempView($data);
        
        $statistics = [
            'common_statistics' => $this->getCommonView($data),
            'chart_statistics' => $this->getChartView($data)
        ];
        return $statistics;
    } 
    public function getCommonView($data)
    {
        
        $groups = $this->table('exercises_leaderboard')
        ->select('place, GROUP_CONCAT(username) as `usernames`, COUNT(user_id) as total_students, points, COALESCE(is_active) as is_active')
        ->groupBy('place')->limit(5)->get()->getResultArray();
        $result = [
            'list' => $groups
        ];
        foreach($result['list'] as &$row){
            //$row['avatar_images'] = explode(',', $row['avatar_images']);
            if($row['total_students'] > 3){
                $usernames = explode(',', $row['usernames']);
                $row['usernames'] = implode(', ', [$usernames[0], $usernames[1], $usernames[2]]);
                //$row['avatar_images'] = [$row['avatar_images'][0], $row['avatar_images'][1], $row['avatar_images'][2]];
                $row['need_more'] = true;
                $row['need_more_total'] = $row['total_students'] - 3;
            }
            if(isset($row['date_finish'])){
                $date_finish = Time::parse($row['date_finish'], Time::now()->getTimezone());
                $row['date_finish'] = $date_finish->humanize();
            }
        }
        return $result;
    }
    public function getChartView($data)
    {
        $DateProcessor = new DateProcessor();
        $table = $this->getTable($data);
        $min_date = $this->select('COALESCE(MIN(date_start), DATE_SUB(NOW(),INTERVAL 1 YEAR)) as min_date')->get()->getRow()->min_date;
        $max_date = $this->select('COALESCE(MAX(date_finish), NOW()) as max_date')->get()->getRow()->max_date;
        $result = [
            'users' => [],
            'dates' => $DateProcessor->getDates($data, $min_date, $max_date, 5),
            'max_points' => $table['max_points']
        ];
        foreach($table['list'] as $index => $row){
            $student_row = [
                'label' => $row['username'],
                'fill' => false,
                'backgroundColor' => $this->chart_colors[$index],
                'borderColor' => $this->chart_colors[$index],
                'borderWidth' => 2,
                'tension' => '0',
                'data' => [],
                'animations' => ['y'=> ['duration' => 1500, 'delay' => 200]]
            ];
            $start_date = $result['dates']['start_dates'][0];
            foreach($result['dates']['start_dates'] as $date_key => $date){
                $student_row['data'][] = $this->from('exercises')->where('exercises.user_id', $row['user_id'])
                ->where("exercises.finished_at >= '".$start_date."'") 
                ->where("exercises.finished_at <= '".$result['dates']['end_dates'][$date_key]."'")
                ->select('COALESCE(SUM(exercises.points), 0) as total_points')->get()->getRow()->total_points;
            }
            $result['users'][] = $student_row;
        }
        return $result;
    }
    private function getTable($data)
    {
        $table = [];
        $user_row = $this->where('user_id', session()->get('user_id'))->get()->getRowArray();
        $max_points = $this->selectMax('points')->get()->getRow()->points;
        if($user_row['place'] > $this->limit + 1){
            $top_students = $this->where("place BETWEEN '0' AND '$this->limit' ")->get()->getResultArray();
            $table = array_merge($top_students, $user_row);
        } else {
            if($max_points > 0){
                $offset = ceil($this->limit/2);
                $table = $this->where("place BETWEEN '".$user_row['place'] - $offset."' AND '".$user_row['place'] + $offset."'")->get()->getResultArray();
            } else {
                $table = $this->where("place BETWEEN '0' AND '$this->limit'")->get()->getResultArray();
            }
        }
        if(!$user_row['place'] && session()->get('user_id')){
            $table[] = $user_row;
        }
        $result = [
            'list' => $table,
            'max_points' => $max_points
        ];
        return $result;
    }
    public function createTempView($data){
        $this->query("SET @rating=0");
        $this->query("SET @total_points=0");
        $where = " 1 ";
        $exercise_where = " 1 ";
        if(isset($data['classroom_id'])){
            if(session()->get('user_data')->profile->classroom_id !== $data['classroom_id']){
                return [];
            }
            //$where .= " AND student.classroom_id = '".$data['classroom_id']."'";
        }
        if(isset($data['date_from'])){
            if($data['date_from'] == 'week'){
                $exercise_where .= " AND stats.finished_at > '".date('Y-m-d Y H:i.s', strtotime('-1 week'))."'";
            } else if($data['date_from'] == 'month'){
                $exercise_where .= " AND stats.finished_at > '".date('Y-m-d Y H:i.s', strtotime('-1 month'))."'";
            }else if($data['date_from'] == 'all'){
                $exercise_where .= "";
            } else {
                $exercise_where .= " AND stats.finished_at > '".$data['date_from']."'";
            }
        }
        if(isset($data['date_to'])){
            $exercise_where .= " AND stats.finished_at < '".$data['date_to']."'";
        }
        /* ORDER BY SECTION */
        $order_by = "total_points";
        if(isset($data['order_by'])){
            $order_by = $data['order_by'];
        }
        /* ORDER BY SECTION END */
        $sql = "
            CREATE TEMPORARY TABLE IF NOT EXISTS exercises_leaderboard
            SELECT * FROM
            (SELECT 
                t.*,
                IF(t.total_points != @total_points,@rating:=@rating + 1, @rating) as place, 
                @total_points:=t.total_points as points
            FROM
                (SELECT 
                    users.id AS user_id,
                    users.username,
                    COALESCE(exercise.total_points, 0) AS total_points,
                    MIN(exercise.created_at) AS date_start,
                    MIN(exercise.date_finish) AS date_finish,
                    IF('".session()->get('user_id')."' = users.id, 1, 0) AS is_active
                FROM
                    users
                        LEFT JOIN
                    (SELECT 
                        exercises.user_id,
                        users.username,
                        SUM(exercises.points) AS total_points,
                        exercises.created_at,
                        MAX(exercises.finished_at) AS date_finish
                    FROM
                        exercises
                    JOIN users ON users.id = exercises.user_id
                    WHERE
                        $exercise_where AND exercises.finished_at IS NOT NULL
                    GROUP BY exercises.user_id) exercise ON users.id = exercise.user_id
                WHERE $where
                GROUP BY users.id
                ORDER BY $order_by DESC , is_active DESC , users.username ASC) t) t1
            ORDER BY t1.$order_by DESC, t1.place , t1.username ASC
        ";
        return $this->query($sql);
    }
    
    
    
}