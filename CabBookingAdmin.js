var xHRObject = false;
if (window.XMLHttpRequest) {
    xHRObject = new XMLHttpRequest();
}

// Displays All Cab Requests in the Database
function displayAllCabRequests() {
    if ((xHRObject.readyState == 4) && (xHRObject.status == 200)) {
        var serverResponse = xHRObject.responseText;
        if (serverResponse != null) {
            try {
                var bookings = JSON.parse(serverResponse);

                // Get The Request Table and Clear it
                const requestTable = document.getElementById("requestTable");
                requestTable.innerHTML = '';

                // Making The Table Body
                if (requestTable.firstChild != null) {
                    var badIEBody = requestTable.childNodes[0];
                    requestTable.removeChild(badIEBody);
                }
                var tBody = document.createElement("tbody");

                // Making The Headers
                var tHead = document.createElement("thead");
                tHead.className = "thead-dark";
                var headRow = document.createElement("tr");

                // Loop Through The Headers (bookingRefNo, userName, contactNo, address, pickupTime, pickupDate, destAddress, status, actions)
                var cabRequestKeys = ["#", "Booking Reference Number", "Booking Date Time", "Customer Name", "Contact Number", "Pickup Suburb", "Destination Suburb", "Pickup Datetime", "Status", "Actions"];
                for (var counter = 0; counter < cabRequestKeys.length; counter++) {
                    var header = document.createElement("th");
                    header.scope = "col";
                    header.appendChild(document.createTextNode(cabRequestKeys[counter]));
                    headRow.appendChild(header);
                }
                tHead.appendChild(headRow);
                requestTable.appendChild(tHead);

                // Making The Data Rows
                var requestCounter = 1;
                var expiredRequestCounter = 0;
                var completedRequestCounter = 0;
                bookings.forEach(cabRequest => {
                    var cabReqeuesttr = document.createElement("tr");

                    // Check if the date and time from the JSON data is expired (Less than the Current Time)
                    let splitDateTime = cabRequest.pickupdatetime.split(' '); // Splitting Y-m-d from H:i:s
                    let jsonDate = new Date(splitDateTime[0]);
                    let jsonTime = splitDateTime[1].split(":")
                    jsonDate.setHours(jsonTime[0], jsonTime[1], jsonTime[2]);
                    let currentDate = new Date();

                    // Change it to Green Background (table-danger from Bootstrap) to Show it has an assigned driver already
                    if (cabRequest.status == "assigned") {
                        cabReqeuesttr.className = "table-success";
                        completedRequestCounter++;
                    }
                    // Change it to Red Background (table-danger from Bootstrap) to Show It's expired (It's over the pickup date) without being assigned
                    else if (jsonDate < currentDate) {
                        cabReqeuesttr.className = "table-danger";
                        expiredRequestCounter++;
                    }

                    // Split the Suburb from the Address (The Suburb is always gonna be the last input from the Address)
                    let pickupSuburb = cabRequest.address.split(",");
                    pickupSuburb = pickupSuburb[pickupSuburb.length - 1];
                    let destSuburb = cabRequest.destaddress.split(",");
                    destSuburb = destSuburb[destSuburb.length - 1];

                    // Create the Row of Data
                    cabReqeuesttr.innerHTML = `
                    <th scope="row">${requestCounter}</th>
                    <td>${cabRequest.bookingrefno}</td>
                    <td>${cabRequest.bookingdatetime}</td>
                    <td>${cabRequest.username}</td>
                    <td>${cabRequest.contactno}</td>
                    <td>${pickupSuburb}</td>
                    <td>${destSuburb}</td>
                    <td>${cabRequest.pickupdatetime}</td>
                    <td>${cabRequest.status}</td>
                    `;

                    // Add The Actions based on what Table view is on
                    if (document.getElementById("tableTitle").innerHTML == "All Cab Requests") {
                        // Action: Delete Data
                        cabReqeuesttr.innerHTML += `
                    <td>
                        <button type="button" class="btn btn-danger" onclick=actionCabRequest(\"Delete\",\"${cabRequest.bookingrefno}\")><svg class="bi bi-trash" width="1em" height="1em" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                            <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                            <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4L4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/></svg>
                        </button>
                    </td>`;
                    } else {
                        // Action: Assign a Driver to Status Data
                        cabReqeuesttr.innerHTML += `
                    <td>
                        <button type="button" class="btn btn-success" onclick=actionCabRequest(\"Assign\",\"${cabRequest.bookingrefno}\") ${((cabRequest.status == "assigned") ? "disabled" : "")}>
                            Assign
                        </button>
                    </td>`;
                    }
                    requestCounter++;
                    tBody.appendChild(cabReqeuesttr);
                });

                // Adding the Captions to the Table and appending the Table Body back to the Table
                var captionElement = document.createElement("caption");
                captionElement.className = "ml-2";
                captionElement.innerHTML = (document.getElementById("tableTitle").innerHTML == "All Cab Requests") ?
                    (requestCounter - 1) + " cab request/s found [" + completedRequestCounter + " Completed and " + expiredRequestCounter + " Expired request/s]." :
                    (requestCounter - 1) + " cab request/s found.";
                requestTable.appendChild(captionElement);
                requestTable.appendChild(tBody);

            } catch (e) {
                alert("Something went wrong with the parsing of data. " + e);
            }
        }
    }
}


