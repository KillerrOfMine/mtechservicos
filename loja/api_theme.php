<?php
require_once 'config.php';
require_once 'classes/ThemeManager.php';

// Verifica se está logado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}

header('Content-Type: application/json');

$themeManager = new ThemeManager($_SESSION['user_id']);

// GET - Retorna tema atual
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (isset($_GET['presets'])) {
        echo json_encode(ThemeManager::getPresets());
    } else {
        echo json_encode($themeManager->getTheme());
    }
    exit;
}

// POST - Atualiza tema
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos']);
        exit;
    }
    
    $result = $themeManager->updateTheme($data);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'theme' => $themeManager->getTheme()
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao atualizar tema']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método não permitido']);
