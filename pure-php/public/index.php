<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use HexagonPractise\Bootstrap\Container;
use HexagonPractise\Infrastructure\Http\AppointmentController;
use HexagonPractise\Infrastructure\Http\AuditLogController;
use HexagonPractise\Infrastructure\Http\DoctorController;
use HexagonPractise\Infrastructure\Http\PatientController;
use HexagonPractise\Infrastructure\Http\PrescriptionController;

$config      = require dirname(__DIR__) . '/config/app.php';
$useInMemory = (getenv('USE_IN_MEMORY') ?: '') === '1';
$container   = Container::fromConfig($config, $useInMemory);

$method      = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path        = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$body        = (string) file_get_contents('php://input');

$controllers = [
    new AuditLogController($container),
    new AppointmentController($container),
    new DoctorController($container),
    new PatientController($container),
    new PrescriptionController($container),
];

$result      = ['status' => 404, 'error' => 'Not found'];
foreach ($controllers as $controller) {
    $result = $controller instanceof AuditLogController
        ? $controller->handle($method, $path)
        : $controller->handle($method, $path, $body);
    if (($result['status'] ?? 200) !== 404) {
        break;
    }
}

$status      = (int) ($result['status'] ?? 200);
unset($result['status']);

http_response_code($status);
header('Content-Type: application/json');
echo json_encode($result, JSON_THROW_ON_ERROR);
