<?php
// ============================================================
// app/Models/SituacionEncontrada.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SituacionEncontrada extends Model
{
    protected $table = 'TBLC_SituacionEncontrada';
    protected $primaryKey = 'IDSituacion';
    public $timestamps = false;
    protected $fillable = ['Nombre'];
}