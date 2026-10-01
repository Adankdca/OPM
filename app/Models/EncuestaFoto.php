<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncuestaFoto extends Model
{
    protected $table = 'tbld_encuestafotos';
    protected $primaryKey = 'IDFoto';
    public $timestamps = false;

    // Posicion: 1..5 (máximo 5 fotos por encuesta; una foto por posición)
    protected $fillable = ['IDEncuesta', 'Posicion', 'RutaArchivo', 'NombreOriginal', 'FechaSubida'];
}
