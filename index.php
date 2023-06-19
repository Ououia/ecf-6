<!DOCTYPE html>
<html>

<body>
    <div id="container">
        <div>
            <input type="radio" id="year" name="contact" value="year" onchange="displayForm(this.value)">
            <label for="email">Annuel</label>

            <input type="radio" id="trimester" name="contact" value="trimester" onchange="displayForm(this.value)">
            <label for="phone">Trimestriel</label>

            <input type="radio" id="mensuel" name="contact" value="mensuel" onchange="displayForm(this.value)">
            <label for="mail">Mensuel</label>
        </div>
    </div>

    <form action="" id="form_date"></form>
</body>

<script>
    function displayForm(value) {
        let form_date = document.getElementById("form_date");
        form_date.innerHTML = '';

        let inputYear = document.createElement('input');
        inputYear.type = 'number';
        inputYear.name = 'year';
        inputYear.placeholder = 'Enter year';
        form_date.appendChild(inputYear);

        if (value === "trimester") {
            let inputQuarter = document.createElement('input');
            inputQuarter.type = 'number';
            inputQuarter.name = 'quarter';
            inputQuarter.placeholder = 'Enter quarter (1-4)';
            form_date.appendChild(inputQuarter);
        }

        if (value === "mensuel") {
            let inputMonth = document.createElement('input');
            inputMonth.type = 'number';
            inputMonth.name = 'month';
            inputMonth.placeholder = 'Enter month (1-12)';
            form_date.appendChild(inputMonth);
        }
    }
</script>

</html>