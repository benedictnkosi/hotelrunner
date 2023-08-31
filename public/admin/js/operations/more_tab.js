$(document).ready(function () {
    //other tabs
    $('.filter-other-tabs').unbind('click')
    $(".filter-other-tabs").click(function (event) {
        event.stopImmediatePropagation();
        filterOtherTabs(event);
    });

    $('#refresh_more_tab_button').unbind('click')
    $("#refresh_more_tab_button").click(function (event) {
        loadMoreTabPageData();
    });



    //cleaning
    $("#cleaning_rooms_select").change(function(event) {
        getCleaning(event.target.value);
    });

    //occupancy

    $('#occupancy_days').click(function (event) {
        sessionStorage.setItem("occupancy_days", event.target.value )
    });

    $('#occupancy_days').blur(function (event) {
        const savedOccupancyDays = sessionStorage.getItem("occupancy_days");
        if(savedOccupancyDays.localeCompare($('#occupancy_days').val()) !== 0 &&  !isNaN($('#occupancy_days').val())){
            const days = $('#occupancy_days').val();
            getOverallOccupancy(days, "overall-30-occupancy");
            getOccupancyPerRoom(days);
        }
    });

    //whatsapp
    $("#formStartChat").submit(function(event) {
        event.preventDefault();
        window.open(
            "https://api.whatsapp.com/send?phone=+27 " + $('#whatapp_chat').val().replace('+27', '0') + "&text=Hello",
            '_blank' // <- This is what makes it open in a new window.
        );
    });

    let date = new Date();

    let endDate = new Date(date.getTime());
    getTotalIncome(date.getFullYear() + "-" +( date.getMonth() + 1) + "-" + date.getDate(),
        date.getFullYear() + "-" + (date.getMonth() + 1 )+ "-" + date.getDate());
    //date picker
    $.getScript("https://cdn.jsdelivr.net/momentjs/latest/moment.min.js", function () {
        $.getScript("https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js", function () {

            $('#cashReportDate').daterangepicker({
                startDate: date,
                endDate: endDate,
                opens: 'left',
                autoApply: true,
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: " to "
                },
            }, function (start, end, label) {
                console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
            });

            $('#cashReportDate').on('apply.daterangepicker', function (event, picker) {
                getTotalIncome(picker.startDate.format("YYYY-MM-DD"), picker.endDate.format("YYYY-MM-DD"));
                getCashTransactionsTable(picker.startDate.format("YYYY-MM-DD"), picker.endDate.format("YYYY-MM-DD"));
                sessionStorage.setItem("income_report_start_date",picker.startDate.format("YYYY-MM-DD") );
                sessionStorage.setItem("income_report_end_date",picker.endDate.format("YYYY-MM-DD") );
            });
        });
    });

    getCashTransactionsTable(date.getFullYear() + "-" +( date.getMonth() + 1) + "-" + date.getDate(),
        date.getFullYear() + "-" + (date.getMonth() + 1 )+ "-" + date.getDate());

    $("#select-payment-channel").change(function (event) {
        getCashTransactionsTable(sessionStorage.getItem("income_report_start_date"),
            sessionStorage.getItem("income_report_end_date"));
        getTotalIncome(sessionStorage.getItem("income_report_start_date"),
            sessionStorage.getItem("income_report_end_date"));
    });

    $("#groupPayments").change(function (event) {
        getCashTransactionsTable(sessionStorage.getItem("income_report_start_date"),
            sessionStorage.getItem("income_report_end_date"));
    });

});


function loadMoreTabPageData() {
    //blocked
    $("body").addClass("loading");
    getBlockedRooms();
    getBlockRooms();
    bindBlockedRoomsEvents();
    //cleaning
    getRooms("cleaning_rooms_select");
    //occupancy
    const d = new Date();
    let date = d.getDate();
    getOverallOccupancy("30", "overall-30-occupancy");
    getOverallOccupancy(date, "overall-month-occupancy");
    getOccupancyPerRoom("30");


    date = new Date();
    let endDate = new Date(date.getTime());
    getTotalIncome(date.getFullYear() + "-" +( date.getMonth() + 1) + "-" + date.getDate(),
        date.getFullYear() + "-" + (date.getMonth() + 1 )+ "-" + date.getDate());

    getCashTransactionsTable(date.getFullYear() + "-" +( date.getMonth() + 1) + "-" + date.getDate(),
        date.getFullYear() + "-" + (date.getMonth() + 1 )+ "-" + date.getDate());

    //date picker
    $.getScript("https://cdn.jsdelivr.net/momentjs/latest/moment.min.js", function () {
        $.getScript("https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js", function () {

            $('#cashReportDate').daterangepicker({
                startDate: firstDay,
                endDate: endDate,
                opens: 'left',
                autoApply: true,
                locale: {
                    format: 'YYYY-MM-DD',
                    separator: " to "
                },
            }, function (start, end, label) {
                console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
            });

            $('#cashReportDate').on('apply.daterangepicker', function (event, picker) {
                getTotalIncome(picker.startDate.format("YYYY-MM-DD"), picker.endDate.format("YYYY-MM-DD"));
                getCashTransactionsTable(picker.startDate.format("YYYY-MM-DD"), picker.endDate.format("YYYY-MM-DD"));
            });
        });
    });
}

