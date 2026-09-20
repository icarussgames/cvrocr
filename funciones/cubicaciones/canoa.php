<?php
/**
 * Tipología: Restauración canoa.
 * Restauración de una canoa de hormigón armado (RepCanoa.bas). Se
 * repone hormigón con espesor 0.3 m sobre losa de fondo y desarrollo
 * del muro; la demolición se estima como la mitad del hormigón
 * repuesto.
 */

function cubicacion_canoa($largo, $base, $altura) {
    $espesor = 0.3;
    $hormigon = $espesor * (($largo * $base) + ($largo * $altura));
    $demolicion = $hormigon * 0.5;
    $moldaje = $hormigon * 10;

    $roce_faja = $largo * ($base + 4);
    $escarpe = $roce_faja * ESPESOR_ESCARPE;

    return [
        'hormigon' => $hormigon,
        'enfierradura' => $hormigon * cuantia_acero($altura),
        'moldaje' => $moldaje,
        'demolicion' => $demolicion,
        'antisol' => $moldaje,
        'roce_faja' => $roce_faja,
        'escarpe' => $escarpe,
        'excedentes_totales' => $escarpe * (1 + ESPONJAMIENTO) + $demolicion,
    ];
}

function _canoa($p) {
    $c = cubicacion_canoa($p['largo'], $p['base'], $p['altura']);
    $partidas = [
        ['MT035', 1],
        ['MT027', $c['escarpe']],
        ['MT036', $c['demolicion']],
        ['MT033', $c['excedentes_totales']],
        ['MT003', $c['hormigon']],
        ['MT011', $c['enfierradura']],
        ['MT018', $c['moldaje']],
        ['MT040', $c['antisol']],
    ];
    return [$partidas, []];
}

$TIPOLOGIAS['canoa'] = [
    'titulo' => 'Construcción de Canoa en Cruce de Cauce Natural',
    'campos' => [
        _campo('largo', 'Largo de la canoa', 'm', 24.0,
            'Longitud total de la canoa a restaurar.'),
        _campo('base', 'Ancho interior', 'm', 1.0,
            'Ancho interior de la canoa.'),
        _campo('altura', 'Altura interior', 'm', 1.0,
            'Altura interior de la canoa.'),
    ],
    'cubicar' => '_canoa',
];
