<?php

// ============================================================
// app/Models/TipoOrigen.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoOrigen extends Model
{
    protected $table = 'TBLC_TipoOrigen';
    protected $primaryKey = 'IDTipoOrigen';
    public $timestamps = false;
    protected $fillable = ['Nombre'];
}
