<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\I18n\Time;

class ChallengeModel extends Model
{
    use PermissionTrait;
    protected $table      = 'challenges';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'image'
    ];
    
    public function getItem ($challenge_id) 
    {
        $this->useSharedOf('classrooms', 'classroom_id');

        if(!$this->hasPermission($challenge_id, 'r')){
            return 'forbidden';
        }

        $ChallengeWinnerModel = model('ChallengeWinnerModel');
        
        $challenge = $this->join('challenges_winners', 'challenges_winners.challenge_id = challenges.id', 'left')
        ->select('challenges.*, (challenges.winner_limit - COUNT(challenges_winners.id)) as winner_left')
        ->where('challenges.id', $challenge_id)->get()->getRowArray();
        
        $user_winner = $this->join('challenges_winners', 'challenges_winners.challenge_id = challenges.id')
        ->where('challenges_winners.user_id', session()->get('user_id'))
        ->where('challenges.id', $challenge_id)->get()->getRowArray();
        if(empty($challenge)){
            return 'not_found';
        }
        $challenge['title'] = lang('App.challenge.title.'.$challenge['code'], [$challenge['value']]);

        $challenge['image'] = base_url('image/' . $challenge['image']);
        $challenge['progress'] = $this->getProgress($challenge['value'], $challenge['date_start'], $challenge['date_end']);
        $challenge['is_finished'] = $this->checkFinished($challenge);
        $challenge['is_winner'] = $ChallengeWinnerModel->checkWinner($challenge);
        $challenge['winner_confirmed'] = !empty($user_winner['user_id']);
        if($challenge['date_start']){
            $challenge['date_start_humanized'] = Time::parse($challenge['date_start'], Time::now()->getTimezone())->humanize();
        }
        if($challenge['date_end']){
            $date_end = Time::parse($challenge['date_end'], Time::now()->getTimezone());
            $challenge['time_left'] = Time::now()->difference($date_end)->getDays();
            $challenge['date_end_humanized'] = $date_end->humanize();
            $challenge['time_left_humanized'] = Time::now()->difference($date_end)->humanize();
        }
        return $challenge;
    }
    public function getList ($data) 
    {
        if(isset($data['classroom_id'])){
            $this->useSharedOf('classrooms', 'classroom_id');
        }
        $ChallengeWinnerModel = model('ChallengeWinnerModel');
        
        $challenges = $this->join('challenges_winners', 'challenges_winners.challenge_id = challenges.id', 'left')
        ->select('challenges.*, (challenges.winner_limit - COUNT(challenges_winners.id)) as winner_left')
        ->where('challenges.classroom_id', $data['classroom_id'])->whereHasPermission('r')
        ->groupBy('challenges.id')
        ->limit($data['limit'], $data['offset'])->orderBy('date_end')->get()->getResultArray();

        if(empty($challenges)){
            return 'not_found';
        }
        
        foreach($challenges as &$challenge){
            $challenge['title'] = lang('App.challenge.title.'.$challenge['code'], [$challenge['value']]);
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
        $ExerciseModel = model('ExerciseModel');

        $total_points = $ExerciseModel->getTotal($date_start, $date_end);
        $result = [
            'value' => $total_points,
            'percentage' => 0
        ];
        if($target_value != 0){
            $result['percentage'] = ceil($result['value'] * 100 / $target_value);
            if($result['percentage'] > 100){
                $result['percentage'] = 100;
            }
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