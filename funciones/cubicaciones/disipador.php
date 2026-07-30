<?php
/**
 * Tipología: Mejoramiento disipador de energía.
 * Disipador de energía dimensionado desde el caudal (Caida.bas).
 * El largo se deduce con fórmulas empíricas de caída vertical.
 */

function cubicacion_disipador($caudal, $base, $altura) {
    $espesor = 0.2;
    $hc = altura_critica_rectangular($caudal, $base);
    $grada = 1.1 * $hc;
    $h_ingreso = 0.54 * $grada * ($altura / $grada) ** 1.275;
    $h_salida = 1.66 * $grada * ($altura / $grada) ** 0.81;
    $largo_caida = 4.3 * $grada * ($altura / $grada) ** 0.81;
    $largo_resalto = 6.9 * ($h_salida - $h_ingreso);
    $largo = $largo_caida + $largo_resalto;

    $altura_final = $altura + $grada;   // altura de los muros en la zona de caída
    $altura_muro = $altura + $espesor;

    $hormigon = $espesor * ($largo * ($base + 2 * $espesor))
              + $espesor * 2 * ($largo * $altura_final);
    $superficie_fondo = $largo * ($base + 2 * $espesor);

    $roce_faja = $largo * ($base + 3 + 3);
    $escarpe = $roce_faja * ESPESOR_ESCARPE;
    $excavacion = $largo * $base;
    $relleno = $largo * ($altura_muro + 0.1 + 0.05) * 0.5 * 2;

    [$excedente_escarpe, $excedente_excavacion, $excedentes_totales] =
        excedentes($escarpe, $excavacion, $relleno);

    return [
        'hormigon' => $hormigon,
        'enfierradura' => $hormigon * cuantia_acero($altura_final),
        'moldaje' => $hormigon * 10,
        'emplantillado' => 0.05 * $superficie_fondo,
        'base_granular' => 0.1 * $superficie_fondo,
        'antisol' => (2 * ($largo * $altura_final)) * 2,
        'roce_faja' => $roce_faja,
        'escarpe' => $escarpe,
        'excavacion' => $excavacion,
        'relleno' => $relleno,
        'excedente_escarpe' => $excedente_escarpe,
        'excedente_excavacion' => $excedente_excavacion,
        'excedentes_totales' => $excedentes_totales,
        'largo' => $largo,
    ];
}

function _disipador($p) {
    $c = cubicacion_disipador($p['caudal'], $p['base'], $p['altura']);
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
    $informativos = [
        ['Largo del disipador', $c['largo'], 'm'],
    ];
    return [$partidas, $informativos];
}

$TIPOLOGIAS['disipador'] = [
    'titulo' => 'Mejoramiento disipador de energia',
    'campos' => [
        _campo('caudal', 'Caudal de diseno', 'm3/s', 0.67,
            'Caudal de diseno que debe conducir la obra, usado para dimensionar la caida y el resalto hidraulico.'),
        _campo('base', 'Ancho de la base', 'm', 1.0,
            'Ancho interior de la base del disipador.'),
        _campo('altura', 'Altura de la caida', 'm', 1.0,
            'Desnivel a salvar en la caida, entre la cota de entrada y la cota de salida del disipador.'),
    ],
    'cubicar' => '_disipador',
];
