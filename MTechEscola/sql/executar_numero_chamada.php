<?php
require_once '../db_connect_horarios.php';

echo "<pre>";
echo "=== ADICIONANDO COLUNA E VINCULANDO NÚMEROS DA CHAMADA ===\n\n";

try {
    // 1. Adicionar coluna
    $conn->exec("ALTER TABLE alunos ADD COLUMN IF NOT EXISTS numero_chamada INTEGER");
    echo "✓ Coluna 'numero_chamada' adicionada com sucesso!\n\n";
    
    // 2. Atualizar números da chamada - 6º ANO MATUTINO
    echo "Atualizando números da chamada...\n";
    
    $alunos = [
        1 => 'Alice Vilela Cruvinel',
        2 => 'Ana Laura Daniel Carneiro',
        3 => 'Anna Julia Mendonça de Almeida',
        4 => 'Brayann Raphael Souza Morais',
        5 => 'Elisa Machado Assunção',
        6 => 'Estevão Carvalho Lopes',
        7 => 'Gabriela Mendonça Tomaz',
        8 => 'Guilherme Gomes de Morais',
        9 => 'Luca William Ford Oliveira',
        10 => 'Manuella Inácio',
        11 => 'Maria Alice Araujo Palla',
        12 => 'Maria Eduarda Santos Ferreira',
        13 => 'Maria Valentina Souza Inácio',
        14 => 'Marianny Kasbaum do Amaral Silva',
        15 => 'Matheus Mattos Clementino',
        16 => 'Miguel Vitor Peres e Silva',
        17 => 'Nicolle Desiree Pereira Lopes',
        18 => 'Sarah Rodrigues Garcia',
        19 => 'Sophia Riccelli Felippe Ferreira',
        20 => 'Valentina Pimenta Textor',
        21 => 'Katheriny Bernardes Barros',
        22 => 'Sofia Fernandes Fonseca'
    ];
    
    $atualizados = 0;
    $nao_encontrados = [];
    
    foreach ($alunos as $numero => $nome) {
        // Remover acentos para busca mais flexível
        $nome_busca = '%' . $nome . '%';
        
        $stmt = $conn->prepare("UPDATE alunos SET numero_chamada = ? WHERE nome ILIKE ?");
        $stmt->execute([$numero, $nome_busca]);
        
        if ($stmt->rowCount() > 0) {
            echo "✓ $numero - $nome\n";
            $atualizados++;
        } else {
            echo "✗ $numero - $nome (NÃO ENCONTRADO)\n";
            $nao_encontrados[] = $nome;
        }
    }
    
    echo "\n=== RESUMO ===\n";
    echo "Alunos atualizados: $atualizados\n";
    echo "Não encontrados: " . count($nao_encontrados) . "\n";
    
    if (count($nao_encontrados) > 0) {
        echo "\nAlunos não encontrados:\n";
        foreach ($nao_encontrados as $nome) {
            echo "- $nome\n";
        }
    }
    
    // 3. Mostrar resultado
    echo "\n=== ALUNOS COM NÚMERO DA CHAMADA ===\n";
    $stmt = $conn->query("
        SELECT a.nome, a.numero_chamada, t.nome as turma 
        FROM alunos a 
        LEFT JOIN turmas t ON a.turma_id = t.id
        WHERE a.numero_chamada IS NOT NULL 
        ORDER BY a.numero_chamada
    ");
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo sprintf("%2d - %-40s [%s]\n", 
            $row['numero_chamada'], 
            $row['nome'], 
            $row['turma'] ?? 'Sem turma'
        );
    }
    
    echo "\n✓ PROCESSO CONCLUÍDO COM SUCESSO!\n";
    
} catch (Exception $e) {
    echo "✗ ERRO: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
