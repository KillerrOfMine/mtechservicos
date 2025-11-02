<?php
session_start();
if (!isset($_SESSION['usuario_id'])) {
	header('Location: login.php');
	exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
	<meta charset="UTF-8">
	<title>Gerenciamento de Turmas - MTech Escola</title>
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
	<h1>Gerenciamento de Turmas</h1>
	<h2 style="font-size:1.3em; font-weight:500; color:#ffff1c; margin-bottom:18px;">Turmas</h2>
		<div class="filter">
			<form method="get">
				<input type="text" name="busca_nome" placeholder="Buscar por nome da turma" style="padding:8px; border-radius:8px; border:none; width:220px;">
				<button type="submit" class="btn">Buscar</button>
				<a href="cadastrar_turma.php" class="btn">Cadastrar Nova Turma</a>
			</form>
		</div>
		<table>
			<tr>
				   <th>Nome da Turma</th>
				   <th>Qtd. Alunos</th>
				   <th>Ações</th>
			</tr>
			<?php
			require_once 'db_connect_horarios.php';
			$busca = isset($_GET['busca_nome']) ? $_GET['busca_nome'] : '';
			   $sql = "SELECT t.id, t.nome, COUNT(a.id) AS qtd_alunos FROM turmas t LEFT JOIN alunos a ON t.id = a.turma_id WHERE t.nome ILIKE ? GROUP BY t.id, t.nome ORDER BY t.nome";
			   $stmt = $conn->prepare($sql);
			   $stmt->execute(['%' . $busca . '%']);
			   while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				   echo '<tr>';
				   echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
				   echo '<td>' . $row['qtd_alunos'] . '</td>';
				   echo '<td><a href="editar_turma.php?id=' . $row['id'] . '" class="btn">Editar</a> ';
				   echo '<form method="post" style="display:inline;" onsubmit="return confirm(\'Confirma excluir esta turma?\');">
					   <input type="hidden" name="excluir_turma_id" value="' . $row['id'] . '">
					   <button type="submit" class="btn" style="background:linear-gradient(90deg,#ff3c3c 40%,#ffff1c 100%);color:#222;">Excluir</button>
				   </form>';
				   echo '</td>';
				   echo '</tr>';
			   }
			?>
		</table>
	   <?php
	   // Exclusão de turma
	   if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['excluir_turma_id'])) {
		   $id = $_POST['excluir_turma_id'];
		   // Verifica se há alunos vinculados
		   $sql = "SELECT COUNT(*) FROM alunos WHERE turma_id = ?";
		   $stmt = $conn->prepare($sql);
		   $stmt->execute([$id]);
		   $temAlunos = $stmt->fetchColumn();
		   if ($temAlunos > 0) {
			   echo '<div style="color:#ff3c3c; font-weight:700; margin-bottom:16px;">Não é possível excluir: existem alunos vinculados a esta turma.</div>';
		   } else {
			   $sql = "DELETE FROM turmas WHERE id = ?";
			   $stmt = $conn->prepare($sql);
			   if ($stmt->execute([$id])) {
				   echo '<div style="color:#00c3ff; font-weight:700; margin-bottom:16px;">Turma excluída com sucesso!</div>';
				   echo '<meta http-equiv="refresh" content="1;url=turmas.php">';
			   } else {
				   echo '<div style="color:#ff3c3c; font-weight:700; margin-bottom:16px;">Erro ao excluir turma.</div>';
			   }
		   }
	   }
	   ?>
	   </div>
</body>
</html>