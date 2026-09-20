<?php
/**
 * Tipología: Construcción de Sifón en Cruce de Camino o FFCC o Cauce Natural
 * (tipología 3 del catálogo CNR).
 * Portado desde el Excel de ejemplo "Construcción Sifón para Cruce"
 * (tubería HDPE + cámaras de entrada/salida + cubo de hormigón sobre
 * tubería bajo cauce).
 *
 * No confundir con reparacion_sifon.php ("Restauración tramo sifón") ni
 * con reja_sifon.php — son otras tipologías y otros ids.
 */

function cubicacion_sifon_cruce(
    $longitud_sifon,
    $longitud_bajo_cauce,
    $diametro_tuberia,
    $alto_existente,
    $ancho_existente,
    $alto_caida
) {
    // Constantes de diseño del Excel (hojas 2 PARAMETROS / 3.1 SIFÓN / 3.2 OBRAS).
    $espesor_muros = 0.2;           // m
    $sobreexcavacion = 0.5;         // m a cada lado (= SOBREEXC)
    $prof_media_instalacion = 1.6;  // m (G8)
    $factor_moldaje = 12.0;         // m2 moldaje / m3 hormigón (cámaras)
    $cuantia_cubo = 120.0;          // kg/m3 (Excel 3.1; distinto de CUANTIA_ACERO)
    $espesor_emplantillado = 0.1;   // m (Excel 3.2 C38)
    $espesor_cama_arena = 0.2;      // m

    $D = $diametro_tuberia;
    $L = $longitud_sifon;
    $Lb = $longitud_bajo_cauce;

    // Derivados geométricos (Excel 2 PARAMETROS / 3.1).
    $alto_total_entrada = $alto_existente + $alto_caida; // C13
    $ancho_entrada = $D + 0.4;                           // C14
    $lado_refuerzo = $D + 0.4;                           // G6 (cubo sobre tubería)
    $ancho_zanja = $D + 0.6;                             // G7
    $prof_media_zanja = $prof_media_instalacion + $D + 0.2; // G9

    // --- 3.1 SIFÓN: cubo de hormigón sobre tubería + movimiento de tierras ---
    $area_tubo = M_PI * ($D * $D) / 4.0;
    $hormigon_cubo = ($lado_refuerzo * $lado_refuerzo * $Lb) - ($area_tubo * $Lb);
    $moldaje_cubo = ($lado_refuerzo * $Lb) * 3.0;
    $enfierradura_cubo = $cuantia_cubo * $hormigon_cubo;

    $escarpe = $ancho_zanja * 1.5 * $L * 0.3; // destronque/despeje faja
    $enrocado = $Lb * 5.0;
    $excavacion_sifon = $prof_media_zanja * $ancho_zanja * $L;
    $relleno_sifon = $excavacion_sifon - ($area_tubo * $Lb);
    $cama_arena = $espesor_cama_arena * $L * $ancho_zanja;
    $largo_tuberia = $L;

    // --- 3.2 OBRAS: cámaras de entrada y salida ---
    // Excel: cada muro/caída = alto × ancho_entrada × espesor (longitud
    // de muro aproximada por el ancho de entrada).
    $vol_caida_baja = $alto_caida * $ancho_entrada * $espesor_muros;
    $vol_caida_alta = $alto_total_entrada * $ancho_entrada * $espesor_muros;
    $vol_muro = $alto_total_entrada * $ancho_entrada * $espesor_muros;
    $vol_losa = $ancho_entrada * $ancho_entrada * $espesor_muros;
    $hormigon_camara = $vol_caida_baja + $vol_caida_alta + 2.0 * $vol_muro + $vol_losa;
    $hormigon_camaras = 2.0 * $hormigon_camara; // entrada + salida

    $enfierradura_camaras = $hormigon_camaras * CUANTIA_ACERO;
    $moldaje_camaras = $hormigon_camaras * $factor_moldaje;
    $antisol = $moldaje_camaras; // Excel: antisol = moldaje cámaras
    $emplantillado = $ancho_entrada * $ancho_entrada * $espesor_emplantillado * 2.0;

    $ancho_exc_camara = $ancho_entrada + 2.0 * $espesor_muros + 2.0 * $sobreexcavacion;
    $largo_exc_camara = $ancho_exc_camara; // misma fórmula Excel C26/C27
    $prof_exc_camara = $alto_total_entrada + $espesor_muros + 0.1;
    $excavacion_camaras = 2.0 * $ancho_exc_camara * $largo_exc_camara * $prof_exc_camara;

    // Relleno perimetral por cámara (Excel C35): franjas de sobre-excavación.
    $franja_relleno = $sobreexcavacion * ($sobreexcavacion + $ancho_entrada + $sobreexcavacion)
        + $sobreexcavacion * ($sobreexcavacion + $ancho_entrada + $sobreexcavacion)
        + $sobreexcavacion * $ancho_entrada
        + $sobreexcavacion * $ancho_entrada;
    $relleno_camaras = 2.0 * $franja_relleno * $prof_exc_camara;

    // Totales.
    $hormigon = $hormigon_cubo + $hormigon_camaras;
    $enfierradura = $enfierradura_cubo + $enfierradura_camaras;
    $moldaje = $moldaje_cubo + $moldaje_camaras;
    $excavacion = $excavacion_sifon + $excavacion_camaras;
    $relleno = $relleno_sifon + $relleno_camaras;

    [$excedente_escarpe, $excedente_excavacion, $excedentes_totales] =
        excedentes($escarpe, $excavacion, $relleno);

    return [
        'hormigon' => $hormigon,
        'hormigon_cubo' => $hormigon_cubo,
        'hormigon_camaras' => $hormigon_camaras,
        'enfierradura' => $enfierradura,
        'enfierradura_cubo' => $enfierradura_cubo,
        'enfierradura_camaras' => $enfierradura_camaras,
        'moldaje' => $moldaje,
        'moldaje_cubo' => $moldaje_cubo,
        'moldaje_camaras' => $moldaje_camaras,
        'antisol' => $antisol,
        'emplantillado' => $emplantillado,
        'escarpe' => $escarpe,
        'excavacion' => $excavacion,
        'excavacion_sifon' => $excavacion_sifon,
        'excavacion_camaras' => $excavacion_camaras,
        'relleno' => $relleno,
        'relleno_sifon' => $relleno_sifon,
        'relleno_camaras' => $relleno_camaras,
        'excedente_escarpe' => $excedente_escarpe,
        'excedente_excavacion' => $excedente_excavacion,
        'excedentes_totales' => $excedentes_totales,
        'cama_arena' => $cama_arena,
        'enrocado' => $enrocado,
        'largo_tuberia' => $largo_tuberia,
        'ancho_entrada' => $ancho_entrada,
        'alto_total_entrada' => $alto_total_entrada,
        'lado_refuerzo' => $lado_refuerzo,
        'ancho_zanja' => $ancho_zanja,
        'prof_media_zanja' => $prof_media_zanja,
        'ancho_existente' => $ancho_existente, // informativo (no entra a fórmulas)
    ];
}

