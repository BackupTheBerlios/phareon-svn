<?php

error_reporting(E_ALL);

include_once '../../../phareon.php';
include_once 'lib/database/Database.php';


$connection = new Database();

try {
    $connection->connect('fileserver', 'lan', 'samTron', 'intranet');
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

echo 'Anzahl der Datensätze: ' . $recordSet->count();

?>
