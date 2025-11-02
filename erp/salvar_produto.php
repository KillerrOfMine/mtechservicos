<?php
require_once '../includes/db_connect.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validação dos campos obrigatórios
        $tipo_produto = $_POST['tipo_produto'] ?? '';
        $nome = $_POST['nome_produto'] ?? '';
        $codigo_barras = $_POST['codigo_barras'] ?? '';
        $origem_icms = $_POST['origem_icms'] ?? '';
        $unidade_medida = $_POST['unidade_medida'] ?? '';
        $ncm = $_POST['ncm'] ?? '';
        $valor = $_POST['valor'] ?? 0;
        $peso_liquido = $_POST['peso_liquido'] ?? 0;
        $peso_bruto = $_POST['peso_bruto'] ?? 0;
        $num_volumes = $_POST['num_volumes'] ?? 0;
        $tipo_embalagem = $_POST['tipo_embalagem'] ?? '';
        $embalagem = $_POST['embalagem'] ?? '';
        $largura = $_POST['largura'] ?? 0;
        $altura = $_POST['altura'] ?? 0;
        $comprimento = $_POST['comprimento'] ?? 0;

        if (empty($nome)) {
            throw new Exception('O campo Nome do produto é obrigatório.');
        }
        if (empty($unidade_medida)) {
            throw new Exception('O campo Unidade de medida é obrigatório.');
        }
        if (empty($ncm)) {
            throw new Exception('O campo NCM é obrigatório.');
        }
        if ($valor === '' || $valor === null) {
            throw new Exception('O campo Valor é obrigatório.');
        }
        // Conversão de valores numéricos
        $valor = is_numeric($valor) ? $valor : 0;
        $peso_liquido = is_numeric($peso_liquido) ? $peso_liquido : 0;
        $peso_bruto = is_numeric($peso_bruto) ? $peso_bruto : 0;
        $num_volumes = is_numeric($num_volumes) ? $num_volumes : 0;
        $largura = is_numeric($largura) ? $largura : 0;
        $altura = is_numeric($altura) ? $altura : 0;
        $comprimento = is_numeric($comprimento) ? $comprimento : 0;
        // Comando SQL
        $stmt = $pdo->prepare('INSERT INTO produtos (
            tipo_produto, nome, codigo_barras, origem_icms, unidade_medida, ncm, valor,
            peso_liquido, peso_bruto, num_volumes, tipo_embalagem, embalagem,
            largura, altura, comprimento
        ) VALUES (
            :tipo_produto, :nome, :codigo_barras, :origem_icms, :unidade_medida, :ncm, :valor,
            :peso_liquido, :peso_bruto, :num_volumes, :tipo_embalagem, :embalagem,
            :largura, :altura, :comprimento
        )');
        $stmt->execute([
            ':tipo_produto' => $tipo_produto,
            ':nome' => $nome,
            ':codigo_barras' => $codigo_barras,
            ':origem_icms' => $origem_icms,
            ':unidade_medida' => $unidade_medida,
            ':ncm' => $ncm,
            ':valor' => $valor,
            ':peso_liquido' => $peso_liquido,
            ':peso_bruto' => $peso_bruto,
            ':num_volumes' => $num_volumes,
            ':tipo_embalagem' => $tipo_embalagem,
            ':embalagem' => $embalagem,
            ':largura' => $largura,
            ':altura' => $altura,
            ':comprimento' => $comprimento
        ]);
        header('Location: produtos.php?sucesso=1');
        exit;
    } catch (Exception $e) {
        echo '<div style="color:red;font-weight:bold;padding:24px;">Erro: ' . htmlspecialchars($e->getMessage()) . '</div>';
    } catch (PDOException $e) {
        echo '<div style="color:red;font-weight:bold;padding:24px;">Erro ao salvar produto: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
}
?>
