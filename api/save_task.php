<?php
// api/save_task.php
// Salva o aggiorna un promemoria nel database

// Disabilita la visualizzazione degli errori HTML e cattura tutti gli errori
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Gestore di errori personalizzato per rispondere sempre in JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    // Non interrompere lo script per warning non fatali, ma loggali
    if (!(error_reporting() & $errno)) {
        return false;
    }
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Errore PHP: ' . $errstr . ' in ' . basename($errfile) . ' alla riga ' . $errline
    ]);
    exit;
});

session_start();
header('Content-Type: application/json');

// Verifica che l'utente sia loggato
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit;
}

// Includi la connessione al database
// Assicurati che il percorso sia corretto. Se questo file è in /api/ e db_conn.php è nella root:
if (file_exists('../db_conn.php')) {
    include '../db_conn.php';
} else {
    echo json_encode(['success' => false, 'message' => 'Errore: file db_conn.php non trovato']);
    exit;
}

// Legge i dati JSON dal body della richiesta
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Dati JSON non validi']);
    exit;
}

$id_utente = $_SESSION['id'];
// ID del promemoria (può essere un ID reale DB o un timestamp JS)
$id_promemoria_input = isset($input['id']) ? $input['id'] : null;
$titolo = trim($input['title'] ?? '');
$descrizione = trim($input['description'] ?? '');
$priorita = isset($input['priority']) ? intval($input['priority']) : 0;
$data_input = !empty($input['date']) ? $input['date'] : null;
$nome_lista = trim($input['list'] ?? 'Generale'); // Default a 'Generale' se vuoto

// Validazione base
if (empty($titolo)) {
    echo json_encode(['success' => false, 'message' => 'Il titolo è obbligatorio']);
    if (isset($conn)) $conn->close();
    exit;
}

// ---------------------------------------------------------
// 1. GESTIONE LISTA (Trova ID o Crea)
// ---------------------------------------------------------

$id_lista = null;

// Cerca se la lista esiste già per questo utente
$sql_lista = "SELECT id_lista FROM Lista WHERE nome_lista = ? AND id_utente = ?";
$stmt_lista = $conn->prepare($sql_lista);
if (!$stmt_lista) {
    echo json_encode(['success' => false, 'message' => 'Errore SQL Lista: ' . $conn->error]);
    exit;
}
$stmt_lista->bind_param("si", $nome_lista, $id_utente);
$stmt_lista->execute();
$result_lista = $stmt_lista->get_result();

if ($result_lista->num_rows > 0) {
    $row_lista = $result_lista->fetch_assoc();
    $id_lista = $row_lista['id_lista'];
} else {
    // La lista non esiste, la creiamo
    $stmt_lista->close(); // Chiudiamo il precedente statement
    
    $sql_insert_lista = "INSERT INTO Lista (nome_lista, id_utente) VALUES (?, ?)";
    $stmt_insert_lista = $conn->prepare($sql_insert_lista);
    if (!$stmt_insert_lista) {
        echo json_encode(['success' => false, 'message' => 'Errore SQL Insert Lista: ' . $conn->error]);
        exit;
    }
    $stmt_insert_lista->bind_param("si", $nome_lista, $id_utente);
    if ($stmt_insert_lista->execute()) {
        $id_lista = $conn->insert_id;
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore creazione lista: ' . $stmt_insert_lista->error]);
        exit;
    }
    $stmt_insert_lista->close();
}
// Se stmt_lista era ancora aperto (nel ramo if), chiudilo
if (isset($stmt_lista) && $stmt_lista instanceof mysqli_stmt) {
    $stmt_lista->close();
}

// ---------------------------------------------------------
// 2. PREPARAZIONE DATI (Data MySQL)
// ---------------------------------------------------------

$data_scad_mysql = null;
if ($data_input && trim($data_input) !== '') {
    // Tenta di convertire la data. datetime-local è solitamente "YYYY-MM-DDTHH:MM"
    $timestamp = strtotime($data_input);
    if ($timestamp !== false) {
        $data_scad_mysql = date('Y-m-d H:i:s', $timestamp);
    }
}

// ---------------------------------------------------------
// 3. DETERMINA SE INSERT O UPDATE
// ---------------------------------------------------------

$is_update = false;
$id_reale_db = null;

// Un ID è "esistente" se è numerico, > 0 e "piccolo" (gli ID temporanei JS sono > 10^12)
// Inoltre, controlliamo l'azione esplicita se passata dal JS
$action = $input['action'] ?? '';

if ($action === 'update' && $id_promemoria_input && is_numeric($id_promemoria_input) && $id_promemoria_input < 2147483647) {
    // È un potenziale aggiornamento. Verifichiamo che il task esista e appartenga all'utente.
    // JOIN con Lista per verificare l'utente proprietario
    $sql_check = "SELECT P.id_promemoria 
                  FROM Promemoria P 
                  JOIN Lista L ON P.id_lista = L.id_lista 
                  WHERE P.id_promemoria = ? AND L.id_utente = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("ii", $id_promemoria_input, $id_utente);
    $stmt_check->execute();
    $stmt_check->store_result();
    
    if ($stmt_check->num_rows > 0) {
        $is_update = true;
        $id_reale_db = $id_promemoria_input;
    }
    $stmt_check->close();
}

// ---------------------------------------------------------
// 4. ESECUZIONE QUERY (Promemoria)
// ---------------------------------------------------------

if ($is_update) {
    // --- UPDATE ---
    $sql_update = "UPDATE Promemoria 
                   SET id_lista = ?, titolo = ?, descrizione = ?, priorita = ?, data_scad = ? 
                   WHERE id_promemoria = ?";
    
    $stmt = $conn->prepare($sql_update);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Errore SQL Prepare Update: ' . $conn->error]);
        exit;
    }
    
    // Bind param: 'issisi' -> integer, string, string, integer, string (o null), integer
    // Nota: bind_param non accetta direttamente null per valore, ma accetta una variabile che è null
    $stmt->bind_param("issisi", $id_lista, $titolo, $descrizione, $priorita, $data_scad_mysql, $id_reale_db);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Promemoria aggiornato', 
            'id' => $id_reale_db,
            'action_performed' => 'update'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore esecuzione update: ' . $stmt->error]);
    }
    $stmt->close();

} else {
    // --- INSERT ---
    $sql_insert = "INSERT INTO Promemoria (id_lista, titolo, descrizione, priorita, data_scad) 
                   VALUES (?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql_insert);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Errore SQL Prepare Insert: ' . $conn->error]);
        exit;
    }
    
    // Bind param: 'issis' -> integer, string, string, integer, string (o null)
    $stmt->bind_param("issis", $id_lista, $titolo, $descrizione, $priorita, $data_scad_mysql);
    
    if ($stmt->execute()) {
        $nuovo_id = $conn->insert_id;
        echo json_encode([
            'success' => true, 
            'message' => 'Promemoria creato', 
            'id' => $nuovo_id,
            'action_performed' => 'create'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Errore esecuzione insert: ' . $stmt->error]);
    }
    $stmt->close();
}

$conn->close();
?>