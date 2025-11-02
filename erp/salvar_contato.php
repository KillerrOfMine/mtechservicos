<?php
require_once __DIR__ . '/includes/db_connect.php';
header('Content-Type: application/json');

// Recebe dados via POST
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) $data = $_POST;

// Validação simples
if (empty($data['nome'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'O campo nome é obrigatório.']);
    exit;
}

try {
    $stmt = $pdo->prepare('INSERT INTO contatos (
        nome, fantasia, codigo, tipo_pessoa, cpf_cnpj, contribuinte, insc_estadual, insc_municipal, tipo_contato, endereco, municipio, uf, cep, contato
    ) VALUES (
        :nome, :fantasia, :codigo, :tipo_pessoa, :cpf_cnpj, :contribuinte, :insc_estadual, :insc_municipal, :tipo_contato, :endereco, :municipio, :uf, :cep, :contato
    )');
    $stmt->execute([
        ':nome' => $data['nome'],
        ':fantasia' => $data['fantasia'] ?? '',
        ':codigo' => $data['codigo'] ?? '',
        ':tipo_pessoa' => $data['tipo_pessoa'],
        ':cpf_cnpj' => $data['cpf_cnpj'],
        ':contribuinte' => $data['contribuinte'] ?? '9',
        ':insc_estadual' => $data['insc_estadual'] ?? '',
        ':insc_municipal' => $data['insc_municipal'] ?? '',
        ':tipo_contato' => $data['tipo_contato'],
        ':endereco' => $data['endereco'] ?? '',
        ':municipio' => $data['municipio'] ?? '',
        ':uf' => $data['uf'] ?? '',
        ':cep' => $data['cep'] ?? '',
        ':contato' => $data['contato'] ?? ''
    ]);
    echo json_encode(['success' => true, 'message' => 'Contato salvo com sucesso!']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar contato: ' . $e->getMessage()]);
}
