<?php
// ============================================================
// app/Models/Encuesta.php  (REEMPLAZA tu modelo Encuesta actual, si ya
// tenías uno -- si no tenías modelo y trabajabas todo con DB::table(),
// créalo con este contenido)
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Encuesta extends Model
{
    protected $table = 'tbld_encuestas';
    protected $primaryKey = 'IDEncuesta';
    public $timestamps = false;

    protected $fillable = [
        'IDAcciones', 'Folio', 'FechaVisita', 'PorcentajeAvance', 'IDEstatus',
        'IDSituacionEncontrada', 'Recomendacion', 'NombreEncuestador', 'Observaciones',
        'InformanteNombre', 'InformanteDomicilio', 'InformanteSexo', 'InformanteEdad',
        'InversionProgramada', 'Beneficiarios', 'TipoBeneficiario', 'FechaCreacion',
    ];

    // OJO: ya NO se relaciona directo con Obraproyecto -- ahora la obra
    // se conoce siempre a través de la Acción dueña de la encuesta.
    public function accion()
    {
        return $this->belongsTo(Accion::class, 'IDAcciones', 'IDAcciones');
    }

    public function respuestas()
    {
        return $this->hasMany(EncuestaRespuesta::class, 'IDEncuesta', 'IDEncuesta');
    }

    public function fotos()
    {
        return $this->hasMany(EncuestaFoto::class, 'IDEncuesta', 'IDEncuesta');
    }

    public function documentos()
    {
        return $this->hasMany(EncuestaDocumento::class, 'IDEncuesta', 'IDEncuesta');
    }
}
