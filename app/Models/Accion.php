<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Accion extends Model
{
    protected $table = 'TblD_Acciones';
    protected $primaryKey = 'IDAcciones';
    public $timestamps = false;
    protected $fillable = [
        'IDobraproyecto', 'IDañoobraproyecto', 'OP_Año', 'AC_Accion',
        'AC_Descripcionaccion', 'IDContrato', 'IDstatusobra', 'IDTipoaccion',
    ];
}
