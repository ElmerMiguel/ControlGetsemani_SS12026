-- ==============================================================================
-- Inicialización de MariaDB de Producción — Sistema Iglesia Getsemaní
-- Reglas: ARQUITECTURA_FASE3.md §6 | Prompt P21
-- Únicamente configuración de charset e intercalación recomendada (utf8mb4).
-- Prohibido dumps con datos de prueba o credenciales en este archivo.
-- ==============================================================================

ALTER DATABASE `${MYSQL_DATABASE}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