function _sifon_cruce($p) {
    $c = cubicacion_sifon_cruce(
        $p['longitud_sifon'],
        $p['longitud_bajo_cauce'],
        $p['diametro_tuberia'],
        $p['alto_existente'],
        $p['ancho_existente'],
        $p['alto_caida']
    );

    $partidas = [
        ['MT035', 1, null, ['Instalación de faenas']],
        ['MT027', $c['escarpe'], null, ['Sifón proyectado', 'Movimiento de tierras']],
        ['MT021', $c['excavacion'], null, ['Sifón proyectado', 'Movimiento de tierras']],
        ['MT024', $c['relleno'], null, ['Sifón proyectado', 'Movimiento de tierras']],
        ['MT033', $c['excedentes_totales'], null, ['Sifón proyectado', 'Movimiento de tierras']],
        ['MT001', $c['emplantillado'], null, ['Sifón proyectado', 'Hormigones']],
        ['MT003', $c['hormigon'], null, ['Sifón proyectado', 'Hormigones']],
        ['MT011', $c['enfierradura'], null, ['Sifón proyectado', 'Hormigones']],
        ['MT018', $c['moldaje'], null, ['Sifón proyectado', 'Hormigones']],
        ['MT040', $c['antisol'], null, ['Obras adicionales']],
    ];

    $informativos = [
        ['Volumen hormigón cubo sobre tubería (H30 Excel)', $c['hormigon_cubo'], 'm³'],
        ['Volumen hormigón cámaras entrada/salida', $c['hormigon_camaras'], 'm³'],
        ['Enfierradura cubo (120 kg/m³)', $c['enfierradura_cubo'], 'kg'],
        ['Enfierradura cámaras (80 kg/m³)', $c['enfierradura_camaras'], 'kg'],
        ['Moldaje cubo sobre tubería', $c['moldaje_cubo'], 'm²'],
        ['Moldaje cámaras', $c['moldaje_camaras'], 'm²'],
        ['Excavación zanja tubería', $c['excavacion_sifon'], 'm³'],
        ['Excavación cámaras', $c['excavacion_camaras'], 'm³'],
        ['Cama de arena (sin código MT)', $c['cama_arena'], 'm³'],
        ['Enrocado en cauce (sin código MT)', $c['enrocado'], 'm²'],
        ['Largo tubería HDPE (sin código MT)', $c['largo_tuberia'], 'm'],
        ['Ancho de entrada (D+0,4)', $c['ancho_entrada'], 'm'],
        ['Alto total entrada', $c['alto_total_entrada'], 'm'],
        ['Lado del cubo de refuerzo', $c['lado_refuerzo'], 'm'],
        ['Ancho de zanja', $c['ancho_zanja'], 'm'],
        ['Profundidad media de zanja', $c['prof_media_zanja'], 'm'],
    ];

    $notas = [
        "Tipología 3 — Construcción de Sifón en Cruce de Camino/FFCC/Cauce Natural. "
            . "Caudal de porteo de referencia: " . $p['caudal'] . " m³/s "
            . "(informativo; no dimensiona la tubería en este motor).",
        "Hormigón = cubo H30 sobre tubería bajo cauce ("
            . round($c['hormigon_cubo'], 2) . " m³) + cámaras G25 de entrada/salida ("
            . round($c['hormigon_camaras'], 2) . " m³). Ambos se valorizan como MT003 "
            . "(precios.csv no distingue H30). El presupuesto del Excel omite el cubo "
            . "H30 pese a calcularlo en la hoja 3.1; este motor lo incluye.",
        "Enfierradura = 120 kg/m³ en cubo + " . CUANTIA_ACERO . " kg/m³ en cámaras. "
            . "Moldaje = fórmula Excel del cubo (3·lado·Lb) + 12 m²/m³ en cámaras. "
            . "Antisol = solo moldaje de cámaras (criterio Excel).",
        "Movimiento de tierras: escarpe/destronque = ancho_zanja×1,5×L×0,3; "
            . "excavación = zanja (prof_media×ancho_zanja×L) + cámaras; "
            . "relleno = zanja (excav − área_tubo·Lb) + franjas de sobre-excavación "
            . "de cámaras. Excedentes vía excedentes() (incluye escarpe esponjado; "
            . "el Excel de tipología 3 no suma el escarpe al botadero).",
        "Partidas no mapeadas (sin código MT en precios.csv): «Cama de arena» ("
            . round($c['cama_arena'], 2) . " m³), «Suministro e inst. tub. HDPE PN6» ("
            . round($c['largo_tuberia'], 1) . " m, D="
            . ($p['diametro_tuberia'] * 1000) . " mm) y «Enrocado en cauce» ("
            . round($c['enrocado'], 1) . " m²). Incluir manualmente si aplica.",
        "Ancho existente del canal (" . $p['ancho_existente'] . " m) es informativo "
            . "en este motor: el Excel lo lee pero las fórmulas de cámaras usan "
            . "ancho_entrada = D+0,4 m.",
    ];

    return [$partidas, $informativos, $notas];
}

