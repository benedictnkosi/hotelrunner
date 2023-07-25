$(document).ready(function () {
    $('.filter-reservations').unbind('click')
    $(".filter-reservations").click(function (event) {
        filterReservations(event);
    });

    $('#refresh_reservations_button').unbind('click')
    $("#refresh_reservations_button").click(function (event) {
        refreshReservations();
    });

    //upload reservations
    $("#upload_res_form").submit(function (event) {
        event.preventDefault();
    });

    $("#upload_res_form").validate({
        // Specify validation rules
        rules: {

        }, submitHandler: function () {
            uploadReservations();
        }

    });


});

function loadReservationsPageData() {
    refreshReservations();
    window.setTimeout(hideLoader, 5000);
}

function refreshReservations() {
    getPropertyUid();
    $("body").addClass("loading");
    getReservationsByPeriod("future");
    getReservationsByPeriod("pending");
    getReservationsByPeriod("past");
}

function getReservationsByPeriod(period) {
    isUserLoggedIn();
    let url = "/api/reservations/" + period;
    $.ajax({
        type: "get",
        url: url,
        crossDomain: true,
        cache: false,
        dataType: "jsonp",
        contentType: "application/json; charset=UTF-8",
        success: function (data) {
            $("body").removeClass("loading");
            $("#" + period + "-list").html(data.html);
            $('.open-reservation-details').unbind('click')
            $(".open-reservation-details").click(function (event) {
                event.stopImmediatePropagation();
                sessionStorage.setItem("reservation_id", event.target.getAttribute("data-res-id"));
                getReservationById(event.target.getAttribute("data-res-id"));
            });
        },
        error: function (xhr) {
            console.log("request for " + period + " is " + xhr.status);
            if (!isRetry("" + period + "")) {
                return;
            }
            getReservationsByPeriod(period);
        },
        done: function (xhr) {
            console.log("request for " + period + " is " + xhr.status);
        }
    });
}

function setBindings() {
    $('.changeBookingStatus').unbind('click')
    $(".changeBookingStatus").click(function (event) {
        event.stopImmediatePropagation();
        changeBookingStatus(event);
    });

    $.getScript("js/jquery.timepicker.min.js", function () {
        $('.time-picker').timepicker({
            'showDuration': false,
            'timeFormat': 'H:mm',
            change: function (time) {
                updateCheckInOutTime();
            }
        });
    });

    $.getScript("https://cdn.jsdelivr.net/momentjs/latest/moment.min.js", function () {
        $.getScript("https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js", function () {
            $('input[name="check_in_date"]').daterangepicker({
                opens: 'left',
                autoApply: true
            }, function (start, end, label) {
                console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
            });

            $('input[name="check_in_date"]').on('apply.daterangepicker', function (event, picker) {
                updateCheckInDate(event, picker.startDate.format('YYYY-MM-DD'), picker.endDate.format('YYYY-MM-DD'));
            });

            $('input[name="check_in_date"]').on('show.daterangepicker', function (event, picker) {
                sessionStorage.setItem('original_check_in_date', picker.startDate.format('MM/DD/YYYY'));
                sessionStorage.setItem('original_check_out_date', picker.endDate.format('MM/DD/YYYY'));
            });
        });

    });

    $('.reservation_room_input').unbind('change')
    $(".reservation_room_input").change(function (event) {
        event.stopImmediatePropagation();
        const roomId = $(this).find(':selected').val()
        updateReservationRoom(event, roomId)
    });

    $('.blockGuest').unbind('click')
    $(".blockGuest").click(function (event) {
        event.stopImmediatePropagation();
        blockGuest(event);
    });


    $('.NotCheckedIn').unbind('click')
    $(".NotCheckedIn").click(function (event) {
        event.stopImmediatePropagation();
        markReservationAsCheckedInOut(event, "checked_in");
    });

    $('.NotCheckedOut').unbind('click')
    $(".NotCheckedOut").click(function (event) {
        event.stopImmediatePropagation();
        markReservationAsCheckedInOut(event, "checked_out");
    });

    $('.res_add_payment').unbind('click')
    $(".res_add_payment").click(function (event) {
        event.stopImmediatePropagation();
        addPayment(event);
    });

    $('.res_add_discount').unbind('click')
    $(".res_add_discount").click(function (event) {
        event.stopImmediatePropagation();
        addDiscount(event);
    });

    $('.res_add_guest_phone').unbind('click')
    $(".res_add_guest_phone").click(function (event) {
        event.stopImmediatePropagation();
        addGuestPhone(event);
    });

    $('.res_add_guest_email').unbind('click')
    $(".res_add_guest_email").click(function (event) {
        event.stopImmediatePropagation();
        addGuestEmail(event);
    });

    $('.res_add_guest_id').unbind('click')
    $(".res_add_guest_id").click(function (event) {
        event.stopImmediatePropagation();
        addGuestID(event);
    });

    $('.res_add_note').unbind('click')
    $(".res_add_note").click(function (event) {
        event.stopImmediatePropagation();
        addNote(event);
    });

    $('.res_mark_cleaned').unbind('click')
    $(".res_mark_cleaned").click(function (event) {
        event.stopImmediatePropagation();
        addCleaning(event);
    });

    $('.res_block_guest').unbind('click')
    $(".res_block_guest").click(function (event) {
        event.stopImmediatePropagation();
        blockGuest(event);
    });

    $('.res_add_add_on').unbind('click')
    $(".res_add_add_on").click(function (event) {
        event.stopImmediatePropagation();
        addAddOn(event);
    });

    $('.delete_addon_link').unbind('click')
    $(".delete_addon_link").click(function (event) {
        event.stopImmediatePropagation();
        removeAddOnFromBooking(event);
    });

    $('.delete_payment_link').unbind('click')
    $(".delete_payment_link").click(function (event) {
        event.stopImmediatePropagation();
        removePayment(event);
    });

    $('.reservations_actions_link').unbind('click')
    $(".reservations_actions_link").click(function (event) {
        event.stopImmediatePropagation();
        showRightDivForMobile(event);
    });
}

