var xHRObject = false;
if (window.XMLHttpRequest) {
    xHRObject = new XMLHttpRequest();
}

// Set Current Date and Time on Opening
function updateClock() {
    date = new Date();

    // Time Set
    var h = (date.getHours() < 10) ? "0" + date.getHours() : date.getHours(),
        m = (date.getMinutes() < 10) ? "0" + date.getMinutes() : date.getMinutes(),
        s = (date.getSeconds() < 10) ? "0" + date.getSeconds() : date.getSeconds();
    var time = (h + ':' + m + ':' + s);
    document.getElementById("inputTime").value = time;

    // Year Set
    var y = date.getFullYear(),
        m = ((date.getMonth() + 1) < 10) ? "0" + (date.getMonth() + 1) : date.getMonth() + 1,
        d = (date.getDate() < 10) ? "0" + date.getDate() : date.getDate();
    var inputDate = y + "-" + m + "-" + d;
    document.getElementById("inputDate").value = inputDate;
}
updateClock();

// If they bypass the required attribute in HTML
function checkform(form) {
    var inputs = form.getElementsByTagName('input');
    for (var i = 0; i < inputs.length; i++) {
        // only validate the inputs that have the required attribute
        if (inputs[i].hasAttribute("required")) {
            if (inputs[i].value == "") {
                // found an empty field that is required
                alert("Please fill all required fields");
                return false;
            }
        }
    }

    // If the seconds is null / undefined from the inputted time
    let inputTime = document.getElementById("inputTime").value.split(":");
    if (inputTime[2] == null) {
        inputTime[2] = "00";
        document.getElementById("inputTime").value = (inputTime[0] + ':' + inputTime[1] + ':' + inputTime[2]);
    }

    // Check if the date and time from the form is less than the current time from the server
    let inputDate = document.getElementById("inputDate").value;
    let setDate = new Date(inputDate);
    setDate.setHours(inputTime[0], inputTime[1], inputTime[2]);
    let currentDate = new Date();
    if (setDate < currentDate) {
        alert("Date or Time is set below the current Date or Time of [" + currentDate + "].");
        return false;
    }

    // Send the Request if nothing fails
    sendCabRequest();
}

// A Function that creates a popup/modal when the request is finished.
function showPopupConfirmation() {
    if ((xHRObject.readyState == 4) && (xHRObject.status == 200)) {
        var serverResponse = xHRObject.responseText;
        if (serverResponse != null) {
            try {
                // Creating the Confirmation Popup Modal
                var bookings = JSON.parse(serverResponse);
                var bookingModal = document.getElementById("confirmationModal");
                var bookingModalContent = document.createElement("div");
                bookingModalContent = bookingModal.appendChild(bookingModalContent);

                // Reformatting Date to d/m/Y
                let splitDateTime = bookings.pickupdatetime.split(' '); // Splitting Y-m-d from H:i:s
                let reformattedDate = splitDateTime[0].split("-");
                reformattedDate = reformattedDate[2] + "/" + reformattedDate[1] + "/" + reformattedDate[0];

                // AM-PM Reformatted Time
                let reformattedTime = splitDateTime[1].split(":");
                if (reformattedTime[0] == 00) {
                    reformattedTime = 12 + ":" + reformattedTime[1] + ":" + reformattedTime[2] + " AM";
                } else if (reformattedTime[0] == 12) {
                    reformattedTime = reformattedTime[0] + ":" + reformattedTime[1] + ":" + reformattedTime[2] + " PM";
                } else if (reformattedTime[0] > 12) {
                    reformattedTime = (reformattedTime[0] - 12) + ":" + reformattedTime[1] + ":" + reformattedTime[2] + " PM";
                } else if (reformattedTime[0] < 12) {
                    reformattedTime = reformattedTime[0] + ":" + reformattedTime[1] + ":" + reformattedTime[2] + " AM";
                }

                // Create the modal popup
                bookingModalContent.innerHTML = `
                <!-- Modal -->
                <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-labelledby="modalCenterTitle" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered" role="document">
                    <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Booking Success!</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        Thank you! Your booking reference number is <strong>${bookings.bookingrefno}</strong>. You will be picked up in front of your provided address at 
                        <strong>${reformattedTime}</strong> on <strong>${reformattedDate}</strong>.
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-dark" data-dismiss="modal">Confirm</button>
                    </div>
                </div>
                </div>
                `;
                $("#confirmModal").modal('show');
                // Remove When Modal is Hidden instead of Hiding it
                $(document).on('hidden.bs.modal', '.modal', function() {
                    $("#confirmModal").remove();
                    $(".modal-dialog").remove();
                    bookingModalContent.remove();
                });
            } catch (e) {
                alert("Something went wrong with the parsing of data. " + e);
            }
        }
    }
}

// Sending cab request from the client to the server
function sendCabRequest() {
    var url = "ManageBooking.php";

    // Remove Extra Quotation marks in the JSON Stringify
    var serializedForm = JSON.stringify($('form').serialize()).replace(/\"/g, "");
    var params = 'action=Book&' + serializedForm + '&value=' + Number(new Date);
    xHRObject.open("POST", url, true);

    //Send the proper header information along with the request
    xHRObject.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xHRObject.onreadystatechange = showPopupConfirmation;
    xHRObject.send(params);
}