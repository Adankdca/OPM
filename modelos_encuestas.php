<?php
/*
 * ════════════════════════════════════════════════════════════════════════
 * Este archivo junta VARIOS modelos solo para que los revises de un jalón.
 * En tu proyecto cada clase va en SU PROPIO archivo dentro de app/Models/,
 * tal como ya vienes trabajando (un archivo = una clase). Copia cada
 * bloque al archivo indicado en el comentario.
 * ════════════════════════════════════════════════════════════════════════
 */


// ============================================================
// app/Models/TipoOrigen.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoOrigen extends Model
{
    protected $table = 'TBLC_TipoOrigen';
    protected $primaryKey = 'IDTipoOrigen';
    public $timestamps = false;
    protected $fillable = ['Nombre'];
}


// ============================================================
// app/Models/SituacionEncontrada.php
// ============================================================
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SituacionEncontrada extends Model
{
    protected $table = 'TBLC_SituacionEncontrada';
    protected $primaryKey = 'IDSituacion';
    public $timestamps = false;
    protected $fillable = ['Nombre'];
}


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
