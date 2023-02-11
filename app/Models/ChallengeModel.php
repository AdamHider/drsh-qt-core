<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class ChallengeModel extends Model
{
    protected $table      = 'challenges';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'image'
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    public function getList ($data) 
    {
        $DescriptionModel = model('DescriptionModel');
        $ChallengeWinnerModel = model('ChallengeWinnerModel');
        
        $challenges = $this->join('challenges_winners', 'challenges_winners.challenge_id = challenges.id', 'left')
        ->select('challenges.*, (challenges.winner_limit - COUNT(challenges_winners.id)) as winner_left')
        ->where('challenges.classroom_id', session()->get('user_data')->profile->classroom_id)
        ->groupBy('challenges.id')
        ->limit($data['limit'], $data['offset'])->orderBy('date_end')->get()->getResultArray();
        foreach($challenges as &$challenge){
            $challenge['description'] = $DescriptionModel->getItem('challenge', $challenge['id']);
            $challenge['image'] = base_url('image/' . $challenge['image']);
            $challenge['progress'] = $this->getProgress($challenge['value'], $challenge['date_start'], $challenge['date_end']);
            $challenge['is_finished'] = $this->checkFinished($challenge);
            $challenge['is_winner'] = $ChallengeWinnerModel->checkWinner($challenge);
            if($challenge['date_start']){
                $challenge['date_start_humanized'] = Time::parse($challenge['date_start'], Time::now()->getTimezone())->humanize();
            }
            if($challenge['date_end']){
                $time = Time::parse($challenge['date_end'], Time::now()->getTimezone());
                $challenge['time_left'] = Time::now()->difference($time)->getDays();
                $challenge['date_end_humanized'] = $time->humanize();
                $challenge['time_left_humanized'] = Time::now()->difference($time)->humanize();
            }
            
        }
        return $challenges;
    }
    public function getProgress($target_value, $date_start, $date_end)
    {
        $this->from('exercises')->where('exercises.user_id = '.session()->get('user_id'))
        ->select("COALESCE(sum(exercises.points), 0) as total_points");

        if($date_start){
            $this->where("exercises.finished_at > '".$date_start."'");
        }
        if($date_end){
            $this->where("exercises.finished_at < '".$date_end."'");
        }

        $progress = $this->get()->getRowArray();

        $result = [
            'value' => $progress['total_points'],
            'percentage' => 0
        ];
        if($target_value != 0){
            $result['percentage'] = ceil($result['value'] * 100 / $target_value);
        }
        return $result;
    }
    private function checkFinished($challenge)
    {
        if($challenge['winner_left'] == 0){
            return true;
        }
        if($challenge['code'] == 'total_points' || $challenge['code'] == 'total_lessons'){
            return strtotime($challenge['date_end']) <= strtotime('now');
        }
        if($challenge['code'] == 'total_points_first'){
            return $challenge['progress']['value'] >= $challenge['value'];
        }
    }
    
}