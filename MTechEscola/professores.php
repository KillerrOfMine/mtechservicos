<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Gerenciamento de Professores - MTech Escola</title>
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
<?php
session_start();
require_once 'db_connect_horarios.php';
if (!isset($_SESSION['usuario_id'])) {
    header('Location: login.php');
    exit;
}
?>
    <div class="container" style="padding-top:80px;">
    <?php include 'includes/header.php'; ?>
    <?php
    // Contador de professores ativos
    $cont_prof_ativos = $conn->query("SELECT COUNT(*) FROM professores WHERE ativo = true")->fetchColumn();
    ?>
    <h1>Gerenciamento de Professores</h1>
    <div style="margin-top:8px; margin-bottom:16px; font-size:1em; color:#ffff1c; font-weight:700; text-align:left;">
        Ativos: <?php echo $cont_prof_ativos; ?>
    </div>
        <div class="filter">
            <form method="get">
                <input type="text" name="busca_nome" placeholder="Buscar por nome do professor" style="padding:8px; border-radius:8px; border:none; width:220px;">
                <button type="submit" class="btn">Buscar</button>
                <a href="cadastrar_professor.php" class="btn">Cadastrar Novo Professor</a>
            </form>
        </div>
        <table>
            <tr>
                <th>Nome</th>
                <th>E-mail</th>
                <th>Status</th>
                <th>Disciplinas</th>
                <th>Ações</th>
            </tr>
            <?php
            require_once 'db_connect_horarios.php';
            $busca = isset($_GET['busca_nome']) ? $_GET['busca_nome'] : '';
            $sql = "SELECT id, nome, email, ativo FROM professores WHERE nome ILIKE ? ORDER BY nome";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['%' . $busca . '%']);
            $tem_professor = false;
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $tem_professor = true;
                $sql_disc = "SELECT d.nome FROM professores_disciplinas pd JOIN disciplinas d ON pd.disciplina_id = d.id WHERE pd.professor_id = ? ORDER BY d.nome";
                $stmt_disc = $conn->prepare($sql_disc);
                $stmt_disc->execute([$row['id']]);
                $disciplinas = $stmt_disc->fetchAll(PDO::FETCH_COLUMN);
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
                echo '<td>' . htmlspecialchars($row['email']) . '</td>';
                echo '<td>' . ($row['ativo'] ? 'Ativo' : 'Inativo') . '</td>';
                echo '<td>' . implode(', ', array_map('htmlspecialchars', $disciplinas)) . '</td>';
                echo '<td>';
                echo '<a href="editar_professor.php?id=' . $row['id'] . '" class="btn">Editar</a> ';
                echo '<a href="excluir_professor.php?id=' . $row['id'] . '" class="btn" onclick="return confirm(\'Tem certeza que deseja excluir este professor?\');">Excluir</a>';
                echo '</td>';
                echo '</tr>';
            }
            if (!$tem_professor) {
                echo '<tr><td colspan="5" style="text-align:center; color:#ff3c3c; font-weight:700;">Nenhum professor encontrado.</td></tr>';
            }
            ?>
        </table>
    </div>
</body>
</html>
