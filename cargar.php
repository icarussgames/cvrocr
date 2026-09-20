<?php
/**
 * Punto de entrada único para toda la lógica de cálculo y presupuesto.
 * Los archivos api-*.php y test_piloto.php deben hacer:
 *
 *     require __DIR__ . '/cargar.php';
 *
 * en vez de require directo a archivos individuales - así el orden de
 * carga (que sí importa: cada pieza depende de la anterior) queda
 * resuelto en un solo lugar.
 *
 * Para agregar una tipología nueva: crea su archivo en
 * funciones/cubicaciones/nombre.php (mismo patrón que las demás -
 * cubicacion_nombre(), _nombre($p), y $TIPOLOGIAS['nombre'] = [...])
 * y aparece sola, sin tocar este archivo (se carga por glob()).
 */

$TIPOLOGIAS = [];

require __DIR__ . '/funciones/constantes.php';
require __DIR__ . '/funciones/hidraulica.php';
require __DIR__ . '/funciones/parametros.php';
require __DIR__ . '/funciones/comun.php';
require __DIR__ . '/funciones/tablas.php';

// valorizacion.php define _campo(), que cada archivo de cubicaciones
// usa al registrarse - por eso se carga antes que ellas.
require __DIR__ . '/funciones/valorizacion.php';

foreach (glob(__DIR__ . '/funciones/cubicaciones/*.php') as $archivo) {
    require $archivo;
}
