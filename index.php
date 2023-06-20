<?php
include 'xml.php';
?>

<!DOCTYPE html>
<html>

<body>

    <form action="#" method="get" id="form_data">
        <input type="radio" id="year" name="contact" value="ANNUEL" onchange="displayForm(this.value)">
        <label for="email">Annuel</label>

        <input type="radio" id="trimester" name="contact" value="TRIMESTRIEL" onchange="displayForm(this.value)">
        <label for="phone">Trimestriel</label>

        <input type="radio" id="mensuel" name="contact" value="MENSUEL" onchange="displayForm(this.value)">
        <label for="mail">Mensuel</label>
        </div>
        <div id="date_data">
        </div>

        <input type="submit" target="_blank" value="Send">
    </form>

    <script>
        function displayForm(value) {
            let form_data = document.getElementById("form_data");
            let date_data = document.getElementById("date_data");

            date_data.innerHTML = '';

            let inputYear = document.createElement('input');
            inputYear.type = 'number';
            inputYear.name = 'year';
            inputYear.min = "2000";
            inputYear.max = "3000";
            inputYear.value = '2020';
            date_data.appendChild(inputYear);

            if (value === "TRIMESTRIEL") {
                let inputQuarter = document.createElement('select');
                inputQuarter.id = "quarter-select"; // Add an id to the select element for easier access
                inputQuarter.name = "quarter"; // Set the name attribute for the select element
                for (let i = 1; i < 5; i++) {
                    let inputQuarterOption = document.createElement('option');
                    inputQuarterOption.id = "option-" + i;
                    inputQuarterOption.value = i; // Set the value attribute to the quarter number
                    inputQuarterOption.innerHTML = 'Q' + i;
                    inputQuarter.appendChild(inputQuarterOption);
                }
                date_data.appendChild(inputQuarter);
            }

            if (value === "MENSUEL") {
                let inputMonth = document.createElement('select');
                inputMonth.name = "month";
                for (let i = 1; i <= 12; i++) {
                    let inputMonthOption = document.createElement('option');
                    inputMonthOption.id = "option-" + i;
                    inputMonthOption.value = (i < 10 ? '0' : '') + i;
                    switch (i) {
                        case 1:
                            inputMonthOption.innerHTML = "Janvier";
                            break;
                        case 2:
                            inputMonthOption.innerHTML = "Février";
                            break;
                        case 3:
                            inputMonthOption.innerHTML = "Mars";
                            break;
                        case 4:
                            inputMonthOption.innerHTML = "Avril";
                            break;
                        case 5:
                            inputMonthOption.innerHTML = "Mai";
                            break;
                        case 6:
                            inputMonthOption.innerHTML = "Juin";
                            break;
                        case 7:
                            inputMonthOption.innerHTML = "Juillet";
                            break;
                        case 8:
                            inputMonthOption.innerHTML = "Août";
                            break;
                        case 9:
                            inputMonthOption.innerHTML = "Septembre";
                            break;
                        case 10:
                            inputMonthOption.innerHTML = "Octobre";
                            break;
                        case 11:
                            inputMonthOption.innerHTML = "Novembre";
                            break;
                        case 12:
                            inputMonthOption.innerHTML = "Décembre";
                            break;
                        default:
                            break;
                    }
                    inputMonth.appendChild(inputMonthOption);
                }
                date_data.appendChild(inputMonth);
            }
        }
    </script>
</body>


</html>