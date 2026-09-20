<?php
/**
 * Tipología: Reposición de compuerta.
 * Obra civil de un tramo estándar de 2 m de largo, espesor 0.3 m
 * (Compuerta.bas). Ojo: excedentes_totales acá incluye la demolición,
 * a diferencia de las demás tipologías.
 */

function cubicacion_compuerta($base, $altura) {
    $espesor = 0.3;
    $largo = 2.0;

    $hormigon = $largo * $base * $espesor
              + 2 * $largo * $altura * $espesor
              + $altura * $base * $espesor;
    $demolicion = $hormigon * 0.5;
    $moldaje = $hormigon * 12;

    $escarpe = ($largo + 4) * ($base + 4) * ESPESOR_ESCARPE;
    $excavacion = ($base + 2 * 0.5) * ($altura + 0.3) * 0.5;
    $relleno = ($base + 0.5 + 0.5) * $altura * 0.5;

    [$excedente_escarpe, $excedente_excavacion, $excedentes_parciales] =
        excedentes($escarpe, $excavacion, $relleno);

    return [
        'hormigon' => $hormigon,
        'enfierradura' => $hormigon * 80,
        'moldaje' => $moldaje,
        'demolicion' => $demolicion,
        'antisol' => $moldaje,
        'escarpe' => $escarpe,
        'excavacion' => $excavacion,
        'relleno' => $relleno,
        'excedente_escarpe' => $excedente_escarpe,
        'excedente_excavacion' => $excedente_excavacion,
        'excedentes_totales' => $demolicion + $excedentes_parciales,
    ];
}

function _compuerta($p) {
    $c = cubicacion_compuerta($p['base'], $p['altura']);
    $partidas = [
        ['MT035', 1],
        ['MT042', 1],
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

$TIPOLOGIAS['compuerta'] = [
    'titulo' => 'Reposición de Compuertas',
    'campos' => [
        _campo('base', 'Ancho del canal en la compuerta', 'm', 1.0,
            'Ancho interior del canal en el punto donde va la compuerta.'),
        _campo('altura', 'Altura del canal en la compuerta', 'm', 1.0,
            'Altura del canal en el punto donde va la compuerta.'),
    ],
    'cubicar' => '_compuerta',
];
