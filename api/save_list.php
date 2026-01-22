<?php
// api/save_list.php
// Endpoint per salvare o aggiornare una lista nel database

session_start(); // Avvia la sessione per verificare l'utente loggato
header('Content-Type: application/json'); // Risposta in formato JSON

// 1. Controlla che l'utente sia loggato
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit;
}

// Include connessione al database
include '../db_conn.php';

// 2. Legge i dati JSON inviati tramite POST
$input = json_decode(file_get_contents('php://input'), true);

// Controlla che i dati siano validi
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dati non validi']);
    exit;
}

$id_utente = $_SESSION['id'];               // ID utente loggato
$nome_lista = trim($input['nome'] ?? '');   // Nome lista, rimuove spazi e fallback stringa vuota

// 3. Validazione: il nome della lista non può essere vuoto
if (empty($nome_lista)) {
    echo json_encode(['success' => false, 'message' => 'Il nome della lista è obbligatorio']);
    $conn->close();
    exit;
}


// 4. Controlla se esiste già una lista con lo stesso nome per questo utente
$sql_check = "SELECT id_lista FROM Lista WHERE nome_lista = ? AND id_utente = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("si", $nome_lista, $id_utente);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

if ($result_check->num_rows > 0) {
    // Lista già esistente: restituisce l'ID esistente
    $row = $result_check->fetch_assoc();
    $stmt_check->close();
    echo json_encode([
        'success' => true, 
        'message' => 'Lista già esistente',
        'id' => $row['id_lista'],
        'nome' => $nome_lista
    ]);
} else {
    // 5. Crea nuova lista
    $sql_insert = "INSERT INTO Lista (nome_lista, id_utente) VALUES (?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("si", $nome_lista, $id_utente);

    if ($stmt_insert->execute()) {
        $nuovo_id = $conn->insert_id; // ID della nuova lista appena creata
        echo json_encode([
            'success' => true,
            'message' => 'Lista creata',
            'id' => $nuovo_id,
            'nome' => $nome_lista
        ]);
    } else {
        // Errore durante l'inserimento
        echo json_encode(['success' => false, 'message' => 'Errore inserimento: ' . $conn->error]);
    }
    $stmt_insert->close();
}

$conn->close(); // Chiude la connessione al database
?>
