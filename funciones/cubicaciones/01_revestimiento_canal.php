<?php

function cubicacion_revestimiento_canal($largo, $ancho, $alto, $espesor, $talud) {

// DIMENSIONES REVESTIMIENTO

    $base = $espesor + $ancho + $espesor; // m 
    $xy_muro = $alto * sqrt(1 + $talud ** 2); // m
    $xx_muro = $talud * $alto; // m
    $ancho_x = $xx_muro + $base + $xx_muro; // m
    $superficie_fondo = $largo * $base; // m2


// MOVIMIENTOS DE TIERRA REVESTIMIENTO

    $roce_faja = (SOBREEXC + $largo + SOBREEXC) * (4 + $ancho_x + 4); // m2
    $escarpe = $roce_faja * ESPESOR_ESCARPE; // m3
    $excavacion = $largo * (SOBREEXC + $ancho_x + SOBREEXC) * ($alto + EMPLANT + BASEGR); //m3
    $relleno = $largo * ($alto + $espesor + EMPLANT + BASEGR) * SOBREEXC * 2 
             + $largo * ($alto * $xx_muro * 0.5) * 2  
             + $largo * (EMPLANT + BASEGR) * $xx_muro; // m3

    [$excedente_escarpe, $excedente_excavacion, $excedentes_totales] =
        excedentes($escarpe, $excavacion, $relleno); // m3


// OBRAS CIVILES

    $sup_cara_ext_rev = $largo * ($xy_muro + $espesor); // m2
    $sup_cara_int_rev = $largo * $xy_muro; // m2
    $moldaje_rev = 2 * $sup_cara_ext_rev + 2 * $sup_cara_int_rev; // m2

    $hormigon = $espesor * ($largo * $base)
              + $espesor * 2 * ($largo * $xy_muro); //m3


// MURO DE ALA, 1 AGUAS ARRIBA, 1 AGUAS ABAJO

    $bajo_rev = 0.5; // m
    $ebase_muro = 0.1; // m
    $largo_muro = $ancho; // m
    $altura_muro_ala = $alto; // m 
    $alto_to = $alto + $espesor + $bajo_rev; // m
    $ancho_tr = $largo_muro + $ancho_x + $largo_muro; // m

    $sup_cara_ext_muro = ($largo_muro) * $altura_muro_ala * 2
                       + ($xx_muro * $alto * 0.5) 
                       + $ancho_tr * ($bajo_rev + $espesor); // m2
    $sup_cara_int_muro = $sup_cara_ext_muro - $espesor * ($ancho + 2 * $xy_muro); // m2

    $moldaje_ala_1ext = $sup_cara_ext_muro + $sup_cara_int_muro; // m2
    $moldaje_muros_ala = $moldaje_ala_1ext * 2; // m2

    $hormigon_ala_1ext = $espesor * $sup_cara_ext_muro; // m3
    $hormigon_muros_ala = $hormigon_ala_1ext * 2; // m3

    $base_estabilizada_1ext = $ancho_tr * $espesor * $ebase_muro; // m3
    $base_estabilizada_muros = $base_estabilizada_1ext * 2; // m3

    $profundidad_exc_muro = $altura_muro_ala + $bajo_rev + $espesor + EMPLANT + BASEGR; // m
    $excavacion_muro_1ext = ($ancho_tr + 2 * SOBREEXC) * ($espesor + 2 * SOBREEXC) * $profundidad_exc_muro; // m3
    $relleno_muro_1ext = $excavacion_muro_1ext - ($espesor * $ancho_tr * $alto_to) - $base_estabilizada_1ext; // m3
    $excavacion_muros_ala = $excavacion_muro_1ext * 2; // m3
    $relleno_muros_ala = $relleno_muro_1ext * 2; // m3

    $exc_muros = excedentes(0, $excavacion_muros_ala, $relleno_muros_ala); // m3
    $excedentes_muros_ala = $exc_muros[2]; // m3


// CANTIDADES FINALES

    return [
        'hormigon' => $hormigon,
        'hormigon_muros_ala' => $hormigon_muros_ala,
        'enfierradura' => $hormigon * CUANTIA_ACERO,
        'enfierradura_muros_ala' => $hormigon_muros_ala * CUANTIA_ACERO,
        'moldaje' => $moldaje_rev * 1.1,
        'moldaje_muros_ala' => $moldaje_muros_ala * 1.1,
        'emplantillado' => EMPLANT * $superficie_fondo,
        'base_granular' => BASEGR * $superficie_fondo,
        'base_estabilizada_muros' => $base_estabilizada_muros,
        'excavacion_muros_ala' => $excavacion_muros_ala,
        'relleno_muros_ala' => $relleno_muros_ala,
        'excedentes_muros_ala' => $excedentes_muros_ala,
        'juntas_dilatacion' => floor($largo / 5) * ($ancho + 2 * $xy_muro),
        'antisol' => $sup_cara_ext_rev * 2 + $sup_cara_int_muro * 4,
        'roce_faja' => $roce_faja,
        'escarpe' => $escarpe,
        'excavacion' => $excavacion,
        'relleno' => $relleno,
        'excedente_escarpe' => $excedente_escarpe,
        'excedente_excavacion' => $excedente_excavacion,
        'excedentes_totales' => $excedentes_totales,
    ];
}

