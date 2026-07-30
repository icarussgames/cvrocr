<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/cargar.php';

echo json_encode(listar_tipologias(), JSON_UNESCAPED_UNICODE);
