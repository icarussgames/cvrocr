<?php
/**
 * Tipología: Mejoramiento con Entubamiento en Zonas de Derrumbes y
 * Otras Singularidades (tipología 13 del catálogo CNR).
 *
 * Misma cubicación de tierras que la tipología 12
 * (abovedamiento_derrumbes.php / Excel 3.1–3.2), pero la sección
 * interior B×H del cajón se reemplaza por el diámetro D de una
 * tubería HDPE corrugada (el usuario elige el diámetro; no se
 * autodimensiona). No hay espesor de muro e: el alto exterior para
 * el relleno es D.
 *
 * El presupuesto no lleva cajón prefabricado (MT041). En su lugar,
 * suministro e instalación de tubería HDPE corrugada (MT043, ml),
 * con PU del bloque HDPE-Corrugado de la hoja PU TUB según el
 * diámetro elegido (5º elemento de la partida; ver valorizar()).
 *
 * La verificación de Manning (hoja VERIF HID TUNEL, material HDPE-Co)
 * es informativa: no modifica las cubicaciones.
 *
 * NO confundir con:
 * - abovedamiento_derrumbes.php (tipología 12 — cajón prefabricado)
 * - revestimiento_canal.php (tipología 11 — hormigón in situ)
 */

/**
 * Catálogo PU TUB, bloque HDPE-Corrugado: diámetro mm => precio $/m.
 * La columna C de esa hoja (150) no entra al precio unitario.
 */
function _catalogo_hdpe_corrugado() {
    return [
        400 => 23336,
        500 => 29170,
        600 => 41040,
        700 => 47121,
        800 => 54000,
        900 => 73440,
        1000 => 86400,
        1200 => 108000,
        1400 => 146880,
        1500 => 176944,
    ];
}

function cubicacion_entubamiento_derrumbes($largo, $diametro) {
    // Constantes de diseño del Excel tip. 12 (hojas 3.1 / 3.2).
    // B y H del cajón se sustituyen por D; no hay espesor e.
    $margen_escarpe_lado = 3.0;       // m (C6 = 3 + D + 3)
    $profundidad_escarpe = 0.15;     // m (C7)
    $ancho_sobre_excavacion = 0.4;   // m (C18) por lado
    $profundidad_excavacion = 0.7;   // m (C22)
    $margen_sobre_relleno = 0.1;     // m (C21 = alto + 0,1)
    $espesor_base_granular = 0.1;    // m (C23 = L · 0,1 · D; = BASEGR)

    $L = $largo;
    $D = $diametro;

    // --- 3.1 CUB REV (base granular; Antisol no aplica a HDPE) ---
    $base_granular = $L * $espesor_base_granular * $D;

    // --- 3.2 CUB OBRAS: escarpe ---
    $ancho_escarpe = $margen_escarpe_lado + $D + $margen_escarpe_lado; // C6
    $roce_faja = $ancho_escarpe * $L;                                   // C9
    $escarpe = $roce_faja * $profundidad_escarpe;                       // C10

    // --- 3.2 CUB OBRAS: movimiento de tierras ---
    $ancho_total_excavacion = $ancho_sobre_excavacion + $D + $ancho_sobre_excavacion; // C19
    // Sin espesor de muro: alto exterior = D (en el cajón era H + 2·e).
    $alto_muros = $D;
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
        'largo_tuberia' => $L,
        'diametro' => $D,
        'base_granular' => $base_granular,
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
        'profundidad_excavacion' => $profundidad_excavacion,
        'profundidad_escarpe' => $profundidad_escarpe,
        'ancho_sobre_excavacion' => $ancho_sobre_excavacion,
    ];
}

/**
 * Ángulo central θ (grados) del tubo circular parcialmente lleno.
 * Punto fijo de VERIF HID TUNEL (CO7 = 1° y la recurrencia O1…O20):
 *   θ <- ( (π·θ/360 / D^4 · (Q·n/√i)^1,5 )^0,4 · 4 + sen(θ)/2 ) · 360/π
 * con θ en grados. Equivale a Manning Q = (1/n)·A·R^(2/3)·√i.
 * Devuelve null si no queda un ángulo en (0, 360).
 */
function _entubamiento_angulo_central($D, $Q, $n, $i) {
    $theta = 1.0;
    $k15 = pow($Q * $n / sqrt($i), 1.5);
    for ($iter = 0; $iter < 80; $iter++) {
        $term = M_PI * $theta / 360.0 / pow($D, 4) * $k15;
        if (!is_finite($term) || $term < 0.0) {
            return null;
        }
        $theta_next = (pow($term, 0.4) * 4.0 + sin(deg2rad($theta)) / 2.0) * 360.0 / M_PI;
        if (!is_finite($theta_next) || $theta_next <= 0.0) {
            return null;
        }
        if (abs($theta_next - $theta) < 1e-5) {
            $theta = $theta_next;
            break;
        }
        $theta = $theta_next;
    }
    if ($theta <= 0.0 || $theta >= 360.0) {
        return null;
    }
    return $theta;
}

