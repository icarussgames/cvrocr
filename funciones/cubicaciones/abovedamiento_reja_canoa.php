<?php
/**
 * Tipología: Mejoramiento con Abovedamiento y Reja en Canoas
 * (tipología 7 del catálogo CNR).
 * Portado desde el Excel de ejemplo "Mejoramiento con Abovedamiento
 * y Reja en Canoas" (losa de abovedamiento aguas arriba y abajo de
 * una canoa existente + rejas en entrada y salida).
 *
 * NO confundir con canoa.php (tipología 6 — Construcción de Canoa),
 * que construye la canoa nueva con estribos. Esta obra es de
 * mejoramiento sobre una canoa ya existente.
 */

function cubicacion_abovedamiento_reja_canoa(
    $abovedamiento_aguas_arriba,
    $abovedamiento_aguas_abajo,
    $alto_existente,
    $ancho_existente,
    $ancho_losa
) {
    // Constantes de diseño del Excel (hojas 3.1 LOSAS / 3.2 REJAS).
    $espesor_losa = 0.15;           // m (F10)
    $separacion_pletina = 0.1;      // m (F6)
    $espesor_pletina_mm = 3.0;      // mm (F7)
    $alto_pletina_mm = 80.0;        // mm (F8)
    $n_pletinas_horiz = 2.0;        // (C7)
    $factor_despuntes = 1.1;        // 10% despuntes
    $margen_alto_reja = 0.1;        // m (Excel F4 = C15 + F10 + 0,1)

    $L_up = $abovedamiento_aguas_arriba;   // F8 ← C9
    $L_down = $abovedamiento_aguas_abajo;  // F9 ← C10
    $e = $espesor_losa;                    // F10
    $b_losa = $ancho_losa;                 // F7 ← C14

    // Derivados geométricos (Excel 2 PARAMETROS).
    // C13 = C12 + C11*0,25*2 (talud 0,25:1 a ambos lados) — informativo.
    $ancho_superior = $ancho_existente + $alto_existente * 0.25 * 2.0;
    // C15 = C11; F4 = C15 + F10 + 0,1
    $alto_reja = $alto_existente + $e + $margen_alto_reja;
    $ancho_reja = $ancho_existente; // F5 ← C12

    // --- 3.1 LOSAS ABOVEDAMIENTO ---
    // C6 = F8*F10*F7; C11 = SUM(C6:C10) ≈ C6 (celdas intermedias vacías).
    $hormigon_entrada = $L_up * $e * $b_losa;
    // C13 = F9*F10*F7; C18 = SUM(C13:C17) ≈ C13.
    $hormigon_salida = $L_down * $e * $b_losa;
    $hormigon = $hormigon_entrada + $hormigon_salida; // C20
    $enfierradura = CUANTIA_ACERO * $hormigon;         // C21 = C20*80

    // Moldaje Excel C22 (unidad m² pese a etiqueta m3):
    // F8*F7*2 + F7*F10*ROUNDUP(F8/3,0) + F9*F10*2
    // + F9*F7*2 + F7*F10*ROUNDUP(F9/3,0) + F9*F10*2
    // (asimetría entrada/salida tal cual el Excel de ejemplo).
    $moldaje = $L_up * $b_losa * 2.0
        + $b_losa * $e * ceil($L_up / 3.0)
        + $L_down * $e * 2.0
        + $L_down * $b_losa * 2.0
        + $b_losa * $e * ceil($L_down / 3.0)
        + $L_down * $e * 2.0;
    $antisol = $moldaje; // C24 = C22

    // --- 3.2 REJAS (entrada + salida) ---
    // Excel tip.7 pone largo de pletinas verticales = F5 (ancho);
    // aquí se usa el alto de reja (criterio físico, igual tip.4/5).
    $n_pletinas_vert = $ancho_reja / $separacion_pletina + 1.0;
    $metros_pletina = (
        $n_pletinas_horiz * $ancho_reja
        + $n_pletinas_vert * $alto_reja
    ) * $factor_despuntes;
    $peso_pletinas = $metros_pletina
        * ($espesor_pletina_mm / 1000.0)
        * ($alto_pletina_mm / 1000.0)
        * DENSIDAD_ACERO;
    $peso_reja_una = $peso_pletinas * 2.0; // pletinas + refuerzos/marco (C13)
    $peso_reja = $peso_reja_una * 2.0;     // aguas arriba + abajo (C18)

    return [
        'hormigon' => $hormigon,
        'hormigon_entrada' => $hormigon_entrada,
        'hormigon_salida' => $hormigon_salida,
        'enfierradura' => $enfierradura,
        'moldaje' => $moldaje,
        'antisol' => $antisol,
        'peso_reja' => $peso_reja,
        'peso_reja_una' => $peso_reja_una,
        'peso_pletinas' => $peso_pletinas,
        'metros_pletina' => $metros_pletina,
        'n_pletinas_vert' => $n_pletinas_vert,
        'alto_reja' => $alto_reja,
        'ancho_reja' => $ancho_reja,
        'ancho_superior' => $ancho_superior,
        'espesor_losa' => $espesor_losa,
        'L_up' => $L_up,
        'L_down' => $L_down,
    ];
}

