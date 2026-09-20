<?php
/**
 * Tipología: Construcción de Canoa en Cruce de Cauce Natural
 * (tipología 6 del catálogo CNR).
 * Portado desde el Excel de ejemplo "Construcción de Canoa en Cruce de
 * Cauce Natural" (canoa de hormigón armado apoyada en estribos a ambos
 * lados de la quebrada; reemplazo de canoa existente en ladrillo).
 *
 * El splash tipología 6 tenía engineId null tras v0.6 (se borró el
 * canoa.php legacy). Este es un motor NUEVO Excel-port, no el legacy.
 */

function cubicacion_canoa(
    $longitud_canoa,
    $ancho_canoa,
    $alto_canoa,
    $alto_fondo_quebrada
) {
    // Constantes de diseño del Excel (hoja 3.1 CANOA).
    $espesor_muros = 0.2;              // m (F7)
    $ancho_adicional_estribo = 0.4;    // m (F8)
    $largo_estribo = 0.5;              // m (F10)
    $sobreexcavacion = 0.5;            // m por lado (F11 = SOBREEXC)
    $cuantia_canoa = 100.0;            // kg/m³ (Excel C21 = C20*100; distinto de CUANTIA_ACERO=80)
    $espesor_emplantillado = 0.1;      // m (Excel C38)
    $margen_alto_estribo = 1.5;        // m (Excel F9 = alto_fondo_quebrada + 1,5)

    $L = $longitud_canoa;              // F4
    $b = $ancho_canoa;                 // F5 interior
    $h = $alto_canoa;                  // F6 interior
    $e = $espesor_muros;               // F7
    $ae = $ancho_adicional_estribo;    // F8
    $he = $alto_fondo_quebrada + $margen_alto_estribo; // F9
    $le = $largo_estribo;              // F10
    $sx = $sobreexcavacion;            // F11

    // --- Hormigones (Excel C6 / C7 / C20) ---
    // Canoa: losa fondo + 2 muros laterales a lo largo de L.
    $hormigon_canoa = $b * $L * $e + $L * $e * ($h + $e) * 2.0;
    // Estribos (ambos lados de la quebrada): Excel C7.
    $hormigon_estribos = (
        $ae * $le * ($h + $e + $he) * 2.0
        + $he * $le * ($b + $e + $e)
    ) * 2.0;
    $hormigon = $hormigon_canoa + $hormigon_estribos;

    $enfierradura = $cuantia_canoa * $hormigon;

    // Ancho total del estribo (Excel C26) y moldaje (C22; unidad m² pese a etiqueta m3).
    $ancho_total_estribo = $ae + $e + $b + $e + $ae;
    $moldaje = ($ancho_total_estribo * $he + $le * $he) * 2.0 * 2.0
        + $L * ($b + $e + $e)
        + $L * $b
        + $L * $h
        + $L * $h
        + $L * ($h + $e) * 2.0;
    $antisol = $moldaje; // Excel C24 = C22

    // --- Movimiento de tierras (Excel C33–C36, C38) ---
    $ancho_exc_estribo = $ancho_total_estribo + 2.0 * $sx; // C27
    $largo_exc_estribo = $le + 2.0 * $sx;                  // C28

    $escarpe = $largo_exc_estribo * 2.0 * ($ancho_exc_estribo + 0.8) * 0.3 * 2.0;
    $excavacion = (
        ($ae + $e + $b + $e + $ae + 2.0 * $sx)
        * ($sx + $le + $sx)
        * $he
        * 2.0
    ) * 1.5;
    $relleno = (
        $sx * ($sx + $le + $sx) * $he * 2.0
        + ($ae + $e + $b + $e + $ae) * $le * $he * 2.0
    ) * 2.0;
    $emplantillado = ($ae + $e + $b + $e + $ae) * $le * $espesor_emplantillado * 2.0;

    // Excel C36 = excav·1,2 − relleno/0,9 (sin sumar escarpe).
    // Aquí se usa excedentes() (esponjamiento 10% + escarpe) — ver notas.
    [$excedente_escarpe, $excedente_excavacion, $excedentes_totales] =
        excedentes($escarpe, $excavacion, $relleno);

    return [
        'hormigon' => $hormigon,
        'hormigon_canoa' => $hormigon_canoa,
        'hormigon_estribos' => $hormigon_estribos,
        'enfierradura' => $enfierradura,
        'moldaje' => $moldaje,
        'antisol' => $antisol,
        'emplantillado' => $emplantillado,
        'escarpe' => $escarpe,
        'excavacion' => $excavacion,
        'relleno' => $relleno,
        'excedente_escarpe' => $excedente_escarpe,
        'excedente_excavacion' => $excedente_excavacion,
        'excedentes_totales' => $excedentes_totales,
        'ancho_total_estribo' => $ancho_total_estribo,
        'alto_estribo' => $he,
        'ancho_exc_estribo' => $ancho_exc_estribo,
        'largo_exc_estribo' => $largo_exc_estribo,
        'espesor_muros' => $espesor_muros,
        'cuantia_canoa' => $cuantia_canoa,
    ];
}

