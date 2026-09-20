<?php
/**
 * Tipología: Construcción de Bocatoma (tipología 1 del catálogo CNR).
 * Portado desde el Excel de ejemplo "Construcción de Bocatoma".
 * Obra de captación permanente con losa de fondo, zapatas, machones
 * de apoyo de compuertas y muros de anclaje laterales.
 *
 * No confundir con desarenador.php ("Reposición de bocatoma"), que es
 * otra tipología y otro id.
 */

function cubicacion_bocatoma(
    $ancho_captacion,
    $alto_seccion,
    $longitud_lecho,
    $n_compuertas,
    $ancho_compuerta,
    $ancho_machon,
    $alto_machon
) {
    // Espesores y profundidades de diseño del Excel (hoja 3.1 OBRAS / 3.2).
    $espesor_losa = 0.25;           // m
    $espesor_zapata_muros = 0.25;   // m
    $profundidad_zapata = 0.5;      // m
    $profundidad_excavacion = 0.9;  // m (media terreno existente vs NPT)
    $altura_relleno = 2.0;          // m
    $sobreexcavacion = 1.0;         // m a cada lado (Excel; distinto de SOBREEXC)
    $profundidad_escarpe = 0.15;    // m (Excel; distinto de ESPESOR_ESCARPE)
    $margen_roce = 3.0;             // m a cada lado del alto de sección
    $factor_moldaje = 12.0;         // m2 moldaje / m3 hormigón (criterio Excel)

    $n_apoyos = $n_compuertas + 1;  // n compuertas → n+1 machones

    // --- Hormigones (3.1 OBRAS) ---
    // Longitud de obra en el Excel = ancho de captación (F3 ← C8).
    $largo_obra = $ancho_captacion;
    $ancho_obra = $longitud_lecho;

    $vol_machon = $ancho_machon * $alto_machon * $longitud_lecho;
    $hormigon_apoyos = $vol_machon * $n_apoyos;

    $hormigon_losa = $longitud_lecho * $largo_obra * $espesor_losa;
    $hormigon_zapata_aa = $profundidad_zapata * $largo_obra * $espesor_zapata_muros;
    $hormigon_zapata_ab = $profundidad_zapata * $espesor_zapata_muros * $largo_obra;
    $hormigon_obra_entrada = $hormigon_losa + $hormigon_zapata_aa + $hormigon_zapata_ab;

    // Ancho residual de cada muro de anclaje = (captación − n·compuerta − (n+1)·machón) / 2
    $ancho_muro_anclaje = ($largo_obra
        - $n_compuertas * $ancho_compuerta
        - $n_apoyos * $ancho_machon) / 2.0;
    if ($ancho_muro_anclaje < 0) {
        $ancho_muro_anclaje = 0.0;
    }
    $hormigon_muro_anclaje = $ancho_muro_anclaje * $alto_machon * $espesor_zapata_muros;
    $hormigon_muros_anclaje = $hormigon_muro_anclaje * 2.0;

    $hormigon = $hormigon_apoyos + $hormigon_obra_entrada + $hormigon_muros_anclaje;
    $enfierradura = $hormigon * CUANTIA_ACERO;
    $moldaje = $hormigon * $factor_moldaje;
    $antisol = $moldaje; // Excel: antisol = moldaje (m2)

    // --- Movimiento de tierras (3.2 MOV TIERRA) ---
    $ancho_roce = $margen_roce + $alto_seccion + $margen_roce;
    $roce_faja = $ancho_roce * $largo_obra;
    $escarpe = $roce_faja * $profundidad_escarpe;

    $ancho_total_excavacion = $sobreexcavacion + $ancho_obra + $sobreexcavacion;
    $excavacion = $ancho_total_excavacion * $largo_obra * $profundidad_excavacion;
    $relleno = $largo_obra * $altura_relleno * 2.0 * $sobreexcavacion;

    [$excedente_escarpe, $excedente_excavacion, $excedentes_totales] =
        excedentes($escarpe, $excavacion, $relleno);

    return [
        'hormigon' => $hormigon,
        'hormigon_apoyos' => $hormigon_apoyos,
        'hormigon_obra_entrada' => $hormigon_obra_entrada,
        'hormigon_muros_anclaje' => $hormigon_muros_anclaje,
        'enfierradura' => $enfierradura,
        'moldaje' => $moldaje,
        'antisol' => $antisol,
        'roce_faja' => $roce_faja,
        'escarpe' => $escarpe,
        'excavacion' => $excavacion,
        'relleno' => $relleno,
        'excedente_escarpe' => $excedente_escarpe,
        'excedente_excavacion' => $excedente_excavacion,
        'excedentes_totales' => $excedentes_totales,
        'n_compuertas' => $n_compuertas,
        'n_apoyos' => $n_apoyos,
        'ancho_muro_anclaje' => $ancho_muro_anclaje,
    ];
}

