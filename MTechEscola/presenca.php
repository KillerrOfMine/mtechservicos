<?php
session_start();
require_once 'db_connect_horarios.php';
require_once 'includes/auth.php';

// Processar salvamento de presença ANTES de qualquer output (incluindo headers HTML)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['registrar_presenca'])) {
    try {
        $turma_id = $_POST['turma_id'];
        $disciplina_id = $_POST['disciplina_id'];
        $aula_data = $_POST['aula_data'];
        
        // Verificar se há dados de presença
        if (isset($_POST['presenca']) && is_array($_POST['presenca'])) {
            foreach ($_POST['presenca'] as $aluno_id => $status) {
                // Se for professor, usar ID 1 (admin padrão) como registrado_por
                // pois a tabela presencas tem FK para usuarios, não professores
                $registrado_por = $is_professor ? 1 : $usuario_id;
                
                $stmt = $conn->prepare("INSERT INTO presencas (aluno_id, turma_id, disciplina_id, aula_data, status, registrado_por) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$aluno_id, $turma_id, $disciplina_id, $aula_data, $status, $registrado_por]);
            }
            
            // Redirecionar para evitar reenvio do formulário
            $_SESSION['msg_sucesso'] = 'Presenças registradas com sucesso!';
        } else {
            $_SESSION['msg_erro'] = 'Nenhuma presença foi marcada!';
        }
        
        header("Location: presenca.php?turma_id=$turma_id&disciplina_id=$disciplina_id&aula_data=$aula_data");
        exit;
    } catch (Exception $e) {
        $_SESSION['msg_erro'] = 'Erro ao salvar: ' . $e->getMessage();
        header("Location: presenca.php");
        exit;
    }
}

