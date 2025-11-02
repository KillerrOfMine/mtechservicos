<?php
require_once 'db_connect_horarios.php';

if (!isset($_GET['id'])) {
    echo '<p>Professor não encontrado.</p>';
    echo '<a href="professores.php" class="btn">Voltar</a>';
    exit;
}

$id = $_GET['id'];

// Busca dados do professor
$sql = "SELECT nome, email, ativo FROM professores WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$professor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$professor) {
    echo '<p>Professor não encontrado.</p>';
    echo '<a href="professores.php" class="btn">Voltar</a>';
    exit;
}

// Busca disciplinas
$disciplinas = $conn->query("SELECT id, nome FROM disciplinas ORDER BY nome");

// Busca disciplinas do professor
$sql = "SELECT disciplina_id FROM professores_disciplinas WHERE professor_id = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$disciplinas_prof = $stmt->fetchAll(PDO::FETCH_COLUMN);

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $email = $_POST['email'];
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $disciplinas_ids = isset($_POST['disciplinas']) ? $_POST['disciplinas'] : [];
    $nova_senha = $_POST['nova_senha'] ?? '';
    
    if ($nova_senha) {
        $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $sql = "UPDATE professores SET nome = ?, email = ?, ativo = ?, senha = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $ok = $stmt->execute([$nome, $email, $ativo, $senha_hash, $id]);
    } else {
        $sql = "UPDATE professores SET nome = ?, email = ?, ativo = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $ok = $stmt->execute([$nome, $email, $ativo, $id]);
    }
    
    if ($ok) {
        // Atualiza disciplinas
        $conn->prepare("DELETE FROM professores_disciplinas WHERE professor_id = ?")->execute([$id]);
        foreach ($disciplinas_ids as $disc_id) {
            $conn->prepare("INSERT INTO professores_disciplinas (professor_id, disciplina_id) VALUES (?, ?)")->execute([$id, $disc_id]);
        }
        $msg = 'Professor atualizado com sucesso!';
        
        // Recarrega dados
        $stmt = $conn->prepare("SELECT nome, email, ativo FROM professores WHERE id = ?");
        $stmt->execute([$id]);
        $professor = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $conn->prepare("SELECT disciplina_id FROM professores_disciplinas WHERE professor_id = ?");
        $stmt->execute([$id]);
        $disciplinas_prof = $stmt->fetchAll(PDO::FETCH_COLUMN);
    } else {
        $msg = 'Erro ao atualizar professor.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Editar Professor - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; padding: 20px; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 600px; margin: 0 auto; }
        h1 { font-size: 2em; font-weight: 700; letter-spacing: 2px; margin-bottom: 24px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .msg { background: #00c3ff; color: #222; padding: 12px; border-radius: 8px; margin-bottom: 16px; font-weight: 700; text-align: center; }
        .msg.erro { background: #ff3366; color: #fff; }
        label { display: block; margin-top: 16px; margin-bottom: 8px; font-weight: 600; }
        input[type="text"], input[type="email"], input[type="password"] { 
            width: 100%; 
            padding: 12px; 
            border-radius: 8px; 
            border: none; 
            font-size: 1em;
            box-sizing: border-box;
        }
        .disciplinas-list { 
            margin-top: 16px; 
            margin-bottom: 16px;
            padding: 16px;
            background: rgba(0, 195, 255, 0.1);
            border-radius: 8px;
        }
        .disciplinas-list > label:first-child { 
            display: block; 
            font-weight: 700;
            font-size: 1.1em;
            margin-bottom: 12px;
            color: #ffff1c;
        }
        .disc-item { 
            display: inline-block; 
            margin-right: 16px;
            margin-bottom: 8px;
            background: rgba(255, 255, 255, 0.1);
            padding: 6px 12px;
            border-radius: 6px;
        }
        .disc-item input[type="checkbox"] {
            margin-right: 6px;
        }
        .checkbox-ativo {
            margin-top: 16px;
            padding: 12px;
            background: rgba(0, 195, 255, 0.1);
            border-radius: 8px;
        }
        .checkbox-ativo input[type="checkbox"] {
            margin-right: 8px;
        }
        .btn { 
            background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); 
            color: #222; 
            font-weight: 700; 
            border: none; 
            border-radius: 12px; 
            padding: 12px 32px; 
            font-size: 1em; 
            cursor: pointer; 
            box-shadow: 0 2px 8px #0005; 
            margin-top: 24px;
            margin-right: 8px; 
            text-decoration: none; 
            display: inline-block; 
        }
        .btn:hover { transform: scale(1.07); box-shadow: 0 4px 16px #00c3ff55; }
        .btn-cancelar {
            background: linear-gradient(90deg, #ff3366 40%, #ff6633 100%);
            color: #fff;
        }
        .senha-info {
            font-size: 0.9em;
            color: #ffff1c;
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Editar Professor</h1>
        
        <?php if ($msg): ?>
            <div class="msg <?php echo strpos($msg, 'Erro') !== false ? 'erro' : ''; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>
        
        <form method="post">
            <label for="nome">Nome do Professor:</label>
            <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($professor['nome']); ?>" required>
            
            <label for="email">E-mail:</label>
            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($professor['email']); ?>" required>
            
            <label for="nova_senha">Redefinir Senha:</label>
            <input type="password" id="nova_senha" name="nova_senha" placeholder="Digite a nova senha (deixe em branco para manter a atual)">
            <div class="senha-info">⚠️ Deixe em branco para não alterar a senha</div>
            
            <div class="disciplinas-list">
                <label>Disciplinas:</label>
                <?php 
                $disciplinas->execute(); // Reset do cursor
                while ($d = $disciplinas->fetch(PDO::FETCH_ASSOC)) {
                    $checked = in_array($d['id'], $disciplinas_prof) ? 'checked' : '';
                    echo '<label class="disc-item"><input type="checkbox" name="disciplinas[]" value="' . $d['id'] . '" ' . $checked . '> ' . htmlspecialchars($d['nome']) . '</label>';
                } 
                ?>
            </div>
            
            <div class="checkbox-ativo">
                <label>
                    <input type="checkbox" name="ativo" <?php if($professor['ativo']) echo 'checked'; ?>>
                    Professor Ativo
                </label>
            </div>
            
            <button type="submit" class="btn">💾 Salvar Alterações</button>
            <a href="professores.php" class="btn btn-cancelar">❌ Cancelar</a>
        </form>
    </div>
</body>
</html>
