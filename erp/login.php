<?php
session_start();
if (isset($_SESSION['usuario_id'])) {
  header('Location: /erp/dashboard.php');
  exit;
}
require_once __DIR__ . '/includes/db_connect.php';

$erro = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = $_POST['usuario'] ?? '';
    $senha = $_POST['senha'] ?? '';
    
    $stmt = $pdo->prepare('SELECT id, senha_hash, role FROM usuarios WHERE usuario = :usuario');
    $stmt->execute([':usuario' => $usuario]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user && password_verify($senha, $user['senha_hash'])) {
  $_SESSION['usuario_id'] = $user['id'];
  $_SESSION['role'] = $user['role'];
        header('Location: /erp/dashboard.php');
        exit;
    } else {
        $erro = 'Usuário ou senha inválidos.';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login ERP</title>
  <style>
    body { background: #1857d8; min-height: 100vh; margin: 0; font-family: 'Segoe UI', Arial, sans-serif; }
    .login-container { display: flex; justify-content: center; align-items: center; min-height: 100vh; }
    .login-card { background: #fff; border-radius: 32px; box-shadow: 0 4px 24px rgba(24,87,216,0.12); padding: 40px 32px; max-width: 400px; width: 100%; text-align: center; margin-right: 48px; }
    .login-card h1 { font-size: 2rem; color: #1857d8; margin-bottom: 24px; }
    .login-card label { display: block; text-align: left; margin-bottom: 8px; color: #1857d8; font-weight: 500; }
    .login-card input { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #dbeafe; margin-bottom: 16px; font-size: 1rem; }
    .login-card button { width: 100%; background: #1857d8; color: #fff; border: none; border-radius: 8px; padding: 12px; font-size: 1rem; font-weight: bold; cursor: pointer; margin-top: 8px; transition: background 0.2s; }
    .login-card button:hover { background: #0f3c9c; }
    .login-info { color: #fff; max-width: 400px; margin-left: 48px; }
    .login-info h2 { font-size: 2rem; margin-bottom: 16px; }
    .login-info p { font-size: 1.1rem; line-height: 1.5; }
    @media (max-width: 900px) {
      .login-container { flex-direction: column; }
      .login-card, .login-info { margin: 0; max-width: 100%; }
      .login-info { margin-top: 32px; }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <form class="login-card" method="post">
      <h1>ERP MTech</h1>
      <?php if ($erro): ?>
        <div style="color:#d00; margin-bottom:16px; font-weight:bold;"> <?= htmlspecialchars($erro) ?> </div>
      <?php endif; ?>
      <label for="usuario">Usuário</label>
      <input type="text" name="usuario" id="usuario" placeholder="Digite seu usuário" required autofocus>
      <label for="senha">Senha</label>
      <input type="password" name="senha" id="senha" placeholder="Digite sua senha" required>
      <button type="submit">Entrar</button>
    </form>
    <div class="login-info">
      <h2>Bem-vindo!</h2>
      <p>Entre com seus dados para acessar o ERP MTech, um sistema completo para gestão de serviços e negócios.</p>
    </div>
  </div>
</body>
</html>
