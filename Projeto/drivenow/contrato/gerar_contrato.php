<?php
require_once '../includes/auth.php';

// Verificar se o MPDF está disponível
$mpdfDisponivel = file_exists('../vendor/autoload.php');
if ($mpdfDisponivel) {
    require_once '../vendor/autoload.php';
}

// Verificar autenticação
if (!estaLogado()) {
    header('Location: ../login.php');
    exit;
}

$usuario = getUsuario();
global $pdo;

// Verificar se o ID da reserva foi fornecido
if (!isset($_GET['reserva']) || !is_numeric($_GET['reserva'])) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Reserva não especificada.'
    ];
    header('Location: ../reserva/minhas_reservas.php');
    exit;
}

$reservaId = (int)$_GET['reserva'];

// Buscar detalhes completos da reserva
$stmt = $pdo->prepare("
    SELECT r.*, v.veiculo_marca, v.veiculo_modelo, v.veiculo_ano, v.veiculo_placa,
           v.veiculo_km, v.veiculo_cambio, v.veiculo_combustivel, v.veiculo_portas,
           v.veiculo_acentos, v.veiculo_tracao,
           loc.nome_local, loc.endereco, loc.complemento, loc.cep,
           c.cidade_nome, e.estado_nome, e.sigla, 
           d.conta_usuario_id AS proprietario_id,
           proprio.primeiro_nome AS dono_nome, proprio.segundo_nome AS dono_sobrenome,
           proprio.cpf AS dono_cpf, proprio.telefone AS dono_telefone, proprio.e_mail AS dono_email,
           locat.primeiro_nome AS locatario_nome, locat.segundo_nome AS locatario_sobrenome,
           locat.cpf AS locatario_cpf, locat.telefone AS locatario_telefone, locat.e_mail AS locatario_email,
           locat.id AS locatario_id
    FROM reserva r
    INNER JOIN veiculo v ON r.veiculo_id = v.id
    INNER JOIN dono d ON v.dono_id = d.id
    INNER JOIN conta_usuario proprio ON d.conta_usuario_id = proprio.id
    INNER JOIN conta_usuario locat ON r.conta_usuario_id = locat.id
    LEFT JOIN local loc ON v.local_id = loc.id
    LEFT JOIN cidade c ON loc.cidade_id = c.id
    LEFT JOIN estado e ON c.estado_id = e.id
    WHERE r.id = ?
");
$stmt->execute([$reservaId]);
$reserva = $stmt->fetch();

if (!$reserva) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Reserva não encontrada.'
    ];
    header('Location: ../reserva/minhas_reservas.php');
    exit;
}

// Verificar se o usuário tem permissão para acessar esta reserva
if ($reserva['locatario_id'] !== $usuario['id'] && $reserva['proprietario_id'] !== $usuario['id']) {
    $_SESSION['notification'] = [
        'type' => 'error',
        'message' => 'Você não tem permissão para acessar esta reserva.'
    ];
    header('Location: ../reserva/minhas_reservas.php');
    exit;
}

// Apenas exibe o contrato na página e dá opção de baixar como PDF
$visualizarPdf = isset($_GET['pdf']) && $_GET['pdf'] === '1';

// Formatar valores para o contrato
$valorDiaria = number_format($reserva['diaria_valor'], 2, ',', '.');
$taxaUso = number_format($reserva['taxas_de_uso'], 2, ',', '.');
$taxaLimpeza = number_format($reserva['taxas_de_limpeza'], 2, ',', '.');
$valorTotal = number_format($reserva['valor_total'], 2, ',', '.');

// Calcular o número de dias
$dataInicio = new DateTime($reserva['reserva_data']);
$dataFim = new DateTime($reserva['devolucao_data']);
$dias = $dataInicio->diff($dataFim)->days;

// Formatar datas
$dataInicioFormatada = $dataInicio->format('d/m/Y');
$dataFimFormatada = $dataFim->format('d/m/Y');
$dataAtual = date('d/m/Y');

// Texto completo do contrato
$textoContrato = "
<h1 style='text-align: center; margin-bottom: 30px;'>CONTRATO DE LOCAÇÃO DE VEÍCULO</h1>

<p style='margin-bottom: 20px;'>Contrato nº {$reserva['id']}</p>

<p><strong>LOCADOR:</strong> {$reserva['dono_nome']} {$reserva['dono_sobrenome']}, CPF {$reserva['dono_cpf']}, residente e domiciliado na cidade de {$reserva['cidade_nome']}/{$reserva['sigla']}, telefone {$reserva['dono_telefone']}, e-mail {$reserva['dono_email']}.</p>

<p><strong>LOCATÁRIO:</strong> {$reserva['locatario_nome']} {$reserva['locatario_sobrenome']}, CPF {$reserva['locatario_cpf']}, telefone {$reserva['locatario_telefone']}, e-mail {$reserva['locatario_email']}.</p>

<p style='margin-top: 20px;'><strong>1. OBJETO DO CONTRATO</strong></p>

