<?php
/**
 * Tipología: Construcción de Cajón, Desarenador y Reja en Cruce de Camino o FFCC
 * (tipología 4 del catálogo CNR).
 * Portado desde el Excel de ejemplo "Construcción de Cajón, Desarenador y Reja"
 * (trampa de arena / desarenador + reja de entrada + cajón prefabricado bajo
 * puente vehicular o FFCC).
 *
 * No confundir con cajon.php ("Mejoramiento o construcción de cajón" / CCaj.bas),
 * desarenador.php ni reja_sifon.php — son otras tipologías y otros ids.
 */

function cubicacion_cajon_desarenador_reja(
    $longitud_cajon,
    $ancho_cajon,
    $alto_cajon,
    $alto_existente,
    $ancho_existente
) {
    // Constantes de diseño del Excel (hojas 3.1 TRAMPA DE ARENA / 3.2 REJA).
    $longitud_trampa = 5.0;         // m (G3; fijo en Excel, no en hoja Parámetros)
    $espesor_muros = 0.2;           // m (G6)
    $separacion_pletina = 0.1;      // m
    $espesor_pletina_mm = 3.0;      // mm
    $alto_pletina_mm = 80.0;        // mm
    $n_pletinas_horiz = 2.0;
    $factor_despuntes = 1.1;        // 10% despuntes en pletinas
    $espesor_cama_arena = 0.2;      // m
    $ancho_cama_arena = 2.0;        // m (Excel: E29*2*0.2)

    // Derivados geométricos (Excel 2 PARAMETROS → 3.1 / 3.2).
    $ancho_trampa = $ancho_cajon * 2.0;              // G4
    $alto_trampa = $alto_existente + 0.6;            // G5
    $alto_reja = $alto_existente + 0.2;              // F4
    $ancho_reja = $ancho_cajon;                      // F5
    $Lt = $longitud_trampa;
    $e = $espesor_muros;

    // --- 3.1 TRAMPA DE ARENA (desarenador) ---
    $emplantillado = ($Lt + 4.0) * ($ancho_trampa + 2.0 * $e) * 0.1;
    // Hormigón: losa + 2 muros laterales, con factor 1,2 (Excel D6).
    $hormigon = (
        $ancho_trampa * $Lt * $e
        + ($alto_trampa + $e) * $Lt * $e
        + ($alto_trampa + $e) * $Lt * $e
    ) * 1.2;
    $moldaje = (($Lt + 4.0) * $alto_trampa + ($Lt + 4.0) * ($alto_trampa + $e)) * 2.0;
    $enfierradura = CUANTIA_ACERO * $hormigon;
    $antisol = (($Lt + 4.0) * ($alto_trampa + $e)) * 2.0;

    $escarpe = $ancho_trampa * 1.5 * $Lt * 0.3;
    $excavacion = ($Lt + 4.0) * ($ancho_trampa + 1.0) * ($alto_trampa + 0.1);
    $relleno = 0.5 * ($Lt + 4.0) * 2.0 * ($alto_trampa + 0.1);

    [$excedente_escarpe, $excedente_excavacion, $excedentes_totales] =
        excedentes($escarpe, $excavacion, $relleno);

    // --- 3.2 REJA ---
    // Excel tipología 4 pone largo de pletinas verticales = F5 (ancho);
    // aquí se usa el alto de reja (criterio físico, igual que reja_sifon.php).
    // Con los defaults del Excel (alto_reja = ancho_reja = 2 m) el resultado coincide.
    $n_pletinas_vert = $ancho_reja / $separacion_pletina + 1.0;
    $metros_pletina = (
        $n_pletinas_horiz * $ancho_reja
        + $n_pletinas_vert * $alto_reja
    ) * $factor_despuntes;
    $peso_pletinas = $metros_pletina
        * ($espesor_pletina_mm / 1000.0)
        * ($alto_pletina_mm / 1000.0)
        * DENSIDAD_ACERO;
    $peso_reja = $peso_pletinas * 2.0; // pletinas + refuerzos/marco (Excel C21)

    // --- 2.4 CAJÓN PREFABRICADO ---
    $largo_cajon = $longitud_cajon;
    $cama_arena = $largo_cajon * $ancho_cama_arena * $espesor_cama_arena;

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
        'peso_reja' => $peso_reja,
        'peso_pletinas' => $peso_pletinas,
        'metros_pletina' => $metros_pletina,
        'n_pletinas_vert' => $n_pletinas_vert,
        'largo_cajon' => $largo_cajon,
        'cama_arena' => $cama_arena,
        'ancho_trampa' => $ancho_trampa,
        'alto_trampa' => $alto_trampa,
        'alto_reja' => $alto_reja,
        'ancho_reja' => $ancho_reja,
        'longitud_trampa' => $longitud_trampa,
        'alto_cajon' => $alto_cajon,             // informativo (define sección prefab)
        'ancho_existente' => $ancho_existente, // informativo (no entra a fórmulas)
    ];
}

