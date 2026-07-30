<?php
/**
 * Tipología: Mejoramiento tramo túnel (shotcrete).
 * Refuerzo con shotcrete de un tramo de túnel (TunelShot.bas). Se
 * proyecta sobre las paredes y bóveda/fondo: 2·L·b + 2·L·h. La faja de
 * acceso considera solo el frente de trabajo (5 m de largo).
 */

function cubicacion_tunel_shotcrete($largo, $base, $altura) {
    $escarpe = 5 * ($base + 4) * ESPESOR_ESCARPE;

    return [
        'superficie_shotcrete' => 2 * $largo * $base + 2 * $largo * $altura,
        'escarpe' => $escarpe,
        'excedentes_totales' => $escarpe * (1 + ESPONJAMIENTO),
    ];
}

function _tunel_shotcrete($p) {
    $c = cubicacion_tunel_shotcrete($p['largo'], $p['base'], $p['altura']);
    $partidas = [
        ['MT035', 1],
        ['MT027', $c['escarpe']],
        ['MT033', $c['excedentes_totales']],
        ['MT037', $c['superficie_shotcrete']],
        ['MT038', $c['superficie_shotcrete']],
    ];
    return [$partidas, []];
}

$TIPOLOGIAS['tunel_shotcrete'] = [
    'titulo' => 'Mejoramiento tramo tunel (shotcrete)',
    'campos' => [
        _campo('largo', 'Largo del tramo', 'm', 50.0,
            'Longitud del tramo de tunel a reforzar con shotcrete.'),
        _campo('base', 'Ancho del tunel', 'm', 2.0,
            'Ancho interior del tunel.'),
        _campo('altura', 'Alto del tunel', 'm', 2.0,
            'Alto interior del tunel.'),
    ],
    'cubicar' => '_tunel_shotcrete',
];
