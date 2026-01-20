<?php
// register.php
include 'db_conn.php';

$msg = "";
$msg_type = ""; // 'error' o 'success'

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validazione base
    if (empty($username) || empty($password)) {
        $msg = "Compila tutti i campi!";
        $msg_type = "error-msg";
    } elseif ($password !== $confirm_password) {
        $msg = "Le password non coincidono!";
        $msg_type = "error-msg";
    } else {
        // 1. Controlliamo se l'utente esiste già
        // NOTA: Usiamo 'id_utente' e tabella 'Utente'
        $stmt = $conn->prepare("SELECT id_utente FROM Utente WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $msg = "Username già in uso.";
            $msg_type = "error-msg";
        } else {
            // 2. Criptiamo la password (HASHING)
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // 3. Inseriamo nel database (Tabella Utente)
            $insert = $conn->prepare("INSERT INTO Utente (username, password) VALUES (?, ?)");
            $insert->bind_param("ss", $username, $hashed_password);

            if ($insert->execute()) {
                $msg = "Registrazione avvenuta! <a href='login.php' style='color:white; text-decoration:underline;'>Accedi ora</a>";
                $msg_type = "success-msg";
            } else {
                $msg = "Errore generico: " . $conn->error;
                $msg_type = "error-msg";
            }
            $insert->close();
        }
        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Registrazione</title>
    <!-- Uso lo stile principale per coerenza -->
    <link rel="stylesheet" href="css/auth.css">
    <style>
        /* Override per centrare il form nella pagina */
        body { display: flex; justify-content: center; align-items: center; }
        .auth-container { padding: 40px; width: 100%; max-width: 400px; text-align: center; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .error-msg { color: white; background: #ff3b30; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;}
        .success-msg { color: white; background: #34c759; padding: 10px; border-radius: 8px; margin-bottom: 20px; font-size: 0.9rem;}
        .auth-footer { margin-top: 20px; font-size: 0.9rem; color: #888; }
        .auth-footer a { color: #007aff; text-decoration: none; }
    </style>
</head>
<body>
<div class="auth-container">
    <h2 style="margin-bottom: 20px; color: #1c1c1e;">Crea Account</h2>

    <?php if($msg): ?>
        <div class="<?php echo $msg_type; ?>"><?php echo $msg; ?></div>
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
        <div class="form-group">
            <label>Conferma Password</label>
            <input type="password" name="confirm_password" required>
        </div>
        <button type="submit" class="btn-primary">Registrati</button>
    </form>

    <div class="auth-footer">
        Hai già un account? <a href="login.php">Accedi</a>
    </div>
</div>
</body>
</html>