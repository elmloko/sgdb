<?php

namespace Database\Seeders;

use App\Models\Bug;
use App\Models\BugHistorial;
use App\Models\Proyecto;
use App\Models\Resolucion;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Usuarios ──────────────────────────────────────────────────────────

        $admin = User::updateOrCreate(
            ['email' => 'admin@sgdb.local'],
            [
                'name'       => 'Admin Sistema',
                'password'   => Hash::make('admin1234'),
                'rol_global' => 'admin',
                'activo'     => true,
            ]
        );

        $dev1 = User::updateOrCreate(
            ['email' => 'ana@sgdb.local'],
            [
                'name'       => 'Ana Quispe',
                'password'   => Hash::make('dev1234'),
                'rol_global' => 'desarrollador',
                'activo'     => true,
            ]
        );

        $dev2 = User::updateOrCreate(
            ['email' => 'carlos@sgdb.local'],
            [
                'name'       => 'Carlos Mamani',
                'password'   => Hash::make('dev1234'),
                'rol_global' => 'desarrollador',
                'activo'     => true,
            ]
        );

        $dev3 = User::updateOrCreate(
            ['email' => 'lucia@sgdb.local'],
            [
                'name'       => 'Lucía Flores',
                'password'   => Hash::make('dev1234'),
                'rol_global' => 'desarrollador',
                'activo'     => true,
            ]
        );

        $qa1 = User::updateOrCreate(
            ['email' => 'qa1@sgdb.local'],
            [
                'name'       => 'Roberto Chávez',
                'password'   => Hash::make('qa1234'),
                'rol_global' => 'qa',
                'activo'     => true,
            ]
        );

        $reportante = User::updateOrCreate(
            ['email' => 'reporter@sgdb.local'],
            [
                'name'       => 'María Condori',
                'password'   => Hash::make('rep1234'),
                'rol_global' => 'reportante',
                'activo'     => true,
            ]
        );

        // ── Proyectos ─────────────────────────────────────────────────────────

        $p1 = Proyecto::updateOrCreate(
            ['nombre' => 'Portal de Trámites'],
            [
                'descripcion'        => 'Sistema de gestión de trámites ciudadanos en línea.',
                'estado'             => 'activo',
                'fecha_inicio'       => now()->subMonths(3),
                'fecha_fin_estimada' => now()->addMonths(2),
                'creado_por'         => $admin->id,
            ]
        );

        $p2 = Proyecto::updateOrCreate(
            ['nombre' => 'Sistema de Facturación'],
            [
                'descripcion'        => 'Módulo de emisión y gestión de facturas electrónicas.',
                'estado'             => 'activo',
                'fecha_inicio'       => now()->subMonth(),
                'fecha_fin_estimada' => now()->addMonths(4),
                'creado_por'         => $admin->id,
            ]
        );

        // Asignar usuarios a proyectos
        $p1->usuarios()->syncWithoutDetaching([
            $dev1->id  => ['rol_proyecto' => 'desarrollador'],
            $dev2->id  => ['rol_proyecto' => 'desarrollador'],
            $qa1->id   => ['rol_proyecto' => 'qa'],
            $reportante->id => ['rol_proyecto' => 'reportante'],
        ]);

        $p2->usuarios()->syncWithoutDetaching([
            $dev2->id  => ['rol_proyecto' => 'desarrollador'],
            $dev3->id  => ['rol_proyecto' => 'desarrollador'],
            $qa1->id   => ['rol_proyecto' => 'qa'],
            $reportante->id => ['rol_proyecto' => 'reportante'],
        ]);

        // ── Bugs (creados sin Observer para control total del seeder) ─────────

        $bugs = [
            // Proyecto 1 — Portal de Trámites
            [
                'ticket_num'  => 'BUG-2026-0001',
                'titulo'      => 'Error al subir documentos PDF mayores a 5MB',
                'descripcion' => 'El sistema rechaza documentos PDF que superan los 5MB aunque el límite configurado es 10MB.',
                'prioridad'   => 'critica',
                'estado'      => 'en_desarrollo',
                'proyecto_id' => $p1->id,
                'modulo'      => 'Gestión Documental',
                'entorno'     => 'produccion',
                'reportado_por' => $reportante->id,
                'asignado_a'  => $dev1->id,
                'sla_vence_en' => now()->addHours(2),
            ],
            [
                'ticket_num'  => 'BUG-2026-0002',
                'titulo'      => 'Formulario de registro no valida RCI duplicado',
                'descripcion' => 'Se pueden registrar ciudadanos con el mismo número de RCI sin que el sistema genere error.',
                'prioridad'   => 'alta',
                'estado'      => 'en_qa',
                'proyecto_id' => $p1->id,
                'modulo'      => 'Registro Ciudadano',
                'entorno'     => 'staging',
                'reportado_por' => $qa1->id,
                'asignado_a'  => $dev2->id,
                'sla_vence_en' => now()->addHours(5),
            ],
            [
                'ticket_num'  => 'BUG-2026-0003',
                'titulo'      => 'Página de inicio carga lentamente en Firefox',
                'descripcion' => 'En Firefox la página de inicio tarda más de 10 segundos en cargar completamente.',
                'prioridad'   => 'media',
                'estado'      => 'nuevo',
                'proyecto_id' => $p1->id,
                'modulo'      => 'Frontend',
                'entorno'     => 'produccion',
                'reportado_por' => $reportante->id,
                'asignado_a'  => null,
                'sla_vence_en' => now()->addHours(40),
            ],
            [
                'ticket_num'  => 'BUG-2026-0004',
                'titulo'      => 'Botón "Cancelar trámite" no responde en móvil',
                'descripcion' => 'En dispositivos móviles el botón de cancelación de trámite no dispara el evento click.',
                'prioridad'   => 'alta',
                'estado'      => 'asignado',
                'proyecto_id' => $p1->id,
                'modulo'      => 'Frontend',
                'entorno'     => 'produccion',
                'reportado_por' => $reportante->id,
                'asignado_a'  => $dev1->id,
                'sla_vence_en' => now()->subHour(), // SLA vencido
            ],
            [
                'ticket_num'  => 'BUG-2026-0005',
                'titulo'      => 'Error 500 al imprimir constancia de trámite',
                'descripcion' => 'Al intentar imprimir la constancia el servidor retorna un error 500.',
                'prioridad'   => 'critica',
                'estado'      => 'resuelto',
                'proyecto_id' => $p1->id,
                'modulo'      => 'Reportes',
                'entorno'     => 'produccion',
                'reportado_por' => $reportante->id,
                'asignado_a'  => $dev2->id,
                'sla_vence_en' => now()->subHours(3),
            ],
            [
                'ticket_num'  => 'BUG-2026-0006',
                'titulo'      => 'Notificaciones por email no llegan',
                'descripcion' => 'Los correos de confirmación de trámite no se están enviando.',
                'prioridad'   => 'alta',
                'estado'      => 'cerrado',
                'proyecto_id' => $p1->id,
                'modulo'      => 'Notificaciones',
                'entorno'     => 'produccion',
                'reportado_por' => $qa1->id,
                'asignado_a'  => $dev1->id,
                'sla_vence_en' => now()->subDays(2),
                'cerrado_en'  => now()->subDay(),
            ],
            [
                'ticket_num'  => 'BUG-2026-0007',
                'titulo'      => 'Texto de ayuda incorrecto en formulario de apelación',
                'descripcion' => 'El texto de ayuda del campo "Motivo" describe el campo incorrecto.',
                'prioridad'   => 'baja',
                'estado'      => 'en_revision',
                'proyecto_id' => $p1->id,
                'modulo'      => 'Apelaciones',
                'entorno'     => 'staging',
                'reportado_por' => $reportante->id,
                'asignado_a'  => null,
                'sla_vence_en' => null,
            ],
            [
                'ticket_num'  => 'BUG-2026-0008',
                'titulo'      => 'Búsqueda de trámites no filtra por fecha correctamente',
                'descripcion' => 'Al filtrar por rango de fechas la búsqueda ignora la fecha de fin.',
                'prioridad'   => 'media',
                'estado'      => 'reabierto',
                'proyecto_id' => $p1->id,
                'modulo'      => 'Búsqueda',
                'entorno'     => 'produccion',
                'reportado_por' => $qa1->id,
                'asignado_a'  => $dev2->id,
                'sla_vence_en' => now()->addHours(30),
            ],

            // Proyecto 2 — Sistema de Facturación
            [
                'ticket_num'  => 'BUG-2026-0009',
                'titulo'      => 'NIT no se valida contra padrón tributario',
                'descripcion' => 'El sistema acepta NITs que no existen en el padrón del SIN.',
                'prioridad'   => 'critica',
                'estado'      => 'en_desarrollo',
                'proyecto_id' => $p2->id,
                'modulo'      => 'Emisión de Facturas',
                'entorno'     => 'produccion',
                'reportado_por' => $reportante->id,
                'asignado_a'  => $dev3->id,
                'sla_vence_en' => now()->addHour(),
            ],
            [
                'ticket_num'  => 'BUG-2026-0010',
                'titulo'      => 'Total de factura no incluye ICE en bebidas alcohólicas',
                'descripcion' => 'El cálculo del total no agrega el impuesto ICE para productos alcohólicos.',
                'prioridad'   => 'critica',
                'estado'      => 'asignado',
                'proyecto_id' => $p2->id,
                'modulo'      => 'Cálculo de Impuestos',
                'entorno'     => 'produccion',
                'reportado_por' => $qa1->id,
                'asignado_a'  => $dev2->id,
                'sla_vence_en' => now()->addMinutes(90),
            ],
            [
                'ticket_num'  => 'BUG-2026-0011',
                'titulo'      => 'PDF de factura pierde formato en impresora térmica',
                'descripcion' => 'Las facturas generadas no respetan el formato al imprimir en impresoras térmicas de 80mm.',
                'prioridad'   => 'alta',
                'estado'      => 'nuevo',
                'proyecto_id' => $p2->id,
                'modulo'      => 'Generación PDF',
                'entorno'     => 'produccion',
                'reportado_por' => $reportante->id,
                'asignado_a'  => null,
                'sla_vence_en' => now()->addHours(6),
            ],
            [
                'ticket_num'  => 'BUG-2026-0012',
                'titulo'      => 'No se puede anular factura del día anterior',
                'descripcion' => 'Al intentar anular una factura emitida ayer el sistema muestra "operación no permitida".',
                'prioridad'   => 'alta',
                'estado'      => 'en_qa',
                'proyecto_id' => $p2->id,
                'modulo'      => 'Anulaciones',
                'entorno'     => 'staging',
                'reportado_por' => $qa1->id,
                'asignado_a'  => $dev3->id,
                'sla_vence_en' => now()->addHours(4),
            ],
            [
                'ticket_num'  => 'BUG-2026-0013',
                'titulo'      => 'Exportación a Excel omite filas con descuento',
                'descripcion' => 'Al exportar el libro de ventas en Excel se omiten las filas donde hay descuento.',
                'prioridad'   => 'media',
                'estado'      => 'cerrado',
                'proyecto_id' => $p2->id,
                'modulo'      => 'Reportes',
                'entorno'     => 'produccion',
                'reportado_por' => $reportante->id,
                'asignado_a'  => $dev2->id,
                'sla_vence_en' => now()->subDays(3),
                'cerrado_en'  => now()->subDays(2),
            ],
            [
                'ticket_num'  => 'BUG-2026-0014',
                'titulo'      => 'Sesión caduca a los 5 minutos sin actividad',
                'descripcion' => 'La sesión expira demasiado rápido, interrumpiendo la carga de facturas largas.',
                'prioridad'   => 'media',
                'estado'      => 'rechazado',
                'proyecto_id' => $p2->id,
                'modulo'      => 'Autenticación',
                'entorno'     => 'produccion',
                'reportado_por' => $reportante->id,
                'asignado_a'  => null,
                'sla_vence_en' => null,
            ],
            [
                'ticket_num'  => 'BUG-2026-0015',
                'titulo'      => 'Logo de empresa no aparece en vista previa de factura',
                'descripcion' => 'La vista previa de factura no muestra el logo cargado en configuración.',
                'prioridad'   => 'baja',
                'estado'      => 'nuevo',
                'proyecto_id' => $p2->id,
                'modulo'      => 'Configuración',
                'entorno'     => 'staging',
                'reportado_por' => $reportante->id,
                'asignado_a'  => null,
                'sla_vence_en' => null,
            ],
        ];

        foreach ($bugs as $data) {
            $bug = Bug::withoutEvents(function () use ($data) {
                return Bug::updateOrCreate(
                    ['ticket_num' => $data['ticket_num']],
                    $data
                );
            });

            // Historial inicial
            if (!BugHistorial::where('bug_id', $bug->id)->exists()) {
                BugHistorial::create([
                    'bug_id'      => $bug->id,
                    'user_id'     => $data['reportado_por'],
                    'accion'      => 'cambio_estado',
                    'valor_nuevo' => 'nuevo',
                    'comentario'  => 'Bug reportado.',
                ]);

                // Si tiene asignado, registrar asignación
                if (!empty($data['asignado_a'])) {
                    BugHistorial::create([
                        'bug_id'         => $bug->id,
                        'user_id'        => $admin->id,
                        'accion'         => 'asignacion',
                        'valor_anterior' => null,
                        'valor_nuevo'    => (string) $data['asignado_a'],
                    ]);
                }

                // Si el estado no es 'nuevo', registrar la transición final
                if ($data['estado'] !== 'nuevo') {
                    BugHistorial::create([
                        'bug_id'         => $bug->id,
                        'user_id'        => $data['asignado_a'] ?? $admin->id,
                        'accion'         => 'cambio_estado',
                        'valor_anterior' => 'nuevo',
                        'valor_nuevo'    => $data['estado'],
                        'comentario'     => 'Estado asignado vía seeder.',
                    ]);
                }
            }

            // Resolución para bugs resueltos/cerrados
            if (in_array($data['estado'], ['resuelto', 'cerrado']) && !empty($data['asignado_a'])) {
                Resolucion::updateOrCreate(
                    ['bug_id' => $bug->id],
                    [
                        'tipo'              => 'correccion',
                        'causa_raiz'        => 'Se identificó el error en la capa de servicio.',
                        'solucion_aplicada' => 'Se corrigió la validación y se realizaron pruebas unitarias.',
                        'requiere_deploy'   => true,
                        'resuelto_por'      => $data['asignado_a'],
                    ]
                );
            }
        }

        $this->command->info('Seeder completado:');
        $this->command->info('  Usuarios: 1 admin, 3 devs, 1 QA, 1 reportante');
        $this->command->info('  Proyectos: 2 activos');
        $this->command->info('  Bugs: ' . Bug::count() . ' creados');
        $this->command->line('');
        $this->command->table(
            ['Email', 'Contraseña', 'Rol'],
            [
                ['admin@sgdb.local',    'admin1234', 'admin'],
                ['ana@sgdb.local',      'dev1234',   'desarrollador'],
                ['carlos@sgdb.local',   'dev1234',   'desarrollador'],
                ['lucia@sgdb.local',    'dev1234',   'desarrollador'],
                ['qa1@sgdb.local',      'qa1234',    'qa'],
                ['reporter@sgdb.local', 'rep1234',   'reportante'],
            ]
        );
    }
}
