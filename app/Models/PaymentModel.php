<?php
namespace App\Models;
use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;
use YooKassa\Client;

class PaymentModel extends Model
{
    protected $table      = 'quest_groups';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'user_id',
        'code',
        'status',
        'paid', 
        'data'
    ];
    
    public function createItem ($data) 
    {
        $shopID = getenv('yookassa.shopID');
        $secretKey = getenv('yookassa.secretKey');
        $client = new Client();
        $client->setAuth($shopID, $secretKey);
        $payment = $client->createPayment($data, uniqid('', true));
        print_r($data);
        die;
    }
}