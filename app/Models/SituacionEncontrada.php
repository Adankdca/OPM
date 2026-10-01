<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SituacionEncontrada extends Model
{
    protected $table = 'tblc_situacionencontrada';
    protected $primaryKey = 'IDSituacion';
    public $timestamps = false;
    protected $fillable = ['Nombre'];
}
