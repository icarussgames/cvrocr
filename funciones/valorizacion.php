<?php
/**
 * Armado del presupuesto (portado desde valoriza_app/valorizacion.py).
 * No debería cambiar cuando se ajusta una tipología puntual - eso vive
 * en funciones/cubicaciones/.
 */

$PORCENTAJES_DEFECTO = [
    'gastos_generales' => 8.0,
    'utilidades' => 15.0,
    'imprevistos' => 1.0,
    'iva' => 19.0,
];

// precios.csv vive en la raíz del proyecto, un nivel arriba de funciones/.
define('RUTA_PRECIOS', dirname(__DIR__) . '/precios.csv');

function listar_vectores_precio() {
    $f = fopen(RUTA_PRECIOS, 'r');
    $encabezado = fgetcsv($f);
    fclose($f);
    $vectores = [];
    foreach ($encabezado as $columna) {
        if (strpos($columna, PREFIJO_VECTOR_PRECIO) === 0) {
            $resto = ltrim(substr($columna, strlen(PREFIJO_VECTOR_PRECIO)), '_');
            $etiqueta = $resto !== '' ? ucfirst(str_replace('_', ' ', $resto)) : 'Precios base';
            $vectores[] = ['id' => $columna, 'etiqueta' => $etiqueta];
        }
    }
    return $vectores;
}

function cargar_precios($vector_precio) {
    $f = fopen(RUTA_PRECIOS, 'r');
    $encabezado = fgetcsv($f);
    $idx_vector = array_search($vector_precio, $encabezado);
    if ($idx_vector === false) {
        fclose($f);
        throw new InvalidArgumentException("Vector de precios desconocido: $vector_precio");
    }
    $idx_codigo = array_search('codigo', $encabezado);
    $idx_desc = array_search('descripcion', $encabezado);
    $idx_unidad = array_search('unidad', $encabezado);

    $precios = [];
    while (($fila = fgetcsv($f)) !== false) {
        $precios[$fila[$idx_codigo]] = [
            'descripcion' => $fila[$idx_desc],
            'unidad' => $fila[$idx_unidad],
            'precio_clp' => (int) $fila[$idx_vector],
        ];
    }
    fclose($f);
    return $precios;
}

/**
 * Describe un campo de entrada para el formulario del frontend. Usada
 * por cada archivo de funciones/cubicaciones/ al registrar sus campos.
 * $ayuda es el texto que se muestra en el tooltip "?" junto al campo
 * en index.html - vive acá, junto a la definición del campo, para que
 * ajustarlo no implique tocar el frontend.
 */
function _campo($nombre, $etiqueta, $unidad, $defecto, $ayuda = '') {
    return ['nombre' => $nombre, 'etiqueta' => $etiqueta, 'unidad' => $unidad,
            'defecto' => $defecto, 'ayuda' => $ayuda];
}

/**
 * Agrupa partidas ya calculadas en capítulos y subcapítulos, para la
 * PRESENTACIÓN del presupuesto (los códigos de precio siguen siendo
 * los mismos - esto es puramente de formato). Numera automáticamente
 * (1, 2.1, 2.1.1...) según el orden de aparición y calcula subtotales
 * por capítulo (incluyendo lo que caiga en sus subcapítulos).
 *
 * $entradas: lista de ['ruta' => ['Capitulo'] | ['Capitulo','Subcap'] | [],
 *                       'partida' => <fila ya armada por valorizar()>]
 */
