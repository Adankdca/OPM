<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SubsituacionEncontrada extends Model
{
    protected $table = 'tblc_subsituacionencontrada';
    protected $primaryKey = 'IDSubsituacion';
    public $timestamps = false;
    protected $fillable = ['Nombre', 'Activa'];
}
