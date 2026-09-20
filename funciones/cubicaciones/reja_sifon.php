<?php
/**
 * Tipología: Mejoramiento sifón en obra de entrada (reja).
 * Reja metálica de protección de la entrada de un sifón (SifonReja.bas).
 * Pletinas de 80×3 mm separadas cada 0.1 m en vertical, con dos
 * pletinas horizontales de amarre; el peso de la reja completa
 * duplica el de las pletinas para incluir refuerzos y marco.
 */

function cubicacion_reja_sifon($base, $altura) {
    $separacion = 0.1;       // m entre pletinas verticales
    $espesor_pletina = 3;    // mm
    $alto_pletina = 80;      // mm

    $pletinas_verticales = $base / $separacion + 1;
    $pletinas_horizontales = 2.0;
    $metros_pletina = ($pletinas_verticales * $altura + $pletinas_horizontales * $base) * 1.1;
    $peso_pletinas = $metros_pletina * ($espesor_pletina / 1000) * ($alto_pletina / 1000) * DENSIDAD_ACERO;

    $escarpe = (($base + 2 + 2) * 5) * ESPESOR_ESCARPE;

    return [
        'pletinas_verticales' => $pletinas_verticales,
        'pletinas_horizontales' => $pletinas_horizontales,
        'metros_pletina' => $metros_pletina,
        'peso_pletinas' => $peso_pletinas,
        'peso_reja' => $peso_pletinas * 2,
        'escarpe' => $escarpe,
        'excedentes_totales' => $escarpe * (1 + ESPONJAMIENTO),
    ];
}

function _reja_sifon($p) {
    $c = cubicacion_reja_sifon($p['base'], $p['altura']);
    $partidas = [
        ['MT035', 1],
        ['MT027', $c['escarpe']],
        ['MT033', $c['excedentes_totales']],
        ['MT039', $c['peso_reja']],
    ];
    $informativos = [
        ['Metros lineales de pletina', $c['metros_pletina'], 'm'],
    ];
    return [$partidas, $informativos];
}

$TIPOLOGIAS['reja_sifon'] = [
    'titulo' => 'Mejoramiento sifon en obra de entrada (reja)',
    'esquema' => 'esquema-sifon.png',
    'campos' => [
        _campo('base', 'Ancho de la boca', 'm', 1.0,
            'Ancho de la boca de entrada del sifon, donde va instalada la reja.'),
        _campo('altura', 'Alto de la boca', 'm', 1.0,
            'Alto de la boca de entrada del sifon, donde va instalada la reja.'),
        // Datos informativos - todavia no entran al calculo de cubicacion.
        _campo('diametro_tuberia', 'Diametro de tuberia', 'mm', 800.0,
            'Diametro de la tuberia del sifon. Dato informativo, aun no se usa en el calculo de cubicacion.'),
        _campo('ancho_reja', 'Ancho de reja', 'm', 1.0,
            'Ancho de la reja metalica de proteccion. Dato informativo, aun no se usa en el calculo de cubicacion.'),
    ],
    'cubicar' => '_reja_sifon',
];
