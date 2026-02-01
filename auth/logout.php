<?php
session_start();  
require_once '../config/mongodb.php';
if (isset($_SESSION['id_utente'])) {
    logEvent('logout', "Logout utente: {$_SESSION['username']}", $_SESSION['id_utente']);
}
session_unset();        // Rimuove tutte le variabili di sessione
session_destroy();      // Distrugge la sessione
header("Location: login.php"); // Reindirizza al login
exit();
?>

