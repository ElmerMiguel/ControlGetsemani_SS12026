## Descripción del cambio
<!-- Resume en pocas líneas qué introduce o modifica este Pull Request -->

## Historias de Usuario y Reglas de Negocio cubiertas
<!-- Cita las HU-xx y RN-xx de docs/ARQUITECTURA_FASE2.md cubiertas en esta tarea -->
- [ ] HU:
- [ ] RN:

## Checklist de Calidad y Definición de Terminado (DoD)
- [ ] **Pruebas agregadas/actualizadas:** Se incluyen pruebas automatizadas para los cambios y `php artisan test` pasa en verde.
- [ ] **Formato de código:** Se ejecutó `vendor/bin/pint` sin errores ni advertencias de estilo.
- [ ] **Sin secretos ni credenciales:** Se verificó que no existan contraseñas, tokens, llaves API ni credenciales hardcodeadas en código o migraciones.
- [ ] **Protección de datos y archivos:** No se incluyen archivos `.xlsx`, datos personales reales ni `.env.testing`.
- [ ] **Resumen de defensa creado:** Se creó o actualizó el documento correspondiente en `docs/defensa/NN-*.md`.
- [ ] **Migraciones limpias:** `php artisan migrate:fresh --seed` funciona sin errores en entorno local.
- [ ] **Rama correcta:** La rama proviene de `develop` y este PR apunta hacia `develop`.
