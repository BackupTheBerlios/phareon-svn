<?php

error_reporting(E_ALL);

include_once '../../../phareon.php';
include_once 'lib/database/Database.php';


$connection = new Database();

try {
    $connection->connect('fileserver', 'lan', '...', 'intranet');
}
catch(DatabaseException $e) {
    die($e->toString());
}

echo 'Verbindung erfolgreich.';
echo '<pre>';
print_r($connection);

$statement = $connection->prepareStatement('SELECT * FROM links WHERE catid = ?');
$statement->setInteger(1, 34);

try {
    $record = $statement->query(Database::Record);
}
catch(DatabaseException $e) {
    die($e->toString());
}

print_r($record);
echo 'Anzahl der Datensätze: ' . $record->count() . '<br />';


try {
    $recordSet = $statement->query(Database::RecordSet,  Database::FETCH_NUM);
}
catch(DatabaseException $e) {
    die($e->toString());
}

while($recordSet->next()) {
    echo '<b>' . $recordSet->getInteger(1) . '</b><br />';
    print_r($recordSet);
}

echo 'Anzahl der Datensätze: ' . $recordSet->count() . '<br />';


$connection->selectDatabase('David');

$sql = 'UPDATE test SET name = ? WHERE id = ?';
$statement = $connection->prepareStatement($sql);
$statement->setString(1, md5(uniqid()));
$statement->setInteger(2, 1);

echo $statement->prepareSql() . '<br />';

try {
    print_r($statement->query());
    echo '<br /><b>' . mysql_affected_rows() . '</b>';
}
catch(DatabaseException $e) {
    die($e->toString());
}

print_r($connection->query('SELECT * FROM test', Database::Record));

?>
