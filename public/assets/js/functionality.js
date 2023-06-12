$(document).ready(function () {
    getDefects();
    getFunctionality();
});

function getDefects() {
    let url = "/no_auth/getDefects";
    $.ajax({
        type: "get",
        url: url,
        crossDomain: true,
        cache: false,
        dataType: "jsonp",
        contentType: "application/json; charset=UTF-8",
        success: function (data) {
            $("#defects_div").html(data.html);
            $(".defect_checkbox").change(function(event) {
                let defect_id = event.target.getAttribute("data-id");
                if(this.checked) {
                    updateDefectEnabled(defect_id, 1);
                }else{
                    updateDefectEnabled(defect_id, 0);
                }
            });
        }
    });
}


function getFunctionality() {
    let url = "/no_auth/getFunctionality";
    $.ajax({
        type: "get",
        url: url,
        crossDomain: true,
        cache: false,
        dataType: "jsonp",
        contentType: "application/json; charset=UTF-8",
        success: function (data) {
            $("#functionality_div").html(data.html);
            $(".functionality_checkbox").change(function(event) {
                let functionality_id = event.target.getAttribute("data-id");
                if(this.checked) {
                    updateFunctionalityEnabled(functionality_id, 1);
                }else{
                    updateFunctionalityEnabled(functionality_id, 0);
                }
            });
        }
    });
}

function updateDefectEnabled(defectId, enabled){
    let url = "/no_auth/defect/update/" + defectId +"/"+ enabled;

    $.ajax({
        url : url,
        type: "PUT",
        success: function(response)
        {
            $("body").removeClass("loading");
            if (response.result_code === 0) {
                showResSuccessMessage("functionality", response.result_message);
            } else {
                showResErrorMessage("functionality", response.result_message);
            }
        },
        error: function (jqXHR, textStatus, errorThrown)
        {
            $("body").removeClass("loading");
            showResErrorMessage("functionality", errorThrown);
        }
    });

}


function updateFunctionalityEnabled(functionalityId, enabled){
    let url = "/no_auth/functionality/update/" + functionalityId +"/"+ enabled;

    $.ajax({
        url : url,
        type: "PUT",
        success: function(response)
        {
            $("body").removeClass("loading");
            if (response.result_code === 0) {
                showResSuccessMessage("functionality", response.result_message);
            } else {
                showResErrorMessage("functionality", response.result_message);
            }
        },
        error: function (jqXHR, textStatus, errorThrown)
        {
            $("body").removeClass("loading");
            showResErrorMessage("functionality", errorThrown);
        }
    });

}

