---
name: accounting-integrity
description: Reglas financieras, uso exclusivo de DECIMAL(14,2) y prohibición de floats.
---

skill: accounting_integrity
rules:
  - "Todo cálculo financiero, saldo o importe monetario debe estructurarse como DECIMAL(14,2) en migraciones y 'decimal:2' en casts de Eloquent."
  - "NUNCA utilices tipos de coma flotante (float, double) bajo ninguna circunstancia en migraciones, controladores, servicios o pruebas."
  - "Las operaciones de agregación y sumas monetarias deben realizarse directamente a nivel SQL (SUM) o utilizando estrictamente el wrapper BCMath de PHP."
