<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncuestaRespuesta extends Model
{
    protected $table = 'tbld_encuestarespuestas';
    protected $primaryKey = 'IDRespuesta';
    public $timestamps = false;

    protected $fillable = [
        'IDEncuesta', 'IDPregunta', 'Respuesta', 'Justificacion',
        'RespuestaHombres', 'RespuestaMujeres',
    ];

    public function pregunta()
    {
        return $this->belongsTo(EncuestaPregunta::class, 'IDPregunta', 'IDPregunta');
    }
}
