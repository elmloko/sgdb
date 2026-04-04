# CLAUDE.md — Sistema de Gestión de Desarrollo y Bugs (SGDB)

## Descripción del Proyecto

Sistema web independiente para gestión de bugs, seguimiento de proyectos y equipos de desarrollo.
Corre en **localhost** para uso interno del Área de Sistemas (UTI).

---

## Stack Tecnológico

| Capa           | Tecnología                             |
| -------------- | --------------------------------------- |
| Backend        | Laravel 11 (PHP 8.2+)                   |
| Frontend       | Vue.js 3 + Inertia.js                   |
| Estilos        | Tailwind CSS                            |
| Base de datos  | PostgreSQL 15+                          |
| Autenticación | Laravel Breeze (Inertia/Vue stack)      |
| Servidor local | Laravel Artisan (`php artisan serve`) |

---

## Estructura del Proyecto

```
sgdb/
├── app/
│   ├── Models/
│   │   ├── User.php
│   │   ├── Proyecto.php
│   │   ├── Bug.php
│   │   ├── BugHistorial.php
│   │   ├── Resolucion.php
│   │   ├── Sprint.php
│   │   └── Notificacion.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── BugController.php
│   │   │   ├── ProyectoController.php
│   │   │   ├── DashboardController.php
│   │   │   ├── NotificacionController.php
│   │   │   └── ReporteController.php
│   │   └── Middleware/
│   │       └── CheckRol.php
│   ├── Policies/
│   ├── Notifications/
│   └── Services/
│       ├── BugService.php
│       ├── KpiService.php
│       └── NotificacionService.php
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   └── js/
│       ├── Pages/
│       │   ├── Dashboard/
│       │   ├── Bugs/
│       │   ├── Proyectos/
│       │   ├── Usuarios/
│       │   └── Reportes/
│       ├── Components/
│       │   ├── BugCard.vue
│       │   ├── StatusBadge.vue
│       │   ├── PriorityBadge.vue
│       │   └── KpiWidget.vue
│       └── Layouts/
│           └── AppLayout.vue
├── routes/
│   └── web.php
└── CLAUDE.md
```

---

## Roles de Usuario

El sistema tiene **4 roles**. El rol de Desarrollador también cumple funciones de Líder de Proyecto
(no existe un rol separado para líder).

| Rol               | Descripción                        | Permisos Clave                                                         |
| ----------------- | ----------------------------------- | ---------------------------------------------------------------------- |
| `admin`         | Jefe de sistemas / TI               | Todo el sistema, usuarios, configuración global                       |
| `desarrollador` | Programador + líder de su proyecto | Gestionar proyectos asignados, crear/resolver bugs, ver KPIs           |
| `qa`            | Tester / Control de calidad         | Crear bugs, verificar resoluciones, cambiar estado a EN_QA o REABIERTO |
| `reportante`    | Usuario que reporta errores         | Crear bugs, ver sus tickets, confirmar resolución                     |

> **Regla importante:** Los roles se asignan **por proyecto** (tabla `proyecto_usuario`),
> no globalmente. Excepto `admin`, que tiene acceso a todo.

---

## Modelo de Datos

### Tabla: `users`

```
id, name, email, password, rol_global (admin|desarrollador|qa|reportante),
activo (boolean), created_at, updated_at
```

### Tabla: `proyectos`

```
id, nombre, descripcion, estado (activo|pausado|completado|archivado),
fecha_inicio, fecha_fin_estimada, creado_por (FK users), created_at, updated_at
```

### Tabla: `proyecto_usuario` (pivot)

```
id, proyecto_id (FK), user_id (FK), rol_proyecto (desarrollador|qa|reportante)
```

### Tabla: `bugs`