function filterOtherTabs(event) {
    var id = event.currentTarget.id;
    $('.other_feature_tab').addClass("display-none");

    switch (id) {
        case "view_blocked_rooms_tab":
            $('#div-blocked-rooms').removeClass("display-none");
            break;
        case "block_a_room_tab":
            $('#div-block-room').removeClass("display-none");
            break;
        case "cleaning_tab":
            $('#div-cleaning').removeClass("display-none");
            break;
        case "whatsapp_tab":
            $('#div-whatapp-chat').removeClass("display-none");
            break;
        case "occupancy_tab":
            $('#div-occupancy').removeClass("display-none");
            break;
        case "cash_report_tab":
            $('#div-cash-report').removeClass("display-none");
            break;
        case "load_shedding_tab":
            $('#div-load-shedding').removeClass("display-none");
            break;
        default:
        // code block
    }
}


function bindBlockedRoomsEvents(){
    $("#block-form").validate({
        // Specify validation rules
        rules: {
            block_notes: "required"
        },
        // Specify validation error messages
        messages: {
            block_notes: "Please enter notes",
        }
    });

    $("#block-form").submit(function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        blockRoom();
    });
        $.getScript("https://cdn.jsdelivr.net/momentjs/latest/moment.min.js", function () {
            $.getScript("https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js", function () {
                const date = new Date();

                $('input[name="block_date"]').daterangepicker({
                    autoApply: true,
                    minDate: date,
                    autoUpdateInput: false,
                    locale: {
                        format: 'YYYY-MM-DD',
                        separator: " to "
                    },
                }, function (start, end, label) {
                    console.log("A new date selection was made: " + start.format('YYYY-MM-DD') + ' to ' + end.format('YYYY-MM-DD'));
                });

                $('input[name="block_date"]').on('apply.daterangepicker', function (event, picker) {
                    $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
                    sessionStorage.setItem('blockStartDate', picker.startDate.format("YYYY-MM-DD"));
                    sessionStorage.setItem('blockEndDate', picker.endDate.format("YYYY-MM-DD"));
                });
            });
        });

    $(".filter-block-room-tabs").click(function (event) {
        event.stopImmediatePropagation();
        filterBlockedRoomsTabs(event);
    });
}

function getBlockedRooms() {
    let url = "/api/blockedroom/get";
    isUserLoggedIn();
    $.ajax({
        type: "get",
        url: url,
        crossDomain: true,
        cache: false,
        dataType: "jsonp",
        contentType: "application/json; charset=UTF-8",
        success: function (data) {
            $("body").removeClass("loading");
            $("#block-list").html(data.html);
            $(".deleteBlockRoom").click(function (event) {
                event.stopImmediatePropagation();
                deleteBlockRoom(event);
            });
        },
        error: function (xhr) {
            $("body").removeClass("loading");
            console.log("request for getBlockedRooms is " + xhr.status);
            if (!isRetry("getBlockedRooms")) {
                return;
            }
            getBlockedRooms();
        }
    });
}

function deleteBlockRoom(event) {
    const id = event.target.id.replace("delete_blocked_", "");
    let url = "/api/blockedroom/delete/" + id;
    isUserLoggedIn();
    $.ajax({
        type: "get",
        url: url,
        crossDomain: true,
        cache: false,
        dataType: "jsonp",
        contentType: "application/json; charset=UTF-8",
        success: function (response) {
            const jsonObj = response[0];
            if (jsonObj.result_code === 0) {
                getCalendar("future");
                getBlockedRooms();
            }
        },
        error: function (xhr) {
            console.log("request for deleteBlockRoom is " + xhr.status);
            if (xhr.status > 400) {
                getOverallOccupancy(period, elementId)
            }
        }
    });
}

function filterBlockedRoomsTabs(event) {
    var id = event.currentTarget.id;
    $('.blocked_rooms_tab').addClass("display-none");

    switch (id) {
        case "view_blocked_rooms_tab":
            $('#block-list').removeClass("display-none");
            break;
        case "block_a_room_tab":
            $('#div-block-room').removeClass("display-none");
            break;
        default:
        // code block
    }
}

