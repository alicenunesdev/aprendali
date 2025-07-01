<?php
session_start();
include 'db.php';
require_once 'config.php';
require_once 'auth.php';

// Se já estiver logado, redirecionar
if ($auth->isLoggedIn()) {
    redirect('index.php');
}

$erro = '';
$sucesso = '';

// Processar registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = sanitize($_POST['nome'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';
    
    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $erro = 'Todos os campos são obrigatórios.';
    } elseif ($senha !== $confirmar_senha) {
        $erro = 'As senhas não conferem.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } else {
        $resultado = $auth->register($nome, $email, $senha);
        
        if ($resultado['sucesso']) {
            $sucesso = $resultado['mensagem'] . ' Você pode fazer login agora.';
            // Limpar campos
            $nome = $email = '';
        } else {
            $erro = $resultado['mensagem'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - AprendaAli</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h1 {
            color: #2c3e50;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .register-header p {
            color: #6c757d;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #495057;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .form-group input {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #ffffff;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .register-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .register-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }

        .register-button:active {
            transform: translateY(0);
        }

        .register-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .register-footer {
            text-align: center;
            margin-top: 20px;
        }

        .register-footer a {
            color: #667eea;
            text-decoration: none;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .register-footer a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        /* Indicador de força da senha */
        .password-strength {
            margin-top: 5px;
                        font-size: 0.85rem;
            font-weight: 500;
            color: #495057;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Cadastro</h1>
            <p>Crie sua conta para começar a usar o AprendaAli</p>
        </div>

        <?php if (!empty($erro)): ?>
            <div class="alert alert-error"><?php echo $erro; ?></div>
        <?php elseif (!empty($sucesso)): ?>
            <div class="alert alert-success"><?php echo $sucesso; ?></div>
        <?php endif; ?>

        <form method="post" action="">
            <div class="form-group">
                <label for="nome">Nome Completo</label>
                <input type="text" name="nome" id="nome" required value="<?php echo htmlspecialchars($nome ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" name="email" id="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
            </div>

            <div class="form-group">
                <label for="senha">Senha</label>
                <input type="password" name="senha" id="senha" required oninput="verificarForcaSenha(this.value)">
                <div class="password-strength" id="password-strength"></div>
            </div>

            <div class="form-group">
                <label for="confirmar_senha">Confirmar Senha</label>
                <input type="password" name="confirmar_senha" id="confirmar_senha" required>
            </div>

            <button type="submit" class="register-button">Cadastrar</button>
        </form>

        <div class="register-footer">
            Já possui uma conta? <a href="login.php">Entrar</a>
        </div>
    </div>

    <script>
        function verificarForcaSenha(senha) {
            const força = document.getElementById('password-strength');
            let nivel = 0;

            if (senha.length >= 6) nivel++;
            if (/[A-Z]/.test(senha)) nivel++;
            if (/[0-9]/.test(senha)) nivel++;
            if (/[^A-Za-z0-9]/.test(senha)) nivel++;

            let texto = '';
            let cor = '';

            switch (nivel) {
                case 0:
                case 1:
                    texto = 'Fraca';
                    cor = '#dc3545';
                    break;
                case 2:
                    texto = 'Média';
                    cor = '#ffc107';
                    break;
                case 3:
                    texto = 'Boa';
                    cor = '#28a745';
                    break;
                case 4:
                    texto = 'Forte';
                    cor = '#007bff';
                    break;
            }

            força.textContent = 'Força da senha: ' + texto;
            força.style.color = cor;
        }
    </script>
</body>
</html>
