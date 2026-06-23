<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicle Compliance System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light vcs-navbar border-bottom sticky-top">
        <div class="container py-2">
            <a class="navbar-brand d-flex align-items-center gap-2 fw-bold text-success" href="#home">
                <span class="brand-mark"><i class="bi bi-shield-check"></i></span>
                <span>VCS</span>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse justify-content-end" id="mainNav">
                <div class="navbar-nav align-items-lg-center gap-lg-3">
                    <a class="nav-link" href="#about">About</a>
                    <a class="nav-link" href="#values">Core Values</a>
                    <a class="btn btn-success rounded-pill px-4 ms-lg-2" href="/login">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main id="home">
        <section class="hero-banner">
            <div class="hero-overlay"></div>
            <div class="container hero-content py-5">
                <div class="row align-items-center g-4 g-lg-5 py-4 py-lg-5">
                    <div class="col-lg-7">
                        <span class="eyebrow text-white-50 fw-semibold">Vehicle Compliance System</span>
                        <h1 class="display-5 fw-bold text-white mt-3">
                            Track and Manage Vehicle Compliance in Real Time.
                        </h1>
                        <p class="lead text-white-75 mt-3 mb-4">
                            VCS is a web-based platform that streamlines vehicle compliance in Kenya - connecting Traffic Enforcement Officers, Vehicle Owners, and System Administrators on one secure system to verify, track, and manage vehicle compliance records in real time.
                        </p>
                        <div class="hero-meta d-flex flex-wrap gap-3 gap-lg-4">
                            <div class="hero-chip">
                                <div class="hero-chip-label">Vision</div>
                                <div class="hero-chip-text">To promote safer roads through efficient vehicle compliance monitoring and timely reminders.</div>
                            </div>
                            <div class="hero-chip">
                                <div class="hero-chip-label">Motto</div>
                                <div class="hero-chip-text">Stay compliant. Stay roadworthy.</div>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap gap-3 mt-4">
                            <a class="btn btn-light btn-lg px-4 text-success fw-semibold" href="/login">
                                <i class="bi bi-box-arrow-in-right me-2"></i> Login
                            </a>
                            <a class="btn btn-outline-light btn-lg px-4" href="#about">Learn More</a>
                        </div>
                    </div>
                    <div class="col-lg-5">
                        <div class="hero-panel shadow-lg">
                            <img
                                src="https://commons.wikimedia.org/wiki/Special:FilePath/Section%2058%20Bypass.jpg"
                                class="img-fluid"
                                alt="Nairobi Expressway"
                            >
                            <div class="hero-panel-card">
                                <div class="fw-semibold">Nairobi-centered compliance checks</div>
                                <small>Built for smart vehicle compliance management for owners, enforcement officers, and administrators.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section id="about" class="section-band bg-white">
            <div class="container py-5">
                <div class="row align-items-center g-4">
                    <div class="col-lg-5">
                        <img
                            src="https://commons.wikimedia.org/wiki/Special:FilePath/Kenyatta%20International%20Convention%20Centre,%20Nairobi,%20by%20Karl%20Henrik%20N%C3%B8stvik%20architect,%20entrance.jpg"
                            class="img-fluid rounded-4 shadow-sm"
                            alt="KICC Nairobi"
                        >
                    </div>
                    <div class="col-lg-7">
                        <span class="eyebrow text-success fw-semibold">What VCS does</span>
                        <h2 class="section-title mt-2">Simplifying Vehicle Compliance Management</h2>
                        <p class="text-secondary mb-0">
                            VCS provides a centralised platform for monitoring vehicle compliance records, tracking servicing schedules, and delivering timely reminders to help users stay compliant and roadworthy.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <section id="values" class="section-band bg-light">
            <div class="container py-5">
                <div class="d-flex align-items-end justify-content-between flex-wrap gap-3 mb-4">
                    <div>
                        <span class="eyebrow text-success fw-semibold">Core Values</span>
                        <h2 class="section-title mt-2 mb-0">Built around accountability and service.</h2>
                    </div>
                </div>

                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-3 g-lg-4">
                    <div class="col">
                        <div class="value-card h-100">
                            <div class="value-icon bg-success-subtle text-success"><i class="bi bi-broadcast"></i></div>
                            <h3 class="h5 fw-semibold mt-3">Transparency</h3>
                            <p class="text-secondary mb-0">Every verification is traceable and accountable.</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="value-card h-100">
                            <div class="value-icon bg-success-subtle text-success"><i class="bi bi-lightning-charge"></i></div>
                            <h3 class="h5 fw-semibold mt-3">Efficiency</h3>
                            <p class="text-secondary mb-0">Reducing delays in compliance checks for officers and owners alike.</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="value-card h-100">
                            <div class="value-icon bg-success-subtle text-success"><i class="bi bi-shield-lock"></i></div>
                            <h3 class="h5 fw-semibold mt-3">Integrity</h3>
                            <p class="text-secondary mb-0">Accurate, tamper-resistant records you can trust.</p>
                        </div>
                    </div>
                    <div class="col">
                        <div class="value-card h-100">
                            <div class="value-icon bg-success-subtle text-success"><i class="bi bi-universal-access"></i></div>
                            <h3 class="h5 fw-semibold mt-3">Accessibility</h3>
                            <p class="text-secondary mb-0">A system built to serve every user, regardless of technical skill level.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
