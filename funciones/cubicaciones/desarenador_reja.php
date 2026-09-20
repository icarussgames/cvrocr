<?php
/**
 * Tipología: Mejoramiento con Desarenador y Reja en Cruce de Camino o FFCC
 * (tipología 5 del catálogo CNR).
 * Portado desde el Excel de ejemplo "Mejoramiento con Desarenador y Reja"
 * (trampa de arena / desarenador + reja hidráulica en la entrada de un
 * sifón existente — sin cajón prefabricado).
 *
 * No confundir con cajon_desarenador_reja.php (tipología 4 —
 * "Construcción de Cajón, Desarenador y Reja"), que incluye el cajón
 * bajo puente/FFCC. Esta obra es de mejoramiento sobre un cruce ya
 * existente.
 */

function cubicacion_desarenador_reja(
    $alto_existente,
    $ancho_existente,
    $alto_caida_entrada
) {
    // Constantes de diseño del Excel (hojas 3.1 TRAMPA DE ARENA / 3.2 REJA).
    $longitud_trampa = 5.0;         // m (G3; fijo en Excel)
    $espesor_muros = 0.2;           // m (G6)
    $separacion_pletina = 0.1;      // m
    $espesor_pletina_mm = 3.0;      // mm
    $alto_pletina_mm = 80.0;        // mm
    $n_pletinas_horiz = 2.0;
    $factor_despuntes = 1.1;        // 10% despuntes en pletinas
    $margen_ancho_entrada = 0.5;    // m (Excel C12 = C10 + 0,5)

    // Derivados geométricos (Excel 2 PARAMETROS → 3.1 / 3.2).
    $ancho_entrada = $ancho_existente + $margen_ancho_entrada; // C12
    $alto_total_entrada = $alto_existente + $alto_caida_entrada; // C11 (informativo)
    $ancho_trampa = $ancho_existente * 2.0;          // G4 ← C10*2
    $alto_trampa = $alto_existente + 0.6;            // G5
    $alto_reja = $alto_existente + 0.2;              // F4
    $ancho_reja = $ancho_entrada;                    // F5 ← C12
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
    // Excel tipología 5 pone largo de pletinas verticales = F5 (ancho);
    // aquí se usa el alto de reja (criterio físico, igual que tip.4 /
    // cajon_desarenador_reja.php). Con defaults tip.5 (alto≠ancho) el
    // peso difiere del Excel — ver notas.
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
        'ancho_trampa' => $ancho_trampa,
        'alto_trampa' => $alto_trampa,
        'alto_reja' => $alto_reja,
        'ancho_reja' => $ancho_reja,
        'ancho_entrada' => $ancho_entrada,
        'alto_total_entrada' => $alto_total_entrada,
        'longitud_trampa' => $longitud_trampa,
    ];
}

