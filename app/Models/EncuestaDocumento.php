<?php
// ============================================================
// app/Models/EncuestaDocumento.php
// (idem -- sáltate si ya existe)
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncuestaDocumento extends Model
{
    protected $table = 'tbld_encuestadocumentos';
    protected $primaryKey = 'IDDocumento';
    public $timestamps = false;
    protected $fillable = ['IDEncuesta', 'RutaArchivo', 'NombreOriginal', 'FechaSubida'];
}