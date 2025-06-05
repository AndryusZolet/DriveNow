<?php
require_once 'includes/auth.php';

$erro = '';
$sucesso = '';
$mostrarFormRedefinicao = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verifica se é o formulário de e-mail ou de redefinição de senha
    if (isset($_POST['email'])) {
        $email = trim($_POST['email']);
        
        // Validações básicas
        if (empty($email)) {
            $erro = 'O e-mail é obrigatório.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $erro = 'E-mail inválido.';
        } else {
            // Aqui você implementaria a lógica para enviar o e-mail de recuperação
            // Por exemplo: enviarEmailRecuperacao($email);
            
            // Simulamos que encontrou o e-mail
            $sucesso = 'Se o e-mail estiver cadastrado, você receberá um link para redefinir sua senha.';
            $mostrarFormRedefinicao = true; // Mostra o formulário de redefinição
            $_SESSION['email_redefinicao'] = $email; // Armazena o email na sessão
        }
    } 
    // Processa o formulário de redefinição de senha
    elseif (isset($_POST['nova_senha'])) {
        $novaSenha = $_POST['nova_senha'];
        $confirmarSenha = $_POST['confirmar_senha'];
        $email = $_SESSION['email_redefinicao'] ?? '';
        
        if (empty($novaSenha)) {
            $erro = 'A nova senha é obrigatória.';
        } elseif ($novaSenha !== $confirmarSenha) {
            $erro = 'As senhas não coincidem.';
        } elseif (mb_strlen($novaSenha) < 6) {
            $erro = 'A senha deve ter pelo menos 6 caracteres.';
        } else {
            // Aqui você implementaria a lógica para atualizar a senha no banco de dados
            // Por exemplo: atualizarSenha($email, $novaSenha);
            
            $sucesso = 'Senha redefinida com sucesso! Você já pode fazer login com sua nova senha.';
            $mostrarFormRedefinicao = false;
            unset($_SESSION['email_redefinicao']);
            
            // Redireciona para login após 3 segundos
            header("Refresh: 3; url=login.php");
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - DriveNow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: sans-serif;
        }
        .animate-pulse-15s { animation-duration: 15s; }
        .animate-pulse-20s { animation-duration: 20s; }
        .animate-pulse-25s { animation-duration: 25s; }
        .subtle-border {
            border-color: rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-purple-950 text-white p-4 md:p-8 overflow-x-hidden flex items-center justify-center">

    <!-- Efeitos de fundo -->
    <div class="fixed top-0 right-0 w-96 h-96 rounded-full bg-indigo-700 opacity-10 blur-3xl -z-10 animate-pulse animate-pulse-15s"></div>
    <div class="fixed bottom-0 left-0 w-80 h-80 rounded-full bg-purple-700 opacity-10 blur-3xl -z-10 animate-pulse animate-pulse-20s"></div>
    <div class="fixed top-1/3 left-1/4 w-64 h-64 rounded-full bg-slate-700 opacity-5 blur-3xl -z-10 animate-pulse animate-pulse-25s"></div>

    <!-- Botão de voltar -->
    <div class="absolute top-4 left-4 md:top-8 md:left-8">
        <a href="login.php" class="flex items-center group text-white/70 hover:text-white transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2 group-hover:-translate-x-1 transition-transform">
                <path d="m12 19-7-7 7-7"></path>
                <path d="M19 12H5"></path>
            </svg>
            <span>Voltar ao Login</span>
        </a>
    </div>

    <div class="w-full max-w-md">
        <!-- Logo e cabeçalho -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">DriveNow</h1>
            <p class="text-white/70">Recuperação de senha</p>
        </div>

        <!-- Formulário de recuperação -->
        <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-8 shadow-xl">
            <?php if ($erro): ?>
                <div class="mb-6 bg-red-500/20 border border-red-400/30 text-white px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($erro) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($sucesso): ?>
                <div class="mb-6 bg-green-500/20 border border-green-400/30 text-white px-4 py-3 rounded-xl">
                    <?= htmlspecialchars($sucesso) ?>
                </div>
            <?php endif; ?>

            <?php if (!$mostrarFormRedefinicao && !isset($_POST['nova_senha'])): ?>
                <p class="text-white/70 mb-6">Digite seu e-mail cadastrado para receber um link de recuperação.</p>
                
                <form method="POST" class="space-y-6">
                    <div class="relative">
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            class="block w-full px-4 py-3 text-white bg-white/5 border border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 peer" 
                            placeholder=" " 
                            required 
                            value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                        >
                        <label 
                            for="email" 
                            class="absolute left-3 top-3 text-white/70 transition-all duration-200 -translate-y-0 peer-focus:-translate-y-6 peer-focus:scale-75 peer-focus:text-indigo-300 peer-[:not(:placeholder-shown)]:-translate-y-6 peer-[:not(:placeholder-shown)]:scale-75 peer-[:not(:placeholder-shown)]:text-indigo-300"
                        >
                            Email
                        </label>
                        <div class="absolute right-3 top-3 text-white/50">
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"></path>
                            </svg>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-4 py-3 shadow-md hover:shadow-lg">
                        Enviar Link de Recuperação
                    </button>
                </form>
            <?php elseif ($mostrarFormRedefinicao): ?>
                <form method="POST" class="space-y-6">
                    <div class="relative">
                        <input 
                            type="password" 
                            id="nova_senha" 
                            name="nova_senha" 
                            class="block w-full px-4 py-3 text-white bg-white/5 border border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 peer" 
                            placeholder=" " 
                            required
                        >
                        <label 
                            for="nova_senha" 
                            class="absolute left-3 top-3 text-white/70 transition-all duration-200 -translate-y-0 peer-focus:-translate-y-6 peer-focus:scale-75 peer-focus:text-indigo-300 peer-[:not(:placeholder-shown)]:-translate-y-6 peer-[:not(:placeholder-shown)]:scale-75 peer-[:not(:placeholder-shown)]:text-indigo-300"
                        >
                            Nova Senha
                        </label>
                        <button 
                            type="button" 
                            class="absolute right-3 top-3 text-white/50 hover:text-white/80 transition-colors"
                            onclick="togglePasswordVisibility('nova_senha')"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="show-password-icon">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hide-password-icon hidden">
                                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                <line x1="2" x2="22" y1="2" y2="22"></line>
                            </svg>
                        </button>
                    </div>

                    <div class="relative">
                        <input 
                            type="password" 
                            id="confirmar_senha" 
                            name="confirmar_senha" 
                            class="block w-full px-4 py-3 text-white bg-white/5 border border-white/10 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 peer" 
                            placeholder=" " 
                            required
                        >
                        <label 
                            for="confirmar_senha" 
                            class="absolute left-3 top-3 text-white/70 transition-all duration-200 -translate-y-0 peer-focus:-translate-y-6 peer-focus:scale-75 peer-focus:text-indigo-300 peer-[:not(:placeholder-shown)]:-translate-y-6 peer-[:not(:placeholder-shown)]:scale-75 peer-[:not(:placeholder-shown)]:text-indigo-300"
                        >
                            Confirmar Nova Senha
                        </label>
                        <button 
                            type="button" 
                            class="absolute right-3 top-3 text-white/50 hover:text-white/80 transition-colors"
                            onclick="togglePasswordVisibility('confirmar_senha')"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="show-password-icon">
                                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="hide-password-icon hidden">
                                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"></path>
                                <path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"></path>
                                <path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"></path>
                                <line x1="2" x2="22" y1="2" y2="22"></line>
                            </svg>
                        </button>
                    </div>

                    <button type="submit" class="w-full bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-4 py-3 shadow-md hover:shadow-lg">
                        Redefinir Senha
                    </button>
                </form>
            <?php endif; ?>

            <div class="text-center text-white/70 pt-4 border-t border-white/10 mt-6">
                <p>Lembrou sua senha? <a href="./login.php" class="text-indigo-300 hover:text-indigo-200">Faça login</a></p>
            </div>
        </div>

        <div class="mt-8 text-center text-white/60 text-sm">
            <p>© <script>document.write(new Date().getFullYear())</script> DriveNow. Todos os direitos reservados.</p>
        </div>
    </div>

    <!-- Script para mostrar/ocultar senha -->
    <script>
        function togglePasswordVisibility(inputId) {
            const passwordInput = document.getElementById(inputId);
            const button = passwordInput.nextElementSibling.nextElementSibling;
            const showIcon = button.querySelector('.show-password-icon');
            const hideIcon = button.querySelector('.hide-password-icon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                showIcon.classList.add('hidden');
                hideIcon.classList.remove('hidden');
            } else {
                passwordInput.type = 'password';
                showIcon.classList.remove('hidden');
                hideIcon.classList.add('hidden');
            }
        }
    </script>
</body>
</html>