function _cajon_desarenador_reja($p) {
    $c = cubicacion_cajon_desarenador_reja(
        $p['longitud_cajon'],
        $p['ancho_cajon'],
        $p['alto_cajon'],
        $p['alto_existente'],
        $p['ancho_existente']
    );

    $partidas = [
        ['MT035', 1, null, ['Instalación de faenas']],
        ['MT027', $c['escarpe'], null, ['Trampa de arena, reja y cajón', 'Movimiento de tierras']],
        ['MT021', $c['excavacion'], null, ['Trampa de arena, reja y cajón', 'Movimiento de tierras']],
        ['MT024', $c['relleno'], null, ['Trampa de arena, reja y cajón', 'Movimiento de tierras']],
        ['MT033', $c['excedentes_totales'], null, ['Trampa de arena, reja y cajón', 'Movimiento de tierras']],
        ['MT001', $c['emplantillado'], null, ['Trampa de arena, reja y cajón', 'Hormigones']],
        ['MT003', $c['hormigon'], null, ['Trampa de arena, reja y cajón', 'Hormigones']],
        ['MT011', $c['enfierradura'], null, ['Trampa de arena, reja y cajón', 'Hormigones']],
        ['MT018', $c['moldaje'], null, ['Trampa de arena, reja y cajón', 'Hormigones']],
        ['MT039', $c['peso_reja'], null, ['Trampa de arena, reja y cajón', 'Reja']],
        ['MT041', $c['largo_cajon'], null, ['Trampa de arena, reja y cajón', 'Cajón bajo puente']],
        ['MT040', $c['antisol'], null, ['Obras adicionales']],
    ];

    $informativos = [
        ['Ancho trampa de arena (2·ancho cajón)', $c['ancho_trampa'], 'm'],
        ['Alto trampa de arena (alto existente + 0,6)', $c['alto_trampa'], 'm'],
        ['Longitud trampa de arena (fija Excel)', $c['longitud_trampa'], 'm'],
        ['Alto reja proyectada (alto existente + 0,2)', $c['alto_reja'], 'm'],
        ['Ancho reja proyectada (= ancho cajón)', $c['ancho_reja'], 'm'],
        ['Pletinas verticales', $c['n_pletinas_vert'], 'un'],
        ['Metros lineales de pletina (+10% despuntes)', $c['metros_pletina'], 'm'],
        ['Peso pletinas (sin marco/refuerzos)', $c['peso_pletinas'], 'kg'],
        ['Cama de arena bajo cajón (sin código MT)', $c['cama_arena'], 'm³'],
        ['Sección cajón prefabricado (B × H)', $p['ancho_cajon'] . ' × ' . $p['alto_cajon'], 'm'],
    ];

    $notas = [
        "Tipología 4 — Construcción de Cajón, Desarenador y Reja en Cruce de Camino o FFCC. "
            . "Caudal de porteo de referencia: " . $p['caudal'] . " m³/s "
            . "(informativo; no dimensiona la sección en este motor).",
        "La trampa de arena (desarenador) se cubicá con longitud fija de "
            . $c['longitud_trampa'] . " m (Excel G3), ancho = 2·ancho_cajón ("
            . round($c['ancho_trampa'], 2) . " m) y alto = alto_existente + 0,6 m ("
            . round($c['alto_trampa'], 2) . " m). Hormigón G25 con factor 1,2; "
            . "enfierradura " . CUANTIA_ACERO . " kg/m³; antisol = solo caras superiores "
            . "(criterio Excel D14, distinto del moldaje completo).",
        "Movimiento de tierras solo de la trampa (el Excel no cubicá excavación "
            . "del cajón prefabricado bajo puente). Excedentes vía excedentes() "
            . "(incluye escarpe esponjado); el Excel tipología 4 usa "
            . "excav·1,1 − relleno/0,9 sin sumar el escarpe al botadero.",
        "Reja: pletinas 80×3 mm cada 0,1 m + 2 horizontales, +10% despuntes, "
            . "peso total ×2 (marco/refuerzos). Valorizada como MT039. "
            . "Accesorios (20% del costo reja) y anclaje (40%) del Excel no tienen "
            . "código MT en precios.csv — incluir manualmente si aplica.",
        "Cajón prefabricado: MT041 por ml (sección de referencia B "
            . $p['ancho_cajon'] . " m × H " . $p['alto_cajon'] . " m). "
            . "«Cama de arena» (" . round($c['cama_arena'], 2) . " m³) sin código MT.",
        "Ancho existente del canal (" . $p['ancho_existente'] . " m) es informativo "
            . "en este motor: el Excel lo lee en Parámetros pero las fórmulas usan "
            . "ancho_cajón / ancho_trampa.",
    ];

    return [$partidas, $informativos, $notas];
}

$TIPOLOGIAS['cajon_desarenador_reja'] = [
    'titulo' => 'Construcción de Cajón, Desarenador y Reja en Cruce de Camino o FFCC',
    'esquema' => 'esquema-sifon.png',
    'campos' => [
        _campo('caudal', 'Caudal de porteo del canal', 'm³/s', 0.96,
            'Caudal de porteo de referencia. Informativo en este motor (no dimensiona la sección).'),
        _campo('longitud_cajon', 'Longitud del cajón', 'm', 6.0,
            'Longitud del cajón prefabricado bajo el puente / FFCC (ml de MT041).'),
        _campo('ancho_cajon', 'Ancho del cajón', 'm', 2.0,
            'Ancho interior del cajón prefabricado. Define también el ancho de reja y el de la trampa (2×).'),
        _campo('alto_cajon', 'Alto del cajón', 'm', 1.5,
            'Alto interior del cajón prefabricado (sección de referencia H×B del Excel).'),
        _campo('alto_existente', 'Alto existente del canal', 'm', 1.8,
            'Altura de la sección existente. Define alto de trampa (+0,6 m) y alto de reja (+0,2 m).'),
        _campo('ancho_existente', 'Ancho existente del canal', 'm', 4.7,
            'Ancho de la sección existente. Informativo; las obras se dimensionan con ancho_cajón.'),
    ],
    'cubicar' => '_cajon_desarenador_reja',
];
