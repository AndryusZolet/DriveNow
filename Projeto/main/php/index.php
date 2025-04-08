<?php
session_start();
require 'connection.php';
$conn = Connect();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveNow</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <?php include 'header.php'; ?>

    <main>
        <section class="hero">
            <div class="hero-text">
                <h1>Bem-vindo ao DriveNow</h1>
                <p>Seu transporte rápido, seguro e confiável.</p>
                <a href="#" class="btn">Saiba mais</a>
            </div>
        </section>

        <section class="available-vehicles">
            <h2>Veículos Disponíveis</h2>
            <?php
            // Usando a view veiculos_disponiveis
            $sql = "SELECT * FROM veiculos_disponiveis";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '<div class="vehicle-card">';
                    echo '<h3>' . htmlspecialchars($row['veiculo_nome']) . ' (' . htmlspecialchars($row['veiculo_ano']) . ')</h3>';
                    
                    // Primeira imagem do veículo
                    $img_sql = "SELECT imagem_url FROM imagem WHERE veiculo_id = " . $row['id'] . " ORDER BY imagem_ordem LIMIT 1";
                    $img_result = $conn->query($img_sql);
                    if ($img_result->num_rows > 0) {
                        $img_row = $img_result->fetch_assoc();
                        echo '<img src="' . htmlspecialchars($img_row['imagem_url']) . '" class="vehicle-img" alt="' . htmlspecialchars($row['veiculo_nome']) . '">';
                    } else {
                        echo '<img src="assets/default-car.jpg" class="vehicle-img" alt="Vehicle Image">';
                    }
                    
                    // Detalhes do veículo
                    $details_sql = "SELECT * FROM veiculo WHERE id = " . $row['id'];
                    $details_result = $conn->query($details_sql);
                    if ($details_result->num_rows > 0) {
                        $details = $details_result->fetch_assoc();
                        echo '<p><strong>Câmbio:</strong> ' . htmlspecialchars($details['veiculo_cambio']) . '</p>';
                        echo '<p><strong>Combustível:</strong> ' . htmlspecialchars($details['veiculo_combustivel']) . '</p>';
                        echo '<p><strong>Localização:</strong> ' . htmlspecialchars($row['nome_local']) . '</p>';
                    }
                    
                    if(isset($_SESSION['login_customer'])) {
                        echo '<a href="reservar.php?veiculo_id=' . $row['id'] . '" class="btn">Reservar</a>';
                    } elseif(isset($_SESSION['login_client'])) {
                        echo '<a href="gerenciar_veiculo.php?veiculo_id=' . $row['id'] . '" class="btn">Gerenciar</a>';
                    } else {
                        echo '<a href="login.php" class="btn">Login para reservar</a>';
                    }
                    
                    echo '</div>';
                }
            } else {
                echo '<p>Nenhum veículo disponível no momento.</p>';
            }
            ?>
        </section>
    </main>

    <?php include 'footer.php'; ?>

    <script src="script.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>
