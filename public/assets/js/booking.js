$(document).ready(function () {

    sessionStorage.setItem("property_uid", getUrlParameter('uid'))
    getPropertyName();
    $("#new-res-form").submit(function (event) {
        event.preventDefault();
    });

    $("#new-res-form").validate({
        // Specify validation rules
        rules: {
            guestName: "required",
            phoneNumber: "required",
            email: {
                required: false,
                email: true
            }
        },
        submitHandler: function () {
            createReservation();
        }
    });

    $("#phoneNumber").blur(function (event) {
        if(event.target.value.length > 3 && $("#guestName").val().length < 1){
            getCustomer();
        }
    });

    let minDate = new Date();
    let date = new Date();
    let endDate = new Date(date.getTime());
    //get available rooms for today if previous date not set
    if (sessionStorage.getItem("checkInDate") == null) {
        endDate.setDate(date.getDate() + 1);
        const strToday = date.getFullYear() + "-" + (date.getMonth() + 1) + "-" + date.getDate();
        const strTomorrow = endDate.getFullYear() + "-" + (endDate.getMonth() + 1) + "-" + endDate.getDate();
        sessionStorage.setItem('checkInDate', strToday);
        sessionStorage.setItem('checkOutDate', strTomorrow);
        getAvailableRooms(strToday, strTomorrow);
    } else {
        date = new Date(sessionStorage.getItem('checkInDate'));
        endDate = new Date(sessionStorage.getItem('checkOutDate'));
        $(this).val(sessionStorage.getItem('checkInDate') + ' - ' + sessionStorage.getItem('checkOutDate'));
        getAvailableRooms(sessionStorage.getItem('checkInDate'), sessionStorage.getItem('checkOutDate'));
    }

    $("#children").change(function () {
        getAvailableRooms(sessionStorage.getItem('checkInDate'), sessionStorage.getItem('checkOutDate'));
    });

    //date picker
    $.getScript("https://cdn.jsdelivr.net/momentjs/latest/moment.min.js", function () {
        $.getScript("https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js", function () {

            $('#checkindate').daterangepicker({
                startDate: date,
                endDate: endDate,
                opens: 'left',
                autoApply: true,
                minDate: minDate
            }, function (start, end, label) {
                console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
            });

            $('#checkindate').on('apply.daterangepicker', function (event, picker) {
                hideMessages("reservation");
                let checkInDate = new Date(picker.startDate.format("YYYY-MM-DD"));
                let checkOutDate = new Date(picker.endDate.format("YYYY-MM-DD"))
                let difference = checkOutDate - checkInDate;
                let totalDays = Math.ceil(difference / (1000 * 3600 * 24));
                if (totalDays < 1) {
                    showResErrorMessage("reservation", "Check-in and check-out date can not be the same");
                    return;
                }

                getAvailableRooms(picker.startDate.format("YYYY-MM-DD"), picker.endDate.format("YYYY-MM-DD"));
                sessionStorage.setItem('checkInDate', picker.startDate.format("YYYY-MM-DD"));
                sessionStorage.setItem('checkOutDate', picker.endDate.format("YYYY-MM-DD"));



                console.log("date diff is " + totalDays);
                sessionStorage.setItem('numberOfNights', totalDays);
            });
        });
    });

    showBackToReservationsLink();
});

function displayTotal() {
    let numberOfNights = parseInt(sessionStorage.getItem('numberOfNights'));
    let total = 0;
    let totalRooms = 0;
    let totalSleeps = 0;
    let nightsMessage = "";
    let roomIdArray = [];
    //find all rooms added
    var buttons = document.getElementsByTagName("button");
    let roomSleeps;
    let roomPrice;
    let roomName;
    let roomId;
    for (var i = 0; i < buttons.length; i++) {
        if (buttons[i].textContent === "Remove") {
            roomId = buttons[i].getAttribute("data-roomId");
            roomName = buttons[i].getAttribute("data-roomName");
            roomPrice = buttons[i].getAttribute("data-roomPrice");
            roomSleeps = buttons[i].getAttribute("data-sleeps");
            total += (numberOfNights * parseInt(roomPrice));
            totalRooms++;
            totalSleeps += (parseInt(roomSleeps));
            sessionStorage.setItem("rooms_sleep", totalSleeps);
            sessionStorage.setItem("total_rooms_selected", totalRooms);
            nightsMessage += roomName + " - " + numberOfNights + " x nights @ R" + roomPrice + ".00" + "<br>";
            roomIdArray.push(roomId);
        }
    }

    if (total > 0) {
        sessionStorage.setItem("isRoomSelected", "yes");
    } else {
        sessionStorage.removeItem("isRoomSelected");
    }
    sessionStorage.setItem("selected_rooms_array", JSON.stringify(roomIdArray));

    let totalMessage = "Total: R" + total + ".00";

    sessionStorage.setItem("item_description", nightsMessage);
    sessionStorage.setItem("item_amount", total.toString());

    $('#total_message').html(totalMessage);
    $('#nights_message').html(nightsMessage);
}

