<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo de la tabla TBLP_Obraproyecto.
 *
 * Nota para quien viene de Entity Framework: en EF el DbContext ya sabe
 * la tabla/PK por convención de nombres o por el mapeo del .edmx/Fluent API.
 * En Eloquent es lo mismo pero explícito aquí abajo con $table y $primaryKey,
 * porque el nombre de tabla no sigue la convención estándar de Laravel
 * (que esperaría "obraproyectos" en snake_case plural).
 */
class Obraproyecto extends Model
{
    // Nombre real de la tabla en MySQL (igual que en SQL Server)
    protected $table = 'TBLP_Obraproyecto';

    // Llave primaria real (Laravel asume "id" si no le dices lo contrario)
    protected $primaryKey = 'IDobraproyecto';

    // Esta tabla no tiene columnas created_at/updated_at (igual que en tu diseño original)
    public $timestamps = false;

    // Campos que se pueden asignar masivamente (Obraproyecto::create([...]))
    // Se listan explícitos por seguridad, en vez de $guarded = [] (que abriría todo).
    protected $fillable = [
        'IDRubro',
        'IDPrograma',
        'IDArea',
        'IDNivelmarginacion',
        'IDaprima',
        'OP_Num_obra',
        'OP_NombreObra',
        'OP_Antecendentes',
        'OP_Observaciones',
        'OP_Acciones',
        'Op_Franjafronteriza',
        'OP_Eliminado',
    ];

    // ────────────────────────────────────────────────────────────
    // Relaciones (equivalente a las Navigation Properties de EF)
    // ────────────────────────────────────────────────────────────

    public function subrubro()
    {
        // belongsTo: esta obra le PERTENECE a un subrubro (FK está aquí, IDPrograma)
        return $this->belongsTo(Subrubro::class, 'IDPrograma', 'IDPrograma');
    }

    public function acciones()
    {
        // hasMany: una obra TIENE MUCHAS acciones
        return $this->hasMany(Accion::class, 'IDobraproyecto', 'IDobraproyecto');
    }

    public function aniosInversion()
    {
        return $this->hasMany(AnioObraproyecto::class, 'IDObraproyecto', 'IDobraproyecto');
    }
}
