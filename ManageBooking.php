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
    $querycheck = "SELECT bookingRefNo FROM cabrequests";
    $checkResult = pg_query($conn, $querycheck);

    // Set up the SQL command to create the table if it does not exist
    if (empty($checkResult)) {
        $querycheck = "create table cabrequests"
            . "(bookingRefNo varchar(10) NOT NULL UNIQUE,"
            . " userName varchar(40),"
            . " contactNo varchar(15),"
            . " address varchar(255),"
            . " destAddress varchar(40),"
            . " pickupDateTime DATETIME NOT NULL,"
            . " bookingDateTime DATETIME NOT NULL,"
            . " status varchar(40));";
        $createTable = pg_query($conn, $querycheck);

        if (!$createTable) {
            echo "Something is wrong with creating the table ", $querycheck, ".";
        }
    }

    // If the action is set to "Book"
    $action = $_POST["action"];
    if ($action == "Book") {
        // Get all General Information
        $bookingRefNo = generateRandomID($conn, "cabrequests");
        $userName = $_POST["userName"];
        $contactNo = $_POST["contactNo"];

        // Get all Pickup Information
        $addressUnitNo = (isset($_POST["addressUnitNo"])) ? $_POST["addressUnitNo"] : "";
        $addressSuburb = $_POST["addressSuburb"];
        $address = combineAddress($addressUnitNo, $_POST["addressStreetNo"], $_POST["addressStreetName"], $addressSuburb);
        $pickupTime = $_POST["pickupTime"];
        $pickupDate = $_POST["pickupDate"];
        $pickupDateTime = $pickupDate . ' ' . $pickupTime;

        // Get all Destination Information
        $destUnitNo = (isset($_POST["destUnitNo"])) ? $_POST["destUnitNo"] : "";
        $destSuburb = $_POST["destSuburb"];
        $destAddress = combineAddress($destUnitNo, $_POST["destStreetNo"], $_POST["destStreetName"], $destSuburb);

        // Default Status (unassigned/assigned) - And Booking DateTime 
        $bookingDateTime = date("Y-m-d H:i:s");
        $status = "unassigned";

        // Set up the SQL command to add the data into the table
        $query = "insert into cabrequests"
            . "(bookingRefNo, userName, contactNo, address, destAddress, pickupDateTime, bookingDateTime, status)"
            . "values"
            . "('$bookingRefNo','$userName','$contactNo', '$address', '$destAddress', '$pickupDateTime', '$bookingDateTime', '$status')";

        // Executes the query
        $result = pg_query($conn, $query);
        // Checks if the execution was successful
        if (!$result) {
            echo "Something is wrong with inserting data into the table";
        } else {
            $cabBookRequest = array(
                "bookingRefNo" => $bookingRefNo,
                "userName" => $userName,
                "contactNo" => $contactNo,
                "address" => $address,
                "destAddress" => $destAddress,
                "pickupDateTime" => $pickupDateTime,
                "bookingDateTime" => $bookingDateTime,
                "status" => $status
            );
            echo (toJSON($cabBookRequest));
        } // If successful query operation
    }

    // Close the database connection
    pg_close($conn);
}

// Converts the Cab Bookings Data into JSON to be sent back to the JS client-side
function toJSON($cabBookings)
{
    $json = json_encode($cabBookings);

    return $json;
}

// Generate Book Ref No (Ranges 0000-9999)
function generateRandomID($connection, $sql_table)
{
    $generatedBookRefNo = "";

    // Check if the bookingRefNo is Unique
    do {
        for ($counter = 0; $counter < 5; $counter++) {
            $generatedBookRefNo .= mt_rand(0, 9);
        }

        // Check if it already exists and loop through if it does
        $checkQuery = "SELECT COUNT(*) AS duplicates FROM $sql_table WHERE bookingRefNo = \"$generatedBookRefNo\"";
        $countResult = pg_query($connection, $checkQuery);
        $duplicateAmount = pg_fetch_assoc($countResult);
    } while ($duplicateAmount['duplicates'] != 0);

    return $generatedBookRefNo;
}

// Helper Function to combine Addresses into a single string (ex. 555 Kali Street, Jordan)
function combineAddress($unitNo, $streetNo, $streetName, $suburb)
{
    $unitNo = ($unitNo == "") ?  "" : $unitNo . ", ";
    $addressString = (string) $unitNo . $streetNo . " " . $streetName . ", " . $suburb;
    return $addressString;
}
