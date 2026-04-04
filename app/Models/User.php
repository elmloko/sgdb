<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol_global',
        'activo',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'activo'            => 'boolean',
        ];
    }

    // Proyectos donde participa el usuario (con su rol en cada uno)
    public function proyectos(): BelongsToMany
    {
        return $this->belongsToMany(Proyecto::class, 'proyecto_usuario')
                    ->withPivot('rol_proyecto');
    }

    // Proyectos que el usuario creó
    public function proyectosCreados(): HasMany
    {
        return $this->hasMany(Proyecto::class, 'creado_por');
    }

    // Bugs que el usuario reportó
    public function bugsReportados(): HasMany
    {
        return $this->hasMany(Bug::class, 'reportado_por');
    }

    // Bugs asignados al usuario
    public function bugsAsignados(): HasMany
    {
        return $this->hasMany(Bug::class, 'asignado_a');
    }

    // Acciones del usuario en el historial
    public function bugHistorial(): HasMany
    {
        return $this->hasMany(BugHistorial::class);
    }

    // Bugs resueltos por el usuario
    public function resoluciones(): HasMany
    {
        return $this->hasMany(Resolucion::class, 'resuelto_por');
    }

    // Notificaciones del usuario
    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }
}