/**
 * Verificación informativa de escurrimiento normal (VERIF HID TUNEL).
 * H/D = 0,5·(1 − cos(θ·π/360)); V = Q/A.
 * Capacidad de referencia: Q a H/D ≈ 0,94, con las constantes
 * geométricas de esa hoja (área = 3,06464·D²/4, Rh = 1,158·D/4)
 * y Manning Q = A·Rh^(2/3)·√i / n.
 * estado = OK si Qporteo ≤ Q(0,94D); si no, ERROR (no se reporta Hn).
 */
function _entubamiento_verificacion_manning($D, $Q, $n, $i) {
    $area_094 = 3.06464 * $D * $D / 4.0;
    $radio_094 = 1.158 * $D / 4.0;
    $q_094 = $area_094 * pow($radio_094, 2.0 / 3.0) * sqrt($i) / $n;
    $capacidad_ok = $Q <= $q_094 + 1e-9;

    $theta = null;
    if ($Q > 0.0 && $capacidad_ok) {
        $theta = _entubamiento_angulo_central($D, $Q, $n, $i);
    } elseif ($Q <= 0.0 && $capacidad_ok) {
        return [
            'hn' => 0.0,
            'relacion_hd' => 0.0,
            'vn' => 0.0,
            'q_094' => $q_094,
            'estado' => 'OK',
        ];
    }

    if (!$capacidad_ok || $theta === null) {
        return [
            'hn' => null,
            'relacion_hd' => null,
            'vn' => null,
            'q_094' => $q_094,
            'estado' => 'ERROR',
        ];
    }

    $hd = 0.5 * (1.0 - cos($theta * M_PI / 360.0));
    $theta_rad = deg2rad($theta);
    $area = ($theta_rad - sin($theta_rad)) * $D * $D / 8.0;
    $vn = $area > 0.0 ? $Q / $area : null;

    return [
        'hn' => $hd * $D,
        'relacion_hd' => $hd,
        'vn' => $vn,
        'q_094' => $q_094,
        'estado' => 'OK',
    ];
}