function _desarenador_reja($p) {
    $c = cubicacion_desarenador_reja(
        $p['alto_existente'],
        $p['ancho_existente'],
        $p['alto_caida_entrada']
    );

    $partidas = [
        ['MT035', 1, null, ['Instalación de faenas']],
        ['MT027', $c['escarpe'], null, ['Trampa de arena y reja', 'Movimiento de tierras']],
        ['MT021', $c['excavacion'], null, ['Trampa de arena y reja', 'Movimiento de tierras']],
        ['MT024', $c['relleno'], null, ['Trampa de arena y reja', 'Movimiento de tierras']],
        ['MT033', $c['excedentes_totales'], null, ['Trampa de arena y reja', 'Movimiento de tierras']],
        ['MT001', $c['emplantillado'], null, ['Trampa de arena y reja', 'Hormigones']],
        ['MT003', $c['hormigon'], null, ['Trampa de arena y reja', 'Hormigones']],
        ['MT011', $c['enfierradura'], null, ['Trampa de arena y reja', 'Hormigones']],
        ['MT018', $c['moldaje'], null, ['Trampa de arena y reja', 'Hormigones']],
        ['MT039', $c['peso_reja'], null, ['Trampa de arena y reja', 'Reja']],
        ['MT040', $c['antisol'], null, ['Obras adicionales']],
    ];

    $informativos = [
        ['Ancho entrada (ancho existente + 0,5)', $c['ancho_entrada'], 'm'],
        ['Alto total entrada (alto existente + caída)', $c['alto_total_entrada'], 'm'],
        ['Ancho trampa de arena (2·ancho existente)', $c['ancho_trampa'], 'm'],
        ['Alto trampa de arena (alto existente + 0,6)', $c['alto_trampa'], 'm'],
        ['Longitud trampa de arena (fija Excel)', $c['longitud_trampa'], 'm'],
        ['Alto reja proyectada (alto existente + 0,2)', $c['alto_reja'], 'm'],
        ['Ancho reja proyectada (= ancho entrada)', $c['ancho_reja'], 'm'],
        ['Pletinas verticales', $c['n_pletinas_vert'], 'un'],
        ['Metros lineales de pletina (+10% despuntes)', $c['metros_pletina'], 'm'],
        ['Peso pletinas (sin marco/refuerzos)', $c['peso_pletinas'], 'kg'],
        ['Longitud sifón existente (informativo)', $p['longitud_sifon'], 'm'],
    ];

    $notas = [
        "Tipología 5 — Mejoramiento con Desarenador y Reja en Cruce de Camino o FFCC. "
            . "Agrega trampa de arena y reja hidráulica a la entrada de un sifón "
            . "existente (longitud " . $p['longitud_sifon'] . " m). "
            . "Caudal de porteo de referencia: " . $p['caudal'] . " m³/s "
            . "(informativo; no dimensiona la sección en este motor).",
        "No incluye cajón prefabricado (eso es tipología 4 / "
            . "cajon_desarenador_reja). Aquí solo hay desarenador + reja.",
        "La trampa de arena se cubicá con longitud fija de "
            . $c['longitud_trampa'] . " m (Excel G3), ancho = 2·ancho_existente ("
            . round($c['ancho_trampa'], 2) . " m) y alto = alto_existente + 0,6 m ("
            . round($c['alto_trampa'], 2) . " m). Hormigón G25 con factor 1,2; "
            . "enfierradura " . CUANTIA_ACERO . " kg/m³; antisol = solo caras superiores "
            . "(criterio Excel D14).",
        "Movimiento de tierras solo de la trampa. Excedentes vía excedentes() "
            . "(incluye escarpe esponjado); el Excel tipología 5 usa "
            . "excav·1,1 − relleno/0,9 sin sumar el escarpe al botadero.",
        "Reja: pletinas 80×3 mm cada 0,1 m + 2 horizontales, +10% despuntes, "
            . "peso total ×2 (marco/refuerzos). Valorizada como MT039. "
            . "El Excel tip.5 usa largo de verticales = ancho reja (F5); este "
            . "motor usa alto reja (criterio físico, igual tip.4). Accesorios "
            . "(20% del costo reja) y anclaje (40%) del Excel no tienen código "
            . "MT en precios.csv — incluir manualmente si aplica.",
        "Alto de caída de entrada (" . $p['alto_caida_entrada'] . " m) define el "
            . "alto total de entrada informativo; no entra a las fórmulas de "
            . "cubicación de trampa/reja.",
    ];

    return [$partidas, $informativos, $notas];
}

$TIPOLOGIAS['desarenador_reja'] = [
    'titulo' => 'Mejoramiento con Desarenador y Reja en Cruce de Camino o FFCC',
    'esquema' => 'esquema-sifon.png',
    'campos' => [
        _campo('caudal', 'Caudal de porteo del canal', 'm³/s', 1.16,
            'Caudal de porteo de referencia. Informativo en este motor (no dimensiona la sección).'),
        _campo('longitud_sifon', 'Longitud del sifón existente', 'm', 250.0,
            'Longitud del sifón / cruce ya existente. Informativo; no entra a las cubicaciones.'),
        _campo('alto_existente', 'Alto existente del canal', 'm', 1.0,
            'Altura de la sección existente. Define alto de trampa (+0,6 m) y alto de reja (+0,2 m).'),
        _campo('ancho_existente', 'Ancho existente del canal', 'm', 2.5,
            'Ancho de la sección existente. Define ancho de trampa (2×) y ancho de entrada/reja (+0,5 m).'),
        _campo('alto_caida_entrada', 'Alto de caída en la entrada', 'm', 3.0,
            'Caída entre el canal existente y el piso de la trampa/entrada. Define el alto total de entrada (informativo).'),
    ],
    'cubicar' => '_desarenador_reja',
];
