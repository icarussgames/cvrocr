<?php
/**
 * Tipología: Reposición de Compuertas (tipología 2 del catálogo CNR).
 * Portado desde el Excel de ejemplo "Reposición de Compuertas"
 * (compuerta de descarga + revestimiento del tramo de descarga).
 *
 * Incluye movimiento de tierras y hormigones del revestimiento de la
 * descarga, más reposición de compuertas metálicas. No modela demolición
 * (el Excel de tipología 2 no la incluye).
 */

function cubicacion_compuerta(
    $longitud_descarga,
    $ancho_descarga,
    $alto_descarga,
    $espesor_revestimiento,
    $n_compuertas,
    $ancho_compuerta,
    $alto_compuerta,
    $espesor_compuerta,
    $alto_portico
) {
    // Constantes de diseño del Excel (hojas 3.1 CUB REV / 3.2 CUB OBRAS).
    $profundidad_escarpe = 0.15;   // m (Excel; distinto de ESPESOR_ESCARPE)
    $margen_roce = 3.0;            // m a cada lado del ancho de descarga
    $sobreexcavacion = 0.5;        // m a cada lado (= SOBREEXC)
    $profundidad_excavacion = 0.5; // m (media terreno existente vs NPT)
    $dist_juntas = 5.0;            // m
    $factor_moldaje = 12.0;        // m2 moldaje / m3 hormigón
    $ancho_marco_portico = 0.05;   // m
    $densidad_acero_compuerta = 7850; // kg/m3 (Excel; DENSIDAD_ACERO = 7900)

    $L = $longitud_descarga;
    $B = $ancho_descarga;
    $H = $alto_descarga;
    $e = $espesor_revestimiento;

    // --- Hormigones del revestimiento de descarga (3.1 CUB REV) ---
    // Superficie losa = largo * (2·espesor + ancho)
    $superficie_losa = $L * (2.0 * $e + $B);
    $hormigon_losa = $superficie_losa * $e;
    $area_muros = 2.0 * $L * $H;
    $hormigon_muros = $area_muros * $e;
    $hormigon = $hormigon_losa + $hormigon_muros;

    $enfierradura = $hormigon * CUANTIA_ACERO;
    $moldaje = $hormigon * $factor_moldaje;
    $emplantillado = $superficie_losa * EMPLANT;
    $base_granular = $superficie_losa * BASEGR;
    $juntas_dilatacion = ($L / $dist_juntas) * ($H + $B + $H);
    $antisol = $area_muros * 2.0; // Excel: antisol = 2 · área muros (≠ moldaje)

    // --- Movimiento de tierras (3.2 CUB OBRAS) ---
    $ancho_roce = $margen_roce + $B + $margen_roce;
    $roce_faja = $ancho_roce * $L;
    $escarpe = $roce_faja * $profundidad_escarpe;

    $ancho_total_excavacion = $sobreexcavacion + $B + $sobreexcavacion;
    // Excel escribe excavación = ancho_losa · largo (C17·C16), pero define
    // ancho total y profundidad; con los defaults ambos dan el mismo número.
    // Se usa la fórmula física coherente con bocatoma / el resto del proyecto.
    $excavacion = $ancho_total_excavacion * $L * $profundidad_excavacion;

    $alto_muros_ext = $H + $e;
    $altura_relleno = $alto_muros_ext + BASEGR + EMPLANT; // Excel: +0,1 +0,05
    $relleno = $L * $altura_relleno * 2.0 * $sobreexcavacion;

    [$excedente_escarpe, $excedente_excavacion, $excedentes_totales] =
        excedentes($escarpe, $excavacion, $relleno);

    // --- Peso de compuertas y pórtico (informativo; Excel 3.2) ---
    $peso_compuertas = $ancho_compuerta * $n_compuertas * $alto_compuerta
        * $espesor_compuerta * $densidad_acero_compuerta;
    $peso_portico = $n_compuertas
        * (3.0 * $alto_portico + $ancho_compuerta)
        * 2.0 * $ancho_marco_portico * $espesor_compuerta
        * $densidad_acero_compuerta;
    $peso_total_acero = $peso_compuertas + $peso_portico;

    return [
        'hormigon' => $hormigon,
        'hormigon_losa' => $hormigon_losa,
        'hormigon_muros' => $hormigon_muros,
        'enfierradura' => $enfierradura,
        'moldaje' => $moldaje,
        'emplantillado' => $emplantillado,
        'base_granular' => $base_granular,
        'juntas_dilatacion' => $juntas_dilatacion,
        'antisol' => $antisol,
        'roce_faja' => $roce_faja,
        'escarpe' => $escarpe,
        'excavacion' => $excavacion,
        'relleno' => $relleno,
        'excedente_escarpe' => $excedente_escarpe,
        'excedente_excavacion' => $excedente_excavacion,
        'excedentes_totales' => $excedentes_totales,
        'n_compuertas' => $n_compuertas,
        'peso_compuertas' => $peso_compuertas,
        'peso_portico' => $peso_portico,
        'peso_total_acero' => $peso_total_acero,
        'superficie_losa' => $superficie_losa,
        'area_muros' => $area_muros,
    ];
}

