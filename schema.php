<?php
require 'db.php';
$res = $conn->query("SHOW TABLES");
while($row = $res->fetch_array()) {
    $table = $row[0];
    echo "Table: $table\n";
    $cols = $conn->query("DESCRIBE $table");
    while($col = $cols->fetch_assoc()) {
        echo " - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
}
?>
