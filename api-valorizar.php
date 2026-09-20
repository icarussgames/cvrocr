<?php
header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/cargar.php';

$datos = json_decode(file_get_contents('php://input'), true);
if ($datos === null) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON invalido en el body de la peticion']);
    exit;
}

try {
    $resultado = valorizar(
        $datos['tipologia'] ?? '',
        $datos['parametros'] ?? [],
        $datos['porcentajes'] ?? null,
        $datos['vector_precio'] ?? PREFIJO_VECTOR_PRECIO
    );
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
} catch (InvalidArgumentException $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Error interno: ' . $e->getMessage()]);
}
