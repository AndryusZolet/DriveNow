<?php
require_once '../includes/auth.php';

// Verificar autenticação do usuário
verificarAutenticacao();

if (!usuarioPodeReservar()) {
    header('Location: ../perfil/editar.php');
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Complete seu cadastro e aguarde a aprovação da CNH.'
    ];
    exit();
}

$usuario = getUsuario();

global $pdo;

// Verificar se a coluna 'disponivel' existe
$columnExists = false;
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM veiculo LIKE 'disponivel'");
    $columnExists = ($stmt->rowCount() > 0);
} catch (PDOException $e) {
    // Coluna não existe ou erro na consulta
    $columnExists = false;
}

// Construa a consulta SQL condicionalmente
$stmt = $pdo->prepare(
    "SELECT v.*, CONCAT(u.primeiro_nome, ' ', u.segundo_nome) AS nome_proprietario,
            l.nome_local, c.cidade_nome, e.sigla
     FROM veiculo v 
     LEFT JOIN dono d ON v.dono_id = d.id
     LEFT JOIN conta_usuario u ON d.conta_usuario_id = u.id
     LEFT JOIN local l ON v.local_id = l.id
     LEFT JOIN cidade c ON l.cidade_id = c.id
     LEFT JOIN estado e ON c.estado_id = e.id
     WHERE v.disponivel = 1
     AND v.id NOT IN (
         SELECT veiculo_id FROM reserva 
         WHERE status != 'rejeitada' AND status != 'cancelada' AND status != 'finalizada'
         AND ((CURRENT_DATE() BETWEEN reserva_data AND devolucao_data) 
             OR (reserva_data > CURRENT_DATE()))
     )"
);
$stmt->execute();
$veiculos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Veículos Disponíveis - DriveNow</title>
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

        option {
            background-color: #1e293b !important;
            color: white !important;
        }
        
        .card-shine {
            position: relative;
            overflow: hidden;
        }
        
        .card-shine::after {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(
                to bottom right,
                rgba(255, 255, 255, 0) 0%,
                rgba(255, 255, 255, 0.1) 50%,
                rgba(255, 255, 255, 0) 100%
            );
            transform: rotate(30deg);
            transition: transform 0.5s;
            pointer-events: none;
        }
        
        .card-shine:hover::after {
            transform: rotate(30deg) translate(50%, 50%);
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-purple-950 text-white p-4 md:p-8 overflow-x-hidden">

    <div class="fixed top-0 right-0 w-96 h-96 rounded-full bg-indigo-700 opacity-10 blur-3xl -z-10 animate-pulse animate-pulse-15s"></div>
    <div class="fixed bottom-0 left-0 w-80 h-80 rounded-full bg-purple-700 opacity-10 blur-3xl -z-10 animate-pulse animate-pulse-20s"></div>
    <div class="fixed top-1/3 left-1/4 w-64 h-64 rounded-full bg-slate-700 opacity-5 blur-3xl -z-10 animate-pulse animate-pulse-25s"></div>

    <header class="backdrop-blur-md bg-white/5 border subtle-border rounded-2xl mb-8 shadow-lg overflow-hidden">
        <div class="container mx-auto px-4 py-3">
            <div class="flex justify-between items-center">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-white mr-8">DriveNow</h1>
                    <nav class="hidden md:flex space-x-6">
                        <a href="../index.php" class="text-white/80 hover:text-white transition-colors">Home</a>
                        <a href="../vboard.php" class="text-white/80 hover:text-white transition-colors">Dashboard</a>
                    </nav>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-white hidden md:inline"><?= htmlspecialchars($usuario['primeiro_nome']) ?></span>
                    <div class="relative h-8 w-8 transition-transform hover:scale-110 rounded-full flex items-center justify-center bg-indigo-500 text-white overflow-hidden">
                        <?php if (isset($usuario['foto_perfil']) && !empty($usuario['foto_perfil'])): ?>
                            <img src="<?= htmlspecialchars($usuario['foto_perfil']) ?>" alt="Foto de Perfil" class="h-full w-full object-cover">
                        <?php else: ?>
                            <img src="https://api.dicebear.com/7.x/initials/svg?seed=<?= urlencode($usuario['primeiro_nome']) ?>&backgroundColor=818cf8&textColor=ffffff&fontSize=40" alt="Usuário" class="h-full w-full object-cover">
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <main class="container mx-auto">
        <div class="flex justify-between items-center mb-8 px-4">
            <div>
                <h2 class="text-3xl md:text-4xl font-bold text-white">
                    Veículos Disponíveis
                </h2>
                <p class="text-white/70 mt-2">Encontre o veículo perfeito para sua próxima viagem</p>
            </p>
            </div>
            <div class="flex gap-4">
                <a href="pesquisa_avancada.php" class="border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                        <circle cx="11" cy="11" r="8"/>
                            <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <span>Pesquisa Avaçada</span>
                </a>
                <a href="../vboard.php" class="border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5 mr-2">
                        <path d="m12 19-7-7 7-7"></path>
                        <path d="M19 12H5"></path>
                    </svg>
                    <span>Voltar</span>
                </a>
            </div>
        </div>
        
        <?php if (empty($veiculos)): ?>
            <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl p-8 shadow-lg transition-all hover:shadow-xl hover:bg-white/10 mx-4">
                <div class="text-center py-8">
                    <div class="p-6 rounded-full bg-cyan-500/20 text-white border border-cyan-400/30 inline-flex mb-6">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-10 w-10">
                            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                            <path d="M7 17h10"/>
                            <circle cx="7" cy="17" r="2"/>
                            <path d="M17 17h2"/>
                            <circle cx="17" cy="17" r="2"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-4">Nenhum veículo disponível</h3>
                    <p class="text-white/70 mb-6">No momento não há veículos disponíveis para aluguel. Tente novamente mais tarde.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 px-4">
                <?php foreach ($veiculos as $veiculo): ?>
                    <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl shadow-lg transition-all hover:shadow-xl hover:bg-white/10 overflow-hidden card-shine flex flex-col h-full">
                        <div class="bg-gradient-to-r from-indigo-500/30 to-purple-500/30 p-8 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-20 w-20 text-white">
                                <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9C18.7 10.6 16 10 16 10s-1.3-1.4-2.2-2.3c-.5-.4-1.1-.7-1.8-.7H5c-.6 0-1.1.4-1.4.9l-1.4 2.9A3.7 3.7 0 0 0 2 12v4c0 .6.4 1 1 1h2"/>
                                <path d="M7 17h10"/>
                                <circle cx="7" cy="17" r="2"/>
                                <path d="M17 17h2"/>
                                <circle cx="17" cy="17" r="2"/>
                            </svg>
                        </div>
                        
                        <div class="p-6 flex-grow">
                            <h3 class="text-xl font-bold text-white mb-2">
                                <?= htmlspecialchars($veiculo['veiculo_marca']) ?> <?= htmlspecialchars($veiculo['veiculo_modelo']) ?>
                            </h3>
                            
                            <div class="flex gap-4 text-white/70 text-sm mb-4">
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-1">
                                        <rect x="2" y="4" width="20" height="16" rx="2"></rect>
                                        <path d="M8 2v4"></path>
                                        <path d="M16 2v4"></path>
                                        <path d="M2 10h20"></path>
                                    </svg>
                                    <?= htmlspecialchars($veiculo['veiculo_ano']) ?>
                                </div>
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 mr-1">
                                        <path d="M12 12m-8 0a8 8 0 1 0 16 0a8 8 0 1 0 -16 0"></path>
                                        <path d="M12 12m-3 0a3 3 0 1 0 6 0a3 3 0 1 0 -6 0"></path>
                                    </svg>
                                    <?= number_format($veiculo['veiculo_km'], 0, ',', '.') ?> km
                                </div>
                            </div>
                            
                            <div class="space-y-3 mb-6">
                                <div class="flex items-center text-white">
                                    <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                            <circle cx="6" cy="12" r="3"></circle>
                                            <path d="M6 9v6"></path>
                                            <circle cx="18" cy="12" r="3"></circle>
                                            <path d="M18 9v6"></path>
                                            <path d="M3 12h3"></path>
                                            <path d="M15 12h3"></path>
                                            <path d="M9 6v12"></path>
                                        </svg>
                                    </div>
                                    <span><?= htmlspecialchars($veiculo['veiculo_cambio']) ?></span>
                                </div>
                                
                                <div class="flex items-center text-white">
                                    <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                            <path d="M4 20V10a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10"></path>
                                            <path d="M12 12v8"></path>
                                            <path d="M12 12L8 8"></path>
                                            <path d="M12 12l4-4"></path>
                                        </svg>
                                    </div>
                                    <span><?= htmlspecialchars($veiculo['veiculo_combustivel']) ?></span>
                                </div>
                                
                                <div class="flex items-center text-white">
                                    <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                            <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"></path>
                                            <circle cx="12" cy="10" r="3"></circle>
                                        </svg>
                                    </div>
                                    <span><?= htmlspecialchars($veiculo['nome_local'] ?? 'Local não informado') ?>
                                        <?php if (isset($veiculo['cidade_nome'])): ?>
                                            <span class="text-white/60 text-xs">(<?= htmlspecialchars($veiculo['cidade_nome']) ?>-<?= htmlspecialchars($veiculo['sigla']) ?>)</span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                
                                <div class="flex items-center text-white">
                                    <div class="w-8 h-8 rounded-full bg-white/10 flex items-center justify-center mr-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4">
                                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                    </div>
                                    <span><?= htmlspecialchars($veiculo['nome_proprietario']) ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6 bg-gradient-to-r from-black/20 to-transparent border-t border-white/10">                            <div class="flex justify-between items-center">
                                <div class="text-xl font-bold text-white">
                                    R$ <?= number_format($veiculo['preco_diaria'], 2, ',', '.') ?>
                                    <span class="text-sm font-normal text-white/70">/dia</span>
                                </div>
                                <a href="detalhes_veiculo.php?id=<?= $veiculo['id'] ?>" class="bg-indigo-500 hover:bg-indigo-600 text-white rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 text-sm font-medium shadow-md hover:shadow-lg flex items-center">
                                    Ver Detalhes
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-4 w-4 ml-2">
                                        <path d="M5 12h14"></path>
                                        <path d="m12 5 7 7-7 7"></path>
                                    </svg>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>    <footer class="container mx-auto mt-16 px-4 pb-8 text-center text-white/60 text-sm">
        <p>© <script>document.write(new Date().getFullYear())</script> DriveNow. Todos os direitos reservados.</p>
    </footer>

    <script src="./assets/notifications.js"></script>
</body>
</html>