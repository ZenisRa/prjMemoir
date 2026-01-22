<?php
// api/delete_task.php
// Endpoint per eliminare un promemoria dal database

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

// Controlla che sia stato fornito l'ID del promemoria
if (!$input || !isset($input['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID promemoria non fornito']);
    exit;
}

$id_utente = $_SESSION['id'];               // ID utente loggato
$id_promemoria = intval($input['id']);      // ID del promemoria da eliminare (assicurandosi sia intero)


// 3. Verifica che il promemoria appartenga all'utente
$sql_check = "SELECT p.id_promemoria 
              FROM Promemoria p 
              INNER JOIN Lista l ON p.id_lista = l.id_lista 
              WHERE p.id_promemoria = ? AND l.id_utente = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("ii", $id_promemoria, $id_utente);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

// Se il promemoria non esiste o non appartiene all'utente, restituisce errore
if ($result_check->num_rows === 0) {
    $stmt_check->close();
    echo json_encode(['success' => false, 'message' => 'Promemoria non trovato o non autorizzato']);
    exit;
}
$stmt_check->close();


// 4. Elimina il promemoria
// Se ci sono vincoli di foreign key con CASCADE, eventuali dati correlati saranno rimossi automaticamente
$sql_delete = "DELETE FROM Promemoria WHERE id_promemoria = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $id_promemoria);

if ($stmt_delete->execute()) {
    // Eliminazione avvenuta con successo
    echo json_encode(['success' => true, 'message' => 'Promemoria eliminato']);
} else {
    // Errore durante l'eliminazione
    echo json_encode(['success' => false, 'message' => 'Errore eliminazione: ' . $conn->error]);
}

$stmt_delete->close();

// Chiude la connessione al database
$conn->close();
?>