<p>1.1. O presente contrato tem como objeto a locação do veículo:</p>
<ul>
    <li><strong>Marca:</strong> {$reserva['veiculo_marca']}</li>
    <li><strong>Modelo:</strong> {$reserva['veiculo_modelo']}</li>
    <li><strong>Ano:</strong> {$reserva['veiculo_ano']}</li>
    <li><strong>Placa:</strong> {$reserva['veiculo_placa']}</li>
    <li><strong>Quilometragem na entrega:</strong> {$reserva['veiculo_km']} km</li>
    <li><strong>Câmbio:</strong> {$reserva['veiculo_cambio']}</li>
    <li><strong>Combustível:</strong> {$reserva['veiculo_combustivel']}</li>
    <li><strong>Nº de Portas:</strong> {$reserva['veiculo_portas']}</li>
    <li><strong>Nº de Assentos:</strong> {$reserva['veiculo_acentos']}</li>
</ul>

<p style='margin-top: 20px;'><strong>2. PRAZO DA LOCAÇÃO</strong></p>

<p>2.1. O presente contrato terá duração de {$dias} dias, iniciando-se em {$dataInicioFormatada} e terminando em {$dataFimFormatada}.</p>

<p>2.2. O local de retirada e devolução do veículo será: {$reserva['nome_local']}, {$reserva['endereco']}, {$reserva['complemento']}, {$reserva['cidade_nome']}/{$reserva['sigla']}, CEP {$reserva['cep']}.</p>

<p style='margin-top: 20px;'><strong>3. VALOR E PAGAMENTO</strong></p>

<p>3.1. O LOCATÁRIO pagará ao LOCADOR o valor diário de R$ {$valorDiaria}, totalizando R$ " . number_format($reserva['diaria_valor'] * $dias, 2, ',', '.') . " pelo período completo de locação.</p>

<p>3.2. Além da diária, serão cobradas as seguintes taxas:</p>
<ul>
    <li>Taxa de uso: R$ {$taxaUso}</li>
    <li>Taxa de limpeza: R$ {$taxaLimpeza}</li>
</ul>

<p>3.3. O valor total da locação é de R$ {$valorTotal}, a ser pago conforme as condições da plataforma DriveNow.</p>

<p style='margin-top: 20px;'><strong>4. RESPONSABILIDADES DO LOCATÁRIO</strong></p>

<p>4.1. O LOCATÁRIO compromete-se a:</p>
<ul>
    <li>Utilizar o veículo somente para fins lícitos;</li>
    <li>Manter o veículo nas mesmas condições em que foi recebido;</li>
    <li>Não realizar modificações ou alterações no veículo;</li>
    <li>Responsabilizar-se por multas, infrações e penalidades durante o período de locação;</li>
    <li>Arcar com despesas de combustível durante a utilização;</li>
    <li>Comunicar imediatamente ao LOCADOR qualquer problema, dano ou acidente com o veículo;</li>
    <li>Devolver o veículo na data e horário acordados;</li>
    <li>Não sublocar ou emprestar o veículo a terceiros.</li>
</ul>

<p style='margin-top: 20px;'><strong>5. RESPONSABILIDADES DO LOCADOR</strong></p>

<p>5.1. O LOCADOR compromete-se a:</p>
<ul>
    <li>Entregar o veículo em perfeitas condições de uso, limpeza e segurança;</li>
    <li>Entregar toda a documentação regular do veículo;</li>
    <li>Garantir que o veículo esteja com manutenção em dia;</li>
    <li>Disponibilizar assistência em caso de problemas mecânicos não causados pelo LOCATÁRIO.</li>
</ul>

<p style='margin-top: 20px;'><strong>6. SEGURO E DANOS</strong></p>

<p>6.1. O veículo está coberto por seguro conforme política da plataforma DriveNow.</p>

<p>6.2. Em caso de danos causados por mau uso, o LOCATÁRIO arcará com os custos de reparo.</p>

<p>6.3. O valor da franquia do seguro, caso aplicável, é de responsabilidade do LOCATÁRIO em caso de sinistro.</p>

<p style='margin-top: 20px;'><strong>7. RESCISÃO</strong></p>

<p>7.1. O presente contrato poderá ser rescindido por qualquer das partes em caso de descumprimento de qualquer cláusula contratual, mediante comunicação prévia pela plataforma DriveNow.</p>

<p style='margin-top: 20px;'><strong>8. DISPOSIÇÕES GERAIS</strong></p>

<p>8.1. Este contrato está vinculado aos Termos e Condições de Uso da plataforma DriveNow, que são complementares a este instrumento.</p>

<p>8.2. Para dirimir quaisquer controvérsias oriundas deste contrato, as partes elegem o foro da comarca de {$reserva['cidade_nome']}/{$reserva['sigla']}.</p>

<p style='margin-top: 40px;'>E por estarem justos e contratados, firmam o presente instrumento.</p>

<p style='margin-top: 20px;'>{$reserva['cidade_nome']}, {$dataAtual}.</p>

