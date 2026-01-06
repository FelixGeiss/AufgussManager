<?php
// Debug-Datei für update_aufguss.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h1>POST Daten Debug</h1>";
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    echo "<h2>Form Data Details:</h2>";
    foreach ($_POST as $key => $value) {
        echo "<p><strong>$key:</strong> '$value' (Typ: " . gettype($value) . ", Leer: " . (empty($value) ? 'ja' : 'nein') . ")</p>";
    }
} else {
    echo "<h1>Debug Update Form</h1>";
    echo "<form method='POST'>";
    echo "<input type='text' name='aufguss_id' value='1' placeholder='aufguss_id'><br>";
    echo "<input type='text' name='field' value='zeit' placeholder='field'><br>";
    echo "<input type='text' name='zeit_anfang' value='10:00' placeholder='zeit_anfang'><br>";
    echo "<input type='text' name='zeit_ende' value='11:00' placeholder='zeit_ende'><br>";
    echo "<button type='submit'>Test</button>";
    echo "</form>";
}
?>