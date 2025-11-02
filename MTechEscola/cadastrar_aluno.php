<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Cadastrar Aluno - MTech Escola</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <style>
        body { background: linear-gradient(120deg, #0f2027, #2c5364 80%); min-height: 100vh; margin: 0; font-family: 'Orbitron', Arial, sans-serif; color: #fff; }
        .container { background: rgba(20, 30, 50, 0.85); border-radius: 24px; box-shadow: 0 8px 32px #000a; padding: 32px; max-width: 400px; margin: 40px auto; }
        h1 { font-size: 2em; font-weight: 700; letter-spacing: 2px; margin-bottom: 24px; background: linear-gradient(90deg, #00c3ff, #ffff1c); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        label { display: block; margin-top: 16px; font-weight: 500; }
        input, select { width: 100%; padding: 12px; margin-top: 8px; border-radius: 8px; border: none; font-size: 1em; }
        .btn { background: linear-gradient(90deg, #00c3ff 40%, #ffff1c 100%); color: #222; font-weight: 700; border: none; border-radius: 12px; padding: 12px 32px; font-size: 1.1em; cursor: pointer; box-shadow: 0 2px 8px #0005; margin-top: 24px; text-decoration: none; display: inline-block; }
        .btn:hover { transform: scale(1.07); box-shadow: 0 4px 16px #00c3ff55; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Cadastrar Aluno</h1>
        <form method="post" action="salvar_aluno.php">
            <label for="nome">Nome</label>
            <input type="text" id="nome" name="nome" required>

            <label for="cpf">CPF</label>
            <input type="text" id="cpf" name="cpf">

            <label for="telefone">Telefone</label>
            <input type="text" id="telefone" name="telefone">

            <label for="email">E-mail</label>
            <input type="email" id="email" name="email">

            <label for="data_nascimento">Data de Nascimento</label>
            <input type="date" id="data_nascimento" name="data_nascimento">

            <label for="turma_id">Turma</label>
            <select id="turma_id" name="turma_id">
                <option value="">Selecione</option>
                <?php
                require_once 'db_connect_horarios.php';
                $turmas = $conn->query("SELECT id, nome FROM turmas ORDER BY nome")->fetchAll(PDO::FETCH_ASSOC);
                foreach ($turmas as $turma): ?>
                    <option value="<?= $turma['id'] ?>"><?= htmlspecialchars($turma['nome']) ?></option>
                <?php endforeach; ?>
            </select>

            <label for="numero_chamada">Número da Chamada</label>
            <input type="number" id="numero_chamada" name="numero_chamada" min="1" placeholder="Ex: 1, 2, 3...">

            <button type="submit" class="btn">Salvar</button>
        </form>
    </div>
</body>
</html>
