var interval = null;

window.onload = function() {
    if (window.location.href.indexOf("employee_route_management") > -1) {
        interval = setInterval(callFunc, 10000);    
    }
};

function callFunc() {
       $(".refresh-data").trigger("click");
}