function _canoa($p) {
    $c = cubicacion_canoa(
        $p['longitud_canoa'],
        $p['ancho_canoa'],
        $p['alto_canoa'],
        $p['alto_fondo_quebrada']
    );

    $partidas = [
        ['MT035', 1, null, ['Instalación de faenas']],
        ['MT027', $c['escarpe'], null, ['Canoa de hormigón', 'Movimiento de tierras']],
        ['MT021', $c['excavacion'], null, ['Canoa de hormigón', 'Movimiento de tierras']],
        ['MT024', $c['relleno'], null, ['Canoa de hormigón', 'Movimiento de tierras']],
        ['MT033', $c['excedentes_totales'], null, ['Canoa de hormigón', 'Movimiento de tierras']],
        ['MT001', $c['emplantillado'], null, ['Canoa de hormigón', 'Hormigones']],
        ['MT003', $c['hormigon'], null, ['Canoa de hormigón', 'Hormigones']],
        ['MT011', $c['enfierradura'], null, ['Canoa de hormigón', 'Hormigones']],
        ['MT018', $c['moldaje'], null, ['Canoa de hormigón', 'Hormigones']],
        ['MT040', $c['antisol'], null, ['Obras adicionales']],
    ];

    $informativos = [
        ['Volumen hormigón canoa (losa + muros)', $c['hormigon_canoa'], 'm³'],
        ['Volumen hormigón estribos (ambos lados)', $c['hormigon_estribos'], 'm³'],
        ['Alto total estribo (fondo quebrada + 1,5 m)', $c['alto_estribo'], 'm'],
        ['Ancho total estribo', $c['ancho_total_estribo'], 'm'],
        ['Ancho excavación estribo', $c['ancho_exc_estribo'], 'm'],
        ['Largo excavación estribo', $c['largo_exc_estribo'], 'm'],
        ['Espesor muros (fijo Excel)', $c['espesor_muros'], 'm'],
        ['Cuantía acero canoa/estribos', $c['cuantia_canoa'], 'kg/m³'],
        ['Excedente escarpe (esponjado)', $c['excedente_escarpe'], 'm³'],
        ['Excedente excavación (esponjado)', $c['excedente_excavacion'], 'm³'],
    ];

    $notas = [
        "Tipología 6 — Construcción de Canoa en Cruce de Cauce Natural. "
            . "Reemplazo de canoa existente por obra nueva de hormigón armado "
            . "apoyada en estribos a ambos lados de la quebrada. "
            . "Caudal de porteo de referencia: " . $p['caudal'] . " m³/s "
            . "(informativo; no dimensiona la sección en este motor).",
        "Hormigón G25 = canoa (losa + 2 muros, "
            . round($c['hormigon_canoa'], 2) . " m³) + estribos ("
            . round($c['hormigon_estribos'], 2) . " m³). Enfierradura "
            . $c['cuantia_canoa'] . " kg/m³ (Excel C21; distinto de CUANTIA_ACERO="
            . CUANTIA_ACERO . "). Moldaje y antisol según fórmula Excel C22/C24.",
        "Geometría fija del Excel: espesor muros 0,2 m, ancho adicional estribo "
            . "0,4 m, largo estribo 0,5 m, sobreexcavación 0,5 m/lado. "
            . "Alto estribo = alto_fondo_quebrada + 1,5 m ("
            . round($c['alto_estribo'], 2) . " m).",
        "Movimiento de tierras solo de estribos (Excel C33–C35). Excedentes vía "
            . "excedentes() (incluye escarpe esponjado al 10%); el Excel tipología 6 "
            . "usa excav·1,2 − relleno/0,9 sin sumar el escarpe al botadero.",
        "Demolición de la canoa existente en ladrillo se menciona en el "
            . "diagnóstico del Excel pero no aparece en el presupuesto de "
            . "ejemplo — no se incluye aquí (MT036 disponible en precios.csv "
            . "si se agrega manualmente).",
        "La verificación hidráulica del Excel (hoja 2.1) no se porta: se asume "
            . "que el usuario entrega ancho/alto de canoa ya verificados.",
    ];

    return [$partidas, $informativos, $notas];
}

$TIPOLOGIAS['canoa'] = [
    'titulo' => 'Construcción de Canoa en Cruce de Cauce Natural',
    'esquema' => 'esquema-sifon.png',
    'campos' => [
        _campo('caudal', 'Caudal de porteo del canal', 'm³/s', 1.8,
            'Caudal de porteo de referencia. Informativo en este motor (no dimensiona la sección).'),
        _campo('longitud_canoa', 'Longitud de la canoa proyectada', 'm', 8.0,
            'Luz / largo de la canoa de hormigón entre estribos (Excel: longitud canoa proyectada).'),
        _campo('ancho_canoa', 'Ancho interior de la canoa', 'm', 2.6,
            'Ancho interior de la sección de la canoa proyectada.'),
        _campo('alto_canoa', 'Alto interior de la canoa', 'm', 1.0,
            'Alto interior de la sección de la canoa proyectada (en el Excel suele igualar el alto existente).'),
        _campo('alto_fondo_quebrada', 'Alto al fondo de la quebrada', 'm', 4.0,
            'Desnivel desde el fondo de la canoa hasta el fondo de la quebrada. Define el alto del estribo (+1,5 m).'),
    ],
    'cubicar' => '_canoa',
];
