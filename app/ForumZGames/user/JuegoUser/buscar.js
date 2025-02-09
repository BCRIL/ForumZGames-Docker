// Funcion para buscar juegos segun el texto introducido
function buscarJuegos() {
    var query = document.getElementById("search-bar").value;

    if (query.length > 0) {
        var xhr = new XMLHttpRequest();
        xhr.open("GET", "buscar_juegos.php?q=" + query, true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                document.getElementById("resultados-busqueda").innerHTML = xhr.responseText;
            }
        };
        xhr.send();
    } else {
        document.getElementById("resultados-busqueda").innerHTML = "";
    }
}