function getReservationById(reservation_id) {
    sessionStorage.setItem("reservation_id", reservation_id);
    updateView('upcoming-reservations');
    $('.reservations_tabs').addClass("display-none");
    isUserLoggedIn();
    let url = "/api/reservation_html/" + reservation_id;
    $("body").addClass("loading");
    $.ajax({
        type: "get",
        url: url,
        crossDomain: true,
        cache: false,
        dataType: "jsonp",
        contentType: "application/json; charset=UTF-8",
        success: function (data) {
            $('#reservation-details')
                .html(data.html);
            $('#reservation-details')
                .removeClass("display-none");
            setBindings();
            $("body").removeClass("loading");
        },
        error: function (xhr) {
            $("body").removeClass("loading");
            console.log("request for getRooms is " + xhr.status);
            if (!isRetry("getReservationById")) {
                return;
            }
            getReservationById(reservation_id);
        }
    });
}

function showRightDivForMobile(event) {
    let reservationID = event.target.getAttribute("data-res-id");
    $('.right-div').css("display", "none");

    if (event.target.innerHTML.localeCompare("more...") === 0) {
        $('#right-div-' + reservationID).css("display", "block");
        event.target.innerHTML = "less...";
    } else {
        $('#right-div-' + reservationID).css("display", "none");
        event.target.innerHTML = "more...";
    }

}