function _revestimiento_canal($p) {
    $c = cubicacion_revestimiento_canal($p['largo'], $p['ancho'], $p['alto'], $p['espesor'], $p['talud']);

    $partidas = [
        ['MT035', 1, null, ['Instalación de faenas']],
        ['MT027', $c['escarpe'], null, ['Tramo revestido', 'Movimiento de tierras']],
        ['MT021', $c['excavacion'], null, ['Tramo revestido', 'Movimiento de tierras']],
        ['MT024', $c['relleno'], null, ['Tramo revestido', 'Movimiento de tierras']],
        ['MT033', $c['excedentes_totales'], null, ['Tramo revestido', 'Movimiento de tierras']],
        ['MT001', $c['emplantillado'], null, ['Tramo revestido', 'Hormigones']],
        ['MT026', $c['base_granular'], null, ['Tramo revestido', 'Hormigones']],
        ['MT003', $c['hormigon'], null, ['Tramo revestido', 'Hormigones']],
        ['MT011', $c['enfierradura'], null, ['Tramo revestido', 'Hormigones']],
        ['MT018', $c['moldaje'], null, ['Tramo revestido', 'Hormigones']],
        ['MT026', $c['base_estabilizada_muros'], null, ['Muros de ala', 'Movimiento de tierras']],
        ['MT021', $c['excavacion_muros_ala'], null, ['Muros de ala', 'Movimiento de tierras']],
        ['MT024', $c['relleno_muros_ala'], null, ['Muros de ala', 'Movimiento de tierras']],
        ['MT033', $c['excedentes_muros_ala'], null, ['Muros de ala', 'Movimiento de tierras']],
        ['MT003', $c['hormigon_muros_ala'], null, ['Muros de ala', 'Hormigones']],
        ['MT011', $c['enfierradura_muros_ala'], null, ['Muros de ala', 'Hormigones']],
        ['MT018', $c['moldaje_muros_ala'], null, ['Muros de ala', 'Hormigones']],
        ['MT020', $c['juntas_dilatacion'], null, ['Obras adicionales']],
        ['MT040', $c['antisol'], null, ['Obras adicionales']],
    ];

    $velocidad_asumida = 1.2; // m/s, valor de referencia para revestimientos en hormigón
    $caudal_m3s = $p['caudal'] / 1000; // el campo 'caudal' se ingresa en L/s
    $seccion_teorica = round($caudal_m3s / $velocidad_asumida, 3);
    $seccion_geometria = round($p['alto'] * ($p['ancho'] + $p['talud'] * $p['alto']), 3);
    $notas = [
        "Sección de flujo teórica (asumiendo v = {$velocidad_asumida} m/s): "
            . "{$seccion_teorica} m². Sección resultante de la geometría "
            . "ingresada (ancho, alto y talud): {$seccion_geometria} m².",
        "Incluye muros de ala en inicio y término del tramo (largo de ala = ancho del canal, "
            . "profundidad bajo revestimiento = 0.5 m).",
        "Constantes utilizadas en este cálculo: cuantía de acero = " . CUANTIA_ACERO
            . " kg/m³, espesor de escarpe = " . ESPESOR_ESCARPE
            . " m, sobre-excavación = " . SOBREEXC
            . " m, espesor emplantillado = " . EMPLANT
            . " m, espesor base granular = " . BASEGR . " m.",
    ];

    return [$partidas, [], $notas];
}

$TIPOLOGIAS['revestimiento_canal'] = [
    'titulo' => 'Mejoramiento con Revestimiento en Zonas de Derrumbes y Otras Singularidades',
    'esquema' => 'esquema_revest.png',
    'campos' => [
        _campo('caudal', 'Caudal de diseno', 'L/s', 670,
            'Caudal de porteo del canal.'),
        _campo('largo', 'Largo del tramo', 'm', 100.0,
            'Longitud del tramo de canal a revestir.'),
        _campo('ancho', 'Ancho interior del canal', 'm', 1.0,
            'Ancho interior del canal. Medido en la base del canal (losa de fondo).'),
        _campo('alto', 'Alto del canal', 'm', 0.8,
            'Alto interior del canal. Medido verticalmente (altura de muro).'),
        _campo('talud', 'Talud de los muros (H/V)', '-', 0.0,
            'Use 0 para muros verticales; a mayor valor, talud es más tendido. Ej.: Talud = 2 implica 2 m en la horizontal por cada 1 m en la vertical.'),
        _campo('espesor', 'Espesor de revestimiento', 'm', 0.13,
            'Espesor del revestimiento de hormigón, igual para losa y muros. Se recomienda un mínimo de 0,13 m (13 cm).'),
    ],
    'cubicar' => '_revestimiento_canal',
];