// Action Popup Info that pops up whenever one of the Actions is invoked (Actions so far: ["Assign", "Delete"])
function showActionPopupInfo(header, message) {
    var modalDiv = document.getElementById("actionPopupInfo");
    var modalContent = document.createElement("div");
    modalContent = modalDiv.appendChild(modalContent);

    // Create the modal popup
    modalContent.innerHTML = `
    <!-- Modal -->
    <div class="modal fade" id="actionModal" tabindex="-1" role="dialog" aria-labelledby="modalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="modalTitle">${header}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            ${message}
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-dark" data-dismiss="modal">Confirm</button>
        </div>
    </div>
    </div>
    `;
    $("#actionModal").modal('show');
    // Remove When Modal is Hidden instead of Hiding it
    $(document).on('hidden.bs.modal', '.modal', function() {
        $("#actionModal").remove();
        $(".modal-dialog").remove();
        modalContent.remove();
    });
}

// Function to View All The Requests 
function viewRequests(hourRange) {
    var url = "ManageBookingAdmin.php";

    // hourRange = 0 Means show all requests, else show a specific range from current time
    if (hourRange == 0) {
        document.getElementById("tableTitle").innerHTML = "All Cab Requests";
        var params = 'action=View&value=' + Number(new Date);
    } else {
        document.getElementById("tableTitle").innerHTML = "Cab Requests within " + hourRange + " hours from now";
        var params = 'action=ViewImmediate&hourRange=' + hourRange + '&value=' + Number(new Date);
    }
    xHRObject.open("POST", url, true);

    //Send the proper header information along with the request
    xHRObject.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xHRObject.onreadystatechange = displayAllCabRequests;
    xHRObject.send(params);
}

// Function to Assign / Delete a Cab to a Specific Booking Reference Number
function actionCabRequest(action, bookingRefNo) {
    var url = "ManageBookingAdmin.php";

    // Set the Action Parameter (Actions so far: ["Assign", "Delete"])
    var params = 'action=' + action + '&bookingRefNo=' + bookingRefNo + '&value=' + Number(new Date);
    xHRObject.open("POST", url, true);

    //Send the proper header information along with the request
    xHRObject.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xHRObject.send(params);

    // Send the Action Popup Info 
    switch (action) {
        case "Assign":
            showActionPopupInfo("Assignment of Cab", "The booking request <strong>" + bookingRefNo + "</strong> has been properly assigned.");
            break;

        case "Delete":
            showActionPopupInfo("Deletion of Data", "The booking request <strong>" + bookingRefNo + "</strong> has been properly removed.");
            break;

        default:
            break;
    }
}