// Buscar turmas baseado no tipo de usuário
if ($is_professor) {
    // Professor: apenas turmas que ele leciona
    $stmt = $conn->prepare("
        SELECT DISTINCT t.id, t.nome 
        FROM turmas t
        JOIN turma_disciplina_professor tdp ON t.id = tdp.turma_id
        WHERE tdp.professor_id = ?
        ORDER BY t.nome
    ");
    $stmt->execute([$usuario_id]);
    $turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Admin: todas as turmas
    $turmas = $conn->query("SELECT id, nome FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar disciplinas baseado no tipo de usuário
if ($is_professor) {
    // Professor: apenas disciplinas que ele leciona
    $stmt = $conn->prepare("
        SELECT DISTINCT d.id, d.nome 
        FROM disciplinas d
        JOIN turma_disciplina_professor tdp ON d.id = tdp.disciplina_id
        WHERE tdp.professor_id = ?
        ORDER BY d.nome
    ");
    $stmt->execute([$usuario_id]);
    $disciplinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Admin: todas as disciplinas
    $disciplinas = $conn->query("SELECT id, nome FROM disciplinas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
}

// Buscar alunos se turma_id foi fornecido (GET ou POST)
$alunos = [];
$turma_id_selecionada = $_GET['turma_id'] ?? $_POST['turma_id'] ?? null;
if ($turma_id_selecionada) {
    $stmt = $conn->prepare("SELECT id, nome FROM alunos WHERE turma_id = ? ORDER BY nome");
    $stmt->execute([$turma_id_selecionada]);
    $alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Verificar mensagem de sucesso ou erro
$msg = '';
$msg_tipo = 'success';
if (isset($_SESSION['msg_sucesso'])) {
    $msg = $_SESSION['msg_sucesso'];
    unset($_SESSION['msg_sucesso']);
} elseif (isset($_SESSION['msg_erro'])) {
    $msg = $_SESSION['msg_erro'];
    $msg_tipo = 'error';
    unset($_SESSION['msg_erro']);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Frequência - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, #0f2027, #2c5364); 
            min-height: 100vh; 
            font-family: 'Orbitron', Arial, sans-serif; 
            color: #fff;
            padding-top: 70px;
            padding-bottom: 20px;
        }
        
        /* Header fixo com menu hambúrguer */
        .header-nav {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(10, 15, 25, 0.95);
            backdrop-filter: blur(10px);
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        
        .header-nav h1 {
            font-size: 1.2em;
            background: linear-gradient(90deg, #00c3ff, #ffff1c);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            flex: 1;
            text-align: center;
        }
        
        .btn-voltar {
            background: rgba(0, 195, 255, 0.2);
            border: 2px solid #00c3ff;
            border-radius: 8px;
            color: #00c3ff;
            font-size: 1.3em;
            font-weight: 700;
            cursor: pointer;
            padding: 6px 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        
        .btn-voltar:active {
            background: rgba(0, 195, 255, 0.4);
            transform: scale(0.95);
        }
        
        .menu-toggle {
            background: none;
            border: none;
            color: #00c3ff;
            font-size: 1.8em;
            cursor: pointer;
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .menu-lateral {
            position: fixed;
            top: 0;
            right: -300px;
            width: 280px;
            height: 100vh;
            background: rgba(10, 15, 25, 0.98);
            backdrop-filter: blur(10px);
            transition: right 0.3s ease;
            z-index: 2000;
            padding: 20px;
            overflow-y: auto;
        }
        
        .menu-lateral.ativo {
            right: 0;
        }
        
        .menu-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 1500;
        }
        
        .menu-overlay.ativo {
            opacity: 1;
            visibility: visible;
        }
        
        .menu-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(0, 195, 255, 0.3);
        }
        
        .menu-header h2 {
            font-size: 1.3em;
            color: #00c3ff;
        }
        
        .menu-close {
            background: none;
            border: none;
            color: #00c3ff;
            font-size: 1.8em;
            cursor: pointer;
            padding: 4px;
        }
        
        .menu-item {
            display: block;
            padding: 14px 16px;
            margin-bottom: 8px;
            background: rgba(0, 195, 255, 0.1);
            border-radius: 10px;
            text-decoration: none;
            color: #fff;
            font-size: 0.95em;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        
        .menu-item:hover {
            background: rgba(0, 195, 255, 0.2);
            border-color: #00c3ff;
            transform: translateX(5px);
        }
        
        /* Container */
        .container { 
            padding: 20px 16px;
            max-width: 600px;
            margin: 0 auto;
        }
        
        /* Header */
        .page-header {
            background: rgba(20, 30, 50, 0.9);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
        }
        .page-header h1 { 
            font-size: 1.8em; 
            font-weight: 700;
            background: linear-gradient(90deg, #00c3ff, #ffff1c); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent;
            margin-bottom: 8px;
        }
        .page-header p {
            font-size: 0.9em;
            color: #b0bec5;
        }
        
        /* Form Section */
        .form-section {
            background: rgba(20, 30, 50, 0.8);
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 600;
            font-size: 0.95em;
            color: #00c3ff;
        }
        select, input[type=date] { 
            width: 100%; 
            padding: 12px; 
            border-radius: 10px; 
            border: 2px solid #22334a;
            background: #1a2332;
            color: #fff;
            margin-bottom: 16px; 
            font-size: 1em;
            font-family: inherit;
        }
        select:focus, input[type=date]:focus {
            outline: none;
            border-color: #00c3ff;
        }
        
        /* Aluno Cards */
        .alunos-list {
            margin-top: 20px;
        }
        .aluno-card {
            background: rgba(20, 30, 50, 0.8);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 2px solid transparent;
            transition: all 0.2s;
        }
        .aluno-card.presente {
            border-color: #4caf50;
            background: rgba(76, 175, 80, 0.1);
        }
        .aluno-card.ausente {
            border-color: #f44336;
            background: rgba(244, 67, 54, 0.1);
        }
        .aluno-nome {
            flex: 1;
            font-size: 0.95em;
            font-weight: 500;
        }
        .presenca-toggle {
            display: flex;
            gap: 8px;
        }
        .toggle-btn {
            padding: 8px 16px;
            border-radius: 8px;
            border: 2px solid #22334a;
            background: #1a2332;
            color: #fff;
            font-size: 0.85em;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }
        .toggle-btn:active {
            transform: scale(0.95);
        }
        .toggle-btn.presente {
            background: #4caf50;
            border-color: #4caf50;
            color: #fff;
        }
        .toggle-btn.ausente {
            background: #f44336;
            border-color: #f44336;
            color: #fff;
        }
        
        /* Summary */
        .summary {
            background: rgba(20, 30, 50, 0.8);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .summary-item {
            flex: 1;
        }
        .summary-value {
            font-size: 1.8em;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .summary-value.presentes { color: #4caf50; }
        .summary-value.ausentes { color: #f44336; }
        .summary-label {
            font-size: 0.8em;
            color: #b0bec5;
        }
        
        /* Buttons */
        .btn-container {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn { 
            flex: 1;
            background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); 
            color: #222; 
            font-weight: 700; 
            border: none; 
            border-radius: 12px; 
            padding: 14px 24px; 
            font-size: 1em; 
            cursor: pointer; 
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            transition: all 0.2s;
            font-family: inherit;
        }
        .btn:active { 
            transform: scale(0.97); 
            box-shadow: 0 4px 16px rgba(0,195,255,0.4); 
        }
        .btn-secondary {
            background: rgba(255,255,255,0.1);
            color: #fff;
        }
        .btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        /* Messages */
        .msg { 
            background: #00c3ff;
            color: #222;
            padding: 14px;
            border-radius: 12px;
            margin-bottom: 16px;
            font-weight: 700;
            text-align: center;
            font-size: 0.95em;
        }
        
        .msg-error {
            background: #ff4444;
            color: #fff;
        }
        
        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .quick-btn {
            flex: 1;
            padding: 10px;
            background: rgba(0,195,255,0.2);
            border: 2px solid #00c3ff;
            border-radius: 10px;
            color: #00c3ff;
            font-weight: 600;
            font-size: 0.85em;
            cursor: pointer;
            transition: all 0.2s;
            font-family: inherit;
        }
        .quick-btn:active {
            background: rgba(0,195,255,0.4);
            transform: scale(0.95);
        }
        
        /* Loading */
        .loading {
            text-align: center;
            padding: 20px;
            color: #b0bec5;
        }
        
        /* Desktop */
        @media (min-width: 768px) {
            body { padding-top: 100px; }
            .container { padding: 32px 24px; }
            .page-header h1 { font-size: 2.2em; }
            .aluno-card { padding: 20px; }
            .toggle-btn { padding: 10px 20px; font-size: 0.9em; }
        }
    </style>
</head>
<body>
<!-- Header com menu hambúrguer -->
<div class="header-nav">
    <?php if ($is_professor): ?>
        <a href="professor/home.php" class="btn-voltar">←</a>
    <?php endif; ?>
    <h1><?= $is_professor ? 'Frequência' : 'MTech Escola' ?></h1>
    <button class="menu-toggle" onclick="toggleMenu()">☰</button>
</div>

<!-- Overlay do menu -->
<div class="menu-overlay" id="menuOverlay" onclick="toggleMenu()"></div>

<!-- Menu lateral -->
<div class="menu-lateral" id="menuLateral">
    <div class="menu-header">
        <h2>Menu</h2>
        <button class="menu-close" onclick="toggleMenu()">✕</button>
    </div>
    
    <?php if ($is_professor): ?>
        <a href="professor/home.php" class="menu-item">🏠 Início</a>
        <a href="professor/horario.php" class="menu-item">📅 Meu Horário</a>
        <a href="presenca.php" class="menu-item">📋 Frequência</a>
        <a href="diario.php" class="menu-item">📖 Diário</a>
        <a href="professor/notas.php" class="menu-item">📊 Notas</a>
        <a href="professor/atividades.php" class="menu-item">📝 Atividades</a>
        <a href="professor/login.php" class="menu-item">🚪 Sair</a>
    <?php else: ?>
        <a href="dashboard.php" class="menu-item">🏠 Dashboard</a>
        <a href="presenca.php" class="menu-item">📋 Frequência</a>
        <a href="horarios/professores.php" class="menu-item">👥 Professores</a>
        <a href="horarios/turmas.php" class="menu-item">🎓 Turmas</a>
        <a href="horarios/disciplinas.php" class="menu-item">📚 Disciplinas</a>
        <a href="logout.php" class="menu-item">🚪 Sair</a>
    <?php endif; ?>
</div>

<div class="container">
    <div class="page-header">
        <h1>📋 Frequência</h1>
        <p>Registre a presença dos alunos</p>
    </div>
    
    <?php if ($msg): ?>
        <div class="msg <?= $msg_tipo === 'error' ? 'msg-error' : '' ?>">
            <?= $msg_tipo === 'error' ? '✗' : '✓' ?> <?= htmlspecialchars($msg) ?>
        </div>
    <?php endif; ?>
    
    <form method="post" id="formPresenca">
        <div class="form-section">
            <label for="turma_id">Turma</label>
            <select name="turma_id" id="turma_id" required onchange="this.form.submit()">
                <option value="">Selecione a turma</option>
                <?php foreach ($turmas as $turma): ?>
                    <option value="<?= $turma['id'] ?>" <?= ($turma_id_selecionada == $turma['id']) ? 'selected' : '' ?>><?= htmlspecialchars($turma['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <label for="disciplina_id">Disciplina</label>
            <select name="disciplina_id" id="disciplina_id" required>
                <option value="">Selecione a disciplina</option>
                <?php 
                $disciplina_id_selecionada = $_GET['disciplina_id'] ?? $_POST['disciplina_id'] ?? null;
                foreach ($disciplinas as $disc): 
                ?>
                    <option value="<?= $disc['id'] ?>" <?= ($disciplina_id_selecionada == $disc['id']) ? 'selected' : '' ?>><?= htmlspecialchars($disc['nome']) ?></option>
                <?php endforeach; ?>
            </select>
            
            <label for="aula_data">Data da Aula</label>
            <input type="date" name="aula_data" id="aula_data" required value="<?= htmlspecialchars($_GET['aula_data'] ?? $_POST['aula_data'] ?? date('Y-m-d')) ?>">
        </div>
        
        <?php if ($alunos): ?>
        
        <div class="quick-actions">
            <button type="button" class="quick-btn" onclick="marcarTodos('presente')">✓ Todos Presentes</button>
            <button type="button" class="quick-btn" onclick="marcarTodos('ausente')">✗ Todos Ausentes</button>
        </div>
        
        <div class="summary">
            <div class="summary-item">
                <div class="summary-value presentes" id="countPresentes">0</div>
                <div class="summary-label">Presentes</div>
            </div>
            <div class="summary-item">
                <div class="summary-value ausentes" id="countAusentes">0</div>
                <div class="summary-label">Ausentes</div>
            </div>
            <div class="summary-item">
                <div class="summary-value" id="countTotal"><?= count($alunos) ?></div>
                <div class="summary-label">Total</div>
            </div>
        </div>
        
        <div class="alunos-list" id="alunosList">
            <?php foreach ($alunos as $aluno): ?>
            <div class="aluno-card" data-aluno-id="<?= $aluno['id'] ?>">
                <div class="aluno-nome"><?= htmlspecialchars($aluno['nome']) ?></div>
                <div class="presenca-toggle">
                    <input type="hidden" name="presenca[<?= $aluno['id'] ?>]" value="presente" id="status_<?= $aluno['id'] ?>">
                    <button type="button" class="toggle-btn presente" onclick="togglePresenca(<?= $aluno['id'] ?>, 'presente')">P</button>
                    <button type="button" class="toggle-btn" onclick="togglePresenca(<?= $aluno['id'] ?>, 'ausente')">A</button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div class="btn-container">
            <button type="submit" name="registrar_presenca" class="btn">💾 Salvar Frequência</button>
        </div>
        
        <?php else: ?>
        <div class="loading">
            Selecione uma turma para visualizar os alunos
        </div>
        <?php endif; ?>
    </form>
</div>

<script>
function togglePresenca(alunoId, status) {
    const card = document.querySelector(`[data-aluno-id="${alunoId}"]`);
    const input = document.getElementById(`status_${alunoId}`);
    const btns = card.querySelectorAll('.toggle-btn');
    
    // Atualizar valor
    input.value = status;
    
    // Atualizar visual dos botões
    btns.forEach(btn => btn.classList.remove('presente', 'ausente'));
    if (status === 'presente') {
        btns[0].classList.add('presente');
        card.classList.remove('ausente');
        card.classList.add('presente');
    } else {
        btns[1].classList.add('ausente');
        card.classList.remove('presente');
        card.classList.add('ausente');
    }
    
    atualizarContadores();
}

function marcarTodos(status) {
    const alunos = document.querySelectorAll('[data-aluno-id]');
    alunos.forEach(card => {
        const alunoId = card.getAttribute('data-aluno-id');
        togglePresenca(alunoId, status);
    });
}

function atualizarContadores() {
    const presentes = document.querySelectorAll('.aluno-card.presente').length;
    const ausentes = document.querySelectorAll('.aluno-card.ausente').length;
    const total = document.querySelectorAll('.aluno-card').length;
    
    document.getElementById('countPresentes').textContent = presentes;
    document.getElementById('countAusentes').textContent = ausentes;
    document.getElementById('countTotal').textContent = total;
}

// Inicializar contadores ao carregar
document.addEventListener('DOMContentLoaded', function() {
    // Marcar todos como presentes por padrão
    const alunos = document.querySelectorAll('[data-aluno-id]');
    alunos.forEach(card => {
        const alunoId = card.getAttribute('data-aluno-id');
        card.classList.add('presente');
        card.querySelector('.toggle-btn').classList.add('presente');
    });
    atualizarContadores();
});

// Controle do menu hambúrguer
function toggleMenu() {
    const menu = document.getElementById('menuLateral');
    const overlay = document.getElementById('menuOverlay');
    menu.classList.toggle('ativo');
    overlay.classList.toggle('ativo');
}
</script>
</body>
</html>
