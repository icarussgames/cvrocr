<?php
/**
 * Tipología: Mejoramiento con Portales y Revestimiento en Túnel
 * (tipología 10 del catálogo CNR).
 * Portado desde el Excel de ejemplo "Mejoramiento con Portales y
 * Revestimiento en Túnel" (revestimiento interior con shotcrete +
 * portales de hormigón armado en entrada y salida).
 *
 * El splash tipología 10 tenía engineId null tras v0.6 (se borró el
 * tunel_shotcrete.php legacy). Este es un motor NUEVO Excel-port, no
 * el legacy (que solo cubría shotcrete sin portales).
 */

function cubicacion_tunel_portales(
    $longitud_tunel,
    $ancho_tunel,
    $alto_tunel
) {
    // Constantes de diseño del Excel (hojas 3.1 REVESTIMIENTO / 3.2 PORTALES).
    $espesor_radier = 0.15;        // m (F4) — informativo; el presupuesto cobra m² shotcrete
    $espesor_muro_techo = 0.08;    // m (F5) — e=8 cm del shotcrete
    $sobreancho_portal = 0.25;     // m (F6)
    $cimiento_portal = 0.3;        // m (F7)
    $techo_portal = 0.4;           // m (F8)
    $largo_portal = 0.4;           // m (F9) sentido túnel
    $sobreexcavacion = 0.5;        // m (F10) — no entra a fórmula Excel C26 (usa F6)

    $L = $longitud_tunel;          // F3 ← C8
    $b = $ancho_tunel;             // F7 / F4 ← C12
    $h = $alto_tunel;              // F6 / F5 ← C14 (alto proyectado)

    // --- 3.1 REVESTIMIENTO SHOTCRETE ---
    // C7 = F3*F7; C12 = 2*F3*F6 + F7*F3; C18 = C12+C7
    $superficie_radier = $L * $b;
    $area_muros_techo = 2.0 * $L * $h + $b * $L;
    $superficie_shotcrete = $area_muros_techo + $superficie_radier; // C18 = C19 malla
    // Volúmenes informativos (Excel C10 / C15); no aparecen como partidas.
    $volumen_radier = $superficie_radier * $espesor_radier;
    $volumen_muros = $area_muros_techo * $espesor_muro_techo;
    $excavacion_roca = 0.0; // Excel C21 fijo 0

    // --- 3.2 PORTALES (entrada + salida) ---
    // Columna: F6*(F5+F8+F7)*F9; viga: F4*F8*F9; cimiento: F4*F7*F9
    $vol_columna = $sobreancho_portal * ($h + $techo_portal + $cimiento_portal) * $largo_portal;
    $vol_viga = $b * $techo_portal * $largo_portal;
    $vol_cimiento = $b * $cimiento_portal * $largo_portal;
    $hormigon_portal_uno = 2.0 * $vol_columna + $vol_viga + $vol_cimiento; // C11 / C18
    $hormigon = 2.0 * $hormigon_portal_uno; // C20 entrada + salida
    $enfierradura = CUANTIA_ACERO * $hormigon; // C21 = C20*80

    // Moldaje Excel C22 (un portal × 1,2; no multiplica por 2 portales):
    // ((F5+F7+F8)*F6 + (F5+F7+F8)*F9 + F7*F4 + F4*F8 + F4*F9 + F4*F9)*1,2
    $alto_total_portal = $h + $cimiento_portal + $techo_portal;
    $moldaje = (
        $alto_total_portal * $sobreancho_portal
        + $alto_total_portal * $largo_portal
        + $cimiento_portal * $b
        + $b * $techo_portal
        + $b * $largo_portal
        + $b * $largo_portal
    ) * 1.2;
    $antisol = $moldaje; // C24 = C22 (presupuesto tip.10 lo deja en 0)

    // Movimiento de tierras portales (Excel C26–C28, C30).
    // C26 = F7*(F4+F6+F6)*F9*2  (no usa F10 sobreexcavación)
    $excavacion = $cimiento_portal * ($b + 2.0 * $sobreancho_portal) * $largo_portal * 2.0;
    $relleno = $excavacion * 0.5 * 2.0; // C27 = C26*0.5*2
    // C30 = (F4+F6+F6)*F9*2 — sin espesor; presupuesto tip.10 lo deja en 0.
    $emplantillado = ($b + 2.0 * $sobreancho_portal) * $largo_portal * 2.0;
    // Excel C28 ≈ −0,005 → presupuesto E15 = 0. Usamos excedentes().
    [$excedente_escarpe, $excedente_excavacion, $excedentes_totales] =
        excedentes(0.0, $excavacion, $relleno);

    return [
        'superficie_shotcrete' => $superficie_shotcrete,
        'superficie_radier' => $superficie_radier,
        'area_muros_techo' => $area_muros_techo,
        'volumen_radier' => $volumen_radier,
        'volumen_muros' => $volumen_muros,
        'excavacion_roca' => $excavacion_roca,
        'hormigon' => $hormigon,
        'hormigon_portal_uno' => $hormigon_portal_uno,
        'enfierradura' => $enfierradura,
        'moldaje' => $moldaje,
        'antisol' => $antisol,
        'excavacion' => $excavacion,
        'relleno' => $relleno,
        'emplantillado' => $emplantillado,
        'excedente_escarpe' => $excedente_escarpe,
        'excedente_excavacion' => $excedente_excavacion,
        'excedentes_totales' => $excedentes_totales,
        'espesor_radier' => $espesor_radier,
        'espesor_muro_techo' => $espesor_muro_techo,
        'sobreancho_portal' => $sobreancho_portal,
        'cimiento_portal' => $cimiento_portal,
        'techo_portal' => $techo_portal,
        'largo_portal' => $largo_portal,
        'L' => $L,
        'b' => $b,
        'h' => $h,
    ];
}