```
id, ticket_num (único, ej: BUG-2026-0001), titulo, descripcion,
prioridad (critica|alta|media|baja),
estado (nuevo|en_revision|asignado|en_desarrollo|en_qa|resuelto|cerrado|rechazado|reabierto),
proyecto_id (FK), modulo, entorno (produccion|staging|desarrollo),
pasos_reproducir (text), comportamiento_esperado (text), comportamiento_actual (text),
reportado_por (FK users), asignado_a (FK users, nullable),
sla_vence_en (timestamp), created_at, updated_at, cerrado_en (nullable)
```

### Tabla: `bug_historial`

```
id, bug_id (FK), user_id (FK), accion (cambio_estado|comentario|asignacion|adjunto|resolucion),
valor_anterior, valor_nuevo, comentario (text nullable), created_at
```

### Tabla: `resoluciones`

```
id, bug_id (FK unique), tipo (correccion|no_reproducible|duplicado|disenio|rechazado),
causa_raiz (text), solucion_aplicada (text), archivos_modificados (json),
commit_ref (varchar nullable), requiere_deploy (boolean), notas_qa (text nullable),
prevencion_futura (text nullable), resuelto_por (FK users), created_at
```

### Tabla: `sprints`

```
id, proyecto_id (FK), nombre, fecha_inicio, fecha_fin,
estado (planificado|activo|completado), created_at, updated_at
```

### Tabla: `notificaciones`

```
id, user_id (FK), tipo (asignacion|sla_vence|escalamiento|resolucion|comentario|critico),
titulo, mensaje (text), leido (boolean, default false),
canal (sistema|email|ambos), bug_id (FK nullable), created_at
```

---

## Estados del Bug y Transiciones Permitidas

```
nuevo         → en_revision, rechazado
en_revision   → asignado, rechazado
asignado      → en_desarrollo, rechazado
en_desarrollo → en_qa
en_qa         → resuelto, reabierto
resuelto      → cerrado, reabierto
reabierto     → en_revision, asignado
```

Cada transición debe registrarse en `bug_historial` automáticamente.

---

## Prioridades y SLA (Tiempo Máximo de Resolución)

| Prioridad   | SLA                                    |
| ----------- | -------------------------------------- |
| `critica` | 4 horas                                |
| `alta`    | 8 horas                                |
| `media`   | 48 horas                               |
| `baja`    | Sin límite estricto (próximo sprint) |

El campo `sla_vence_en` se calcula automáticamente al crear el bug según su prioridad.

---

## Convenciones de Código

- Idioma del código: **inglés** (variables, métodos, clases)
- Idioma de la interfaz: **español**
- Idioma de comentarios: **español**
- Modelos en singular PascalCase: `Bug`, `Proyecto`, `BugHistorial`
- Controladores con sufijo `Controller`: `BugController`
- Servicios con sufijo `Service`: `BugService`, `KpiService`
- Rutas nombradas en español con puntos: `bugs.index`, `proyectos.show`
- Componentes Vue en PascalCase: `BugCard.vue`, `StatusBadge.vue`
- Usar **Form Requests** para validación (nunca validar en el controlador directamente)
- Usar **Policies** de Laravel para autorización por rol
- Toda lógica de negocio va en los **Services**, no en los controladores

---

## Comandos Útiles del Proyecto

```bash
# Iniciar servidor
php artisan serve

# Compilar frontend (modo desarrollo)
npm run dev

# Crear migración
php artisan make:migration create_bugs_table

# Crear modelo con migración, factory y seeder
php artisan make:model Bug -mfs

# Crear controlador resource
php artisan make:controller BugController --resource

# Crear Form Request
php artisan make:request StoreBugRequest

# Crear Policy
php artisan make:policy BugPolicy --model=Bug

# Crear Notification
php artisan make:notification BugAsignado

# Ejecutar migraciones
php artisan migrate

# Ejecutar seeders
php artisan db:seed

# Limpiar caché
php artisan optimize:clear
```

---

