<?php

namespace Pjet\Runjet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class SubAccountPayment extends Model
{
    use HasFactory;

    protected $table = "ECMI.dbo.SubAccPayment";

    protected $connection = 'ecmi_prod';

    protected $primaryKey = 'TransactionNo';

    public $timestamps = false;

}