function getAvailableRooms(checkInDate, checkOutDate) {
    $("body").addClass("loading");
    let kids = $('#children').val();
    let url = "/no_auth/availablerooms/" + checkInDate + "/" + checkOutDate + "/" + sessionStorage.getItem("property_uid") + "/" + kids;
    $.getJSON(url + "?callback=?", null, function (data) {
        let roomIndex;
        $("#availableRoomsDropdown").html(data.html);
        const roomArray = [];
        const roomIdsArray = [];
        let room_id = "";
        $('.vodiapicker option').each(function () {
            const img = $(this).attr("data-thumbnail");
            const price = $(this).attr("data-price");
            const room_id = $(this).attr("data-roomId");
            const sleeps = $(this).attr("data-sleeps");
            const beds = $(this).attr("data-beds");
            const room_name = this.innerText;

            const bedArray = beds.split(",");
            let bedshtml = "";

            if (beds.length !== 0) {
                bedArray.forEach(bed => bedshtml += '<span class="fa fa-bed">' + bed + '</span>');
            }

            if (price.localeCompare("0") === 0) {
                var item = '<li><img src="' + img + '" data-price="' + price + '" data-roomId="' + room_id + '" data-roomName="' + room_name + '"/><div class="div-select-room-name">' + room_name + '<div class="select_sleeps"><span>ZAR ' + price + '</span><span class="fa fa-users">' + sleeps + ' Guests</span>' + bedshtml + '</div></div>' +
                    '</li>';
            } else {
                if (bedshtml.length > 1) {
                    var item = '<li>' +
                        '<div class="div-select-room-name">' +
                        '<img src="' + img + '" data-price="' + price + '" data-roomId="' + room_id + '" data-roomName="' + room_name + '"/>' + room_name + '<div class="select_sleeps"><span>ZAR ' + price + '</span><span class="fa fa-users">' + sleeps + ' Guests</span>' + bedshtml + '</div><button class="btn btn-style btn-secondary book mt-3 add-room-button" data-sleeps="' + sleeps + '" data-roomId="' + room_id + '" data-roomName="' + room_name + '" data-roomPrice="' + price + '">Add</button>' +
                        '</div>' +
                        '</li>';
                } else {
                    var item = '<li>' +
                        '<div class="div-select-room-name">' +
                        '<img class="no_beds_image" src="' + img + '" data-price="' + price + '" data-roomId="' + room_id + '" data-roomName="' + room_name + '"/>' + room_name + '<div class="select_sleeps"><span>ZAR ' + price + '</span><span class="fa fa-users">' + sleeps + ' Guests</span>' + bedshtml + '</div>' +
                        '<button class="btn btn-style btn-secondary book mt-3 add-room-button" data-sleeps="' + sleeps + '" data-roomId="' + room_id + '" data-roomName="' + room_name + '" data-roomPrice="' + price + '">Add</button>' +
                        '</div>' +
                        '</li>';
                }

            }

            roomArray.push(item);
            roomIdsArray.push(room_id);
        })

        $('#a').html(roomArray);

//Set the button value to the first el of the array

        $(".b").css("display", "block");

        //check local storage for the lang
        const roomId = getUrlParameter("id");
        if (roomId) {
            //find an item with value of roomId
            roomIndex = roomIdsArray.indexOf(roomId);
            $('.btn-select').html(roomArray[roomIndex]);
            $('.btn-select').attr('value', roomId);
        } else {
            roomIndex = roomArray.indexOf('ch');
            console.log(roomIndex);
            $('.btn-select').html(roomArray[roomIndex]);
            $('.btn-select').attr('value', roomId);
        }
        let checkInDateDate = new Date(checkInDate);
        let checkOutDateDate = new Date(checkOutDate)
        let difference = checkOutDateDate - checkInDateDate;
        let totalDays = Math.ceil(difference / (1000 * 3600 * 24));
        console.log("date diff is " + totalDays);
        sessionStorage.setItem('numberOfNights', totalDays);

        if (room_id.localeCompare("0") !== 0) {
            displayTotal();
        }

        $(".add-room-button").click(function (event) {
            event.preventDefault();
            if ($(this).text().localeCompare("Remove") === 0) {
                $(this).text("Add");
            } else {
                $(this).text("Remove");
            }

            displayTotal();
        });


        $("body").removeClass("loading");
    });
}

