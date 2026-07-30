<?php
/**
 * Tipología: Restauración tramo sifón.
 * Restauración de un tramo de sifón de hormigón (RepSifon.bas). Se
 * repone el ancho de la caja por ambas caras (b·2) a lo largo del
 * tramo.
 */

function cubicacion_reparacion_sifon($largo, $base, $altura, $espesor) {
    $hormigon = $largo * ($base * 2) * $espesor;
    $demolicion = 0.5 * $hormigon;
    $moldaje = $hormigon * 10;

    $roce_faja = ($largo + 2 + 2) * ($base + 2 + 2);
    $escarpe = $roce_faja * ESPESOR_ESCARPE;
    $excavacion = ($altura + 0.3) * ($base + 0.5 + 0.5) * 0.5;
    $relleno = ($base + 0.5 + 0.5) * $altura * 0.5;

    [$excedente_escarpe, $excedente_excavacion] = excedentes($escarpe, $excavacion, $relleno);

    return [
        'hormigon' => $hormigon,
        'enfierradura' => $hormigon * 100,
        'moldaje' => $moldaje,
        'demolicion' => $demolicion,
        'antisol' => $moldaje,
        'roce_faja' => $roce_faja,
        'escarpe' => $escarpe,
        'excavacion' => $excavacion,
        'relleno' => $relleno,
        'excedente_escarpe' => $excedente_escarpe,
        'excedente_excavacion' => $excedente_excavacion,
        'excedentes_totales' => $excedente_escarpe + $excedente_excavacion + $demolicion,
    ];
}

function _reparacion_sifon($p) {
    $c = cubicacion_reparacion_sifon($p['largo'], $p['base'], $p['altura'], $p['espesor']);
    $partidas = [
        ['MT035', 1],
        ['MT027', $c['escarpe']],
        ['MT021', $c['excavacion']],
        ['MT024', $c['relleno']],
        ['MT036', $c['demolicion']],
        ['MT033', $c['excedentes_totales']],
        ['MT003', $c['hormigon']],
        ['MT011', $c['enfierradura']],
        ['MT018', $c['moldaje']],
        ['MT040', $c['antisol']],
    ];
    return [$partidas, []];
}

$TIPOLOGIAS['reparacion_sifon'] = [
    'titulo' => 'Restauracion tramo sifon',
    'esquema' => 'esquema-sifon.png',
    'campos' => [
        _campo('largo', 'Largo del tramo', 'm', 20.0,
            'Longitud del tramo de sifon a restaurar.'),
        _campo('base', 'Ancho de la caja', 'm', 1.0,
            'Ancho interior de la caja del sifon.'),
        _campo('altura', 'Altura de la caja', 'm', 1.0,
            'Altura interior de la caja del sifon.'),
        _campo('espesor', 'Espesor de reposicion', 'm', 0.2,
            'Espesor de hormigon a reponer sobre la estructura existente.'),
        // Datos informativos - todavia no entran al calculo de cubicacion.
        _campo('diametro_tuberia', 'Diametro de tuberia', 'mm', 800.0,
            'Diametro de la tuberia del sifon. Dato informativo, aun no se usa en el calculo de cubicacion.'),
        _campo('ancho_reja', 'Ancho de reja', 'm', 1.0,
            'Ancho de la reja de proteccion asociada a este sifon. Dato informativo, aun no se usa en el calculo de cubicacion.'),
    ],
    'cubicar' => '_reparacion_sifon',
];
