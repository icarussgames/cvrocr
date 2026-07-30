<?php
/**
 * Hidráulica (portado desde motor_calculo/hidraulica.py).
 */

/**
 * Altura crítica de escurrimiento en un canal rectangular (m).
 * hc = (Q² / (g·b²))^(1/3)
 */
function altura_critica_rectangular($caudal, $base) {
    return ($caudal ** 2 / (GRAVEDAD * $base ** 2)) ** (1 / 3);
}

function altura_normal_manning($caudal, $base, $talud, $pendiente, $manning, $altura_inicial) {
    $tolerancia = 0.0001;
    $h = $altura_inicial;
    $h_convergida = 0.0;

    for ($i = 0; $i < 101; $i++) {
        $area = $h * $base;
        $perimetro = $base + 2 * $h * sqrt(1 + $talud ** 2);
        $radio_hidraulico = $area / $perimetro;
        $caudal_calculado = (1 / $manning) * $area * ($radio_hidraulico ** (2 / 3)) * sqrt($pendiente);

        if (abs($caudal - $caudal_calculado) < $tolerancia) {
            $h_convergida = $h;
            break;
        }

        // Paso de Newton-Raphson con derivada aproximada dQ/dh ~ Q/h
        $h = $h - ($caudal_calculado - $caudal) / ($caudal_calculado / $h);
    }

    if ($altura_inicial > $h_convergida + 0.2) {
        return $altura_inicial;
    }
    return $h;
}
