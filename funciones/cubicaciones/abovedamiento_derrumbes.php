<?php
/**
 * Tipología: Mejoramiento con Abovedamiento en Zonas de Derrumbes y
 * Otras Singularidades (tipología 12 del catálogo CNR; antes 11b).
 * Portado desde el Excel de ejemplo (reemplazo del tramo con cajón
 * prefabricado rectangular H×B × L).
 *
 * NO confundir con:
 * - revestimiento_canal.php (tipología 11 — revestimiento in situ)
 * - abovedamiento_reja_canoa.php (tipología 7 — losa + reja en canoa)
 */

function cubicacion_abovedamiento_derrumbes(
    $largo,
    $ancho,
    $alto,
    $espesor
) {
    // Constantes de diseño del Excel (hojas 3.1 CUB REV / 3.2 CUB OBRAS).
    $margen_escarpe_lado = 3.0;       // m (C6 = 3 + ancho + 3)
    $profundidad_escarpe = 0.15;     // m (C7)
    $ancho_sobre_excavacion = 0.4;   // m (C18) por lado
    $profundidad_excavacion = 0.7;   // m (C22)
    $margen_sobre_relleno = 0.1;     // m (C21 = alto_muros + 0,1)
    $espesor_base_granular = 0.1;    // m (C23 = L · 0,1 · B; = BASEGR)

    $L = $largo;   // F3 ← C9
    $B = $ancho;   // F7 ← C8 interior
    $H = $alto;    // F6 ← C7 interior
    $e = $espesor; // F4 / F5 ← C11 (losa y muro del cajón)

    // --- 3.1 CUB REV ---
    // C23 = F3*0,1*F7; C25 = F3*F6 + F3*F7 + F3*F6
    $base_granular = $L * $espesor_base_granular * $B;
    $antisol = $L * $H + $L * $B + $L * $H; // 2·L·H + L·B

    // --- 3.2 CUB OBRAS: escarpe ---
    $ancho_escarpe = $margen_escarpe_lado + $B + $margen_escarpe_lado; // C6
    $roce_faja = $ancho_escarpe * $L;                                   // C9
    $escarpe = $roce_faja * $profundidad_escarpe;                       // C10

    // --- 3.2 CUB OBRAS: movimiento de tierras ---
    $ancho_total_excavacion = $ancho_sobre_excavacion + $B + $ancho_sobre_excavacion; // C19
    // C20 = F7+F4+F5 (alto interior + espesor losa + espesor muro)
    $alto_muros = $H + $e + $e;
    $altura_max_relleno = $alto_muros + $margen_sobre_relleno; // C21
    $excavacion = $ancho_total_excavacion * $L * $profundidad_excavacion; // C24
    $relleno = $L * $altura_max_relleno * 2.0 * $ancho_sobre_excavacion;  // C25

    // Excedentes Excel C29–C32 (no usa comun.php: saldo = excav − relleno,
    // sin compactación 0,9; esponjamiento 10% sobre escarpe y sobre saldo).
    $esponjamiento = ESPONJAMIENTO; // 0,10 (= F9)
    $excedente_escarpe = $escarpe * (1.0 + $esponjamiento); // C29
    $saldo = $excavacion - $relleno;                         // C27
    $excedente_excavacion = $saldo > 0
        ? $saldo * (1.0 + $esponjamiento)
        : 0.0;                                               // C30
    $excedentes_totales = $excedente_escarpe + $excedente_excavacion; // C32

    return [
        'largo_cajon' => $L,
        'base_granular' => $base_granular,
        'antisol' => $antisol,
        'roce_faja' => $roce_faja,
        'escarpe' => $escarpe,
        'ancho_escarpe' => $ancho_escarpe,
        'ancho_total_excavacion' => $ancho_total_excavacion,
        'alto_muros' => $alto_muros,
        'altura_max_relleno' => $altura_max_relleno,
        'excavacion' => $excavacion,
        'relleno' => $relleno,
        'excedente_escarpe' => $excedente_escarpe,
        'excedente_excavacion' => $excedente_excavacion,
        'excedentes_totales' => $excedentes_totales,
        'espesor' => $e,
        'profundidad_excavacion' => $profundidad_excavacion,
        'profundidad_escarpe' => $profundidad_escarpe,
        'ancho_sobre_excavacion' => $ancho_sobre_excavacion,
    ];
}