## Configuración de Base de Datos (.env)

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=sgdb
DB_USERNAME=postgres
DB_PASSWORD=tu_password_aqui
```

---

## KPIs que el Sistema debe Calcular

Todos los KPIs se calculan en `KpiService.php`:

1. **Tiempo Medio de Resolución** — promedio de horas desde `created_at` hasta `cerrado_en`
2. **Tasa de Reapertura** — % de bugs que pasaron al estado `reabierto`
3. **SLA Compliance** — % de bugs cerrados antes de `sla_vence_en`
4. **Throughput del Equipo** — bugs cerrados por desarrollador por semana
5. **Backlog Growth** — bugs creados vs cerrados en el período
6. **Bug Density por Módulo** — cantidad de bugs agrupados por campo `modulo`
7. **Carga Actual por Desarrollador** — bugs en estado `en_desarrollo` o `asignado` por usuario

---

## Dashboards a Implementar

### 1. Dashboard Admin (`/dashboard`)

- KPIs globales del sistema (todos los proyectos)
- Bugs críticos activos
- Gráfico de bugs por semana (últimas 8 semanas)
- Estado de todos los proyectos activos
- Tabla de carga por desarrollador

### 2. Dashboard Desarrollador (`/mi-panel`)

- Cola de trabajo personal ordenada por prioridad
- Bugs asignados hoy
- SLAs próximos a vencer (resaltados en rojo)
- Historial de resoluciones del mes

### 3. Dashboard de Proyecto (`/proyectos/{id}/dashboard`)

- Burndown del sprint activo
- Distribución de bugs por módulo (gráfico donut)
- Estado del equipo del proyecto
- Bugs sin asignar

---

## Notificaciones

Implementar con **Laravel Notifications** (canal `database` + `mail`).

| Evento                 | Notificación        | Destinatario                         |
| ---------------------- | -------------------- | ------------------------------------ |
| Bug CRÍTICO creado    | `BugCriticoCreado` | Admin + Desarrolladores del proyecto |
| Bug asignado           | `BugAsignado`      | Desarrollador asignado               |
| SLA a 1 hora de vencer | `SlaProximoVencer` | Desarrollador asignado               |
| SLA vencido            | `SlaVencido`       | Desarrollador + Admin                |
| Bug resuelto           | `BugResuelto`      | Reportante original                  |
| Bug reabierto          | `BugReabierto`     | Desarrollador + Admin                |
| Nuevo comentario       | `NuevoComentario`  | Participantes del ticket             |

Las notificaciones `SlaProximoVencer` y `SlaVencido` deben dispararse con un
**Laravel Scheduled Command** que corra cada 15 minutos.

---

## Orden de Implementación Sugerido

Seguir este orden para tener siempre algo funcional en cada etapa:

1. Instalación de Laravel + Breeze (Inertia/Vue) + Tailwind
2. Configuración de PostgreSQL y migraciones base
3. Sistema de autenticación y roles (`CheckRol` middleware + Policies)
4. CRUD de Proyectos
5. CRUD de Usuarios y asignación a proyectos
6. Módulo de Bugs (crear, listar, detalle)
7. Flujo de estados y BugHistorial
8. Módulo de Resoluciones
9. Sistema de Notificaciones (base de datos primero, luego email)
10. Dashboards y KPIs
11. Reportes exportables (PDF/Excel)
12. Comando de SLA scheduler

---

## Notas Importantes

- El sistema corre **solo en localhost**. No configurar para producción en esta etapa.
- No hay API REST externa — todo se maneja con Inertia.js (respuestas JSON desde Laravel).
- El número de ticket (`ticket_num`) se genera automáticamente en el modelo usando un Observer.
- El `bug_historial` debe escribirse automáticamente usando un **Model Observer** en `Bug.php`.
- Usar **Laravel Gates** para permisos finos dentro de los roles (ej: un desarrollador solo
  puede editar bugs de sus proyectos).
- Todo texto de la interfaz en **español boliviano** (sin regionalismos técnicos).
