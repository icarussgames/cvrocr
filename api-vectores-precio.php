<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/cargar.php';

echo json_encode(listar_vectores_precio(), JSON_UNESCAPED_UNICODE);
