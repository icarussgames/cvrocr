<?php
/**
 * Lógica compartida entre varias cubicaciones.
 */

/**
 * Excedentes a botadero: escarpe esponjado + saldo de excavación esponjado.
 * El saldo excavación-relleno solo aporta si es positivo.
 * Devuelve [excedente_escarpe, excedente_excavacion, excedentes_totales].
 */
function excedentes($escarpe, $excavacion, $relleno) {
    $excedente_escarpe = $escarpe * (1 + ESPONJAMIENTO);
    $saldo = $excavacion * (1 + ESPONJAMIENTO) - $relleno / (COMPACTACION);
    $excedente_excavacion = $saldo > 0 ? $saldo: 0.0;
    return [$excedente_escarpe, $excedente_excavacion, $excedente_escarpe + $excedente_excavacion];

}