function agrupar_por_capitulos($entradas) {
    $capitulos = [];
    $indice_cap = [];

    foreach ($entradas as $entrada) {
        $ruta = $entrada['ruta'] ?: ['Partidas']; // capitulo generico si no se definio
        $partida = $entrada['partida'];
        $titulo_cap = $ruta[0];

        if (!isset($indice_cap[$titulo_cap])) {
            $indice_cap[$titulo_cap] = count($capitulos);
            $capitulos[] = [
                'numero' => (string) (count($capitulos) + 1),
                'titulo' => $titulo_cap,
                'partidas' => [],
                'subcapitulos' => [],
                '_indice_sub' => [],
                'subtotal_clp' => 0,
                'subtotal_uf' => 0.0,
            ];
        }
        $ic = $indice_cap[$titulo_cap];

        if (count($ruta) >= 2) {
            $titulo_sub = $ruta[1];
            if (!isset($capitulos[$ic]['_indice_sub'][$titulo_sub])) {
                $numero_sub = $capitulos[$ic]['numero'] . '.' . (count($capitulos[$ic]['subcapitulos']) + 1);
                $capitulos[$ic]['_indice_sub'][$titulo_sub] = count($capitulos[$ic]['subcapitulos']);
                $capitulos[$ic]['subcapitulos'][] = [
                    'numero' => $numero_sub,
                    'titulo' => $titulo_sub,
                    'partidas' => [],
                    'subtotal_clp' => 0,
                    'subtotal_uf' => 0.0,
                ];
            }
            $is = $capitulos[$ic]['_indice_sub'][$titulo_sub];
            $item = $capitulos[$ic]['subcapitulos'][$is]['numero'] . '.'
                  . (count($capitulos[$ic]['subcapitulos'][$is]['partidas']) + 1);
            $partida['item'] = $item;
            $capitulos[$ic]['subcapitulos'][$is]['partidas'][] = $partida;
            $capitulos[$ic]['subcapitulos'][$is]['subtotal_clp'] += $partida['subtotal_clp'];
            $capitulos[$ic]['subcapitulos'][$is]['subtotal_uf'] += $partida['subtotal_uf'];
        } else {
            $item = $capitulos[$ic]['numero'] . '.' . (count($capitulos[$ic]['partidas']) + 1);
            $partida['item'] = $item;
            $capitulos[$ic]['partidas'][] = $partida;
        }

        $capitulos[$ic]['subtotal_clp'] += $partida['subtotal_clp'];
        $capitulos[$ic]['subtotal_uf'] += $partida['subtotal_uf'];
    }

    foreach ($capitulos as &$cap) {
        unset($cap['_indice_sub']);
        $cap['subtotal_uf'] = round($cap['subtotal_uf'], 2);
        foreach ($cap['subcapitulos'] as &$sub) {
            $sub['subtotal_uf'] = round($sub['subtotal_uf'], 2);
        }
        unset($sub);
    }
    unset($cap);

    return $capitulos;
}

function listar_tipologias() {
    global $TIPOLOGIAS;
    $salida = [];
    foreach ($TIPOLOGIAS as $id => $t) {
        $salida[] = ['id' => $id, 'titulo' => $t['titulo'], 'campos' => $t['campos'], 'esquema' => $t['esquema'] ?? null];
    }
    return $salida;
}