<div style='margin-top: 60px; display: flex; justify-content: space-between;'>
    <div style='text-align: center; width: 45%;'>
        <div style='border-top: 1px solid #000; padding-top: 10px;'>
            {$reserva['dono_nome']} {$reserva['dono_sobrenome']}<br>
            LOCADOR
        </div>
    </div>
    
    <div style='text-align: center; width: 45%;'>
        <div style='border-top: 1px solid #000; padding-top: 10px;'>
            {$reserva['locatario_nome']} {$reserva['locatario_sobrenome']}<br>
            LOCATÁRIO
        </div>
    </div>
</div>
";

// Se for solicitado para gerar PDF, usa a biblioteca mPDF
if ($visualizarPdf) {
    if (!$mpdfDisponivel) {
        $_SESSION['notification'] = [
            'type' => 'error',
            'message' => 'A funcionalidade de geração de PDF não está disponível. Entre em contato com o administrador do sistema.'
        ];
        header("Location: gerar_contrato.php?reserva={$reservaId}");
        exit;
    }
    
    // Configuração do mPDF
    $mpdf = new \Mpdf\Mpdf([
        'margin_left' => 20,
        'margin_right' => 20,
        'margin_top' => 20,
        'margin_bottom' => 20,
    ]);
    
    // Adiciona estilos CSS para o PDF
    $stylesheet = '
        body { font-family: Arial, sans-serif; font-size: 12pt; line-height: 1.5; }
        h1 { font-size: 18pt; font-weight: bold; color: #333; }
        p { margin-bottom: 10px; }
        ul, ol { margin-left: 20px; margin-bottom: 10px; }
        li { margin-bottom: 5px; }
    ';
    
    $mpdf->WriteHTML($stylesheet, \Mpdf\HTMLParserMode::HEADER_CSS);
    $mpdf->WriteHTML($textoContrato, \Mpdf\HTMLParserMode::HTML_BODY);
    
    // Nome do arquivo
    $nomeArquivo = "contrato_drivenow_{$reserva['id']}.pdf";
    
    // Saída do PDF
    $mpdf->Output($nomeArquivo, \Mpdf\Output\Destination::INLINE);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato de Locação - DriveNow</title>
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

        .contract-container {
            background-color: white;
            color: #333;
            padding: 40px;
            font-family: Arial, sans-serif;
            line-height: 1.5;
        }

        .contract-container h1 {
            font-size: 24px;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
        }

        .contract-container p {
            margin-bottom: 15px;
        }

        .contract-container ul {
            margin-left: 20px;
            margin-bottom: 15px;
        }

        .contract-container li {
            margin-bottom: 5px;
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
                    <a href="../index.php" class="text-xl font-bold text-white mr-8">DriveNow</a>
                    <nav class="hidden md:flex space-x-6">
                        <a href="../index.php" class="text-white/80 hover:text-white transition-colors">Home</a>
                        <a href="../vboard.php" class="text-white/80 hover:text-white transition-colors">Dashboard</a>
                        <a href="../reserva/minhas_reservas.php" class="text-white/80 hover:text-white transition-colors">Reservas</a>
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

    <main class="container mx-auto px-4">        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Contrato de Locação</h1>
            <div class="flex gap-3">
                <?php if ($mpdfDisponivel): ?>
                <a href="gerar_contrato.php?reserva=<?= $reservaId ?>&pdf=1" class="bg-indigo-500 hover:bg-indigo-600 text-white font-medium rounded-xl transition-colors border border-indigo-400/30 px-4 py-2 shadow-md hover:shadow-lg flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                        <polyline points="7 10 12 15 17 10"/>
                        <line x1="12" y1="15" x2="12" y2="3"/>
                    </svg>
                    Baixar PDF
                </a>
                <?php else: ?>
                <span class="bg-gray-500/20 text-gray-300 border border-gray-400/30 px-4 py-2 rounded-xl font-medium flex items-center gap-2" title="Biblioteca PDF não instalada">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    PDF Indisponível
                </span>
                <?php endif; ?>
                <a href="<?= $reserva['locatario_id'] === $usuario['id'] ? '../reserva/minhas_reservas.php' : '../reserva/reservas_recebidas.php' ?>" class="border border-white/20 text-white hover:bg-white/20 rounded-xl px-4 py-2 font-medium backdrop-blur-sm bg-white/5 hover:bg-white/10 shadow-md hover:shadow-lg flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Voltar
                </a>
            </div>
        </div>
        
        <!-- Contrato -->
        <div class="backdrop-blur-lg bg-white/5 border subtle-border rounded-3xl overflow-hidden shadow-lg mb-10">
            <div class="contract-container overflow-auto max-h-[80vh]">
                <?= $textoContrato ?>
            </div>
        </div>
    </main>
    
    <footer class="mt-12 mb-6 px-4 text-center text-white/50 text-sm">
        <p>&copy; <?= date('Y') ?> DriveNow. Todos os direitos reservados.</p>
    </footer>

    <!-- Sistema de notificações -->
    <script src="../assets/notifications.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            initializeNotifications();
            
            <?php if (isset($_SESSION['notification'])): ?>
                notify({
                    type: '<?= $_SESSION['notification']['type'] ?>',
                    message: '<?= $_SESSION['notification']['message'] ?>'
                });
                <?php unset($_SESSION['notification']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>
