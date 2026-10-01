<?php
/**
 * Tipología: Reposición de Marco Partidor (tipología 9 del catálogo CNR).
 * Portado desde el Excel de ejemplo "Reposición de Marco Partidor"
 * (grada de peralte en hormigón + hoja de acero con anclaje).
 *
 * El Excel también calcula un tramo de canal revestido y movimiento de
 * tierras (hoja 3.1, filas inferiores), pero el presupuesto de ejemplo
 * NO los incluye: solo faenas + grada + hoja/anclaje. Este motor sigue
 * el presupuesto.
 */

function cubicacion_marco_partidor($alto_entrada, $ancho_entrada) {
    // --- 3.1 GRADA DE PERALTE (constantes Excel G3–G7) ---
    $longitud_grada = 0.5;       // m (G3)
    $ancho_grada = $ancho_entrada; // Excel G4 = 1; se amarra al ancho de entrada
    $alto_grada = 0.3;           // m (G5)
    $largo_progresion = 0.2;     // m (G6)
    $profundidad_anclaje_grada = 0.2; // m (G7)

    // D6 = G3·G4·G5 + G6·(G5·G4/2) + G7·(G6+G3)·G4
    $hormigon_grada = $longitud_grada * $ancho_grada * $alto_grada
        + $largo_progresion * ($alto_grada * $ancho_grada / 2.0)
        + $profundidad_anclaje_grada * ($largo_progresion + $longitud_grada) * $ancho_grada;
    // D7 = (G5 + G3 + √(G5²+G6²)) · G4
    $moldaje_grada = (
        $alto_grada
        + $longitud_grada
        + sqrt($alto_grada * $alto_grada + $largo_progresion * $largo_progresion)
    ) * $ancho_grada;
    $enfierradura_grada = CUANTIA_ACERO * $hormigon_grada;

    // --- 3.2 HOJA DE ACERO ---
    $alto_pletina = $alto_entrada;          // F4 ← alto canal entrada (0,8 en ejemplo)
    $largo_pletina = $ancho_entrada;        // F5 ← ancho (params C12 / entrada)
    $largo_pletina_hoja = $alto_entrada;    // F7 = 0,8 en ejemplo (= alto)
    $espesor_pletina_mm = 8.0;              // F8
    $largo_anclaje_hoja = 0.2;              // F6
    $n_puntas = 6.0;                        // F9
    $diametro_puntas = 0.05;                // F10 (m) — valor del Excel
    $largo_puntas = 0.1;                    // F11
    $espesor_anclaje = 0.1;                 // F16

    // C6 = (F4·F5·F8/1000)·7900
    $peso_pletina = ($alto_pletina * $largo_pletina * ($espesor_pletina_mm / 1000.0))
        * DENSIDAD_ACERO;
    // C8 = (F11·F9·π·F10²/4)·7900  (calculado en Excel pero NO entra a C10)
    $peso_puntas = ($largo_puntas * $n_puntas * M_PI * pow($diametro_puntas, 2) / 4.0)
        * DENSIDAD_ACERO;
    // C10 = C6·2  (Excel duplica el peso de pletina; no suma puntas)
    $peso_hoja = $peso_pletina * 2.0;
    $pintura_hoja = $alto_pletina * $largo_pletina_hoja * 2.0; // C7 informativo

    // Anclaje hoja: C14 = F16·F6·F4 ; C15 = C14·80
    $hormigon_anclaje = $espesor_anclaje * $largo_anclaje_hoja * $alto_pletina;
    $enfierradura_anclaje = CUANTIA_ACERO * $hormigon_anclaje;

    $hormigon = $hormigon_grada + $hormigon_anclaje;
    $enfierradura = $enfierradura_grada + $enfierradura_anclaje;
    $moldaje = $moldaje_grada;

    return [
        'hormigon' => $hormigon,
        'hormigon_grada' => $hormigon_grada,
        'hormigon_anclaje' => $hormigon_anclaje,
        'enfierradura' => $enfierradura,
        'enfierradura_grada' => $enfierradura_grada,
        'enfierradura_anclaje' => $enfierradura_anclaje,
        'moldaje' => $moldaje,
        'peso_hoja' => $peso_hoja,
        'peso_pletina' => $peso_pletina,
        'peso_puntas' => $peso_puntas,
        'pintura_hoja' => $pintura_hoja,
        'longitud_grada' => $longitud_grada,
        'ancho_grada' => $ancho_grada,
        'alto_grada' => $alto_grada,
        'alto_pletina' => $alto_pletina,
        'largo_pletina' => $largo_pletina,
        'espesor_pletina_mm' => $espesor_pletina_mm,
        'n_puntas' => $n_puntas,
    ];
}