$TIPOLOGIAS['sifon_cruce'] = [
    'titulo' => 'Construcción de Sifón en Cruce de Camino o FFCC o Cauce Natural',
    'esquema' => 'esquema-sifon.png',
    'campos' => [
        _campo('caudal', 'Caudal de porteo del canal', 'm³/s', 0.5,
            'Caudal de porteo de referencia. Informativo en este motor (no dimensiona el diámetro).'),
        _campo('longitud_sifon', 'Longitud del sifón', 'm', 25.0,
            'Longitud total entre cámaras (largo de tubería y de zanja).'),
        _campo('longitud_bajo_cauce', 'Longitud bajo cauce', 'm', 15.0,
            'Tramo bajo el cauce/camino donde va el cubo de hormigón de refuerzo sobre la tubería.'),
        _campo('diametro_tuberia', 'Diámetro de la tubería', 'm', 0.8,
            'Diámetro exterior de la tubería del sifón (HDPE PN-6 en el Excel de ejemplo).'),
        _campo('alto_existente', 'Alto existente del canal', 'm', 0.8,
            'Altura de la sección existente del canal. Suma con la caída para el alto total de entrada.'),
        _campo('ancho_existente', 'Ancho existente del canal', 'm', 0.8,
            'Ancho de la sección existente. Informativo; las cámaras se dimensionan con D+0,4 m.'),
        _campo('alto_caida', 'Alto de caída de entrada', 'm', 1.0,
            'Altura de la caída en la cámara de entrada (Excel: alto caída entrada = 1,0 m).'),
    ],
    'cubicar' => '_sifon_cruce',
];
