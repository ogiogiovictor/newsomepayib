<?php

namespace Pjet\Runjet\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class ECMITokenLogs extends Model
{
    use HasFactory;

    protected $table = "ECMI.dbo.TokenLog";

    protected $connection = 'ecmi_prod';

    public $timestamps = false;

    protected $primaryKey = 'Token';

}