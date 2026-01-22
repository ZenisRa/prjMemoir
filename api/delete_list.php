<?php
// api/delete_list.php
// Questo endpoint elimina una lista dell'utente dal database
// NOTA: tutti i promemoria associati alla lista saranno eliminati automaticamente se il DB ha ON DELETE CASCADE

session_start(); // Avvia la sessione per verificare l'utente

// Imposta il tipo di risposta JSON
header('Content-Type: application/json');

// Verifica che l'utente sia loggato
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    // Se non loggato, restituisce errore JSON
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit;
}

// Include connessione al database
include '../db_conn.php';

// Legge i dati JSON inviati tramite POST
$input = json_decode(file_get_contents('php://input'), true);

// Verifica che sia stato fornito il nome della lista
if (!$input || !isset($input['nome'])) {
    echo json_encode(['success' => false, 'message' => 'Nome lista non fornito']);
    exit;
}

$id_utente = $_SESSION['id'];          // ID utente loggato
$nome_lista = trim($input['nome']);     // Nome della lista da eliminare (rimuove spazi)


// 1. Controlla che la lista appartenga effettivamente all'utente
$sql_check = "SELECT id_lista FROM Lista WHERE nome_lista = ? AND id_utente = ?";
$stmt_check = $conn->prepare($sql_check);
$stmt_check->bind_param("si", $nome_lista, $id_utente);
$stmt_check->execute();
$result_check = $stmt_check->get_result();

// Se non trova la lista o non appartiene all'utente
if ($result_check->num_rows === 0) {
    $stmt_check->close();
    echo json_encode(['success' => false, 'message' => 'Lista non trovata o non autorizzata']);
    exit;
}

// Prende l'ID della lista
$row = $result_check->fetch_assoc();
$id_lista = $row['id_lista'];
$stmt_check->close();


// 2. Controlla che non sia l'ultima lista dell'utente
$sql_count = "SELECT COUNT(*) as totale FROM Lista WHERE id_utente = ?";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bind_param("i", $id_utente);
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$row_count = $result_count->fetch_assoc();
$stmt_count->close();

// Se c'è solo una lista, impedisce l'eliminazione
if ($row_count['totale'] <= 1) {
    echo json_encode(['success' => false, 'message' => 'Non puoi eliminare l\'ultima lista']);
    exit;
}


// 3. Elimina la lista dal DB
// NOTA: se la tabella promemoria ha ON DELETE CASCADE, tutti i promemoria saranno eliminati automaticamente
$sql_delete = "DELETE FROM Lista WHERE id_lista = ?";
$stmt_delete = $conn->prepare($sql_delete);
$stmt_delete->bind_param("i", $id_lista);

if ($stmt_delete->execute()) {
    // Eliminazione riuscita
    echo json_encode(['success' => true, 'message' => 'Lista eliminata']);
} else {
    // Errore DB
    echo json_encode(['success' => false, 'message' => 'Errore eliminazione: ' . $conn->error]);
}
$stmt_delete->close();

// Chiude la connessione al DB
$conn->close();
?>