function _abovedamiento_reja_canoa($p) {
    $c = cubicacion_abovedamiento_reja_canoa(
        $p['abovedamiento_aguas_arriba'],
        $p['abovedamiento_aguas_abajo'],
        $p['alto_existente'],
        $p['ancho_existente'],
        $p['ancho_losa']
    );

    $partidas = [
        ['MT035', 1, null, ['Instalación de faenas']],
        ['MT003', $c['hormigon'], null, ['Abovedado y rejas', 'Hormigones (losa)']],
        ['MT011', $c['enfierradura'], null, ['Abovedado y rejas', 'Hormigones (losa)']],
        ['MT018', $c['moldaje'], null, ['Abovedado y rejas', 'Hormigones (losa)']],
        ['MT039', $c['peso_reja'], null, ['Abovedado y rejas', 'Reja']],
        ['MT040', $c['antisol'], null, ['Obras adicionales']],
    ];

    $informativos = [
        ['Longitud canoa existente (informativo)', $p['longitud_canoa'], 'm'],
        ['Ancho superior canal (basal + 2·0,25·alto)', $c['ancho_superior'], 'm'],
        ['Espesor losa abovedamiento (fijo Excel)', $c['espesor_losa'], 'm'],
        ['Volumen hormigón abov. aguas arriba', $c['hormigon_entrada'], 'm³'],
        ['Volumen hormigón abov. aguas abajo', $c['hormigon_salida'], 'm³'],
        ['Alto reja proyectada (alto + espesor + 0,1)', $c['alto_reja'], 'm'],
        ['Ancho reja proyectada (= ancho existente)', $c['ancho_reja'], 'm'],
        ['Pletinas verticales (por reja)', $c['n_pletinas_vert'], 'un'],
        ['Metros lineales de pletina por reja (+10%)', $c['metros_pletina'], 'm'],
        ['Peso pletinas por reja (sin marco)', $c['peso_pletinas'], 'kg'],
        ['Peso reja una (pletinas + refuerzos)', $c['peso_reja_una'], 'kg'],
    ];

    $notas = [
        "Tipología 7 — Mejoramiento con Abovedamiento y Reja en Canoas. "
            . "Aboveda tramos de canal aguas arriba ("
            . $c['L_up'] . " m) y aguas abajo ("
            . $c['L_down'] . " m) de una canoa existente (largo "
            . $p['longitud_canoa'] . " m) e instala rejas en entrada y "
            . "salida. Caudal de porteo de referencia: "
            . $p['caudal'] . " m³/s (informativo; no dimensiona la sección).",
        "No construye la canoa (eso es tipología 6 / canoa.php). Aquí solo "
            . "hay losas de abovedamiento + dos rejas sobre obra existente.",
        "Hormigón G25 = losa aguas arriba ("
            . round($c['hormigon_entrada'], 2) . " m³) + losa aguas abajo ("
            . round($c['hormigon_salida'], 2) . " m³). Fórmula Excel C6/C13 = "
            . "L · espesor · ancho_losa (espesor fijo 0,15 m). Enfierradura "
            . CUANTIA_ACERO . " kg/m³ (Excel C21). Moldaje según fórmula "
            . "asimétrica Excel C22; antisol = moldaje.",
        "Sin movimiento de tierras en el presupuesto de ejemplo (la losa "
            . "se apoya sobre el canal existente). Emplantillado tampoco "
            . "aparece — no se incluye.",
        "Reja: pletinas 80×3 mm cada 0,1 m + 2 horizontales, +10% despuntes, "
            . "peso ×2 (marco/refuerzos) y ×2 (entrada + salida). Valorizada "
            . "como MT039. El Excel tip.7 usa largo de verticales = ancho reja "
            . "(F5); este motor usa alto reja (criterio físico, igual tip.4/5). "
            . "Accesorios (20% del costo reja) y anclaje (40%) del Excel no "
            . "tienen código MT en precios.csv — incluir manualmente si aplica.",
        "Ancho superior del canal ("
            . round($c['ancho_superior'], 2) . " m = basal + 2·0,25·alto) es "
            . "informativo; no entra a las cubicaciones (la losa usa "
            . "ancho_losa = " . $p['ancho_losa'] . " m).",
    ];

    return [$partidas, $informativos, $notas];
}

$TIPOLOGIAS['abovedamiento_reja_canoa'] = [
    'titulo' => 'Mejoramiento con Abovedamiento y Reja en Canoas',
    'esquema' => 'esquema-sifon.png',
    'campos' => [
        _campo('caudal', 'Caudal de porteo del canal', 'm³/s', 0.9,
            'Caudal de porteo de referencia. Informativo en este motor (no dimensiona la sección).'),
        _campo('longitud_canoa', 'Longitud de la canoa existente', 'm', 12.0,
            'Largo de la canoa ya existente. Informativo; no entra a las cubicaciones de abovedamiento/reja.'),
        _campo('abovedamiento_aguas_arriba', 'Abovedamiento aguas arriba', 'm', 10.0,
            'Longitud del tramo de canal a abovedar aguas arriba de la canoa.'),
        _campo('abovedamiento_aguas_abajo', 'Abovedamiento aguas abajo', 'm', 7.0,
            'Longitud del tramo de canal a abovedar aguas abajo de la canoa.'),
        _campo('alto_existente', 'Alto existente del canal', 'm', 0.9,
            'Altura de la sección existente. Define el alto de reja (+ espesor losa 0,15 m + 0,1 m).'),
        _campo('ancho_existente', 'Ancho basal existente del canal', 'm', 1.4,
            'Ancho basal de la sección existente. Define el ancho de reja y el ancho superior informativo.'),
        _campo('ancho_losa', 'Ancho de la losa de abovedamiento', 'm', 2.2,
            'Ancho de la losa de hormigón que tapa el canal (debe cubrir el ancho superior + apoyos).'),
    ],
    'cubicar' => '_abovedamiento_reja_canoa',
];
