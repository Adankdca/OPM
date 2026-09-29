<?php
// ============================================================
// app/Models/EncuestaPregunta.php
// ============================================================
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class EncuestaPregunta extends Model
{
    protected $table = 'TBLC_EncuestaPreguntas';
    protected $primaryKey = 'IDPregunta';
    public $timestamps = false;
 
    protected $fillable = [
        'IDTipoOrigen', 'Orden', 'Texto', 'TipoRespuesta',
        'RequiereJustificacion', 'Activa',
    ];
 
    public function tipoOrigen()
    {
        return $this->belongsTo(TipoOrigen::class, 'IDTipoOrigen', 'IDTipoOrigen');
    }
 
    public function respuestas()
    {
        return $this->hasMany(EncuestaRespuesta::class, 'IDPregunta', 'IDPregunta');
    }
}