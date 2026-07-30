<?php
/**
 * Tipología: Reposición de bocatoma (desarenador).
 * Desarenador dimensionado desde el caudal del canal (Desarenador.bas).
 * La cámara se ensancha a 3x el ancho del canal y se profundiza 0.5 m
 * sobre la altura de escurrimiento. El largo de decantación se calcula
 * con la velocidad de paso y una velocidad de sedimentación de
 * referencia (w ≈ 0.0512 m/s), más 1 m de transición.
 */

function cubicacion_desarenador($caudal, $ancho, $alto) {
    $espesor = 0.2;
    $base = $ancho * 3;
    $altura = $alto + 0.5;

    $velocidad_paso = $caudal / ($altura * $base);
    $velocidad_sedimentacion = (0.0088 + 10.221 * 0.5) / 100;
    $largo = ($velocidad_paso * $altura) / $velocidad_sedimentacion + 1;

    $hormigon = $espesor * ($largo * ($base + 2 * $espesor))
              + $espesor * 2 * ($largo * $altura);
    $superficie_fondo = $largo * ($base + 2 * $espesor);

    $roce_faja = $largo * ($base + 2 + 2);
    $escarpe = $roce_faja * ESPESOR_ESCARPE;
    $excavacion = $largo * $base;
    $relleno = $largo * ($altura + $espesor + 0.1 + 0.05) * 0.5 * 2;

    [$excedente_escarpe, $excedente_excavacion, $excedentes_totales] =
        excedentes($escarpe, $excavacion, $relleno);

    return [
        'hormigon' => $hormigon,
        'enfierradura' => $hormigon * 80,
        'moldaje' => $hormigon * 12,
        'emplantillado' => 0.05 * $superficie_fondo,
        'base_granular' => 0.1 * $superficie_fondo,
        'antisol' => 2 * 2 * ($largo * $altura),
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

function _desarenador($p) {
    $c = cubicacion_desarenador($p['caudal'], $p['ancho'], $p['alto']);
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
        ['Largo de decantacion', $c['largo'], 'm'],
    ];
    return [$partidas, $informativos];
}

$TIPOLOGIAS['desarenador'] = [
    'titulo' => 'Reposicion de bocatoma (desarenador)',
    'campos' => [
        _campo('caudal', 'Caudal de diseno', 'm3/s', 0.67,
            'Caudal de diseno que debe conducir el canal a la entrada del desarenador.'),
        _campo('ancho', 'Ancho del canal de entrada', 'm', 1.0,
            'Ancho del canal existente aguas arriba del desarenador. La camara se dimensiona a 3 veces este ancho.'),
        _campo('alto', 'Altura de escurrimiento', 'm', 1.0,
            'Altura de escurrimiento del canal de entrada. La camara del desarenador se profundiza 0.5 m adicionales sobre esta altura.'),
    ],
    'cubicar' => '_desarenador',
];
