<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnioObraproyecto extends Model
{
    protected $table = 'TBLD_añoobraproyecto';
    protected $primaryKey = 'IDañoobraproyecto';
    public $timestamps = false;
    protected $fillable = [
        'IDObraproyecto', 'OP_Año', 'OP_InversionEstatal',
        'OP_InversionFederal', 'OP_InversioMunicipal', 'OP_Continuidad', 'OP_Concepto',
    ];
}
