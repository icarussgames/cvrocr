<?php
/**
 * Tipología: Mejoramiento o construcción de cajón de hormigón.
 * Reemplazo de un tramo de canal por cajones de hormigón prefabricados
 * (CCaj.bas). Las dimensiones estándar (base, altura, espesor) se
 * seleccionan desde $GLOBALS['CAJONES_ESTANDAR'] (ver funciones/tablas.php).
 */

function cubicacion_cajon($largo, $ancho_canal, $base_estandar, $altura_estandar, $espesor_estandar) {
    $escarpe_superficie = $largo * ($base_estandar + 6);
    $escarpe = $escarpe_superficie * ESPESOR_ESCARPE;
    $excavacion = $largo * ($base_estandar + 0.4 + 0.4) * 0.7;
    $relleno = $largo * (($altura_estandar + 2 * $espesor_estandar) + 0.1) * 2 * 0.4;

    [$excedente_escarpe, $excedente_excavacion, $excedentes_totales] =
        excedentes($escarpe, $excavacion, $relleno);

    return [
        'largo_cajones' => $largo * numero_cajones_paralelos($ancho_canal),
        'roce_faja' => $escarpe_superficie,
        'escarpe' => $escarpe,
        'excavacion' => $excavacion,
        'relleno' => $relleno,
        'excedente_escarpe' => $excedente_escarpe,
        'excedente_excavacion' => $excedente_excavacion,
        'excedentes_totales' => $excedentes_totales,
        'base_granular' => $largo * $base_estandar * 0.1,
        'antisol' => 2 * $largo * $altura_estandar + $largo * $base_estandar,
    ];
}

function _cajon($p) {
    $fila = buscar_fila_por_dimensiones($GLOBALS['CAJONES_ESTANDAR'], $p['ancho_canal'], $p['alto_canal']);
    [$base_std, $alto_std, $espesor_std] = $fila;
    $c = cubicacion_cajon($p['largo'], $p['ancho_canal'], $base_std, $alto_std, $espesor_std);
    $partidas = [
        ['MT035', 1],
        ['MT027', $c['escarpe']],
        ['MT021', $c['excavacion']],
        ['MT024', $c['relleno']],
        ['MT033', $c['excedentes_totales']],
        ['MT026', $c['base_granular']],
        ['MT041', $c['largo_cajones']],
        ['MT040', $c['antisol']],
    ];
    $informativos = [
        ['Cajon estandar seleccionado (b x h x e)', "$base_std x $alto_std x $espesor_std", 'm'],
    ];
    return [$partidas, $informativos];
}

$TIPOLOGIAS['cajon'] = [
    'titulo' => 'Mejoramiento o construccion de cajon de hormigon',
    'campos' => [
        _campo('largo', 'Largo del tramo', 'm', 20.0,
            'Longitud total del tramo de canal a reemplazar por cajones.'),
        _campo('ancho_canal', 'Ancho del canal', 'm', 1.5,
            'Ancho del canal existente. Se usa para elegir el cajon estandar mas adecuado y para calcular cuantos cajones van en paralelo.'),
        _campo('alto_canal', 'Alto del canal', 'm', 1.5,
            'Alto del canal existente. Se usa para elegir el cajon estandar mas adecuado.'),
    ],
    'cubicar' => '_cajon',
];
