<?php
require_once('../../conf/assign2sqlinfo.inc.php');

// mysqli_connect returns false if connection failed, otherwise a connection value
$conn = @mysqli_connect(
    $sql_host,
    $sql_user,
    $sql_pass,
    $sql_db
);

// Checks if connection is successful
if (!$conn) {
    echo "Database connection failure";
} else {
    $querycheck = "SELECT bookingRefNo FROM cabrequests";
    $checkResult = mysqli_query($conn, $querycheck);

    // Set up the SQL command to create the table if it does not exist
    if (empty($checkResult)) {
        $querycheck = "create table cabrequests"
            . "(bookingRefNo varchar(10) NOT NULL UNIQUE,"
            . " userName varchar(40),"
            . " contactNo varchar(15),"
            . " address varchar(255),"
            . " pickupDateTime DATETIME NOT NULL,"
            . " destAddress varchar(40),"
            . " bookingDateTime DATETIME NOT NULL,"
            . " status varchar(40));";
        $createTable = mysqli_query($conn, $querycheck);

        if (!$createTable) {
            echo "Something is wrong with creating the table ", $querycheck, ".";
        } 
    }

    // Action variable that will decide what the PHP will do
    $action = $_POST["action"];
    $viewQuery = "SELECT * FROM $sql_tble;"; // Default View Query to be called for refreshing
    // Set up the SQL command to view the data from the table - Viewing All Cab Requests
    if ($action == "View")
    {
        viewTable($conn, $viewQuery);
    }
    // Set up the SQL command to view the data from the table - Viewing All Cab Requests based on the Search Range
    else if ($action == "ViewImmediate")
    {
        $hourRange = $_POST["hourRange"];
        $currentDateTime = date("Y-m-d H:i:s");
        $maxDateTime = date("Y-m-d H:i:s", strtotime(sprintf("+%d hours", $hourRange)));
        $viewImmediateQuery = "SELECT * FROM $sql_tble WHERE pickupDateTime >= '$currentDateTime' AND pickupDateTime < '$maxDateTime' AND status='unassigned';";
        viewTable($conn, $viewImmediateQuery);
    }
    // Set up the SQL command to update the data from the table - Assigning a Cab
    else if ($action == "Assign")
    {
        $bookRefNo = $_POST["bookingRefNo"];
        $assignQuery = "UPDATE $sql_tble SET status='assigned' WHERE bookingRefNo='$bookRefNo';";
        $updateTable = mysqli_query($conn, $assignQuery);
        
        if (!$updateTable) {
            echo "Something is wrong the assignment of cabs. " . $assignQuery . "." . mysqli_error($conn);
        }
        else {
            // Update Table in the Immediate Table View to the Client Side
            $currentDateTime = date("Y-m-d H:i:s");
            $maxDateTime = date("Y-m-d H:i:s", strtotime(sprintf("+%d hours", 2)));
            $viewImmediateQuery = "SELECT * FROM $sql_tble WHERE pickupDateTime >= '$currentDateTime' AND pickupDateTime < '$maxDateTime' AND status='unassigned';";
            viewTable($conn, $viewImmediateQuery);
        }
    }
    // Set up the SQL command to delete a specific data from the table - Deleting A Cab Requests
    else if ($action == "Delete")
    {
        $bookRefNo = $_POST["bookingRefNo"];
        $deleteQuery = "DELETE FROM $sql_tble WHERE bookingRefNo='$bookRefNo';";
        $deleteData = mysqli_query($conn, $deleteQuery);
        
        if (!$deleteData) {
            echo "Something is wrong with the deletion of data to the table. " . $deleteQuery . "." . mysqli_error($conn);
        }
        else {
            // Update Table in the Client Side
            viewTable($conn, $viewQuery);
        }
    }   
    // Close the database connection
    mysqli_close($conn);
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
function viewTable($conn, $sql_query) {
    $viewTable = mysqli_query($conn, $sql_query);
    if (!$viewTable) {
        echo "Something is wrong the viewing of cab request database. " . $sql_query . "." . mysqli_error($conn);
    }

    // Loop Through all the Rows as an Associative Array
    while ($row = mysqli_fetch_assoc($viewTable)){
        $resultArray[] = $row;
    }
    echo toJSON($resultArray);
}
