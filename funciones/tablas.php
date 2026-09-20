<?php
/**
 * Selección en tablas de dimensiones estándar (portado desde
 * motor_calculo/tablas.py).
 */

/**
 * Selecciona el valor "más cercano inteligente" a un objetivo: se
 * prefiere el menor valor que sea mayor o igual al objetivo (la
 * dimensión estándar que cubre lo requerido); si ninguno lo alcanza,
 * se toma el más cercano en valor absoluto.
 */
function valor_mas_cercano($valores, $objetivo) {
    $valores = array_values($valores);
    if (empty($valores)) {
        throw new InvalidArgumentException('No hay valores donde buscar');
    }
    $mayores_o_iguales = array_values(array_filter($valores, fn($v) => $v >= $objetivo));
    if (!empty($mayores_o_iguales)) {
        usort($mayores_o_iguales, fn($a, $b) => ($a - $objetivo) <=> ($b - $objetivo));
        return $mayores_o_iguales[0];
    }
    usort($valores, fn($a, $b) => abs($a - $objetivo) <=> abs($b - $objetivo));
    return $valores[0];
}

/**
 * Selecciona la fila de una tabla de dimensiones estándar según base y
 * altura: primero se elige la base estándar más adecuada, luego entre
 * las filas con esa base, la altura más adecuada (misma regla).
 * $columna_base/$columna_altura son índices dentro de cada fila (array).
 */
function buscar_fila_por_dimensiones($filas, $base, $altura, $columna_base = 0, $columna_altura = 1) {
    $filas = array_values($filas);
    $bases = array_map(fn($fila) => $fila[$columna_base], $filas);
    $base_elegida = valor_mas_cercano($bases, $base);

    $candidatas = array_values(array_filter($filas, fn($fila) => $fila[$columna_base] == $base_elegida));
    $alturas = array_map(fn($fila) => $fila[$columna_altura], $candidatas);
    $altura_elegida = valor_mas_cercano($alturas, $altura);

    foreach ($candidatas as $fila) {
        if ($fila[$columna_altura] == $altura_elegida) {
            return $fila;
        }
    }
    throw new RuntimeException('inalcanzable: la altura elegida proviene de las candidatas');
}

// Tabla referencial de cajones prefabricados estándar (base, alto,
// espesor, en m). Reemplazar por la tabla real "CajonM" del libro
// Excel cuando esté disponible - mismos valores que el original.
$GLOBALS['CAJONES_ESTANDAR'] = [
    [1.0, 1.0, 0.15],
    [1.5, 1.5, 0.15],
    [2.0, 1.5, 0.20],
    [2.0, 2.0, 0.20],
    [3.0, 2.0, 0.25],
];
