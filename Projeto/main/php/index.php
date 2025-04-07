<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DriveNow</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>

<body>
    <?php
    session_start();
    require 'connection.php';
    $conn = Connect();
    ?>
    
    <header>
        <img class="logo" src="LogoB.png" alt="Logo">
        <nav class="navegation">
            <a href="index.php">Home</a>
            <a href="#">Sobre</a>
            <a href="#">Serviço</a>
            <a href="#">Contato</a>
            <?php if(isset($_SESSION['login_client'])): ?>
                <span>Bem-vindo, <?php echo $_SESSION['login_client']; ?></span>
                <a href="logout.php">Logout</a>
            <?php elseif(isset($_SESSION['login_customer'])): ?>
                <span>Bem-vindo, <?php echo $_SESSION['login_customer']; ?></span>
                <a href="logout.php">Logout</a>
            <?php else: ?>
                <button class="btnlogin-popup" onclick="location.href='./login.php';">Login</button>
            <?php endif; ?>
        </nav>
    </header>

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
            // Using the veiculos_disponiveis view from your database
            $sql = "SELECT * FROM veiculos_disponiveis";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    echo '<div class="vehicle-card">';
                    echo '<h3>' . htmlspecialchars($row['veiculo_nome']) . ' (' . htmlspecialchars($row['veiculo_ano']) . ')</h3>';
                    
                    // Get the first image for the vehicle
                    $img_sql = "SELECT imagem_url FROM imagem WHERE veiculo_id = " . $row['id'] . " ORDER BY imagem_ordem LIMIT 1";
                    $img_result = $conn->query($img_sql);
                    if ($img_result->num_rows > 0) {
                        $img_row = $img_result->fetch_assoc();
                        echo '<img src="' . htmlspecialchars($img_row['imagem_url']) . '" class="vehicle-img" alt="' . htmlspecialchars($row['veiculo_nome']) . '">';
                    } else {
                        echo '<img src="assets/default-car.jpg" class="vehicle-img" alt="Vehicle Image">';
                    }
                    
                    // Get vehicle details
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

    <footer>
        <div class="footer_info">
            <div class="footer_width about">
                <h2>Sobre</h2>
                <p>
                    DriveNow é uma plataforma inovadora que conecta motoristas e passageiros, 
                    oferecendo uma experiência de transporte eficiente e conveniente. 
                    Nossa missão é facilitar a mobilidade urbana, proporcionando um serviço seguro e acessível para 
                    todos. Com tecnologia de ponta e um compromisso com a qualidade, estamos transformando a forma 
                    como as pessoas se deslocam nas cidades.
                </p>
                <div class="social-media">
                    <ul>
                        <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                        <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                        <li><a href="#"><i class="fab fa-instagram"></i></a></li>
                        <li><a href="#"><i class="fab fa-linkedin-in"></i></a></li>
                    </ul>
                </div>
            </div>
            <div class="footer_width link">
                <h2>Links</h2>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="#">Sobre</a></li>
                    <li><a href="#">Serviço</a></li>
                    <li><a href="#">Contato</a></li>
                    <li><a href="#">Suporte</a></li>
                </ul>
            </div>
            <div class="footer_width Contact">
                <h2>Contato</h2>
                <ul>
                    <li>
                        <span><i class="fas fa-map-marker-alt"></i></span>
                        <p>
                            Rua das Flores, 123<br>
                            Centro, São Paulo - SP<br>
                            CEP: 01234-567
                        </p>
                    </li>
                    <li>
                        <span><i class="fas fa-envelope"></i></span>
                        <a href="mailto:DriveNow@gmail.com">DriveNow@gmail.com</a>
                    </li>
                    <li>
                        <span><i class="fas fa-phone-volume"></i></span>
                        <p>(11) 1234-5678</p>
                    </li>
                </ul>
            </div>
        </div>
        <div class="copy-right">
            <p>© DriveNow LLC <?php echo date("Y"); ?> - College Project.</p>
        </div>
    </footer>

    <script src="script.js"></script>
    <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
    <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
</body>
</html>