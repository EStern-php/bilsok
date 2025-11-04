<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <title>Eriks bilSök</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="container py-4">

  <h1 class="mb-3 text-center">Sök bilannonser</h1>

  <!-- Sökformulär -->
  <form id="f" class="row g-2 justify-content-center mb-5">
    <div class="col-sm-3"><input class="form-control" id="make" name="make" placeholder="Märke (t.ex. Volvo)"></div>
    <div class="col-sm-3"><input class="form-control" id="year" name="year" placeholder="Årsmodell" type="number"></div>
    <div class="col-sm-3"><input class="form-control" id="regno" name="regno" placeholder="Regnr (ABC123)"></div>
    <div class="col-sm-3 d-grid"><button class="btn btn-primary">Sök</button></div>
  </form>

  <div id="res" class="mt-4"></div>

  <!-- Divider -->
  <hr class="my-5">

  <?php 
    if (isset($this->scrapeCount)) {
       echo "<pre>Klart. Hämtade detaljer för $this->scrapeCount annonser.</pre>";
    }
  ?>
  <form id="scrapeForm" method="post" action="/?action=scrape-cars" class="text-center">
    <button type="submit" class="btn btn-success btn-lg px-5">
      <i class="bi bi-cloud-download"></i> Hämta bilannonser
    </button>
  </form>

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

<script>
  const form  = document.getElementById('f');
  const make = document.getElementById('make');
  const year = document.getElementById('year');
  const regno = document.getElementById('regno');
  const res = document.getElementById('res');

   form.addEventListener('submit', async (e) => {
    e.preventDefault();
     const params = {
      action: 'ajax-search',
      make: make.value.trim(),
      year: year.value.trim(),
      regno: regno.value,
    };

    const urlParams = new URLSearchParams(params).toString();
    const url = '/index.php?' + urlParams;
    const result = await fetch(url, {headers: {'Accept':'application/json'}});
    const data = await result.json();

    if(data.results.length > 0) {
      res.innerHTML = data.results.map(car => `
        <div class="card mb-3 shadow-sm">
          <div class="card-body">
            <h5 class="card-title">${car.make || ''}</h5>
            <p class="card-text">
              <strong>Model:</strong> ${car.model ?? '-'}<br>
              <strong>År:</strong> ${car.model_year ?? '-'}<br>
              <strong>Pris:</strong> ${car.price ?? '-'} kr<br>
              <strong>Regnr:</strong> ${car.regno ?? '-'}<br>
              <strong>Miltal:</strong> ${car.mileage ?? '-'}<br>
              <strong>Ort:</strong> ${car.location ?? '-'}
            </p>
          </div>
        </div>
      `).join('');
    } else {
       res.innerHTML = '<div class="alert alert-warning text-center">Inga resultat hittades</div>';
    }


  });


</script>

</body>
</html>

