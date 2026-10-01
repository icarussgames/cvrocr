<?php
/**
 * Tipología: Construcción de Desarenador (tipología 8 del catálogo CNR).
 * Portado desde el Excel de ejemplo "Construcción de Desarenador"
 * (reposición / construcción de trampa de arena standalone).
 *
 * No confundir con:
 *   - desarenador_reja.php (tipología 5 — desarenador + reja en cruce)
 *   - cajon_desarenador_reja.php (tipología 4 — cajón + desarenador + reja)
 * Esta obra es solo la trampa de arena; no incluye reja ni cajón.
 */

function cubicacion_desarenador($alto_existente, $ancho_existente) {
    // Constantes de diseño del Excel (hoja 3.1 DESARENADOR).
    $longitud_trampa = 10.0;        // m (G3; fijo en Excel)
    $espesor_muros = 0.2;           // m (G6)

    // Derivados geométricos (Excel 2 PARAMETROS → 3.1).
    $ancho_trampa = $ancho_existente * 3.0;   // G4 ← C10*3
    $alto_trampa = $alto_existente + 1.0;     // G5 ← C9+1
    $Lt = $longitud_trampa;
    $e = $espesor_muros;

    // --- 3.1 OBRA DE TRAMPA DE ARENA ---
    $emplantillado = ($Lt + 4.0) * ($ancho_trampa + 2.0 * $e) * 0.1;
    // Hormigón: losa + 2 muros laterales, con factor 1,2 (Excel D6).
    $hormigon = (
        $ancho_trampa * $Lt * $e
        + ($alto_trampa + $e) * $Lt * $e
        + ($alto_trampa + $e) * $Lt * $e
    ) * 1.2;
    $moldaje = (($Lt + 4.0) * $alto_trampa + ($Lt + 4.0) * ($alto_trampa + $e)) * 2.0;
    $enfierradura = CUANTIA_ACERO * $hormigon; // Excel D8 = 80*(D6)
    $antisol = (($Lt + 4.0) * ($alto_trampa + $e)) * 2.0;

    $escarpe = $ancho_trampa * 1.5 * $Lt * 0.3;
    $excavacion = ($Lt + 4.0) * ($ancho_trampa + 1.0) * ($alto_trampa + 0.1);
    $relleno = 0.5 * ($Lt + 4.0) * 2.0 * ($alto_trampa + 0.1);

    [$excedente_escarpe, $excedente_excavacion, $excedentes_totales] =
        excedentes($escarpe, $excavacion, $relleno);

    // Fórmula botadero del Excel (sin escarpe): excav·1,1 − relleno/0,9
    $botadero_excel = $excavacion * 1.1 - $relleno / 0.9;

    return [
        'hormigon' => $hormigon,
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
        'botadero_excel' => $botadero_excel,
        'ancho_trampa' => $ancho_trampa,
        'alto_trampa' => $alto_trampa,
        'longitud_trampa' => $longitud_trampa,
    ];
}

function _desarenador($p) {
    $c = cubicacion_desarenador(
        $p['alto_existente'],
        $p['ancho_existente']
    );

    $partidas = [
        ['MT035', 1, null, ['Instalación de faenas']],
        ['MT027', $c['escarpe'], null, ['Desarenador', 'Movimiento de tierras']],
        ['MT021', $c['excavacion'], null, ['Desarenador', 'Movimiento de tierras']],
        ['MT024', $c['relleno'], null, ['Desarenador', 'Movimiento de tierras']],
        ['MT033', $c['excedentes_totales'], null, ['Desarenador', 'Movimiento de tierras']],
        ['MT001', $c['emplantillado'], null, ['Desarenador', 'Hormigones']],
        ['MT003', $c['hormigon'], null, ['Desarenador', 'Hormigones']],
        ['MT011', $c['enfierradura'], null, ['Desarenador', 'Hormigones']],
        ['MT018', $c['moldaje'], null, ['Desarenador', 'Hormigones']],
        ['MT040', $c['antisol'], null, ['Obras adicionales']],
    ];

    $informativos = [
        ['Ancho trampa de arena (3·ancho existente)', $c['ancho_trampa'], 'm'],
        ['Alto trampa de arena (alto existente + 1,0)', $c['alto_trampa'], 'm'],
        ['Longitud trampa de arena (fija Excel)', $c['longitud_trampa'], 'm'],
        ['Botadero fórmula Excel (sin escarpe)', $c['botadero_excel'], 'm³'],
        ['Largo obra existente (informativo)', $p['largo_existente'], 'm'],
    ];

    $notas = [
        "Tipología 8 — Construcción de Desarenador (trampa de arena standalone). "
            . "Caudal de porteo de referencia: " . $p['caudal'] . " m³/s "
            . "(informativo; no dimensiona la sección en este motor).",
        "No incluye reja ni cajón (esas son tipologías 5 y 4). Aquí solo hay desarenador.",
        "La trampa se cubicá con longitud fija de " . $c['longitud_trampa'] . " m (Excel G3), "
            . "ancho = 3·ancho_existente (" . round($c['ancho_trampa'], 2) . " m) y "
            . "alto = alto_existente + 1,0 m (" . round($c['alto_trampa'], 2) . " m). "
            . "Hormigón G25 con factor 1,2; enfierradura " . CUANTIA_ACERO . " kg/m³ "
            . "(el Excel etiqueta la partida como «Malla A63-42ES» pero la cantidad es 80·hormigón). "
            . "Antisol = solo caras superiores (criterio Excel D14).",
        "Movimiento de tierras: escarpe = ancho_trampa·1,5·Lt·0,3; excavación = "
            . "(Lt+4)·(ancho_trampa+1)·(alto_trampa+0,1); relleno = 0,5·(Lt+4)·2·(alto_trampa+0,1). "
            . "Excedentes vía excedentes() (incluye escarpe esponjado). El Excel tipología 8 usa "
            . "excav·1,1 − relleno/0,9 sin sumar el escarpe al botadero "
            . "(≈ " . round($c['botadero_excel'], 2) . " m³ con estos inputs).",
        "El largo de la obra existente (" . $p['largo_existente'] . " m) es informativo; "
            . "la longitud de diseño de la trampa queda fija en " . $c['longitud_trampa'] . " m.",
    ];

    return [$partidas, $informativos, $notas];
}

$TIPOLOGIAS['desarenador'] = [
    'titulo' => 'Construcción de Desarenador',
    'esquema' => 'esquema-sifon.png',
    'campos' => [
        _campo('caudal', 'Caudal de porteo del canal', 'm³/s', 0.8,
            'Caudal de porteo de referencia. Informativo en este motor (no dimensiona la sección).'),
        _campo('largo_existente', 'Largo de la obra existente', 'm', 20.0,
            'Longitud del desarenador existente (ficha de descripción). Informativo; la trampa proyectada usa 10 m fijos del Excel.'),
        _campo('alto_existente', 'Alto existente', 'm', 2.5,
            'Altura de la sección / obra existente. Define alto de trampa (+1,0 m).'),
        _campo('ancho_existente', 'Ancho existente', 'm', 2.5,
            'Ancho de la sección / obra existente. Define ancho de trampa (3×).'),
    ],
    'cubicar' => '_desarenador',
];