function changeBookingStatus(event) {
    var data = {};
    var newButtonText = "";
    data["field"] = "status";

    var className = $('#' + event.target.id).attr('class');

    if (className.includes("glyphicon-triangle")) {
        data["reservation_id"] = event.target.id.replace("changeBookingStatus_", "");
    } else {
        data["reservation_id"] = event.target.id.replace("cancelBooking_", "");
    }

    let cancelAction = true;

    $("#dialog-confirm").removeClass("display-none");
    $("#dialog-confirm").dialog({
        resizable: false,
        height: "auto",
        width: 400,
        modal: true,
        buttons: {
            "I'm Sure": function () {
                $(this).dialog("close");
                if (className.includes("glyphicon-triangle-top")) {
                        data["new_value"] = "opened";
                        $('#' + event.target.id).toggleClass("glyphicon-triangle-top");
                        $('#' + event.target.id).toggleClass("glyphicon-triangle-bottom");
                } else if (className.includes("glyphicon-triangle-bottom")) {
                    data["new_value"] = "confirmed";
                    $('#' + event.target.id).toggleClass("glyphicon-triangle-top");
                    $('#' + event.target.id).toggleClass("glyphicon-triangle-bottom");
                } else if (className.includes("glyphicon-remove")) {
                    data["new_value"] = "cancelled";
                    $('#' + event.target.id).toggleClass("glyphicon-remove");
                    $('#' + event.target.id).toggleClass("glyphicon-ok");
                } else if (className.includes("glyphicon-ok")) {
                    data["new_value"] = "confirmed";
                    $('#' + event.target.id).toggleClass("glyphicon-remove");
                    $('#' + event.target.id).toggleClass("glyphicon-ok");
                }

                $("body").addClass("loading");
                isUserLoggedIn();
                let url = "/api/reservations/" + data["reservation_id"] + "/update/status/" + data["new_value"];

                $.ajax({
                    url: url,
                    type: "PUT",
                    data: "",
                    success: function (response) {
                        $("body").removeClass("loading");
                        if (response[0].result_code === 0) {
                            $("#" + event.target.id).val(newButtonText);
                            getCalendar("future");
                            refreshReservations();
                            showResSuccessMessage("reservation", response[0].result_message);
                        } else {

                            showResErrorMessage("reservation", response[0].result_message);
                        }
                    },
                    error: function (jqXHR, textStatus, errorThrown) {
                        $("body").removeClass("loading");
                        showResErrorMessage("reservation", errorThrown);
                    }
                });
            },
            Cancel: function () {
                $(this).dialog("close");
            }
        }
    });


}

function blockGuest(event) {
    const res_id = event.target.id.replace("block_guest_button_", "");
    const article = document.querySelector('#block_guest_button_' + res_id);
    if (!$("#block_note_" + article.dataset.resid).val()) {
        //hide other opened reservation inputs
        $(".reservation_input").addClass("display-none");
        $("#block_note_" + article.dataset.resid).removeClass("display-none");

    } else {
        const note = $("#block_note_" + article.dataset.resid).val();
        $("body").addClass("loading");
        isUserLoggedIn();
        let url = "/admin_api/reservations/" + res_id + "/blockguest/" + note;


        $.ajax({
            type: "PUT",
            url: url,
            data: "",
            contentType: "application/json; charset=UTF-8",
            success: function (data) {
                refreshReservations();
                $("body").removeClass("loading");
                showResSuccessMessage("reservation", data[0].result_message);
            },
            error: function (xhr) {
                $("body").removeClass("loading");
                if (xhr.status === 403) {
                    showResErrorMessage("reservation", "Unauthorised to use this function");
                } else {
                    showResErrorMessage("reservation", "Server Error");
                }
            }
        });
    }

}

function filterReservations(event) {
    var id = event.currentTarget.id;
    $('.reservations_tabs').addClass("display-none");

    switch (id) {
        case "reservations_all":
            $('#future-list').removeClass("display-none");
            $('#reservations-heading').text("Upcoming Reservations");
            break;
        case "reservations_past_reservations":
            $('#past-list').removeClass("display-none");
            $('#reservations-heading').text("Past Reservations");
            break;
        case "reservations_pending_reservations":
            $('#pending-list').removeClass("display-none");
            $('#reservations-heading').text("Pending Reservations");
            break;
        case "reservations_upload_reservations":
            $('#upload-div').removeClass("display-none");
            $('#reservations-heading').text("Upload Reservations");
            break;

        default:
        // code block
    }
}

