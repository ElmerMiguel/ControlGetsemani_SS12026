---
name: phase2-architecture-full
description: Especificación exhaustiva y mandatoria de la arquitectura, modelo de datos, reglas de negocio, seguridad y convenciones técnicas de Getsemaní Finanzas (Fase 2).
---

skill: phase2_architecture_full
rules:
  # 1. TECNOLOGÍA, STACK Y PROHIBICIONES
  - "STACK EXCLUSIVO OBLIGATORIO: Laravel 12 (^12.0), Blade, Tailwind CSS, Alpine.js, Chart.js (vía npm/Vite, sin CDN), Breeze (scaffolding Blade), spatie/laravel-permission, barryvdh/laravel-dompdf, maatwebsite/excel (^3.1), PHPUnit/Pest. PHP >= 8.2."
  - "TERMINANTEMENTE PROHIBIDO: Livewire, Inertia.js, Vue, React, Filament, Nova, Backpack, Jetstream, Fortify, Sanctum, Passport, owen-it/laravel-auditing, Bootstrap, jQuery."
  - "ENTORNO Y ZONA: APP_ENV=local, DB_HOST=127.0.0.1, base dev 'getsemani_dev', base testing 'getsemani_test'. Timezone obligatoria: 'America/Guatemala' (UTC-6, sin DST). Moneda obligatoria: GTQ (Quetzales, formato 'Q 12,103.50')."
  - "CONVENCIONES DE CÓDIGO: Nombres de dominio en español sin tildes ni eñes (Caja, Ingreso, Egreso, CorteCaja, Aportante, Bitacora, Transferencia). Sufijos técnicos en inglés (Controller, Service, Policy, FormRequest, Export, Listener). Un archivo = una responsabilidad (Services <= 250 líneas)."

  # 2. CAPAS Y RESPONSABILIDADES DE ARQUITECTURA
  - "SEPARACIÓN ESTRICTA: Controllers delgados (solo reciben, autorizan vía Gate::authorize o middleware can, delegan a Services, devuelven vista/redirección). FormRequests validan formato y existencia. Policies validan quién puede hacer qué sobre qué registro. Services contienen toda la lógica de negocio y transacciones (DB::transaction). Modelos definen relaciones, casts, scopes y auditable (sin lógica de flujo). Blade solo presenta (PROHIBIDO invocar modelos Model:: dentro de vistas)."
  - "MODELOS ELOQUENT: Definir explícitamente $table, $fillable y método casts(). PROHIBIDO usar $guarded = []. Casts obligatorios de dinero a 'decimal:2'. Enums de PHP guardados como string en base de datos (evita fricción de migraciones en MariaDB)."

  # 3. ESQUEMA DE BASE DE DATOS Y ENTIDADES (FASE 2)
  - "ESQUEMA - users: id, name, email (unique), email_verified_at, password, remember_token, activo (bool default 1), must_change_password (bool default 1), last_login_at, timestamps."
  - "ESQUEMA - departamentos: id, nombre (unique), tipo (string 20: consejo|comite|congregacion|junta), descripcion (null), activo (bool default 1), timestamps."
  - "ESQUEMA - cajas: id, departamento_id (FK), nombre, codigo (string 20, unique), medio (string 10: efectivo|banco), saldo_apertura (decimal 14,2 default 0), fecha_apertura (date), activa (bool default 1), unique(departamento_id, nombre), timestamps."
  - "ESQUEMA - caja_user (pivote): caja_id (FK), user_id (FK), primary key compuesta (caja_id, user_id)."
  - "ESQUEMA - catalogo_ingresos y catalogo_egresos: id, codigo (string 10, unique), nombre, es_transferencia (bool default 0), activo (bool default 1), timestamps."
  - "ESQUEMA - aportantes: id, nombre_completo, cui_dpi (string 13, null, unique), telefono (null), activo (bool default 1), timestamps."
  - "ESQUEMA - ingresos: id, caja_id (FK), fecha (date), cuenta_ingreso_id (FK catalogo_ingresos), monto (decimal 14,2), recibo (string 100 null), aportante_id (FK aportantes null), observaciones (text null), usuario_id (FK users), transferencia_id (FK transferencias null), anulado_por (FK users null), motivo_anulacion (string 255 null), deleted_at (SoftDeletes), timestamps. Índices: (caja_id, fecha), cuenta_ingreso_id, transferencia_id."
  - "ESQUEMA - egresos: id, caja_id (FK), fecha (date), cuenta_egreso_id (FK catalogo_egresos), monto (decimal 14,2), descripcion (string 255 NOT NULL obligatoria), referencia (string 100 null), usuario_id (FK users), transferencia_id (FK transferencias null), anulado_por (FK users null), motivo_anulacion (string 255 null), deleted_at (SoftDeletes), timestamps. Índices: (caja_id, fecha), cuenta_egreso_id, transferencia_id."
  - "ESQUEMA - transferencias: id, caja_origen_id (FK cajas), caja_destino_id (FK cajas), fecha (date), monto (decimal 14,2), concepto (string 255), usuario_id (FK users), anulado_por (FK users null), motivo_anulacion (string 255 null), deleted_at (SoftDeletes), timestamps."
  - "ESQUEMA - cortes_caja: id, caja_id (FK), periodo_inicio (date), periodo_fin (date), estado (string 12: pendiente|aprobado|rechazado|reabierto), saldo_inicial (decimal 14,2), total_ingresos (decimal 14,2), total_egresos (decimal 14,2), saldo_final (decimal 14,2), solicitado_por (FK users), solicitado_at (datetime), revisado_por (FK users null), revisado_at (datetime null), observaciones (text null), timestamps. Índices: (caja_id, periodo_inicio, periodo_fin), (caja_id, estado)."
  - "ESQUEMA - bitacora_auditoria (INMUTABLE, SIN updated_at): id, usuario_id (FK users null), accion (string 40), tabla_afectada (string null), registro_id (unsigned bigint null), descripcion (string 255), datos_antes (json null), datos_despues (json null), ip (string 45 null), user_agent (string 255 null), fecha_hora (timestamp). Índices: (tabla_afectada, registro_id), (usuario_id, fecha_hora), fecha_hora."
  - "ESQUEMA - notifications: migración estándar de Laravel (php artisan make:notifications-table)."
  - "FASE 3 DIFERIDA: La tabla 'inventario' y los comprobantes PDF quedan terminantemente fuera de la Fase 2."

  # 4. REGLAS DE NEGOCIO FINANCIERAS (RN-01 A RN-07)
  - "RN-01 (PRECISIÓN MONETARIA): Todo valor de dinero debe ser DECIMAL(14,2), > 0. TERMINANTEMENTE PROHIBIDO el tipo float/double. Sumas directas en SQL (SUM) o usando bcmath en PHP."
  - "RN-02 (SALDO DINÁMICO): El saldo actual de una caja NUNCA se persiste en tabla. Se calcula dinámicamente en CajaService: saldo_apertura + SUM(ingresos vigentes) - SUM(egresos vigentes), con fecha >= fecha_apertura y deleted_at IS NULL."
  - "RN-03 (SALDO DE PERIODO): saldo_inicial(P) = saldo al cierre del día anterior a periodo_inicio. saldo_final(P) = saldo_inicial(P) + SUM(ingresos P) - SUM(egresos P)."
  - "RN-04 (FECHAS DE MOVIMIENTOS): La fecha no puede ser anterior a fecha_apertura de la caja, no puede ser futura (respetar America/Guatemala) y NO PUEDE PERTENECER a un periodo bloqueado."
  - "RN-05 (PERIODO BLOQUEADO): Un periodo está bloqueado si existe un corte_caja de esa caja en estado 'pendiente' o 'aprobado' donde periodo_inicio <= fecha_movimiento <= periodo_fin."
  - "RN-06 (INMUTABILIDAD POR BLOQUEO): Nadie (absolutamente nadie, ni siquiera el admin) puede crear, editar o anular movimientos en un periodo bloqueado sin reabrir formalmente el corte."
  - "RN-07 (ANULACIÓN Y NO BORRADO): Los movimientos no se borran físicamente. Se anulan mediante SoftDeletes, obligando a registrar anulado_por y motivo_anulacion (mínimo 10 caracteres). Queda auditado en bitácora. Las sumas financieras ignoran registros anulados."
  - "RN-10 (SALDO NEGATIVO): No se bloquea la transacción si el saldo baja de cero; se permite, pero se emite alerta visual y marca roja en el dashboard."

  # 5. REGLAS DE NEGOCIO: TRANSFERENCIAS Y CATÁLOGOS (RN-11 A RN-13)
  - "RN-11 (TRANSFERENCIAS ATÓMICAS): Caja origen != destino. Se ejecutan en una sola transacción DB::transaction: genera un Egreso en origen (cuenta de sistema '900') y un Ingreso en destino (cuenta '900') con idéntica fecha, monto y vinculados por transferencia_id. Anular la transferencia anula en cascada ambos movimientos vía SoftDeletes. En reportes consolidados se desglosan aparte para evitar doble conteo."
  - "RN-12 (CATÁLOGOS PROTEGIDOS): Códigos únicos. Cuentas con movimientos no se eliminan físicamente, solo se desactivan (activo=0). Cuentas inactivas no aparecen en nuevos formularios pero persisten en reportes históricos. Cuentas del sistema (código '900', es_transferencia=true) no son editables."
  - "RN-13 (DEPARTAMENTOS Y CAJAS): No se borran si tienen movimientos asociados; se desactivan. Una caja inactiva no admite nuevos movimientos."

  # 6. REGLAS DEL PROCESO COMPLEJO: CORTE DE CAJA (RN-08, RN-09)
  - "RN-08 (SOLICITUD DE CORTE): periodo_inicio es inmutable y automático: día siguiente a periodo_fin del último corte 'aprobado' (o fecha_apertura si es el primer corte). El tesorero solo define periodo_fin (>= periodo_inicio y <= hoy). No puede existir más de un corte en estado 'pendiente' por caja. Al solicitar, el servicio calcula y GUARDA EL SNAPSHOT (saldo_inicial, total_ingresos, total_egresos, saldo_final) y el periodo queda inmediatamente bloqueado. Garantiza: saldo_final del corte anterior == saldo_inicial del corte siguiente."
  - "RN-09 (REVISIÓN DE CORTE): Solo rol 'admin'. Aprobar: recalcula el snapshot en la transacción; si cuadra, pasa a 'aprobado'. Rechazar: pasa a 'rechazado' con observaciones obligatorias y desbloquea el periodo. Reabrir: SOLO se puede reabrir el ÚLTIMO corte 'aprobado' de la caja, con motivo obligatorio; pasa a 'reabierto' y desbloquea el periodo para correcciones. Todo cambio de estado genera notificación y bitácora."

  # 7. SEGURIDAD, AUTORIZACIÓN Y AISLAMIENTO (RN-14, RN-15, RN-16)
  - "RN-15 (AISLAMIENTO MULTI-CAJA OBLIGATORIO): Toda consulta de movimientos, cortes y reportes DEBE aplicar el scope 'accesiblesPara($user)'. El admin accede a todas las cajas; el tesorero accede ESTRICTAMENTE a las cajas asignadas en 'caja_user'. Intentar acceder a otra caja lanza 403 Forbidden (control IDOR obligatorio)."
  - "AUTORIZACIÓN EN 3 CAPAS: Para cada acción sobre un registro se deben verificar simultáneamente: 1) Permiso de Spatie, 2) Acceso a la caja vía caja_user (o admin), 3) Estado de bloqueo del periodo (RN-05). PROHIBIDO usar Gate::before para saltarse la capa 3 de bloqueo."
  - "RN-14 (GESTIÓN DE USUARIOS): Registro público eliminado (sin /register). Solo admin crea usuarios. Contraseña temporal + must_change_password=1 obligatorio en primer login. Usuario inactivo (activo=0) bloqueado en cada petición por middleware EnsureUserIsActive. Prohibido desactivarse a uno mismo o dejar el sistema sin al menos un admin activo."
  - "RN-16 (BITÁCORA INMUTABLE): La entidad Bitacora lanza excepción en update() o delete(). Audita eventos create, update, delete, restore en modelos auditables (trait Auditable), además de login, logout, failed login, eventos de corte, permisos y reportes. NUNCA registrar passwords, tokens o campos sensibles en datos_antes/datos_despues."
  - "SECURITY-BY-DESIGN: @csrf en todos los formularios Blade. Métodos PUT/PATCH/DELETE con directiva @method. Cero SQL crudo o concatenación; usar solo Eloquent y bindings parametrizados. CUI/DPI mostrado enmascarado (solo últimos 4 dígitos)."

  # 8. ORÁCULO DE PRUEBAS Y DATOS CRÍTICOS (INFORME 2022)
  - "ORÁCULO DIEZMO CONSEJO LOCAL 2022: saldo_apertura=5,492.50, ingresos=883,707.00, egresos=872,701.50 -> saldo_final esperado exacto: 16,498.00."
  - "ORÁCULO JUVENIL 2022: saldo_apertura=2,534.50, ingresos=247,711.00, egresos=249,312.00 -> saldo_final esperado exacto: 933.50."
  - "INTEGRIDAD DE CADENA DE CORTES: En pruebas con cortes mensuales en cadena, el saldo_final de cada mes debe ser matemáticamente igual al saldo_inicial del mes siguiente."