function _entubamiento_derrumbes($p) {
    $catalogo = _catalogo_hdpe_corrugado();
    $diametro_mm = (int) round($p['diametro_mm']);
    if (!isset($catalogo[$diametro_mm])) {
        $validos = implode(', ', array_keys($catalogo));
        throw new InvalidArgumentException(
            "Diámetro {$diametro_mm} mm no está en el catálogo PU TUB "
            . "(HDPE-Corrugado). Diámetros válidos (mm): {$validos}."
        );
    }
    if ($p['pendiente'] <= 0.0 || $p['n_manning'] <= 0.0) {
        throw new InvalidArgumentException(
            'La pendiente i (m/m) y el n de Manning deben ser mayores que cero.'
        );
    }

    $D = $diametro_mm / 1000.0;
    $precio_tubo = $catalogo[$diametro_mm];
    $c = cubicacion_entubamiento_derrumbes($p['largo'], $D);
    $hid = _entubamiento_verificacion_manning(
        $D,
        $p['caudal'],
        $p['n_manning'],
        $p['pendiente']
    );

    $etiqueta_tubo = 'Suministro e instalación tubería HDPE corrugada Ø '
        . $diametro_mm . ' mm';

    // Sin MT041 y sin Antisol (MT040): el HDPE no se cura como hormigón.
    // El 5º elemento fija el PU del diámetro (PU TUB), no el del CSV.
    $partidas = [
        ['MT035', 1, null, ['Instalación de faenas']],
        ['MT027', $c['escarpe'], null, ['Tramo entubado', 'Movimiento de tierras']],
        ['MT021', $c['excavacion'], null, ['Tramo entubado', 'Movimiento de tierras']],
        ['MT024', $c['relleno'], null, ['Tramo entubado', 'Movimiento de tierras']],
        ['MT033', $c['excedentes_totales'], null, ['Tramo entubado', 'Movimiento de tierras']],
        ['MT043', $c['largo_tuberia'], $etiqueta_tubo, ['Tramo entubado', 'Tubería HDPE corrugada'], $precio_tubo],
        ['MT026', $c['base_granular'], null, ['Tramo entubado', 'Tubería HDPE corrugada']],
    ];

    $informativos = [
        ['Roce de la faja del canal', $c['roce_faja'], 'm²'],
        ['Ancho faja escarpe (3 + D + 3)', $c['ancho_escarpe'], 'm'],
        ['Ancho total excavación (0,4 + D + 0,4)', $c['ancho_total_excavacion'], 'm'],
        ['Diámetro interior D', $D, 'm'],
        ['Alto de relleno (D; sin espesor de muro)', $c['alto_muros'], 'm'],
        ['Altura máxima a rellenar (+0,1 m)', $c['altura_max_relleno'], 'm'],
        ['Excedente escarpe (×1,1)', $c['excedente_escarpe'], 'm³'],
        ['Excedente mov. de tierras (×1,1)', $c['excedente_excavacion'], 'm³'],
        ['Talud (informativo; no entra a la cubicación)', $p['talud'], '-'],
        ['Altura normal Hn', $hid['hn'] ?? 'ERROR', $hid['hn'] === null ? '-' : 'm'],
        ['Relación H/D', $hid['relacion_hd'] ?? 'ERROR', '-'],
        ['Velocidad normal Vn', $hid['vn'] ?? 'ERROR', $hid['vn'] === null ? '-' : 'm/s'],
        ['Caudal a H/D = 0,94', $hid['q_094'], 'm³/s'],
        ['Verificación Qporteo ≤ Q(0,94D)', $hid['estado'], '-'],
        ['PU tubería HDPE corrugada (PU TUB)', $precio_tubo, '$/m'],
    ];

    $notas = [
        "Tipología 13 — Mejoramiento con Entubamiento en Zonas de Derrumbes "
            . "y Otras Singularidades. Reemplazo del tramo (L = "
            . $p['largo'] . " m) por tubería HDPE corrugada Ø "
            . $diametro_mm . " mm (D = " . $D . " m). Caudal de porteo: "
            . $p['caudal'] . " m³/s. El diámetro lo elige el usuario; "
            . "Manning no dimensiona la sección.",
        "No es tipología 12 (abovedamiento_derrumbes: cajón prefabricado "
            . "MT041) ni tipología 11 (revestimiento_canal: hormigón in situ).",
        "Movimiento de tierras como el Excel 3.2 de la tipología 12, con "
            . "B = H = D y sin espesor de muro e: escarpe = (3+D+3)·L·0,15; "
            . "excavación = (0,4+D+0,4)·L·0,7; relleno = L·(D+0,1)·2·0,4. "
            . "Excedentes = escarpe·1,1 + max(0, excav−relleno)·1,1 "
            . "(fórmula Excel C29–C32; no usa compactación 0,9 de excedentes()).",
        "Presupuesto: la partida de cajón (MT041, «Sum. e Inst. Cajón…», "
            . "capítulo «Cajón prefabricado») se reemplaza por suministro e "
            . "instalación de tubería HDPE corrugada (MT043, ml = L) en "
            . "«Tramo entubado» / «Tubería HDPE corrugada». El PU es el del "
            . "bloque HDPE-Corrugado de PU TUB para el Ø elegido "
            . "($" . number_format($precio_tubo, 0, ',', '.') . "/m), pasado "
            . "como 5º elemento de la partida (el CSV solo tiene una fila "
            . "MT043 de referencia). Base granular = L·0,1·D (MT026).",
        "No se cobra Antisol (MT040). En el Excel de derrumbes el Antisol "
            . "cubre caras de hormigón del cajón (2·L·H + L·B). La tubería "
            . "HDPE no tiene curado de hormigón, y esa planilla no lo carga "
            . "a un entubamiento.",
        "Verificación hidráulica informativa (VERIF HID TUNEL, HDPE-Co): "
            . "iteración del ángulo central θ y H/D = 0,5·(1−cos(θ·π/360)); "
            . "Vn = Q/A. Compuerta de capacidad: Qporteo ≤ Q(H/D≈0,94). "
            . "n = " . $p['n_manning'] . ", i = " . $p['pendiente']
            . " m/m. Estado: " . $hid['estado'] . ". Talud informativo, "
            . "igual que en la tipología 12.",
    ];

    return [$partidas, $informativos, $notas];
}

$TIPOLOGIAS['entubamiento_derrumbes'] = [
    'titulo' => 'Mejoramiento con Entubamiento en Zonas de Derrumbes y Otras Singularidades',
    'esquema' => 'esquema_revest.png',
    'campos' => [
        _campo('caudal', 'Caudal de porteo del canal', 'm³/s', 0.56,
            'Caudal de porteo. Informativo: se compara con Q a H/D≈0,94, pero no elige el diámetro.'),
        _campo('largo', 'Largo del tramo', 'm', 45.0,
            'Longitud del tramo a entubar (ml de tubería HDPE corrugada).'),
        _campo('diametro_mm', 'Diámetro de la tubería HDPE corrugada', 'mm', 1000,
            'Diámetro nominal del catálogo PU TUB (HDPE-Corrugado): 400, 500, 600, 700, 800, 900, 1000, 1200, 1400 o 1500 mm. El usuario lo elige; no se autodimensiona.'),
        _campo('pendiente', 'Pendiente del tramo', 'm/m', 0.002,
            'Pendiente hidráulica i (m/m) para la verificación de Manning. No entra al movimiento de tierras.'),
        _campo('n_manning', 'Coeficiente n de Manning', '-', 0.02,
            'n de Manning del HDPE corrugado (VERIF HID TUNEL usa 0,02 para HDPE-Co). Solo informativo.'),
        _campo('talud', 'Talud de los muros (H/V)', '-', 0.0,
            'Informativo. La tubería circular no lo usa en las cubicaciones (igual que el talud en la tipología 12).'),
    ],
    'cubicar' => '_entubamiento_derrumbes',
];
