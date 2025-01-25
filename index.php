<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SPCast Echtzeitstatistiken - SPCast Live</title>
    <meta name="description" content="Erhalten Sie Echtzeitstatistiken über Nutzeraktivitäten im SPCast-Netzwerk. Einblicke in eingehende und ausgehende Daten, live aktualisiert.">
    <meta name="keywords" content="spcast live, spcast echtzeitstatistiken, nutzerstatistik, netzwerkstatistiken, live daten, sendernutzung">
    <link rel="canonical" href="https://live.spcast.eu/" />

    <!-- Bootstrap CSS -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap ICONS -->
    <link rel="stylesheet" href="css/bootstrap-icons.min.css">
</head>

<body>
    <?php require_once "includes/nav.php"; ?>

    <div class="container mt-5">
        <div class="text-center mb-4">
            <h1>SPCast Echtzeitstatistiken</h1>
            <p>Erhalten Sie anonymisierte Echtzeitstatistiken zu den Nutzeraktivitäten im SPCast-Netzwerk. Verfolgen Sie allgemeine Trends und analysieren Sie das Nutzerverhalten aller Radiostationen
                in einer zentralen Übersicht.</p>
        </div>
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Eingehend & Ausgehend</h2>
                        <p class="card-text">Es handelt sich um Nutzer, die in den letzten 30 Minuten einen Sender im Netzwerk eingeschaltet oder ausgeschaltet haben.</p>
                        <a href="all.php" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Eingehend</h2>
                        <p class="card-text">Es handelt sich um Nutzer, die in den letzten 30 Minuten einen Sender im Netzwerk eingeschaltet haben.</p>
                        <a href="in.php" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Ausgehend</h2>
                        <p class="card-text">Es handelt sich um Nutzer, die in den letzten 30 Minuten einen Sender im Netzwerk ausgeschaltet haben.</p>
                        <a href="out.php" class="btn btn-primary">Aufrufen</a>
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
                        <a href="country.php" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Bundesland</h2>
                        <p class="card-text">Zeigt die Bundesländer der Nutzer für heute, gestern, die letzten 7 und die letzten 30 Tage.</p>
                        <a href="region.php" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Stadt</h2>
                        <p class="card-text">Zeigt die Städte der Nutzer für heute, gestern, die letzten 7 und die letzten 30 Tage.</p>
                        <a href="city.php" class="btn btn-primary">Aufrufen</a>
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
                        <a href="os.php" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Browser</h2>
                        <p class="card-text">Zeigt die genutzten Browser der Nutzer für heute, gestern, die letzten 7 und die letzten 30 Tage.</p>
                        <a href="browser.php" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Mountpoint</h2>
                        <p class="card-text">Zeigt die Mountpoints der Nutzer für heute, gestern, die letzten 7 und die letzten 30 Tage.</p>
                        <a href="mount.php" class="btn btn-primary">Aufrufen</a>
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
                        <a href="protocol.php" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">Port</h2>
                        <p class="card-text">Zeigt die genutzten Ports der Nutzer für heute, gestern, die letzten 7 und die letzten 30 Tage.</p>
                        <a href="port.php" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>
            <!--<div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h2 class="card-title">IP-Version</h2>
                        <p class="card-text">Zeigt die genutzten IP-Versionen der Nutzer für heute, gestern, die letzten 7 und die letzten 30 Tage.</p>
                        <a href="ipversion.php" class="btn btn-primary">Aufrufen</a>
                    </div>
                </div>
            </div>-->
        </div>
    </div>

    <?php require_once "includes/footer.php"; ?>
</body>

</html>