function _bocatoma($p) {
    $c = cubicacion_bocatoma(
        $p['ancho_captacion'],
        $p['alto_seccion'],
        $p['longitud_lecho'],
        $p['n_compuertas'],
        $p['ancho_compuerta'],
        $p['ancho_machon'],
        $p['alto_machon']
    );

    $partidas = [
        ['MT035', 1, null, ['Instalación de faenas']],
        ['MT027', $c['escarpe'], null, ['Bocatoma proyectada', 'Movimiento de tierras']],
        ['MT021', $c['excavacion'], null, ['Bocatoma proyectada', 'Movimiento de tierras']],
        ['MT024', $c['relleno'], null, ['Bocatoma proyectada', 'Movimiento de tierras']],
        ['MT033', $c['excedentes_totales'], null, ['Bocatoma proyectada', 'Movimiento de tierras']],
        ['MT003', $c['hormigon'], null, ['Bocatoma proyectada', 'Hormigones']],
        ['MT011', $c['enfierradura'], null, ['Bocatoma proyectada', 'Hormigones']],
        ['MT018', $c['moldaje'], null, ['Bocatoma proyectada', 'Hormigones']],
        ['MT042', $c['n_compuertas'], null, ['Bocatoma proyectada', 'Compuertas']],
        ['MT040', $c['antisol'], null, ['Obras adicionales']],
    ];

    $informativos = [
        ['Volumen hormigón apoyos (machones)', $c['hormigon_apoyos'], 'm³'],
        ['Volumen hormigón obra de entrada', $c['hormigon_obra_entrada'], 'm³'],
        ['Volumen hormigón muros de anclaje', $c['hormigon_muros_anclaje'], 'm³'],
        ['Cantidad de machones de apoyo', $c['n_apoyos'], 'un'],
        ['Ancho de cada muro de anclaje', $c['ancho_muro_anclaje'], 'm'],
    ];

    $notas = [
        "Tipología 1 — Construcción de Bocatoma permanente. Caudal de porteo de referencia: "
            . $p['caudal'] . " m³/s (informativo; no entra al dimensionamiento de este motor).",
        "Hormigón G25 = apoyos de compuerta (" . $c['n_apoyos'] . " machones) + losa de fondo "
            . "y zapatas aguas arriba/abajo + muros de anclaje laterales. Enfierradura = "
            . CUANTIA_ACERO . " kg/m³; moldaje = 12 m²/m³ (criterio del Excel de ejemplo).",
        "Movimiento de tierras según Excel: escarpe e = 0,15 m; sobre-excavación lateral = 1,0 m; "
            . "profundidad media de excavación = 0,9 m; altura de relleno = 2,0 m. "
            . "Excedentes con esponjamiento = " . ESPONJAMIENTO . " vía excedentes().",
        "Partida no mapeada (sin código MT en precios.csv): «Automatización y telemetría de "
            . "compuertas» (gl, 1 und. en el Excel, ~16,2 M$). Incluir manualmente si aplica.",
        "El Excel de parámetros declara ancho de machón = 0,6 m, pero la hoja de obras usa "
            . "0,5 m; el default de este motor es 0,5 m para reproducir el presupuesto de ejemplo.",
        "No se modelaron emplantillado ni base granular (el Excel de tipología 1 no los incluye).",
    ];

    return [$partidas, $informativos, $notas];
}

$TIPOLOGIAS['bocatoma'] = [
    'titulo' => 'Construcción de Bocatoma',
    'campos' => [
        _campo('caudal', 'Caudal de porteo del canal', 'm³/s', 0.03,
            'Caudal de porteo de referencia del canal. Informativo en este motor (no dimensiona la obra).'),
        _campo('ancho_captacion', 'Ancho de captación', 'm', 7.0,
            'Ancho total de la obra de captación en el lecho (longitud de obra en el Excel).'),
        _campo('alto_seccion', 'Alto de la sección de captación', 'm', 2.0,
            'Altura de la sección de captación. Interviene en el roce de faja y en la excavación.'),
        _campo('longitud_lecho', 'Longitud en lecho de la obra', 'm', 2.0,
            'Profundidad de la obra en el sentido del flujo (ancho de obra en movimiento de tierras).'),
        _campo('n_compuertas', 'Cantidad de compuertas', 'un', 3,
            'Número de compuertas metálicas. Se asumen n+1 machones de apoyo.'),
        _campo('ancho_compuerta', 'Ancho de compuerta', 'm', 0.8,
            'Ancho de cada hoja de compuerta. Resta del ancho disponible para muros de anclaje.'),
        _campo('ancho_machon', 'Ancho del machón de apoyo', 'm', 0.5,
            'Ancho de cada machón de apoyo de compuerta. Default 0,5 m (hoja de obras del Excel).'),
        _campo('alto_machon', 'Alto del machón de apoyo', 'm', 2.0,
            'Altura de los machones de apoyo y de los muros de anclaje laterales.'),
    ],
    'cubicar' => '_bocatoma',
];
