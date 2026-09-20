<?php
/**
 * Parámetros de diseño (portado desde motor_calculo/parametros.py).
 *
 * En v0.6 las tipologías Excel-port ya no usan cuantia_acero /
 * espesor_revestimiento / numero_cajones_paralelos (eran de los
 * motores legacy). Se mantienen como helpers de diseño por si una
 * tipología futura los necesita.
 */

function cuantia_acero($altura) {
    if ($altura <= 0) return 0.0;
    if ($altura <= 1) return 50.0;
    if ($altura <= 2) return 80.0;
    return 100.0;
}

function espesor_revestimiento($altura) {
    if ($altura <= 1.6) return 0.13;
    if ($altura <= 2.4) return 0.13 + (($altura - 1.6) / 0.1) * 0.008333;
    return 0.2 + (($altura - 2.4) / 0.1) * 0.0066666;
}
