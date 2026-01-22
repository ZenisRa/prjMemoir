<?php
// Avvia la sessione PHP per gestire login e variabili di sessione
session_start();

// Include il file di connessione al database
// Assicurati che db_conn.php contenga $conn = new mysqli(...);
include 'db_conn.php';

$error = ""; // Variabile per eventuali messaggi di errore

// Controlla se il form è stato inviato tramite POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Prendi username e password dai campi del form
    $username = trim($_POST['username']); // trim() rimuove spazi iniziali/finali
    $password = $_POST['password'];

    // Verifica che i campi non siano vuoti
    if (empty($username) || empty($password)) {
        $error = "Inserisci username e password.";
    } else {
        // Query preparata per selezionare l'utente dal DB
        // NOTA: Tabella 'Utente' con colonne id_utente, username, password
        $sql = "SELECT id_utente, username, password FROM Utente WHERE username = ?";
        
        // Preparazione della query
        if ($stmt = $conn->prepare($sql)) {
            // Lega il parametro username (tipo stringa "s")
            $stmt->bind_param("s", $username);
            $stmt->execute(); // Esegue la query
            $result = $stmt->get_result(); // Ottiene il risultato

            if ($result->num_rows === 1) {
                // Se l'utente esiste, prende i dati
                $user = $result->fetch_assoc();

                // Verifica che la password inserita corrisponda all'hash salvato
                if (password_verify($password, $user['password'])) {
                    // Login riuscito: setta variabili di sessione
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id'] = $user['id_utente']; // id dell'utente
                    $_SESSION['username'] = $user['username']; // username
                    $_SESSION['just_logged_in'] = true; // flag per animazione fade-in su index.php

                    // Reindirizza alla dashboard
                    header("Location: index.php");
                    exit;
                } else {
                    // Password errata
                    $error = "Password non corretta.";
                }
            } else {
                // Username non trovato
                $error = "Utente non trovato.";
            }

            // Chiude lo statement
            $stmt->close();
        } else {
            // Errore nella preparazione della query
            $error = "Errore nella query SQL: " . $conn->error;
        }
    }

    // Chiude la connessione al database
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Accedi</title>
    <!-- Stile per form di login -->
    <link rel="stylesheet" href="css/auth.css">
    <style>
        /* Centra il form nella pagina */
        body { display: flex; justify-content: center; align-items: center; }
        .auth-container { padding: 40px; width: 100%; max-width: 400px; text-align: center; }
        .form-group { margin-bottom: 20px; text-align: left; }

        /* Messaggi di errore rosso */
        .error-msg { color: white; background: #ff3b30; padding: 10px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="logo-container">
        <img src="logo.jpeg" alt="Logo">
    </div>
    <h2 style="margin-bottom: 20px; color: #1c1c1e;">Bentornato</h2>

    <!-- Mostra eventuale errore -->
    <?php if($error): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Form di login -->
    <form method="POST" action="" id="loginForm">
        <div class="form-group">
            <label>Username</label>
            <input type="text" name="username" required>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <button type="submit" class="btn-primary" style="background-color: #007aff; color: white; border: none; border-radius: 12px; cursor: pointer;">Accedi</button>
    </form>

    <!-- Link alla registrazione -->
    <div style="margin-top: 20px; font-size: 0.9rem; color: #888;">
        Non hai un account? <a href="register.php" style="color: #007aff; text-decoration: none;">Registrati</a>
    </div>
</div>

<script>
    // Effetto dissolvenza quando si invia il form
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Previene submit immediato
        
        // Applica transizione fade-out al body
        document.body.style.transition = 'opacity 0.5s ease-out';
        document.body.style.opacity = '0';
        
        // Dopo 0.5s invia il form
        setTimeout(() => {
            this.submit();
        }, 500);
    });
</script>
</body>
</html>