function valorizar($tipologia_id, $parametros, $porcentajes = null, $vector_precio = PREFIJO_VECTOR_PRECIO) {
    global $TIPOLOGIAS, $PORCENTAJES_DEFECTO;

    if (!isset($TIPOLOGIAS[$tipologia_id])) {
        throw new InvalidArgumentException("Tipologia desconocida: $tipologia_id");
    }
    $tipologia = $TIPOLOGIAS[$tipologia_id];

    $faltantes = [];
    foreach ($tipologia['campos'] as $c) {
        if (!array_key_exists($c['nombre'], $parametros)) {
            $faltantes[] = $c['nombre'];
        }
    }
    if ($faltantes) {
        throw new InvalidArgumentException('Faltan parametros: ' . implode(', ', $faltantes));
    }

    $valores = [];
    foreach ($tipologia['campos'] as $c) {
        $valores[$c['nombre']] = (float) $parametros[$c['nombre']];
    }

    $pct = $PORCENTAJES_DEFECTO;
    foreach (($porcentajes ?: []) as $k => $v) {
        $pct[$k] = $v;
    }

    $precios = cargar_precios($vector_precio);
    // Cada cubicar() devuelve [partidas, informativos] o, opcionalmente,
    // [partidas, informativos, notas] - las tipologías que aún no
    // devuelven notas simplemente quedan con notas = [] (retrocompatible,
    // no hay que tocar las demás cubicar() para agregar esto a una sola).
    $resultado_cubicar = call_user_func($tipologia['cubicar'], $valores);
    $cantidades = $resultado_cubicar[0];
    $informativos = $resultado_cubicar[1];
    $notas = $resultado_cubicar[2] ?? [];

    $partidas = [];
    $partidas_con_ruta = [];
    $costo_directo = 0.0;
    // Cada entrada es [codigo, cantidad] o, opcionalmente,
    // [codigo, cantidad, etiqueta, ruta_capitulo]:
    //   - etiqueta reemplaza la descripcion del CSV solo para esa fila
    //     (util cuando dos partidas usan el mismo codigo/precio pero
    //     corresponden a partes distintas de la obra, ej. "Hormigon
    //     canal" vs "Hormigon muros de ala").
    //   - ruta_capitulo es ['Capitulo'] o ['Capitulo','Subcapitulo'] -
    //     define en que capitulo del presupuesto presentado cae esa
    //     partida (ver agrupar_por_capitulos() mas abajo). Si se omite,
    //     la partida cae en un capitulo generico "Partidas".
    foreach ($cantidades as $entrada) {
        $codigo = $entrada[0];
        $cantidad = $entrada[1];
        $etiqueta = $entrada[2] ?? null;
        $ruta_capitulo = $entrada[3] ?? [];
        $precio = $precios[$codigo];
        $subtotal = $cantidad * $precio['precio_clp'];
        $costo_directo += $subtotal;
        $partida = [
            'codigo' => $codigo,
            'descripcion' => $etiqueta ?? $precio['descripcion'],
            'unidad' => $precio['unidad'],
            'cantidad' => round($cantidad, 2),
            'precio_clp' => $precio['precio_clp'],
            'subtotal_clp' => (int) round($subtotal),
            'subtotal_uf' => round($subtotal / UF_CLP, 2),
        ];
        $partidas[] = $partida;
        $partidas_con_ruta[] = ['ruta' => $ruta_capitulo, 'partida' => $partida];
    }
    $capitulos = agrupar_por_capitulos($partidas_con_ruta);

    $gastos_generales = $costo_directo * $pct['gastos_generales'] / 100;
    $utilidades = $costo_directo * $pct['utilidades'] / 100;
    $imprevistos = $costo_directo * $pct['imprevistos'] / 100;
    $neto = $costo_directo + $gastos_generales + $utilidades + $imprevistos;
    $iva = $neto * $pct['iva'] / 100;
    $total = $neto + $iva;

    $monto = function ($clp) {
        return ['clp' => (int) round($clp), 'uf' => round($clp / UF_CLP, 2)];
    };

    $informativos_out = [];
    foreach ($informativos as [$n, $v, $u]) {
        $informativos_out[] = [
            'nombre' => $n,
            'valor' => is_string($v) ? $v : round($v, 3),
            'unidad' => $u,
        ];
    }

    return [
        'tipologia' => $tipologia['titulo'],
        'partidas' => $partidas,
        'capitulos' => $capitulos,
        'informativos' => $informativos_out,
        'notas' => $notas,
        'porcentajes' => $pct,
        'resumen' => [
            'costo_directo' => $monto($costo_directo),
            'gastos_generales' => $monto($gastos_generales),
            'utilidades' => $monto($utilidades),
            'imprevistos' => $monto($imprevistos),
            'neto' => $monto($neto),
            'iva' => $monto($iva),
            'total' => $monto($total),
        ],
        'uf_clp' => UF_CLP,
        'vector_precio' => $vector_precio,
    ];
}
