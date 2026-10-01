<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Encuesta extends Model
{
    protected $table = 'tbld_encuestas';
    protected $primaryKey = 'IDEncuesta';
    public $timestamps = false;

    protected $fillable = [
        'IDAcciones', 'Folio', 'TipoEncuesta', 'FechaVisita', 'situacionreportada', 'avancefisico', 'foliosrelacionados',
        'Direccion', 'avanceencontrado', 'IDSituacionEncontrada', 'IDSubsituacionencontrada',
        'observacionessituacion',
        'identificacionpersonal', 'nombrecompletopersonal', 'cargopersonal', 'domiciliopersonal',
        'clavemunicipiopersonal', 'idlocalidadpersonal',
        'nombreencuestador', 'recomendacionesencuestador', 'situacionencontradaencuestador',
        'observacionesencuestador',
        'identificacionperencuestado', 'nombreencuestado', 'sexoencuestado', 'edadencuestado',
        'parentescoencuestado', 'domicilioencuestado', 'clavemunicipioencuestador', 'idlocalidadencuestador',
        'nombreencuestadorfinal', 'recomendacionesencuestadorfinal', 'comentariofinal',
        'FechaCreacion',
    ];

    public function accion()
    {
        return $this->belongsTo(Accion::class, 'IDAcciones', 'IDAcciones');
    }

    public function situacion()
    {
        return $this->belongsTo(SituacionEncontrada::class, 'IDSituacionEncontrada', 'IDSituacion');
    }

    public function subsituacion()
    {
        return $this->belongsTo(SubsituacionEncontrada::class, 'IDSubsituacionencontrada', 'IDSubsituacion');
    }

    public function situacionEncuestador()
    {
        return $this->belongsTo(SituacionEncontradaEncuestador::class, 'situacionencontradaencuestador', 'IDSituacionEncuestador');
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
