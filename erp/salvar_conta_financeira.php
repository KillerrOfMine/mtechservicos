<?php
header('Content-Type: application/json');
session_start();
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada.']);
    exit;
}
require_once __DIR__ . '/includes/db_connect.php';

$nome = trim($_POST['nome'] ?? '');
$tipo_conta = trim($_POST['tipo_conta'] ?? 'Caixa');
$banco = trim($_POST['banco'] ?? '');
$agencia = trim($_POST['agencia'] ?? '');
$numero_conta = trim($_POST['numero_conta'] ?? '');
$saldo_inicial = $_POST['saldo_inicial'] ? floatval(str_replace(',', '.', $_POST['saldo_inicial'])) : 0.00;
$data_saldo = !empty($_POST['data_saldo']) ? $_POST['data_saldo'] : null;

if ($nome === '') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'O nome da conta é obrigatório.']);
    exit;
}

try {
    // Futuramente, este script será atualizado para lidar com edição (UPDATE)
    $sql = 'INSERT INTO contas_financeiras (nome, tipo_conta, banco, agencia, numero_conta, saldo_inicial, data_saldo, ativo) VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$nome, $tipo_conta, $banco, $agencia, $numero_conta, $saldo_inicial, $data_saldo]);
    
    echo json_encode(['sucesso' => true]);

} catch (Exception $e) {
    error_log('Erro ao salvar conta financeira: ' . $e->getMessage());
    echo json_encode(['sucesso' => false, 'mensagem' => 'Ocorreu um erro ao salvar a conta. Verifique os logs para mais detalhes.']);
}