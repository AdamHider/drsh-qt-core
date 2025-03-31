<?php
namespace App\Models;
use CodeIgniter\Model;
class UserUpdatesModel extends Model
{
    protected $table      = 'user_updates';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'user_id', 'code', 'data', 'status'
    ];
    public function getList () 
    {
        $notifications = $this->where('user_id', session()->get('user_id'))->orderBy('created_at DESC')->get()->getResultArray();

        if(empty($notifications)){
            return 'not_found';
        }

        foreach($notifications as &$notification){
            $notification['data'] = json_decode($notification['data'], true);
            $notification['time_ago'] = get_time_ago($notification['created_at']);
        }

        return $notifications;
    }

}