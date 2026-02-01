<?php
session_start();        // Avvia la sessione
session_unset();        // Rimuove tutte le variabili di sessione
session_destroy();      // Distrugge la sessione
header("Location: login.php"); // Reindirizza al login
exit();
?>
