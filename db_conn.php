<?php
$servername = "localhost";//su che macchina si trova il db
$username = "root";//utente
$password = "";
$dbname = "promemoria_db";//nome del db

// Creazione di un oggetto connessione
$conn = new mysqli($servername, $username, $password, $dbname);

// Controllo se la connessione è fallita
if ($conn->connect_error) {
    die("Connessione fallita: " . $conn->connect_error);
}
?>