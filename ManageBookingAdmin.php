<?php
// require_once('../../conf/assign2sqlinfo.inc.php');

// mysqli_connect returns false if connection failed, otherwise a connection value
// $conn = @mysqli_connect(
//     $sql_host,
//     $sql_user,
//     $sql_pass,
//     $sql_db
// );

// SQLite Connect for Heroku
$conn = pg_connect(getenv("DATABASE_URL"));

// Checks if connection is successful
if (!$conn) {
    echo "Database connection failure";
} else {
    // Set up the SQL command to create the table if it does not exist
    $querycheck = "create table if not exists cabrequests"
        . "(bookingrefno varchar(10) NOT NULL UNIQUE,"
        . " username varchar(40),"
        . " contactno varchar(15),"
        . " address varchar(255),"
        . " destaddress varchar(40),"
        . " pickupdatetime DATETIME NOT NULL,"
        . " bookingdatetime DATETIME NOT NULL,"
        . " status varchar(40));";
    $createTable = pg_query($conn, $querycheck);

    // Action variable that will decide what the PHP will do
    $action = $_POST["action"];
    $viewQuery = "SELECT * FROM cabrequests;"; // Default View Query to be called for refreshing
    // Set up the SQL command to view the data from the table - Viewing All Cab Requests
    if ($action == "View") {
        viewTable($conn, $viewQuery);
    }
    // Set up the SQL command to view the data from the table - Viewing All Cab Requests based on the Search Range
    else if ($action == "ViewImmediate") {
        $hourRange = $_POST["hourRange"];
        $currentDateTime = date("Y-m-d H:i:s");
        $maxDateTime = date("Y-m-d H:i:s", strtotime(sprintf("+%d hours", $hourRange)));
        $viewImmediateQuery = "SELECT * FROM cabrequests WHERE pickupdatetime >= '$currentDateTime' AND pickupdatetime < '$maxDateTime' AND status='unassigned';";
        viewTable($conn, $viewImmediateQuery);
    }
    // Set up the SQL command to update the data from the table - Assigning a Cab
    else if ($action == "Assign") {
        $bookRefNo = $_POST["bookingrefno"];
        $assignQuery = "UPDATE cabrequests SET status='assigned' WHERE bookingrefno='$bookRefNo';";
        $updateTable = pg_query($conn, $assignQuery);

        if (!$updateTable) {
            echo "Something is wrong the assignment of cabs. " . $assignQuery . "." . pg_last_error($conn);
        } else {
            // Update Table in the Immediate Table View to the Client Side
            $currentDateTime = date("Y-m-d H:i:s");
            $maxDateTime = date("Y-m-d H:i:s", strtotime(sprintf("+%d hours", 2)));
            $viewImmediateQuery = "SELECT * FROM cabrequests WHERE pickupdatetime >= '$currentDateTime' AND pickupdatetime < '$maxDateTime' AND status='unassigned';";
            viewTable($conn, $viewImmediateQuery);
        }
    }
    // Set up the SQL command to delete a specific data from the table - Deleting A Cab Requests
    else if ($action == "Delete") {
        $bookRefNo = $_POST["bookingrefno"];
        $deleteQuery = "DELETE FROM cabrequests WHERE bookingrefno='$bookRefNo';";
        $deleteData = pg_query($conn, $deleteQuery);

        if (!$deleteData) {
            echo "Something is wrong with the deletion of data to the table. " . $deleteQuery . "." . pg_last_error($conn);
        } else {
            // Update Table in the Client Side
            viewTable($conn, $viewQuery);
        }
    }
    // Close the database connection
    pg_close($conn);
}

// Turns Associative Array into Indexed Array to be encoded in JSON
function toJSON($requests)
{
    $cabRequests = array();
    $index = 0;

    foreach ($requests as $key => $value) {
        if ($requests[$key] != null) {
            $cabRequests[$index] = $value;
            $index++;
        }
    }

    $json = json_encode($cabRequests);
    return $json;
}

// Function to be called to return to the client an updated version of the table
function viewTable($conn, $sql_query)
{
    $viewTable = pg_query($conn, $sql_query);
    if (!$viewTable) {
        echo "Something is wrong the viewing of cab request database. " . $sql_query . "." . pg_last_error($conn);
    }

    // Loop Through all the Rows as an Associative Array
    while ($row = pg_fetch_assoc($viewTable)) {
        $resultArray[] = $row;
    }
    echo toJSON($resultArray);
}
