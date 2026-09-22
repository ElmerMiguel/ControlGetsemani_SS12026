---
name: git-workflow-guardrail
description: Reglas estrictas de GitFlow, ramas feature y commits convencionales.
---

skill: git_workflow_guardrail
rules:
  - "Actúa bajo GitFlow estricto. NUNCA hagas commit directamente en 'main' ni en 'develop'."
  - "Trabaja únicamente en ramas de característica con la nomenclatura exacta: feature/f2-XX-* (ej. feature/f2-01-andamiaje)."
  - "Prohibido realizar git merge de forma autónoma. Tus tareas concluyen dejando los cambios listos en la rama actual."
  - "Utiliza estrictamente commits convencionales para los mensajes: feat:, fix:, test:, refactor:, chore:."
