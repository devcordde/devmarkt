function checkInput(base_uri, max_size) {

    var desc = document.getElementById('desc');

    var http = new XMLHttpRequest();
    var params = 'text=' + desc.value;

    http.open('POST', base_uri, true);
    http.setRequestHeader('Content-type', 'application/x-www-form-urlencoded');

    http.onreadystatechange = function() {
        if(http.readyState === 4 && http.status === 200) {

            document.getElementById('length').innerText = this.responseText + "/" + max_size;
            console.log(max_size);
            console.log(this.responseText);
            if (this.responseText > max_size) {

                alert('Text zu lang!');
                return false;

            } else return true;
        }
    }
    http.send(params);

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