function _compuerta($p) {
    $c = cubicacion_compuerta(
        $p['longitud_descarga'],
        $p['ancho_descarga'],
        $p['alto_descarga'],
        $p['espesor_revestimiento'],
        $p['n_compuertas'],
        $p['ancho_compuerta'],
        $p['alto_compuerta'],
        $p['espesor_compuerta'],
        $p['alto_portico']
    );

    $partidas = [
        ['MT035', 1, null, ['Instalación de faenas']],
        ['MT027', $c['escarpe'], null, ['Tramo revestido', 'Movimiento de tierras']],
        ['MT021', $c['excavacion'], null, ['Tramo revestido', 'Movimiento de tierras']],
        ['MT024', $c['relleno'], null, ['Tramo revestido', 'Movimiento de tierras']],
        ['MT033', $c['excedentes_totales'], null, ['Tramo revestido', 'Movimiento de tierras']],
        ['MT003', $c['hormigon'], null, ['Tramo revestido', 'Hormigones']],
        ['MT001', $c['emplantillado'], null, ['Tramo revestido', 'Hormigones']],
        ['MT026', $c['base_granular'], null, ['Tramo revestido', 'Hormigones']],
        ['MT011', $c['enfierradura'], null, ['Tramo revestido', 'Hormigones']],
        ['MT018', $c['moldaje'], null, ['Tramo revestido', 'Hormigones']],
        ['MT042', $c['n_compuertas'], null, ['Tramo revestido', 'Compuertas y pórtico']],
        ['MT040', $c['antisol'], null, ['Obras adicionales']],
        ['MT020', $c['juntas_dilatacion'], null, ['Obras adicionales']],
    ];

    $informativos = [
        ['Volumen hormigón losa de radier', $c['hormigon_losa'], 'm³'],
        ['Volumen hormigón muros', $c['hormigon_muros'], 'm³'],
        ['Superficie losa de radier', $c['superficie_losa'], 'm²'],
        ['Área de muros', $c['area_muros'], 'm²'],
        ['Peso láminas de compuertas (Excel)', $c['peso_compuertas'], 'kg'],
        ['Peso pórtico (Excel)', $c['peso_portico'], 'kg'],
        ['Peso total acero compuertas+pórtico', $c['peso_total_acero'], 'kg'],
    ];

    $notas = [
        "Tipología 2 — Reposición de Compuertas (compuerta de descarga + revestimiento "
            . "del tramo de descarga). Caudal de porteo de referencia: "
            . $p['caudal'] . " m³/s (informativo; no entra al dimensionamiento).",
        "Hormigón G25 = losa de radier + muros laterales del revestimiento de descarga. "
            . "Enfierradura = " . CUANTIA_ACERO . " kg/m³; moldaje = 12 m²/m³ (criterio Excel).",
        "Movimiento de tierras según Excel: escarpe e = 0,15 m; margen de roce = 3,0 m a cada "
            . "lado; sobre-excavación = 0,5 m; profundidad media de excavación = 0,5 m. "
            . "Excedentes con esponjamiento = " . ESPONJAMIENTO . " vía excedentes().",
        "Compuertas valorizadas como MT042 (unidad completa con izaje), "
            . $c['n_compuertas'] . " und. El Excel cotiza acero A-63-42 por kg ("
            . round($c['peso_total_acero'], 1) . " kg) + manilla y tornillo (sin código MT).",
        "Partidas no mapeadas (sin código MT en precios.csv): «Manilla» y «Tornillo» "
            . "(gl, 1 und. por compuerta en el Excel). Incluir manualmente si aplica.",
        "El Excel escribe excavación = ancho_losa × largo; este motor usa "
            . "ancho_total × largo × profundidad (0,5 m), que con los defaults del ejemplo "
            . "reproduce la misma cantidad (400 m³).",
    ];

    return [$partidas, $informativos, $notas];
}

$TIPOLOGIAS['compuerta'] = [
    'titulo' => 'Reposición de Compuertas',
    'campos' => [
        _campo('caudal', 'Caudal de porteo del canal', 'm³/s', 0.5,
            'Caudal de porteo de referencia del canal. Informativo en este motor (no dimensiona la obra).'),
        _campo('longitud_descarga', 'Longitud de la descarga', 'm', 400.0,
            'Largo del tramo de descarga a revestir (largo de obra en el Excel).'),
        _campo('ancho_descarga', 'Ancho interior de la descarga', 'm', 1.0,
            'Ancho interior del canal de descarga (base de la losa).'),
        _campo('alto_descarga', 'Alto interior de la descarga', 'm', 0.8,
            'Altura interior de los muros del revestimiento de descarga.'),
        _campo('espesor_revestimiento', 'Espesor del revestimiento', 'm', 0.13,
            'Espesor de hormigón de losa y muros de la descarga. Default 0,13 m (Excel).'),
        _campo('n_compuertas', 'Cantidad de compuertas', 'un', 2,
            'Número de compuertas metálicas a reponer (partida MT042).'),
        _campo('ancho_compuerta', 'Ancho de compuerta', 'm', 0.6,
            'Ancho de cada hoja. Interviene en el peso informativo de acero del Excel.'),
        _campo('alto_compuerta', 'Alto de compuerta', 'm', 0.8,
            'Alto de cada hoja. Interviene en el peso informativo de acero del Excel.'),
        _campo('espesor_compuerta', 'Espesor de la lámina de compuerta', 'm', 0.008,
            'Espesor de la plancha metálica. Solo afecta el peso informativo (no la partida MT042).'),
        _campo('alto_portico', 'Alto del pórtico', 'm', 1.0,
            'Alto del pórtico de izaje. Solo afecta el peso informativo de acero del Excel.'),
    ],
    'cubicar' => '_compuerta',
];
