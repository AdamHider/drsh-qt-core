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
        if($challenge['winner_left'] > 0){
            if($challenge['code'] == 'total_points'){
                $has_won = $challenge['progress']['value'] >= $challenge['value'] && !$challenge['is_finished'];
            }
            if($challenge['code'] == 'total_points_first'){
                $has_won = $challenge['progress']['value'] >= $challenge['value'];
            }
            if($challenge['code'] == 'total_lessons'){
                $has_won = $challenge['progress']['value'] >= $challenge['value'];
            }
        }
        if($has_won){
            $winner = [
                'challenge_id' => $challenge['id'],
                'phone' => '', 
                'status' => 'unconfirmed' 
            ];
            $this->itemCreate($winner);
            return 'unconfirmed';
        } else {
            return 'none';
        }
    }
    
        
    public function itemCreate ($winner)
    {
        $this->transBegin();
        $winner_id = $this->insert($winner, true);
        $this->transCommit();

        return $winner_id;        
    }
}