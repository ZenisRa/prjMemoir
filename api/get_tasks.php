<?php
// api/get_tasks.php
// Questo script PHP serve a recuperare tutti i promemoria e le liste associate
// all'utente attualmente loggato, restituendo i dati in formato JSON

session_start(); // Avvia la sessione per verificare l'utente
header('Content-Type: application/json'); // Imposta l'header della risposta come JSON

// Controllo che l'utente sia loggato
// Se non ci sono le variabili di sessione necessarie, interrompe l'esecuzione
if (!isset($_SESSION['loggedin']) || !isset($_SESSION['id'])) {
    echo json_encode(['success' => false, 'message' => 'Utente non autenticato']);
    exit;
}

// Connessione al database
include '../db_conn.php';

// Recupera l'id dell'utente dalla sessione
$id_utente = $_SESSION['id'];

// =======================================
// 1. Caricamento di tutte le liste dell'utente
// =======================================
$sql_liste = "SELECT id_lista, nome_lista FROM Lista WHERE id_utente = ? ORDER BY nome_lista";
$stmt_liste = $conn->prepare($sql_liste); // Prepara la query per sicurezza (evita SQL injection)
$stmt_liste->bind_param("i", $id_utente); // Associa il parametro id_utente
$stmt_liste->execute(); // Esegue la query
$result_liste = $stmt_liste->get_result(); // Recupera il risultato

$liste = [];
while ($row = $result_liste->fetch_assoc()) {
    // Riempie l'array $liste con gli ID e i nomi delle liste
    $liste[] = [
        'id' => $row['id_lista'],
        'nome' => $row['nome_lista']
    ];
}
$stmt_liste->close(); // Chiude lo statement per liberare risorse

// =======================================
// 2. Caricamento di tutti i promemoria dell'utente
// =======================================
// La query recupera i promemoria unendo la tabella Lista, così da avere anche il nome della lista
$sql_promemoria = "SELECT 
        p.id_promemoria,
        p.id_lista,
        p.titolo,
        p.priorita,
        p.descrizione,
        p.data_scad,
        l.nome_lista
FROM Promemoria p
INNER JOIN Lista l ON p.id_lista = l.id_lista
WHERE l.id_utente = ?
ORDER BY p.data_scad ASC, p.priorita DESC";

$stmt_promemoria = $conn->prepare($sql_promemoria); // Prepara la query
$stmt_promemoria->bind_param("i", $id_utente); // Associa il parametro id_utente
$stmt_promemoria->execute(); // Esegue la query
$result_promemoria = $stmt_promemoria->get_result(); // Recupera il risultato

$promemoria = [];
while ($row = $result_promemoria->fetch_assoc()) {
    // Converte la data in un formato compatibile con input datetime-local di HTML
    $data_scad = null;
    if ($row['data_scad']) {
        $data_scad = date('Y-m-d\TH:i', strtotime($row['data_scad'])); // Rimuove i secondi
    }
    
    // Aggiunge i promemoria all'array con la struttura richiesta dal frontend
    $promemoria[] = [
        'id' => $row['id_promemoria'],
        'id_lista' => $row['id_lista'],
        'title' => $row['titolo'],
        'description' => $row['descrizione'] ?? '', // Se la descrizione è null, mette stringa vuota
        'date' => $data_scad,
        'priority' => (string)$row['priorita'], // Converte la priorità in stringa per JS
        'list' => $row['nome_lista'],
        'completed' => false // Il campo non esiste nel DB, impostato a false di default
    ];
}
$stmt_promemoria->close(); // Chiude lo statement

// =======================================
// 3. Prepara l'elenco dei nomi delle liste per il frontend
// =======================================
$nomi_liste = array_map(function($l) { return $l['nome']; }, $liste);

// Se l'utente non ha ancora liste, ne crea una di default chiamata "Generale"
if (empty($nomi_liste)) {
    $sql_insert_default = "INSERT INTO Lista (nome_lista, id_utente) VALUES ('Generale', ?)";
    $stmt_default = $conn->prepare($sql_insert_default);
    $stmt_default->bind_param("i", $id_utente);
    $stmt_default->execute();
    $id_lista_generale = $conn->insert_id; // Recupera l'ID della lista appena creata
    $stmt_default->close();
    
    $nomi_liste = ['Generale'];
    $liste = [['id' => $id_lista_generale, 'nome' => 'Generale']];
}

// =======================================
// 4. Restituisce tutti i dati al frontend in JSON
// =======================================
echo json_encode([
    'success' => true,
    'promemoria' => $promemoria,
    'liste' => $nomi_liste,
    'liste_complete' => $liste // Include anche gli ID per eventuali operazioni lato frontend
]);

$conn->close(); // Chiude la connessione al database
?>

