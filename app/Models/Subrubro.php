<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Subrubro extends Model
{
    protected $table = 'TBLC_Subrubro';
    protected $primaryKey = 'IDPrograma';
    public $timestamps = false;
    protected $fillable = ['IDRubro', 'IDDependencia', 'PRO_Nombre', 'descripcion'];
}
