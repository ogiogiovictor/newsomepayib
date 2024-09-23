<?php
namespace Pjet\Runjet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MakeSave extends Model 
{


    protected $table = "make_saves";

    protected $connection = 'ib_prod';

    protected $fillable = [
        'uniqueID',
        'amount',
        'unit',
        'transaction_ref',
        'account_no',
        'meter_no',
        'name',
        'ecmi_ref',
        'transactdno'
    ];
    
}