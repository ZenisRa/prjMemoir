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
        body { display: flex; justify-content: center; align-items: center; background-color: #f2f2f7; }
        .auth-container { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 10px; margin-top: 5px; }
        .btn-primary { width: 100%; padding: 12px; font-size: 1rem; }
        .error-msg { color: white; background: #ff3b30; padding: 10px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
<div class="auth-container">
    <h2 style="margin-bottom: 20px; color: #1c1c1e;">Bentornato</h2>

    <?php if($error): ?>
        <div class="error-msg"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="">
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
</body>
</html>