<?php
require_once "db.php";

echo "Connected database: ";
$result = $conn->query("SELECT DATABASE() AS dbname");
$row = $result->fetch_assoc();
echo $row["dbname"];

echo "<br><br>Tables visible to PHP:<br>";

$result = $conn->query("SHOW TABLES");

while ($row = $result->fetch_array()) {
    echo htmlspecialchars($row[0]) . "<br>";
}
?>