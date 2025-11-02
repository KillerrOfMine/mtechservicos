<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
require_once 'db_connect_horarios.php';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciamento de Alunos - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
    body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 900px; margin: 40px auto; }
        h1 { font-size: 2em; font-weight: 700; letter-spacing: 2px; margin-bottom: 24px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th, td { padding: 12px; border-bottom: 1px solid #2c5364; text-align: left; }
        th { background: #1a2636; color: #ffff1c; }
        tr:hover { background: #22334a; }
        .btn { background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); color: #222; font-weight: 700; border: none; border-radius: 12px; padding: 8px 24px; font-size: 1em; cursor: pointer; box-shadow: 0 2px 8px #0005; margin-right: 8px; text-decoration: none; display: inline-block; }
        .btn:hover { transform: scale(1.07); box-shadow: 0 4px 16px #00c3ff55; }
        .filter { margin-bottom: 16px; }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container">
    <?php
    // Contador de alunos ativos
    $cont_ativos = $conn->query("SELECT COUNT(*) FROM alunos WHERE ativo = true")->fetchColumn();
    ?>
    <h1>Gerenciamento de Alunos</h1>
    <div style="margin-top:8px; margin-bottom:16px; font-size:1em; color:#ffff1c; font-weight:700; text-align:left;">
        Ativos: <?php echo $cont_ativos; ?>
    </div>
        <div class="filter">
            <?php require_once 'db_connect_horarios.php'; ?>
            <form method="get" style="display:flex; flex-wrap:wrap; gap:12px; align-items:center;">
                <input type="text" name="busca_nome" placeholder="Buscar por nome" style="padding:8px; border-radius:8px; border:none; width:180px;">
                <select name="busca_turma" style="padding:8px; border-radius:8px; border:none;">
                    <option value="">Todas as turmas</option>
                    <?php
                    $turmas = $conn->query("SELECT id, nome FROM turmas ORDER BY nome");
                    if ($turmas) {
                        while ($t = $turmas->fetch(PDO::FETCH_ASSOC)) {
                            $selected = (isset($_GET['busca_turma']) && $_GET['busca_turma'] == $t['id']) ? 'selected' : '';
                            echo '<option value="' . $t['id'] . '" ' . $selected . '>' . htmlspecialchars($t['nome']) . '</option>';
                        }
                    }
                    ?>
                </select>
                <select name="busca_status" style="padding:8px; border-radius:8px; border:none;">
                    <option value="">Todos os status</option>
                    <option value="1" <?php if(isset($_GET['busca_status']) && $_GET['busca_status']==='1') echo 'selected'; ?>>Ativo</option>
                    <option value="0" <?php if(isset($_GET['busca_status']) && $_GET['busca_status']==='0') echo 'selected'; ?>>Inativo</option>
                </select>
                <input type="date" name="busca_nascimento" style="padding:8px; border-radius:8px; border:none;">
                <button type="submit" class="btn">Filtrar</button>
                <a href="cadastrar_aluno.php" class="btn">Cadastrar Novo Aluno</a>
            </form>
        </div>
        <table>
            <tr>
                <th>Nome</th>
                <th>Turma</th>
                <th>Nº Chamada</th>
                <th>Telefone</th>
                <th>E-mail</th>
                <th>Data de Nascimento</th>
                <th>Status</th>
                <th>Ações</th>
            </tr>
            <?php
            require_once 'db_connect_horarios.php';
            $busca = isset($_GET['busca_nome']) ? $_GET['busca_nome'] : '';
            $busca_turma = isset($_GET['busca_turma']) ? $_GET['busca_turma'] : '';
            $busca_status = isset($_GET['busca_status']) ? $_GET['busca_status'] : '';
            $busca_nascimento = isset($_GET['busca_nascimento']) ? $_GET['busca_nascimento'] : '';
            
            // Verificar se coluna existe
            try {
                $sql = "SELECT a.id, a.nome, t.nome AS turma, a.numero_chamada, a.telefone, a.email, a.data_nascimento, a.ativo FROM alunos a LEFT JOIN turmas t ON a.turma_id = t.id WHERE a.nome ILIKE ?";
                $params = ['%' . $busca . '%'];
            } catch (Exception $e) {
                // Se coluna não existe, busca sem ela
                $sql = "SELECT a.id, a.nome, t.nome AS turma, a.telefone, a.email, a.data_nascimento, a.ativo FROM alunos a LEFT JOIN turmas t ON a.turma_id = t.id WHERE a.nome ILIKE ?";
                $params = ['%' . $busca . '%'];
            }
            
            if ($busca_turma !== '') {
                $sql .= " AND a.turma_id = ?";
                $params[] = $busca_turma;
            }
            if ($busca_status !== '') {
                $sql .= " AND a.ativo = ?";
                $params[] = $busca_status;
            }
            if ($busca_nascimento !== '') {
                $sql .= " AND a.data_nascimento = ?";
                $params[] = $busca_nascimento;
            }
            $sql .= " ORDER BY a.nome";
            $stmt = $conn->prepare($sql);
            $stmt->execute($params);
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
                echo '<td>' . htmlspecialchars($row['turma']) . '</td>';
                echo '<td>' . (isset($row['numero_chamada']) && $row['numero_chamada'] ? htmlspecialchars($row['numero_chamada']) : '-') . '</td>';
                echo '<td>' . htmlspecialchars($row['telefone']) . '</td>';
                echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                echo '<td>' . htmlspecialchars($row['data_nascimento']) . '</td>';
                echo '<td>' . ($row['ativo'] ? 'Ativo' : 'Inativo') . '</td>';
                echo '<td><a href="editar_aluno.php?id=' . $row['id'] . '" class="btn">Editar</a> <a href="#" class="btn">Excluir</a></td>';
                echo '</tr>';
            }
            ?>
        </table>
    </div>
</body>
</html>
