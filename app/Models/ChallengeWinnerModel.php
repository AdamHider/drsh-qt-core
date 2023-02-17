<?php

namespace App\Models;

use CodeIgniter\Model;

class ChallengeWinnerModel extends Model
{
    protected $table      = 'challenges_winners';
    protected $primaryKey = 'id';

    protected $useAutoIncrement = true;

    protected $returnType = 'array';
    protected $useSoftDeletes = true;

    protected $allowedFields = [
        'user_id',
        'challenge_id',
        'phone', 
        'status'
    ];
    
    protected $useTimestamps = false;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

   
    public function checkWinner($challenge)
    {
        $has_won = false;
        $ExerciseStatisticModel = model('ExerciseStatisticModel');
        $data = [];
        $data['by_classroom'] = true;
        $data['date_start'] = $challenge['date_start'];
        $data['date_end'] = $challenge['date_end'];
        $data['winner_limit'] = $challenge['winner_limit'];
        if($challenge['code'] == 'total_points_first'){
            $data['order_by'] = 'finished_at';
        }
        $user_leaderboard_position = $ExerciseStatisticModel->checkUserPlace($data);
        return (bool) $user_leaderboard_position['is_winner'];
    }
    
        
    public function createItem ($data)
    {
        //$this->transBegin();
        $winner_id = $this->insert($data, true);
        //$this->transCommit();

        return $winner_id;        
    }
}