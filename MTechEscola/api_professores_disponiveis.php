<?php
session_start();
require_once 'db_connect_horarios.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}

$turma_id = $_GET['turma_id'] ?? '';
$disciplina_id = $_GET['disciplina_id'] ?? '';
$dia_semana = $_GET['dia_semana'] ?? '';
$hora_inicio = $_GET['hora_inicio'] ?? '';

// Busca professores disponíveis para esta disciplina neste horário
$professores = [];

if ($disciplina_id && $dia_semana && $hora_inicio) {
    // Busca professores que lecionam esta disciplina
    $stmt = $conn->prepare("
        SELECT DISTINCT p.id, p.nome
        FROM professores p
        INNER JOIN professores_disciplinas pd ON p.id = pd.professor_id
        WHERE pd.disciplina_id = ?
        ORDER BY p.nome
    ");
    $stmt->execute([$disciplina_id]);
    $todos_professores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Se não houver professores vinculados, busca todos os professores ativos
    if (empty($todos_professores)) {
        $stmt = $conn->prepare("SELECT id, nome FROM professores ORDER BY nome");
        $stmt->execute();
        $todos_professores = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    foreach ($todos_professores as $prof) {
        // Verifica se está disponível (tabela de disponibilidade)
        $stmt = $conn->prepare("
            SELECT disponivel 
            FROM horarios_disponiveis_professor 
            WHERE professor_id = ? AND dia_semana = ? AND hora_inicio = ?
        ");
        $stmt->execute([$prof['id'], $dia_semana, $hora_inicio]);
        $disp = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verifica se já tem aula neste horário (em outra turma)
        // Tenta com intervalo_id primeiro, senão usa campos diretos
        $tem_conflito = false;
        try {
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM horarios_aulas ha
                JOIN intervalos i ON ha.intervalo_id = i.id
                WHERE ha.professor_id = ? 
                AND i.dia_semana = ? 
                AND i.hora_inicio = ? 
                AND ha.turma_id != ? 
                AND COALESCE(ha.ativo, TRUE) = TRUE
            ");
            $stmt->execute([$prof['id'], $dia_semana, $hora_inicio, $turma_id]);
            $tem_conflito = $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            // Fallback para estrutura antiga
            $stmt = $conn->prepare("
                SELECT COUNT(*) 
                FROM horarios_aulas
                WHERE professor_id = ? 
                AND dia_semana = ? 
                AND hora_inicio = ? 
                AND turma_id != ? 
                AND COALESCE(ativo, TRUE) = TRUE
            ");
            $stmt->execute([$prof['id'], $dia_semana, $hora_inicio, $turma_id]);
            $tem_conflito = $stmt->fetchColumn() > 0;
        }
        
        $disponivel = true;
        
        // Se professor marcou como indisponível
        if ($disp && !$disp['disponivel']) {
            $disponivel = false;
        }
        
        // Se tem conflito de horário
        if ($tem_conflito) {
            $disponivel = false;
        }
        
        $professores[] = [
            'id' => $prof['id'],
            'nome' => $prof['nome'],
            'disponivel' => $disponivel
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($professores);