function blockRoom() {
    const block_room = $("#block_rooms_select").val();
    const block_note = $("#block_notes").val().trim();

    if (block_room.localeCompare("none") === 0) {
        showResErrorMessage("block", "Select Room")
        return;
    }

    if (block_note.length < 1) {
        showResErrorMessage("block", "Please provide notes")
        return;
    }

    $("body").addClass("loading");
    isUserLoggedIn();
    let url = "/api/blockroom";


    const data = {
        room: block_room,
        start_date: sessionStorage.getItem("blockStartDate"),
        end_date: sessionStorage.getItem("blockEndDate"),
        note: block_note
    };

    $.ajax({
        url : url,
        type: "POST",
        data : data,
        success: function(response)
        {
            $("body").removeClass("loading");
            if (response.result_code === 0) {
                showResSuccessMessage("block", response.result_message)
                getCalendar("future");
                getBlockedRooms();
            } else {
                showResErrorMessage("block", response.result_message);
            }
        },
        error: function (jqXHR, textStatus, errorThrown)
        {
            $("body").removeClass("loading");
            showResErrorMessage("block", errorThrown);
        }
    });

}

function getBlockRooms() {
    getRooms("block_rooms_select");
}

function getCleaning(room) {
    isUserLoggedIn();
    let url =  "/api/cleanings/"+room;
    $.ajax({
        type: "get",
        url: url,
        crossDomain: true,
        cache: false,
        dataType: "jsonp",
        contentType: "application/json; charset=UTF-8",
        success: function (data) {
            $("body").removeClass("loading");
            $("#cleaning-list").html(data.html);
        },
        error: function (xhr) {
            $("body").removeClass("loading");
            console.log("request for getCleaning  is " + xhr.status);
            if (!isRetry("getCleaning")) {
                return;
            }
            getCleaning(room);
        }
    });

}

function getOverallOccupancy(period, elementId) {
    let url = "/api/occupancy/" + period;
    isUserLoggedIn();
    $.ajax({
        type: "get",
        url: url,
        crossDomain: true,
        cache: false,
        dataType: "jsonp",
        contentType: "application/json; charset=UTF-8",
        success: function (data) {
            const jsonObj = data[0];
            if (jsonObj.result_code === 0) {
                $('#' + elementId).text(jsonObj.occupancy);
            }
        },
        error: function (xhr) {
            console.log("request for getOverallOccupancy is " + xhr.status);
            if (!isRetry("getOverallOccupancy")) {
                return;
            }
            getOverallOccupancy(period, elementId);
        }
    });

}

function getOccupancyPerRoomForMonth() {
    var myDate = new Date();
    var dayOfmonth = myDate.getDate();
    getOccupancyPerRoom(dayOfmonth)
}

function getOccupancyPerRoom(period) {
    isUserLoggedIn();
    let url = "/api/occupancy/perroom/" + period;
    $.ajax({
        type: "GET",
        url: url,
        processData: true,
        data: {},
        headers: {
            "Access-Control-Allow-Origin": "*",
            "Access-Control-Allow-Headers": "origin, content-type, accept"
        },
        dataType: "jsonp",
        success: function (data) {
            $("body").removeClass("loading");
            $("#occupancy-div").html(data);
        },
        error: function (xhr) {
            $("body").removeClass("loading");
            console.log("request for getOccupancyPerRoom is " + xhr.status);
            if (!isRetry("getOccupancyPerRoom")) {
                return;
            }
            getOccupancyPerRoom(period);
        }
    });
}

function getTotalIncome(startDate, endDate) {
    isUserLoggedIn();
    let url = "/api/payment/total/cash/" + startDate + "/" + endDate + "/" + $('#select-payment-channel').val();
    $.ajax({
        type: "GET",
        url: url,
        processData: true,
        data: {},
        headers: {
            "Access-Control-Allow-Origin": "*",
            "Access-Control-Allow-Headers": "origin, content-type, accept"
        },
        dataType: "jsonp",
        success: function (data) {
            $("body").removeClass("loading");
            $("#total_cash_amount_h1").text("R" + data[0].result_message);
        },
        error: function (xhr) {
            $("body").removeClass("loading");
            console.log("request for getTotalIncome is " + xhr.status);
            if (!isRetry("getTotalIncome")) {
                return;
            }
            getTotalIncome(startDate, endDate);
        }
    });
}

function getCashTransactionsTable(startDate, endDate) {
    isUserLoggedIn();
    $("body").addClass("loading");
    let isChecked = "false"
    if(document.getElementById('groupPayments').checked) {
        isChecked = "true";
    }

    let url =  "/api/payment/total/cashtransactions/" + startDate + "/" + endDate  + "/" + $('#select-payment-channel').val() + "/" + isChecked;
    $.ajax({
        type: "get",
        url: url,
        crossDomain: true,
        cache: false,
        dataType: "jsonp",
        contentType: "application/json; charset=UTF-8",
        success: function (data) {
            $("body").removeClass("loading");
            $("#cash-report-by-day").html(data.html);
        },
        error: function (xhr) {
            $("body").removeClass("loading");
            console.log("request for getCashTransactionsTable is " + xhr.status);
            if (!isRetry("getCashTransactionsTable")) {
                return;
            }
            getCashTransactionsTable();
        }
    });
}