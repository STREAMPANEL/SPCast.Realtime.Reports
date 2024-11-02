<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPCast Statistiken</title>
    <!-- Include CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>

    <div class="container mt-5">
        <div class="text-center mb-4">
            <h1>SPCast Statistiken</h1>
            <p>Verfolgen Sie Echtzeitdaten der Nutzeraktivitäten im Netzwerk.<br>Hier erhalten Sie Einblicke in eingehende, ausgehende und kombinierte Nutzerdaten, die innerhalb der letzten 30
                Minuten aktualisiert wurden.</p>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Eingehend & Ausgehend</h2>
                        <p class="card-text">Es handelt sich um Nutzer, die in den letzten 30 Minuten einen Sender im Netzwerk eingeschaltet oder ausgeschaltet haben.</p>
                        <a href="all.php" target="_blank" rel="noopener" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Eingehend</h2>
                        <p class="card-text">Es handelt sich um Nutzer, die in den letzten 30 Minuten einen Sender im Netzwerk eingeschaltet haben.</p>
                        <a href="in.php" target="_blank" rel="noopener" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Ausgehend</h2>
                        <p class="card-text">Es handelt sich um Nutzer, die in den letzten 30 Minuten einen Sender im Netzwerk ausgeschaltet haben.</p>
                        <a href="out.php" target="_blank" rel="noopener" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Land</h2>
                        <p class="card-text">Zeigt die Länder der Nutzer für heute, gestern, die letzten 7 und die letzten 30 Tage.</p>
                        <a href="country.php" target="_blank" rel="noopener" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Bundesland</h2>
                        <p class="card-text">Zeigt die Bundesländer der Nutzer für heute, gestern, die letzten 7 und die letzten 30 Tage.</p>
                        <a href="region.php" target="_blank" rel="noopener" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Stadt</h2>
                        <p class="card-text">Zeigt die Städte der Nutzer für heute, gestern, die letzten 7 und die letzten 30 Tage.</p>
                        <a href="city.php" target="_blank" rel="noopener" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Betriebssystem</h2>
                        <p class="card-text">Zeigt die Betriebssysteme der Nutzer für heute, gestern, die letzten 7 und die letzten 30 Tage.</p>
                        <a href="protocol.php" target="_blank" rel="noopener" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Browser</h2>
                        <p class="card-text">Zeigt die genutzten Browser der Nutzer für heute, gestern, die letzten 7 und die letzten 30 Tage.</p>
                        <a href="browser.php" target="_blank" rel="noopener" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Mountpoint</h2>
                        <p class="card-text">Zeigt die Mountpoints der Nutzer für heute, gestern, die letzten 7 und die letzten 30 Tage.</p>
                        <a href="mount.php" target="_blank" rel="noopener" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Protokoll</h2>
                        <p class="card-text">Zeigt die Protokolle der Nutzeraktivitäten für heute, gestern, die letzten 7 und die letzten 30 Tage.</p>
                        <a href="os.php" target="_blank" rel="noopener" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>