function createReservation() {
    let isRoomSelected;
    $("#reservation_error_message_div").addClass("display-none");

    let today = new Date();
    const dd = String(today.getDate()).padStart(2, '0');
    const mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
    const yyyy = today.getFullYear();

    today = yyyy + "-" + mm + '-' + dd;
    const guestName = $('#guestName').val();
    const phoneNumber = $('#phoneNumber').val().trim().replaceAll(" ", "");
    const email = $('#email').val();
    const adultGuests = $('#adults').val();
    const childGuests = $('#children').val();
    const smoker = $('#select_smoker').find(":selected").val();
    const checkInDate = sessionStorage.getItem('checkInDate');
    const checkOutDate = sessionStorage.getItem('checkOutDate');

    isRoomSelected = sessionStorage.getItem("isRoomSelected");

    let guests = parseInt($('#adults').val()) + parseInt($('#children').val());
    if(guests > sessionStorage.getItem('rooms_sleep')){
        showResErrorMessage("reservation", "The selected rooms can not accommodate the number of guests");
        return;
    }

    if(guests < parseInt(sessionStorage.getItem("total_rooms_selected"))){
        showResErrorMessage("reservation", "The number of guests can not be less than the number of rooms booked");
        return;
    }


    if (isRoomSelected === null) {
        showResErrorMessage("reservation", "Please select a room");
        return;
    }

    if(checkInDate === checkOutDate){
        showResErrorMessage("reservation", "Checkin date can not be the same as check-out date");
        return;
    }

    $("body").addClass("loading");


    let url = "/no_auth/reservations/create";

    const data = {
        room_ids: sessionStorage.getItem("selected_rooms_array"),
        name: guestName,
        phone_number: phoneNumber,
        adult_guests: adultGuests,
        child_guests: childGuests,
        check_in_date: checkInDate,
        check_out_date: checkOutDate,
        email: email,
        date: today,
        smoking: smoker
    };

    $.ajax({
        url : url,
        type: "POST",
        data : data,
        success: function(response)
        {
            $("body").removeClass("loading");
            if (response[0].result_code === 0) {
                showResSuccessMessage("reservation", response[0].result_message);
                sessionStorage.setItem("reservation_id", JSON.stringify(data[0].reservation_id));
                window.location.href = "/confirmation";
            } else {
                showResErrorMessage("reservation", response[0].result_message);
            }
        },
        error: function (jqXHR, textStatus, errorThrown)
        {
            $("body").removeClass("loading");
            showResErrorMessage("reservation", errorThrown);
        }
    });


}

function getPropertyName() {
    let url = "no_auth/property_details/" + getUrlParameter("uid");
    $.ajax({
        type: "get",
        url: url,
        crossDomain: true,
        cache: false,
        dataType: "jsonp",
        contentType: "application/json; charset=UTF-8",
        success: function (response) {
            sessionStorage.setItem("PropertyName", response[0].name)
            $('#hotel_name').html(response[0].name);
        }
    });

}


function getCustomer() {
    if(sessionStorage.getItem('authenticated').localeCompare('true') === 0) {
        $("#phoneNumber").val($("#phoneNumber").val().replaceAll(" ", ""));
        $("body").addClass("loading");
        let url = "/api/guests/" + $("#phoneNumber").val();
        $.ajax({
            type: "get",
            url: url,
            crossDomain: true,
            cache: false,
            dataType: "jsonp",
            contentType: "application/json; charset=UTF-8",
            success: function (response) {
                $("body").removeClass("loading");
                if (response[0].result_code === 0) {
                    $('#guestName').val(response[0].name);
                    $('#email').val(response[0].email);
                }
            },
            done: function (response) {
                $("body").removeClass("loading");
            }
        });
    }
}