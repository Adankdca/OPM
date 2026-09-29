<?php
// ============================================================
// app/Models/EncuestaRespuesta.php
// ============================================================
namespace App\Models;
 
use Illuminate\Database\Eloquent\Model;
 
class EncuestaRespuesta extends Model
{
    protected $table = 'TblD_EncuestaRespuestas';
    protected $primaryKey = 'IDRespuesta';
    public $timestamps = false;
 
    protected $fillable = ['IDEncuesta', 'IDPregunta', 'Respuesta', 'Justificacion'];
 
    public function pregunta()
    {
        return $this->belongsTo(EncuestaPregunta::class, 'IDPregunta', 'IDPregunta');
    }
}