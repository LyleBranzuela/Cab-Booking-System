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
    echo "<p>Database connection failure</p>";
} else {
    $querycheck = "SELECT bookingRefNo FROM cabrequests";
    $checkResult = mysqli_query($conn, $querycheck);

    if (empty($checkResult)) {
        // Set up the SQL command to create the table if it does not exist
        $querycheck = "create table cabrequests"
            . "(bookingRefNo varchar(10) NOT NULL UNIQUE,"
            . " userName varchar(40),"
            . " contactNo varchar(15),"
            . " address varchar(255),"
            . " pickupTime TIME NOT NULL,"
            . " pickupDate DATE NOT NULL,"
            . " destAddress varchar(40),"
            . " status varchar(40));";
        $createTable = mysqli_query($conn, $querycheck);

        if (!$createTable) {
            echo "<script>alert(\"Something is wrong with creating the table ", $querycheck, "\")</script>";
            echo "<script>alert(\"Success\")</script>";
        } else {
            // display an operation successful message
            echo "<script>alert(\"Success\")</script>";
        } // if successful query operation
    }


    // If the action is set to "Book"
    $action = $_POST["action"];
    if ($action == "Book") {
        // Get all General Information
        $bookingRefNo = generateRandomID();
        $userName = $_POST["userName"];
        $contactNo = $_POST["contactNo"];

        // Get all Pickup Information
        $addressUnitNo = (isset($_POST["addressUnitNo"])) ? $_POST["addressUnitNo"] : "";
        $addressSuburb = $_POST["addressSuburb"];
        $address = combineAddress($addressUnitNo, $_POST["addressStreetNo"], $_POST["addressStreetName"], $addressSuburb);
        $pickupTime = $_POST["pickupTime"];
        $pickupDate = $_POST["pickupDate"];

        // Get all Destination Information
        $destUnitNo = (isset($_POST["destUnitNo"])) ? $_POST["destUnitNo"] : "";
        $destSuburb = $_POST["destSuburb"];
        $destAddress = combineAddress($destUnitNo, $_POST["destStreetNo"], $_POST["destStreetName"], $destSuburb);

        // Default Status (unassigned/assigned)
        $status = "unassigned";

        // Set up the SQL command to add the data into the table
        $query = "insert into $sql_tble"
            . "(bookingRefNo, userName, contactNo, address, destAddress, pickupTime, pickupDate, status)"
            . "values"
            . "('$bookingRefNo','$userName','$contactNo', '$address', '$destAddress', '$pickupTime', '$pickupDate', '$status')";

        // Executes the query
        $result = mysqli_query($conn, $query);
        // Checks if the execution was successful
        if (!$result) {
            echo "<script>alert(\"Something is wrong with creating the table\")</script>";
        } else {
            $cabBookRequest = array(
                "bookingRefNo" => $bookingRefNo,
                "userName" => $userName,
                "contactNo" => $contactNo,
                "address" => $address,
                "destAddress" => $destAddress,
                "pickupTime" => $pickupTime,
                "pickupDate" => $pickupDate,
                "status" => $status
            );
            echo(toJSON($cabBookRequest));
        } // If successful query operation
    }
}

// Converts the Cab Bookings Data into JSON to be sent back to the JS client-side
function toJSON($cabBookings)
{
    $json = json_encode($cabBookings);
    
    return $json;
}

function generateRandomID()
{
    // // Check if the bookingRefNo is Unique
    // $checkQuery = "SELECT COUNT(*) AS duplicates FROM $sql_tble WHERE statusCode = \"$statusCode\"";
    // $countResult = mysqli_query($conn, $checkQuery);
    // $duplicateAmount = mysqli_fetch_assoc($countResult);
    // $statusCodeUniqueValid = ($duplicateAmount['duplicates'] == 0); // Update Status Code Uniqueness Flag
    return "0002";
}

// Helper Function to combine Addresses into a single string (ex. 555 Kali Street, Jordan)
function combineAddress($unitNo, $streetNo, $streetName, $suburb)
{
    $unitNo = ($unitNo == "") ?  "" : $unitNo . ", ";
    $addressString = (string) $unitNo . $streetNo . " " . $streetName . ", " . $suburb;
    return $addressString;
}