function markReservationAsCheckedInOut(event, status) {
    let reservationID = event.target.getAttribute("reservation_id");

    isUserLoggedIn();
    isUserLoggedIn();
    if (status.localeCompare('checked_out') === 0) {
        if (confirm("Did you collect the key from the guest? Select OK if key collected from the guest") === false) {
            return;
        }
    }
    $("body").addClass("loading");
    let url = "/api/reservations/" + reservationID + "/update/check_in_status/" + status;

    $.ajax({
        url: url,
        type: "PUT",
        data: "",
        success: function (response) {
            $("body").removeClass("loading");
            if (response[0].result_code === 0) {
                refreshReservations();
                getReservationById(reservationID);
                showResSuccessMessage("reservation", response[0].result_message);
            } else {

                showResErrorMessage("reservation", response[0].result_message);
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $("body").removeClass("loading");
            showResErrorMessage("reservation", errorThrown);
        }
    });


}

function isEmail(email) {
    var regex = /^([a-zA-Z0-9_.+-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/;
    return regex.test(email);
}


function updateCheckInOutTime() {
    let checkinTime = $('.check_in_time_input').val();
    let checkOutTime = $('.check_out_time_input').val();

    $("body").addClass("loading");
    isUserLoggedIn();
    let url = "/api/reservations/" + sessionStorage.getItem("reservation_id") + "/update_checkin_time/" + checkinTime + "/" + checkOutTime;
    $.getJSON(url + "?callback=?", null, function (response) {
        $("body").removeClass("loading");
        var jsonObj = response[0];
        if (jsonObj.result_code === 0) {
            refreshReservations();
            showResSuccessMessage("reservation", response[0].result_message);
        } else {
            showResErrorMessage("reservation", response[0].result_message);
        }
    });
}

function updateCheckInDate(event, checkInDate, checkOutDate) {
    let reservationID = event.target.getAttribute("data-res-id");
    $("body").addClass("loading");
    isUserLoggedIn();
    let url = "/api/reservations/" + reservationID + "/update/dates/" + checkInDate + "/" + checkOutDate;

    $.ajax({
        url: url,
        type: "PUT",
        data: "",
        success: function (response) {
            $("body").removeClass("loading");
            if (response.result_code === 0) {
                refreshReservations();
                getReservationById(sessionStorage.getItem("reservation_id"));
                showResSuccessMessage("reservation", response.result_message);
            } else {
                refreshReservations();
                showResErrorMessage("reservation", response.result_message);
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $("body").removeClass("loading");
            showResErrorMessage("reservation", errorThrown);
        }
    });
}

function updateReservationRoom(event, roomId) {
    let reservationID = event.target.getAttribute("data-res-id");
    $("body").addClass("loading");
    isUserLoggedIn();
    let url = "/api/reservations/" + reservationID + "/update_room/" + roomId;

    $.ajax({
        url: url,
        type: "PUT",
        data: "",
        success: function (response) {
            $("body").removeClass("loading");
            if (response.result_code === 0) {
                getReservationById(sessionStorage.getItem("reservation_id"));
                refreshReservations();
                showResSuccessMessage("reservation", response.result_message);
            } else {
                refreshReservations();
                showResErrorMessage("reservation", response.result_message);
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $("body").removeClass("loading");
            showResErrorMessage("reservation", errorThrown);
        }
    });

}

function addCleaning(event) {
    const id = event.target.id.replace("mark_cleaned_button_", "");
    if ($("#div_mark_cleaned_" + id).hasClass("display-none")) {
        //hide other opened reservation inputs
        $(".reservation_input").addClass("display-none");
        $("#div_mark_cleaned_" + id).removeClass("display-none");
    } else {
        const employee_id = $("#select_employee_" + id).val();

        if (employee_id.localeCompare("none") === 0) {
            showResErrorMessage("reservation", "Please select cleaner");
            return;
        }
        isUserLoggedIn();
        $("body").addClass("loading");
        let url = "/api/cleaning/add";
        const data = {
            reservation_id: id,
            employee_id: employee_id
        };

        $.ajax({
            url: url,
            type: "POST",
            data: data,
            success: function (response) {
                $("body").removeClass("loading");
                if (response.result_code === 0) {
                    getReservationById(sessionStorage.getItem("reservation_id"));
                    showResSuccessMessage("reservation", response.result_message);
                    getRoomsNotCleaned();
                } else {
                    showResErrorMessage("reservation", response.result_message);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $("body").removeClass("loading");
                showResErrorMessage("reservation", errorThrown);
            }
        });

    }
}

function addAddOn(event) {
    console.log("addAddOn event fired");
    const id = event.target.id.replace("add_add_on_button_", "");
    if ($("#div_add_on_" + id).hasClass("display-none")) {
        //hide other opened reservation inputs
        $(".reservation_input").addClass("display-none");
        $("#div_add_on_" + id).removeClass("display-none");
    } else {
        const add_on_id = $("#select_add_on_" + id).val();
        const quantity = $("#add_on_quantity_" + id).val();

        if (add_on_id.localeCompare("none") === 0) {
            showResErrorMessage("reservation", "Please select an add on");
            return;
        }
        isUserLoggedIn();
        $("body").addClass("loading");
        let url = "/api/addon/" + add_on_id + "/reservation/" + id + "/quantity/" + quantity;
        $.getJSON(url + "?callback=?", null, function (response) {
            $("body").removeClass("loading");
            if (response[0].result_code === 0) {
                getReservationById(sessionStorage.getItem("reservation_id"));
                showResSuccessMessage("reservation", response[0].result_message);
            } else {
                showResErrorMessage("reservation", response[0].result_message);
            }
        });

    }
}


function removeAddOnFromBooking(event) {
    const add_on_id = event.target.getAttribute("data-addon-id");

    isUserLoggedIn();
    $("body").addClass("loading");
    let url = "/admin_api/reservation_addon/" + add_on_id + "/delete";

    $.ajax({
        type: "get",
        url: url,
        crossDomain: true,
        cache: false,
        dataType: "jsonp",
        contentType: "application/json; charset=UTF-8",
        success: function (data) {
            $("body").removeClass("loading");
            getReservationById(sessionStorage.getItem("reservation_id"));
            showResSuccessMessage("reservation", data[0].result_message);
        },
        error: function (xhr) {
            $("body").removeClass("loading");
            if (xhr.status === 403) {
                showResErrorMessage("reservation", "Unauthorised to use this function");
            } else {
                showResErrorMessage("reservation", "Server Error");
            }
        }
    });
}

function removePayment(event) {
    const payment_id = event.target.getAttribute("data-payment-id");

    isUserLoggedIn();
    $("body").addClass("loading");
    let url = "/admin_api/payment/" + payment_id + "/delete";

    $.ajax({
        url: url,
        type: "REMOVE",
        data: "",
        success: function (response) {
            $("body").removeClass("loading");
            if (response[0].result_code === 0) {
                getReservationById(sessionStorage.getItem("reservation_id"));
                showResSuccessMessage("reservation", response[0].result_message);
            } else {

                showResErrorMessage("reservation", response[0].result_message);
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $("body").removeClass("loading");
            showResErrorMessage("reservation", errorThrown);
        }
    });

}

function uploadReservations() {
    $("body").addClass("loading");
    isUserLoggedIn();
    let url = "/api/upload/reservations";
    const file_data = $("#reservations-fileUploader").prop("files")[0];
    const form_data = new FormData();
    form_data.append("file", file_data);

    if ( file_data === undefined ) {
        showResErrorMessage("reservation","Please upload file");
        return;
    }

    $.ajax({
        url: url,
        type: "POST",
        data:form_data ,
        dataType: 'script',
        cache: false,
        contentType: false,
        processData: false,
        success: function (response) {
            $("body").removeClass("loading");
            const arr = JSON.parse(response);
            let message = "";
            let isErrors = false;
            for (let i = 0; i < arr.length; i++){
                let z = i +1;
                message +=  arr[i]["result_message"] + "\n";
                if(arr[i]["result_code"] === 1){
                    isErrors = true;
                }
            }

            if (!isErrors) {
                refreshReservations();
                getCalendar();
                showResSuccessMessage("reservation",message);
            } else {
                showResErrorMessage("reservation",message);
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            $("body").removeClass("loading");
            showResErrorMessage("reservation", errorThrown);
        }
    });
}




function addGuestPhone(event) {
    const id = $('#guest_phone_input').attr("data-guestid");
    if (!$("#guest_phone_input").val()) {
        //hide other opened reservation inputs
        $(".reservation_input").addClass("display-none");
        $("#guest_phone_input").removeClass("display-none");
    } else {
        const phone = $('#guest_phone_input').val();
        $("body").addClass("loading");
        isUserLoggedIn();
        let url = "/api/guest/" + id + "/phone/" + phone;

        $.ajax({
            url: url,
            type: "PUT",
            data: "",
            success: function (response) {
                $("body").removeClass("loading");
                if (response[0].result_code === 0) {
                    getReservationById(sessionStorage.getItem("reservation_id"));
                    showResSuccessMessage("reservation", response[0].result_message);
                } else {

                    showResErrorMessage("reservation", response[0].result_message);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $("body").removeClass("loading");
                showResErrorMessage("reservation", errorThrown);
            }
        });

    }
}


function addGuestEmail(event) {
    const id = $('#guest_email_input').attr("data-guestid");
    if (!$("#guest_email_input").val()) {
        //hide other opened reservation inputs
        $(".reservation_input").addClass("display-none");
        $("#guest_email_input").removeClass("display-none");
    } else {
        const email = $('#guest_email_input').val();
        if (!isEmail(email)) {
            showResErrorMessage("reservation", 'Email address is invalid');
            return;
        }
        $("body").addClass("loading");
        isUserLoggedIn();
        let url = "/api/guest/" + id + "/email/" + email;
        $.ajax({
            url: url,
            type: "PUT",
            data: "",
            success: function (response) {
                $("body").removeClass("loading");
                if (response[0].result_code === 0) {
                    getReservationById(sessionStorage.getItem("reservation_id"));
                    showResSuccessMessage("reservation", response[0].result_message);
                } else {

                    showResErrorMessage("reservation", response[0].result_message);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $("body").removeClass("loading");
                showResErrorMessage("reservation", errorThrown);
            }
        });
    }
}


function addGuestID() {
    const id = $('#guest_id_input').attr("data-guestid");
    if (!$("#guest_id_input").val()) {
        //hide other opened reservation inputs
        $(".reservation_input").addClass("display-none");
        $("#guest_id_input").removeClass("display-none");
    } else {
        const idNumber = $('#guest_id_input').val();
        $("body").addClass("loading");
        isUserLoggedIn();
        let url = "/api/guest/" + id + "/idnumber/" + idNumber;
        $.ajax({
            url: url,
            type: "PUT",
            data: "",
            success: function (response) {
                $("body").removeClass("loading");
                if (response.result_code === 0) {
                    getReservationById(sessionStorage.getItem("reservation_id"));
                    showResSuccessMessage("reservation", response.result_message);
                } else {

                    showResErrorMessage("reservation", response.result_message);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $("body").removeClass("loading");
                showResErrorMessage("reservation", errorThrown);
            }
        });
    }
}

function addPayment(event) {

    const id = event.target.id.replace("add_payment_button_", "");
    const article = document.querySelector('#add_payment_button_' + id);
    if (!$("#amount_" + article.dataset.resid).val()) {
        //hide other opened reservation inputs
        $(".reservation_input").addClass("display-none");
        $("#div_payment").removeClass("display-none");
        $("#amount_" + article.dataset.resid).removeClass("display-none");
        $("#payment_reference_" + article.dataset.resid).removeClass("display-none");
    } else {
        const amount = $("#amount_" + article.dataset.resid).val();
        const paymentChannel = $("#select_payment_" + article.dataset.resid).val();
        let paymentReference = $("#payment_reference_" + article.dataset.resid).val();
        if (isNaN(amount)) {
            showResErrorMessage(reservation, "Please provide numbers only for payment");
            return;
        }
        if (paymentChannel.localeCompare("none") === 0) {
            showResErrorMessage("reservation", "Please select payment channel");
            return;
        }

        if (paymentChannel.localeCompare("card") === 0) {
            if (paymentReference.length !== 14
                || paymentReference.indexOf("/") !== 4
                || paymentReference.lastIndexOf("/") !== 7) {
                showResErrorMessage("reservation", "Yoco reference incorrect e.g 2023/01/000037");
                return;
            }
        }

        if (paymentChannel.localeCompare("transfer") === 0) {
            if (paymentReference.length < 1) {
                showResErrorMessage("reservation", "Payment reference incorrect e.g Sibusiso M");
                return;
            }
        }

        if (paymentChannel.localeCompare("card") !== 0) {
            if (paymentReference.length === 14
                & paymentReference.indexOf("/") === 4
                & paymentReference.lastIndexOf("/") === 7) {
                showResErrorMessage("reservation", "Looks like you are capturing a card reference for a " + paymentChannel + " payment");
                return;
            }
        }

        if (paymentChannel.localeCompare("cash") === 0) {
            if (paymentReference.length > 1) {
                showResErrorMessage("reservation", "Cash payments do not have a reference");
                return;
            }
            paymentReference = "cash";
        }

        isUserLoggedIn();
        $("body").addClass("loading");
        let url = "/api/payment/add";


        const data = {
            id: id,
            amount: amount,
            channel: paymentChannel,
            reference: paymentReference.replaceAll("/", "_")
        };

        $.ajax({
            url: url,
            type: "POST",
            data: data,
            success: function (response) {
                $("body").removeClass("loading");
                if (response[0].result_code === 0) {
                    refreshReservations();
                    getBlockedRooms();
                    getReservationById(sessionStorage.getItem("reservation_id"));
                    showResSuccessMessage("reservation", response[0].result_message);
                } else {
                    showResErrorMessage("reservation", response[0].result_message);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $("body").removeClass("loading");
                showResErrorMessage("reservation", errorThrown);
            }
        });

    }
}

function addDiscount(event) {

    const id = event.target.id.replace("add_discount_button_", "");
    const article = document.querySelector('#add_discount_button_' + id);
    if (!$("#discount_" + article.dataset.resid).val()) {
        //hide other opened reservation inputs
        $(".reservation_input").addClass("display-none");
        $("#discount_" + article.dataset.resid).removeClass("display-none");
    } else {
        const amount = $("#discount_" + article.dataset.resid).val();
        if (isNaN(amount)) {
            showResErrorMessage(reservation, "Please provide numbers only for discount");
            return;
        }
        isUserLoggedIn();
        $("body").addClass("loading");
        let url = "/api/discount/add";


        const data = {
            id: id,
            amount: amount
        };

        $.ajax({
            url: url,
            type: "POST",
            data: data,
            success: function (response) {
                $("body").removeClass("loading");
                if (response.result_code === 0) {
                    getReservationById(sessionStorage.getItem("reservation_id"));
                    showResSuccessMessage("reservation", response.result_message);
                } else {
                    showResErrorMessage("reservation", response.result_message);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $("body").removeClass("loading");
                showResErrorMessage("reservation", errorThrown);
            }
        });

    }
}

function addNote(event) {

    const id = event.target.id.replace("add_note_button_", "");
    const article = document.querySelector('#add_note_button_' + id);
    if (!$("#note_" + article.dataset.resid).val()) {
        //hide other opened reservation inputs
        $(".reservation_input").addClass("display-none");
        $("#note_" + article.dataset.resid).removeClass("display-none");
    } else {
        const note = $("#note_" + article.dataset.resid).val();
        $("body").addClass("loading");
        isUserLoggedIn();
        let url = "/api/note/add";
        const data = {
            id: id,
            note: note
        };

        $.ajax({
            url: url,
            type: "POST",
            data: data,
            success: function (response) {
                $("body").removeClass("loading");
                if (response.result_code === 0) {
                    getReservationById(sessionStorage.getItem("reservation_id"));
                    showResSuccessMessage("reservation", response.result_message);
                } else {
                    showResErrorMessage("reservation", response.result_message);
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                $("body").removeClass("loading");
                showResErrorMessage("reservation", errorThrown);
            }
        });


    }

}

function getRooms(id) {
    let url = "/no_auth/rooms/all" + "/";
    $.ajax({
        type: "get",
        url: url,
        crossDomain: true,
        cache: false,
        dataType: "jsonp",
        contentType: "application/json; charset=UTF-8",
        success: function (data) {
            $('#' + id)
                .find('option')
                .remove();

            $('#' + id).append($('<option/>').attr({
                "value": "none",
                "data-price": 0
            }).text("Select Room"));

            $.each(data, function (i, room) {
                $('#' + id).append($('<option/>').attr({
                    "value": room.id,
                    "data-price": room.price
                }).text(room.name));
            });
        },
        error: function (xhr) {
            console.log("request for getRooms is " + xhr.status);
            if (!isRetry("getRooms")) {
                return;
            }
            getRooms();
        }
    });
}

function getPropertyUid() {
    isUserLoggedIn();
    let url = "/api/property/propertyuid";
    $.ajax({
        type: "get",
        url: url,
        crossDomain: true,
        cache: false,
        dataType: "jsonp",
        contentType: "application/json; charset=UTF-8",
        success: function (data) {
            $('.new-reservation-link').attr("href", "/booking?uid=" + data[0].uid);
        },
        error: function (xhr) {
            console.log("request for getPropertyUid is " + xhr.status);
            if (!isRetry("getPropertyUid")) {
                return;
            }
            getPropertyUid();
        }
    });
}