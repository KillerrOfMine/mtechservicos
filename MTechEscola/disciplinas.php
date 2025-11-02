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
	<title>Gerenciamento de Disciplinas - MTech Escola</title>
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
		<h1>Gerenciamento de Disciplinas</h1>
		<div class="filter">
			<form method="get">
				<input type="text" name="busca_nome" placeholder="Buscar por nome da disciplina" style="padding:8px; border-radius:8px; border:none; width:220px;">
				<button type="submit" class="btn">Buscar</button>
				<a href="cadastrar_disciplina.php" class="btn">Cadastrar Nova Disciplina</a>
			</form>
		</div>
		<table>
			<tr>
				<th>Nome da Disciplina</th>
				<th>Ações</th>
			</tr>
			<?php
			require_once 'db_connect_horarios.php';
			$busca = isset($_GET['busca_nome']) ? $_GET['busca_nome'] : '';
			$sql = "SELECT id, nome FROM disciplinas WHERE nome ILIKE ? ORDER BY nome";
			$stmt = $conn->prepare($sql);
			$stmt->execute(['%' . $busca . '%']);
			while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
				echo '<tr>';
				echo '<td>' . htmlspecialchars($row['nome']) . '</td>';
				echo '<td><a href="editar_disciplina.php?id=' . $row['id'] . '" class="btn">Editar</a> <a href="#" class="btn">Excluir</a></td>';
				echo '</tr>';
			}
			?>
		</table>
	</div>
</body>
</html>