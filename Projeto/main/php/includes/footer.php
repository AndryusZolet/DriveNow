<?php
// Conexão com o banco de dados
$dsn = 'mysql:host=localhost;dbname=DriveNow;charset=utf8';
$username = 'root';
$password = '';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
];

try {
    $dbh = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}

// Inscrição de e-mail
if(isset($_POST['emailsubscibe'])) {
    $subscriberemail = $_POST['subscriberemail'];
    $sql = "SELECT SubscriberEmail FROM tblsubscribers WHERE SubscriberEmail=:subscriberemail";
    $query = $dbh->prepare($sql);
    $query->bindParam(':subscriberemail', $subscriberemail, PDO::PARAM_STR);
    $query->execute();
    
    if($query->rowCount() > 0) {
        echo "<script>alert('Ja registrado.');</script>";
    } else {
        $sql = "INSERT INTO tblsubscribers(SubscriberEmail) VALUES(:subscriberemail)";
        $query = $dbh->prepare($sql);
        $query->bindParam(':subscriberemail', $subscriberemail, PDO::PARAM_STR);
        $query->execute();
        
        if($dbh->lastInsertId()) {
            echo "<script>alert('Registrado com sucesso.');</script>";
        } else {
            echo "<script>alert('Algo deu errado. Tente novamente');</script>";
        }
    }
}
?>

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
                        <li><a href="#"></a></li>
                        <li><a href="#"></a></li>
                        <li><a href="#"></a></li>
                        <li><a href="#"></a></li>
                    </ul>
                </div>
            </div>
            <div class="footer_width link">
                <h2>Link</h2>
                <ul>
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Sobre</a></li>
                    <li><a href="#">Serviço</a></li>
                    <li><a href="#">Contato</a></li>
                    <li><a href="#">Suporte</a></li>
                </ul>
            </div>
            <div class="footer_width Contact">
                <h2>Contact</h2>
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
                        <a href="#">DriveNow@gmail.com</a>
                    </li>
                    <li>
                        <span><i class="fas fa-phone-volume"></i></span>
                        <p>(11) 1234-5678</p>
                    </li>
                </ul>
            </div>
        </div>
        <div class="copy-right">
            <p>© DriveNow LLC 2025 - 2025, College Project.</p>
        </div>
    </footer>
