<?php
/**
 * Prueba del piloto v0.6: tipologías vivas
 * (revestimiento_canal, bocatoma, compuerta, sifon_cruce,
 * cajon_desarenador_reja, desarenador_reja, canoa). Comparar a mano contra
 * los esperado_*.json.
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

$bocatoma = valorizar('bocatoma', [
    'caudal' => 0.03,
    'ancho_captacion' => 7.0,
    'alto_seccion' => 2.0,
    'longitud_lecho' => 2.0,
    'n_compuertas' => 3,
    'ancho_compuerta' => 0.8,
    'ancho_machon' => 0.5,
    'alto_machon' => 2.0,
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

$sifon_cruce = valorizar('sifon_cruce', [
    'caudal' => 0.5,
    'longitud_sifon' => 25.0,
    'longitud_bajo_cauce' => 15.0,
    'diametro_tuberia' => 0.8,
    'alto_existente' => 0.8,
    'ancho_existente' => 0.8,
    'alto_caida' => 1.0,
]);

$cajon_desarenador_reja = valorizar('cajon_desarenador_reja', [
    'caudal' => 0.96,
    'longitud_cajon' => 6.0,
    'ancho_cajon' => 2.0,
    'alto_cajon' => 1.5,
    'alto_existente' => 1.8,
    'ancho_existente' => 4.7,
]);

$desarenador_reja = valorizar('desarenador_reja', [
    'caudal' => 1.16,
    'longitud_sifon' => 250.0,
    'alto_existente' => 1.0,
    'ancho_existente' => 2.5,
    'alto_caida_entrada' => 3.0,
]);

$canoa = valorizar('canoa', [
    'caudal' => 1.8,
    'longitud_canoa' => 8.0,
    'ancho_canoa' => 2.6,
    'alto_canoa' => 1.0,
    'alto_fondo_quebrada' => 4.0,
]);

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'revestimiento_canal' => $revestimiento,
    'bocatoma' => $bocatoma,
    'compuerta' => $compuerta,
    'sifon_cruce' => $sifon_cruce,
    'cajon_desarenador_reja' => $cajon_desarenador_reja,
    'desarenador_reja' => $desarenador_reja,
    'canoa' => $canoa,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo "\n";
