function checkInput(base_uri) {

    var desc = document.getElementById('desc');

    $.post(base_uri,{text:desc.value}, function(data,status) {

        $("#length").html(data + "/1000");
        if(desc.value.length >= 1000) {

            alert('Text zu lang!');
            return false;

        } else return true;

    });

}

function checkAdditionalURL() {
    
    var link = document.getElementById('additional_url');

    if (link.value.toLowerCase().includes("discord")) {
        alert('Keine Discord-Einladungen!');
    }

}

function changeColor() {

    color = document.getElementById('color');
    color.style.backgroundColor = color.value;

}