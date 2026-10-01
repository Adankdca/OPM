<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncuestaPregunta extends Model
{
    protected $table = 'tblc_encuestapreguntas';
    protected $primaryKey = 'IDPregunta';
    public $timestamps = false;

    // Bloque: 1 = ENCUESTA 1, 2 = ENCUESTA 2 (ya no depende de Obra/Programa)
    protected $fillable = [
        'Bloque', 'Orden', 'Texto', 'TipoRespuesta',
        'RequiereJustificacion', 'EtiquetaJustificacion', 'Obligatoria', 'Activa',
    ];

    public function respuestas()
    {
        return $this->hasMany(EncuestaRespuesta::class, 'IDPregunta', 'IDPregunta');
    }
}
