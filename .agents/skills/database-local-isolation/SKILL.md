---
name: database-local-isolation
description: Aislamiento estricto de base de datos local y prohibición de entornos cloud.
---

skill: database_local_isolation
rules:
  - "TERMINANTEMENTE PROHIBIDO configurar o interactuar con bases de datos en la nube o producción."
  - "Entorno exclusivo: MariaDB/MySQL local (127.0.0.1) usando bases de datos locales: getsemani_dev o getsemani_test."
  - "PROHIBIDO usar comandos destructivos como 'migrate:fresh' sin verificar estrictamente que el entorno activo sea local de desarrollo."
