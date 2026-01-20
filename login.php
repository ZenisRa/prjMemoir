<?php
// login.php
session_start();
include 'db_conn.php'; // Assicurati che questo file esista e colleghi al DB corretto

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Inserisci username e password.";
    } else {
        // NOTA: Usiamo i nomi della tabella 'Utente' come da schema SQL tradotto
        // Colonne: id_utente, username, password
        $sql = "SELECT id_utente, username, password FROM Utente WHERE username = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Verifica Hash Password
                if (password_verify($password, $user['password'])) {
                    // Login OK
                    $_SESSION['loggedin'] = true;
                    $_SESSION['id'] = $user['id_utente']; // Usa id_utente
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['just_logged_in'] = true; // Flag per dissolvenza in index.php

                    header("Location: index.php");
                    exit;
                } else {
                    $error = "Password non corretta.";
                }
            } else {
                $error = "Utente non trovato.";
            }
            $stmt->close();
        } else {
            $error = "Errore nella query SQL: " . $conn->error;
        }
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Accedi</title>
    <!-- Usa lo stesso stile dell'app per coerenza -->
    <link rel="stylesheet" href="css/auth.css">
    <style>
        /* Override per centrare il login nella pagina */
        body { display: flex; justify-content: center; align-items: center; }
        .auth-container { padding: 40px; width: 100%; max-width: 400px; text-align: center; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .error-msg { color: white; background: #ff3b30; padding: 10px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="auth-container">
    <div class="logo-container">
        <img src="logo.jpeg" alt="Logo">
    </div>
    <h2 style="margin-bottom: 20px; color: #1c1c1e;">Bentornato</h2>

    <?php if($error): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

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

    <div style="margin-top: 20px; font-size: 0.9rem; color: #888;">
        Non hai un account? <a href="register.php" style="color: #007aff; text-decoration: none;">Registrati</a>
    </div>
</div>

<script>
    // Dissolvenza fade-out quando si submita il form
    document.getElementById('loginForm').addEventListener('submit', function(e) {
        // Previeni il submit immediato
        e.preventDefault();
        
        // Aggiungi classe fade-out al body
        document.body.style.transition = 'opacity 0.5s ease-out';
        document.body.style.opacity = '0';
        
        // Dopo la dissolvenza, submita il form
        setTimeout(() => {
            this.submit();
        }, 500);
    });
</script>
</body>
</html>