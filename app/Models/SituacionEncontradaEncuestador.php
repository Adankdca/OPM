<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SituacionEncontradaEncuestador extends Model
{
    protected $table = 'tblc_situacionencontradaencuestador';
    protected $primaryKey = 'IDSituacionEncuestador';
    public $timestamps = false;
    protected $fillable = ['Nombre', 'Activa'];
}
