<?php
session_start();
if (!isset($_SESSION['loggedin'])) {
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username'] ?? 'Utente';
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promemoria</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Piccoli aggiustamenti inline per icone e colori */
        .sort-select { cursor: pointer; border: 1px solid transparent; }
        .sort-select:hover { border-color: #ddd; }
        .priority-indicator { font-weight: 900; margin-right: 5px; }
        .prio-1 { color: #007aff; }
        .prio-2 { color: #ff9f0a; }
        .prio-3 { color: #ff3b30; }
        
        .btn-delete-list {
            background: none; border: none; color: #ff3b30;
            cursor: pointer; font-size: 1.2rem; margin-left: auto;
            opacity: 0; transition: opacity 0.2s; padding: 0 5px;
        }
        .list-item:hover .btn-delete-list { opacity: 1; }
    </style>
</head>
<body>

<div class="app-container">

    <!-- SIDEBAR SINISTRA -->
    <aside class="sidebar" id="sidebar">
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="🔍 Cerca ovunque...">
        </div>

        <div class="lists-group">
            <h4>Smart Lists</h4>
            <!-- Oggi -->
            <div class="list-item active" id="filter-today" onclick="switchFilter('today', this)">
                <span>📅 Oggi</span>
            </div>
            <!-- Domani (NUOVO) -->
            <div class="list-item" id="filter-tomorrow" onclick="switchFilter('tomorrow', this)">
                <span>🌅 Domani</span>
            </div>
            <!-- Prossimamente -->
            <div class="list-item" id="filter-upcoming" onclick="switchFilter('upcoming', this)">
                <span>🔭 Prossimamente</span>
            </div>
            <!-- Tutti -->
            <div class="list-item" id="filter-all" onclick="switchFilter('all', this)">
                <span>🗂️ Tutti</span>
            </div>

            <h4>Le mie Liste</h4>
            <div id="customListsContainer">
                <!-- Qui JavaScript caricherà le liste personalizzate -->
            </div>
        </div>

        <div class="sidebar-footer">
            <button class="btn-text" onclick="addNewList()">+ Lista</button>
            <button class="glass-btn btn-theme-glass" onclick="toggleTheme()" id="themeBtn" title="Cambia tema">🌙</button>
            <button class="btn-text" onclick="logout()" style="color: #ff3b30;">Esci</button>
        </div>
    </aside>

    <!-- LISTA CENTRALE -->
    <main class="main-list">
        <header class="list-header">
            <h1 id="currentListTitle">Oggi</h1>
            <div class="header-actions" style="flex:1; display:flex; justify-content:flex-end; align-items:center;">
                <!-- Bottone "+" in stile Liquid Glass -->
                <button title="Nuovo Promemoria" onclick="createNewTask()" class="glass-btn btn-add-main">
                    +
                </button>
                
                <select id="sortOrder" class="sort-select glass-select" onchange="renderAllTasks()">
                    <option value="manual">Ordina per...</option>
                    <option value="date">📅 Data Scadenza</option>
                    <option value="priority">❗ Urgenza</option>
                </select>
            </div>
        </header>

        <div id="tasksContainer" class="tasks-container">
            <!-- Qui JavaScript caricherà i promemoria -->
        </div>
    </main>

    <!-- PANNELLO DETTAGLI DESTRA -->
    <aside id="detailsPanel" class="details-panel">
        <div class="details-header">
            <h3>Dettagli</h3>
            <button class="btn-close" onclick="closeDetails()">✕</button>
        </div>

        <form id="detailsForm" onsubmit="return false;">
            <div class="detail-group">
                <input type="text" id="detTitle" style="font-size:1.2rem; font-weight:bold;" placeholder="Titolo">
            </div>
            <div class="detail-group">
                <label>Note</label>
                <textarea id="detDesc" placeholder="Note..."></textarea>
            </div>
            <div class="detail-group">
                <label>Data</label>
                <input type="datetime-local" id="detDate">
            </div>
            <div class="detail-group">
                <label>Priorità</label>
                <select id="detPriority">
                    <option value="0">Nessuna</option>
                    <option value="1">! Bassa</option>
                    <option value="2">!! Media</option>
                    <option value="3">!!! Alta</option>
                </select>
            </div>
            <div class="detail-group">
                <label>Lista</label>
                <select id="detList">
                    <!-- Caricato dinamicamente -->
                </select>
            </div>
            <button type="button" class="btn-primary" onclick="saveTask()">Salva Modifiche</button>
            <button type="button" onclick="deleteTask()" style="width:100%; margin-top:10px; color:#ff3b30; border:none; background:none; cursor:pointer;">Elimina</button>
        </form>
    </aside>

</div>

<script src="js/script.js"></script>
</body>
</html>