function _abovedamiento_derrumbes($p) {
    $c = cubicacion_abovedamiento_derrumbes(
        $p['largo'],
        $p['ancho'],
        $p['alto'],
        $p['espesor']
    );

    $partidas = [
        ['MT035', 1, null, ['Instalación de faenas']],
        ['MT027', $c['escarpe'], null, ['Tramo abovedado', 'Movimiento de tierras']],
        ['MT021', $c['excavacion'], null, ['Tramo abovedado', 'Movimiento de tierras']],
        ['MT024', $c['relleno'], null, ['Tramo abovedado', 'Movimiento de tierras']],
        ['MT033', $c['excedentes_totales'], null, ['Tramo abovedado', 'Movimiento de tierras']],
        ['MT041', $c['largo_cajon'], null, ['Tramo abovedado', 'Cajón prefabricado']],
        ['MT026', $c['base_granular'], null, ['Tramo abovedado', 'Cajón prefabricado']],
        ['MT040', $c['antisol'], null, ['Obras adicionales']],
    ];

    $informativos = [
        ['Roce de la faja del canal', $c['roce_faja'], 'm²'],
        ['Ancho faja escarpe (3 + B + 3)', $c['ancho_escarpe'], 'm'],
        ['Ancho total excavación (0,4 + B + 0,4)', $c['ancho_total_excavacion'], 'm'],
        ['Alto muros cajón (H + 2·e)', $c['alto_muros'], 'm'],
        ['Altura máxima a rellenar (+0,1 m)', $c['altura_max_relleno'], 'm'],
        ['Excedente escarpe (×1,1)', $c['excedente_escarpe'], 'm³'],
        ['Excedente mov. de tierras (×1,1)', $c['excedente_excavacion'], 'm³'],
        ['Talud (informativo; cajón rectangular)', $p['talud'], '-'],
    ];

    $notas = [
        "Tipología 12 — Mejoramiento con Abovedamiento en Zonas de Derrumbes "
            . "y Otras Singularidades. Reemplazo del tramo (L = "
            . $p['largo'] . " m) por cajón prefabricado rectangular H×B = "
            . $p['alto'] . "×" . $p['ancho'] . " m, espesor " . $p['espesor']
            . " m. Caudal de porteo de referencia: " . $p['caudal']
            . " m³/s (informativo; no dimensiona la sección).",
        "No es tipología 11 (revestimiento_canal: hormigón in situ) ni "
            . "tipología 7 (abovedamiento_reja_canoa: losa + reja sobre canoa).",
        "Movimiento de tierras Excel 3.2: escarpe = (3+B+3)·L·0,15; "
            . "excavación = (0,4+B+0,4)·L·0,7; relleno = L·(H+2e+0,1)·2·0,4. "
            . "Excedentes = escarpe·1,1 + max(0, excav−relleno)·1,1 "
            . "(fórmula Excel C29–C32; no usa compactación 0,9 de excedentes()).",
        "Cajón prefabricado valorizado como MT041 por ml (sección de "
            . "referencia del Excel). Base granular = L·0,1·B (MT026). "
            . "Antisol = 2·L·H + L·B (MT040). El Excel no cobra hormigón/"
            . "enfierradura/moldaje in situ — el cajón llega prefabricado.",
        "Precios desde precios.csv (MT*). El PU de relleno del Excel "
            . "(≈9603) difiere de MT024; se usa el vector del CSV. Talud "
            . "del Excel no entra a las fórmulas del cajón rectangular.",
    ];

    return [$partidas, $informativos, $notas];
}

$TIPOLOGIAS['abovedamiento_derrumbes'] = [
    'titulo' => 'Mejoramiento con Abovedamiento en Zonas de Derrumbes y Otras Singularidades',
    'esquema' => 'esquema_revest.png',
    'campos' => [
        _campo('caudal', 'Caudal de porteo del canal', 'm³/s', 0.5,
            'Caudal de porteo de referencia. Informativo en este motor (no dimensiona la sección).'),
        _campo('largo', 'Largo del tramo', 'm', 100.0,
            'Longitud del tramo a reemplazar con cajón prefabricado (ml de MT041).'),
        _campo('ancho', 'Ancho interior del cajón', 'm', 1.0,
            'Ancho interior del cajón prefabricado (sección B).'),
        _campo('alto', 'Alto interior del cajón', 'm', 1.0,
            'Alto interior del cajón prefabricado (sección H).'),
        _campo('espesor', 'Espesor muro/losa del cajón', 'm', 0.13,
            'Espesor de losa y muro del cajón (Excel C11; igual para ambos). Entra al alto de muros y al relleno.'),
        _campo('talud', 'Talud de los muros (H/V)', '-', 0.0,
            'Informativo. El Excel lo lista pero el cajón rectangular no lo usa en las cubicaciones.'),
    ],
    'cubicar' => '_abovedamiento_derrumbes',
];
