<?php
// ============================================================
// app/Models/EncuestaFoto.php
// (sáltate este bloque si ya tienes este modelo -- no se modificó nada
// de fotos/documentos, se incluye solo por si te falta la clase)
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncuestaFoto extends Model
{
    protected $table = 'tbld_encuestafotos';
    protected $primaryKey = 'IDFoto';
    public $timestamps = false;
    protected $fillable = ['IDEncuesta', 'RutaArchivo', 'NombreOriginal', 'FechaSubida'];
}