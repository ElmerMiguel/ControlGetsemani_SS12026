<?php

if (! function_exists('formato_moneda')) {
    /**
     * RN-01: Formato estándar de moneda para la Iglesia Getsemaní (Quetzales).
     * Ejemplo: Q 1,234.50 o -Q 500.00
     */
    function formato_moneda(mixed $monto): string
    {
        if ($monto === null || $monto === '') {
            return 'Q 0.00';
        }

        $val = (float) $monto;
        if ($val < 0) {
            return '-Q '.number_format(abs($val), 2, '.', ',');
        }

        return 'Q '.number_format($val, 2, '.', ',');
    }
}