function _tunel_portales($p) {
    $c = cubicacion_tunel_portales(
        $p['longitud_tunel'],
        $p['ancho_tunel'],
        $p['alto_tunel']
    );

    // Partidas alineadas al presupuesto Excel tip.10 (ítems con cantidad > 0
    // o que el Excel lista aunque queden en 0). Emplantillado y antisol se
    // calculan en 3.2 pero el presupuesto los deja en 0 — no se incluyen.
    $partidas = [
        ['MT035', 1, null, ['Instalación de faenas']],
        ['MT021', $c['excavacion'], null, ['Portales y revestimiento', 'Movimiento de tierras']],
        ['MT024', $c['relleno'], null, ['Portales y revestimiento', 'Movimiento de tierras']],
        ['MT033', $c['excedentes_totales'], null, ['Portales y revestimiento', 'Movimiento de tierras']],
        ['MT003', $c['hormigon'], null, ['Portales y revestimiento', 'Hormigones']],
        ['MT011', $c['enfierradura'], null, ['Portales y revestimiento', 'Hormigones']],
        ['MT018', $c['moldaje'], null, ['Portales y revestimiento', 'Hormigones']],
        ['MT037', $c['superficie_shotcrete'], null, ['Portales y revestimiento', 'Revestimiento shotcrete']],
        ['MT038', $c['superficie_shotcrete'], null, ['Portales y revestimiento', 'Revestimiento shotcrete']],
    ];

    $informativos = [
        ['Alto existente del canal (informativo)', $p['alto_existente'], 'm'],
        ['Ancho existente del canal (informativo)', $p['ancho_existente'], 'm'],
        ['Espesor radier shotcrete (fijo Excel)', $c['espesor_radier'], 'm'],
        ['Espesor muro/techo shotcrete (fijo Excel)', $c['espesor_muro_techo'], 'm'],
        ['Superficie radier', $c['superficie_radier'], 'm²'],
        ['Área muros + techo', $c['area_muros_techo'], 'm²'],
        ['Volumen radier (informativo; no se cobra aparte)', $c['volumen_radier'], 'm³'],
        ['Volumen muros/techo (informativo; no se cobra aparte)', $c['volumen_muros'], 'm³'],
        ['Hormigón un portal (entrada o salida)', $c['hormigon_portal_uno'], 'm³'],
        ['Sobreancho portal (fijo Excel)', $c['sobreancho_portal'], 'm'],
        ['Cimiento / techo / largo portal', $c['cimiento_portal'] . ' / ' . $c['techo_portal'] . ' / ' . $c['largo_portal'], 'm'],
        ['Emplantillado calculado Excel C30 (presupuesto = 0)', $c['emplantillado'], 'm³'],
        ['Antisol = moldaje Excel C24 (presupuesto = 0)', $c['antisol'], 'm²'],
    ];

    $notas = [
        "Tipología 10 — Mejoramiento con Portales y Revestimiento en Túnel. "
            . "Revestimiento interior con shotcrete e=8 cm (radier + muros + "
            . "techo) y portales de hormigón armado en entrada y salida. "
            . "Caudal de porteo de referencia: " . $p['caudal'] . " m³/s "
            . "(informativo; no dimensiona la sección).",
        "Shotcrete: superficie = 2·L·H + 2·L·b = "
            . round($c['superficie_shotcrete'], 2) . " m² (Excel C18). Se "
            . "valoriza como MT037 (shotcrete) + MT038 (malla ACMA C-139), "
            . "ambos con la misma superficie. El Excel tip.10 usa un PU "
            . "combinado (~43 560 \$/m²); precios.csv los separa (35 000 + "
            . "7 500). Volúmenes de radier/muros del Excel 3.1 son "
            . "informativos — no se cobran como hormigón G25.",
        "Portales: hormigón G25 = 2 × (2 columnas + viga + cimiento) = "
            . round($c['hormigon'], 3) . " m³. Enfierradura "
            . CUANTIA_ACERO . " kg/m³. Moldaje según fórmula Excel C22 "
            . "(un portal × 1,2; no multiplica por 2 portales — fidelidad "
            . "parcial al Excel de ejemplo).",
        "Movimiento de tierras solo de portales (Excel C26–C28). Excavación "
            . "= cimiento·(ancho+2·sobreancho)·largo·2; relleno = excav·0,5·2. "
            . "Excedentes vía excedentes() (≈ 0 en el ejemplo, igual que "
            . "presupuesto E15 = 0). Escarpe = 0 en el Excel.",
        "Emplantillado (C30) y antisol (C24) se calculan en 3.2 pero el "
            . "presupuesto tip.10 los deja en cantidad 0 — no se incluyen "
            . "como partidas (sí aparecen como informativos). Excavación en "
            . "roca (C21 = 0) tampoco se incluye.",
        "Geometría fija del Excel: sobreancho portal 0,25 m, cimiento 0,3 m, "
            . "techo 0,4 m, largo portal 0,4 m, espesor radier 0,15 m, "
            . "espesor muro/techo 0,08 m. Alto/ancho existente del canal son "
            . "informativos (no entran a las cubicaciones).",
    ];

    return [$partidas, $informativos, $notas];
}