function _marco_partidor($p) {
    $c = cubicacion_marco_partidor(
        $p['alto_entrada'],
        $p['ancho_entrada']
    );

    $partidas = [
        ['MT035', 1, null, ['Instalación de faenas']],
        ['MT003', $c['hormigon_grada'], null, ['Grada de peralte', 'Hormigones']],
        ['MT018', $c['moldaje'], null, ['Grada de peralte', 'Hormigones']],
        ['MT011', $c['enfierradura_grada'], null, ['Grada de peralte', 'Hormigones']],
        // Excel valoriza la pletina con PU de acero A-63; aquí MT039 (pletinas, kg).
        ['MT039', $c['peso_hoja'], null, ['Hoja de acero', 'Marco partidor']],
        ['MT003', $c['hormigon_anclaje'], null, ['Anclaje de hoja', 'Hormigones']],
        ['MT011', $c['enfierradura_anclaje'], null, ['Anclaje de hoja', 'Hormigones']],
    ];

    $informativos = [
        ['Hormigón grada de peralte', $c['hormigon_grada'], 'm³'],
        ['Hormigón anclaje de hoja', $c['hormigon_anclaje'], 'm³'],
        ['Enfierradura grada', $c['enfierradura_grada'], 'kg'],
        ['Enfierradura anclaje', $c['enfierradura_anclaje'], 'kg'],
        ['Peso una pletina (sin duplicar)', $c['peso_pletina'], 'kg'],
        ['Peso puntas de anclaje (Excel C8; no entra a C10)', $c['peso_puntas'], 'kg'],
        ['Pintura hoja (2 caras; no valorizada)', $c['pintura_hoja'], 'm²'],
        ['Longitud grada (fija Excel)', $c['longitud_grada'], 'm'],
        ['Alto grada (fija Excel)', $c['alto_grada'], 'm'],
        ['Espesor pletina', $c['espesor_pletina_mm'], 'mm'],
        ['Ancho canal salida 1 (informativo)', $p['ancho_salida_1'], 'm'],
        ['Ancho canal salida 2 (informativo)', $p['ancho_salida_2'], 'm'],
    ];

    $notas = [
        "Tipología 9 — Reposición de Marco Partidor: grada de peralte en hormigón "
            . "más hoja de acero con anclaje. Caudal de porteo de referencia: "
            . $p['caudal'] . " m³/s (informativo).",
        "Grada (Excel 3.1): L=" . $c['longitud_grada'] . " m, alto=" . $c['alto_grada']
            . " m fijos; ancho = ancho de entrada (" . round($c['ancho_grada'], 2)
            . " m). Hormigón = bloque + cuña de progresión + anclaje de grada; "
            . "moldaje = (alto + L + √(alto²+progresión²))·ancho; acero "
            . CUANTIA_ACERO . " kg/m³.",
        "Hoja (Excel 3.2): pletina e=" . $c['espesor_pletina_mm'] . " mm, alto×largo = "
            . "alto_entrada × ancho_entrada. El Excel fija peso total C10 = 2·peso_pletina "
            . "(duplica la pletina) y NO suma las puntas de anclaje (= "
            . round($c['peso_puntas'], 2) . " kg calculados). Valorizada como MT039 "
            . "(pletinas/kg); el Excel usa el PU de acero A-63.",
        "Anclaje de hoja: hormigón = 0,1·0,2·alto_pletina; acero "
            . CUANTIA_ACERO . " kg/m³.",
        "El Excel también cubicá un tramo de canal revestido (L=5 m) y movimiento "
            . "de tierras en 3.1, pero el presupuesto de ejemplo NO los incluye. "
            . "Este motor tampoco. La partida unitaria «Suministro e instalación de "
            . "hoja de Marco Partidor» (gl) del catálogo PU no se usa: el Excel "
            . "cobra la hoja por kg.",
        "La hoja 2 PARAMETROS del Excel parece copiada de otra tipología (habla de "
            . "cajón/cruce); los defaults salen de 1 DESCRIP + dimensiones fijas de "
            . "3.1/3.2.",
    ];

    return [$partidas, $informativos, $notas];
}

$TIPOLOGIAS['marco_partidor'] = [
    'titulo' => 'Reposición de Marco Partidor',
    'campos' => [
        _campo('caudal', 'Caudal de porteo del canal', 'm³/s', 0.036,
            'Caudal de porteo de referencia. Informativo en este motor.'),
        _campo('alto_entrada', 'Alto canal entrada', 'm', 0.8,
            'Altura de la sección de entrada. Define el alto de la pletina/hoja.'),
        _campo('ancho_entrada', 'Ancho canal entrada', 'm', 1.0,
            'Ancho de la sección de entrada. Define el ancho de la grada y el largo de la pletina.'),
        _campo('ancho_salida_1', 'Ancho canal salida 1', 'm', 1.0,
            'Ancho de la primera salida del partidor. Informativo.'),
        _campo('ancho_salida_2', 'Ancho canal salida 2', 'm', 1.0,
            'Ancho de la segunda salida del partidor. Informativo.'),
    ],
    'cubicar' => '_marco_partidor',
];
