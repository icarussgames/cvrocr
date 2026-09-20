<?php
/**
 * Tipología: Reposición marco partidor.
 * Marco partidor dimensionado desde el caudal (Partidor.bas).
 * Desde la altura crítica hc: grada a = max(0.4·hc, 0.1), zona de
 * vertido Lv = 3.5·hc y zona de salida Ls = 2·Lv. La altura de los
 * muros se eleva a hc+a+0.15 si la altura del canal no alcanza esa
 * revancha mínima.
 */

function cubicacion_partidor($caudal, $base, $altura) {
    $espesor = 0.2;
    $hc = altura_critica_rectangular($caudal, $base);
    $grada = max(0.4 * $hc, 0.1);
    $largo_vertido = 3.5 * $hc;
    $largo_salida = $largo_vertido * 2;
    $largo = $largo_vertido + $largo_salida;

    $altura = max($altura, $hc + $grada + 0.15);

    $hormigon = $espesor * ($largo * ($base + 2 * $espesor))
              + $espesor * 2 * ($largo * $altura)
              + $base * $grada * $largo_vertido
              + $largo_salida * $altura * $espesor;
    $superficie_fondo = $largo * ($base + 2 * $espesor);

    $roce_faja = $largo * ($base + 3 + 3);
    $escarpe = $roce_faja * ESPESOR_ESCARPE;
    $excavacion = $largo * $base;
    $relleno = $largo * ($altura + $espesor + 0.1 + 0.05) * 0.5 * 2;

    [$excedente_escarpe, $excedente_excavacion, $excedentes_totales] =
        excedentes($escarpe, $excavacion, $relleno);

    return [
        'hormigon' => $hormigon,
        'enfierradura' => $hormigon * cuantia_acero($altura),
        'moldaje' => $hormigon * 10,
        'emplantillado' => 0.05 * $superficie_fondo,
        'base_granular' => 0.1 * $superficie_fondo,
        'antisol' => 2 * (2 * ($largo * $altura) + $largo_salida * $altura),
        'roce_faja' => $roce_faja,
        'escarpe' => $escarpe,
        'excavacion' => $excavacion,
        'relleno' => $relleno,
        'excedente_escarpe' => $excedente_escarpe,
        'excedente_excavacion' => $excedente_excavacion,
        'excedentes_totales' => $excedentes_totales,
    ];
}

function _partidor($p) {
    $c = cubicacion_partidor($p['caudal'], $p['base'], $p['altura']);
    $partidas = [
        ['MT035', 1],
        ['MT027', $c['escarpe']],
        ['MT021', $c['excavacion']],
        ['MT024', $c['relleno']],
        ['MT033', $c['excedentes_totales']],
        ['MT001', $c['emplantillado']],
        ['MT026', $c['base_granular']],
        ['MT003', $c['hormigon']],
        ['MT011', $c['enfierradura']],
        ['MT018', $c['moldaje']],
        ['MT040', $c['antisol']],
    ];
    return [$partidas, []];
}

$TIPOLOGIAS['partidor'] = [
    'titulo' => 'Reposición de Marco Partidor',
    'campos' => [
        _campo('caudal', 'Caudal de diseno', 'm3/s', 0.67,
            'Caudal de diseno que debe repartir el marco partidor.'),
        _campo('base', 'Ancho de la base', 'm', 1.0,
            'Ancho interior de la base del partidor.'),
        _campo('altura', 'Altura del canal', 'm', 1.0,
            'Altura del canal existente. Si es menor a la revancha minima calculada (altura critica + grada + 0.15 m), se usa esta ultima.'),
    ],
    'cubicar' => '_partidor',
];