$TIPOLOGIAS['tunel_portales'] = [
    'titulo' => 'Mejoramiento con Portales y Revestimiento en Túnel',
    'esquema' => 'esquema-sifon.png',
    'campos' => [
        _campo('caudal', 'Caudal de porteo del canal', 'm³/s', 0.65,
            'Caudal de porteo de referencia. Informativo en este motor (no dimensiona la sección).'),
        _campo('longitud_tunel', 'Longitud del túnel', 'm', 20.0,
            'Largo del túnel a revestir con shotcrete (Excel: longitud túnel).'),
        _campo('ancho_tunel', 'Ancho del túnel', 'm', 1.4,
            'Ancho interior del túnel proyectado. Define radier, techo y portales.'),
        _campo('alto_tunel', 'Alto del túnel proyectado', 'm', 1.0,
            'Alto interior del túnel proyectado. Define muros de shotcrete y alto de portales.'),
        _campo('alto_existente', 'Alto existente del canal', 'm', 1.0,
            'Altura de la sección de canal existente. Informativo; no entra a las cubicaciones.'),
        _campo('ancho_existente', 'Ancho existente del canal', 'm', 1.0,
            'Ancho de la sección de canal existente. Informativo; no entra a las cubicaciones.'),
    ],
    'cubicar' => '_tunel_portales',
];
