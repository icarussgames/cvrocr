<?php
/**
 * Prueba del piloto: corre los mismos casos que la versión Python y
 * muestra el JSON resultante para comparar a mano contra
 * esperado_python.json (revestimiento_canal) y esperado_disipador.json.
 *
 * Uso (por SSH o terminal si tu hosting lo permite):
 *   php test_piloto.php
 *
 * Si no tienes acceso a terminal, sube esta carpeta completa a tu hosting
 * y visita test_piloto.php desde el navegador - también imprime el JSON.
 */

require __DIR__ . '/cargar.php';

$revestimiento = valorizar('revestimiento_canal', [
    'caudal' => 670,
    'largo' => 100,
    'ancho' => 1,
    'alto' => 1,
    'talud' => 0,
    'espesor' => 0.13,
]);

$disipador = valorizar('disipador', [
    'caudal' => 0.67,
    'base' => 1.0,
    'altura' => 1.0,
]);

$desarenador = valorizar('desarenador', [
    'caudal' => 0.67,
    'ancho' => 1.0,
    'alto' => 1.0,
]);

$partidor = valorizar('partidor', [
    'caudal' => 0.67,
    'base' => 1.0,
    'altura' => 1.0,
]);

$compuerta = valorizar('compuerta', [
    'caudal' => 0.5,
    'longitud_descarga' => 400.0,
    'ancho_descarga' => 1.0,
    'alto_descarga' => 0.8,
    'espesor_revestimiento' => 0.13,
    'n_compuertas' => 2,
    'ancho_compuerta' => 0.6,
    'alto_compuerta' => 0.8,
    'espesor_compuerta' => 0.008,
    'alto_portico' => 1.0,
]);

$cajon = valorizar('cajon', [
    'largo' => 20.0,
    'ancho_canal' => 1.5,
    'alto_canal' => 1.5,
]);

$canoa = valorizar('canoa', [
    'largo' => 24.0,
    'base' => 1.0,
    'altura' => 1.0,
]);

$reparacion_sifon = valorizar('reparacion_sifon', [
    'largo' => 20.0,
    'base' => 1.0,
    'altura' => 1.0,
    'espesor' => 0.2,
]);

$reja_sifon = valorizar('reja_sifon', [
    'base' => 1.0,
    'altura' => 1.0,
]);

$tunel_shotcrete = valorizar('tunel_shotcrete', [
    'largo' => 50.0,
    'base' => 2.0,
    'altura' => 2.0,
]);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'revestimiento_canal' => $revestimiento,
    'disipador' => $disipador,
    'desarenador' => $desarenador,
    'partidor' => $partidor,
    'compuerta' => $compuerta,
    'cajon' => $cajon,
    'canoa' => $canoa,
    'reparacion_sifon' => $reparacion_sifon,
    'reja_sifon' => $reja_sifon,
    'tunel_shotcrete' => $tunel_shotcrete,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n";
