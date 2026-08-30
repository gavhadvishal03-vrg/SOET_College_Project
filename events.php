<?php
$page_title = "Events & Sub-Events Calendar";
include_once __DIR__ . '/includes/header.php';

$cms = new ContentManager();
$type = isset($_GET['type']) ? $_GET['type'] : 'upcoming';

// Check registration submission
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['register_event'])) {
    $event_id = (int)$_POST['event_id'];
    $sub_event_id = !empty($_POST['sub_event_id']) ? (int)$_POST['sub_event_id'] : null;
    $name = Security::sanitize($_POST['name']);
    $email = Security::sanitizeEmail($_POST['email']);
    $phone = Security::sanitize($_POST['phone']);
    $roll = Security::sanitize($_POST['roll_number'] ?? '');
    $dept = Security::sanitize($_POST['department'] ?? '');
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'Invalid security token. Please try again.');
    } elseif (empty($name) || empty($email) || empty($phone)) {
        setFlash('danger', 'Please fill in all mandatory fields.');
    } elseif (!Security::validateEmail($email)) {
        setFlash('danger', 'Invalid email address.');
    } else {
        $regNo = 'EVT-REG-' . date('Y') . '-' . strtoupper(substr(md5(uniqid(microtime(), true)), 0, 5));

        $registered = $cms->db->insert('event_registrations', [
            'registration_no' => $regNo,
            'event_id' => $event_id,
            'sub_event_id' => $sub_event_id,
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'roll_number' => $roll,
            'department' => $dept,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        if ($registered) {
            $eventObj = $cms->db->fetchOne("SELECT * FROM events WHERE id = ?", [$event_id]);
            $subObj = $sub_event_id ? $cms->db->fetchOne("SELECT * FROM sub_events WHERE id = ?", [$sub_event_id]) : null;

            Session::flash('event_pass', [
                'registration_no' => $regNo,
                'event_title' => $eventObj['title'] ?? 'College Event',
                'sub_event_title' => $subObj['title'] ?? 'Main Event Overall',
                'participant_name' => $name,
                'email' => $email,
                'phone' => $phone,
                'roll_number' => $roll ?: 'N/A',
                'department' => $dept ?: 'N/A',
                'date' => $subObj['sub_event_date'] ?? ($eventObj['event_date'] ?? date('Y-m-d')),
                'time' => $subObj['sub_event_time'] ?? ($eventObj['event_time'] ?? '10:00:00'),
                'venue' => $subObj['venue'] ?? ($eventObj['venue'] ?? 'SOET Campus')
            ]);

            setFlash('success', 'Registration successful! Your official Event Entry Pass Number is: ' . $regNo);
        } else {
            setFlash('danger', 'Unable to process registration. Please try again later.');
        }
    }
    redirect('events.php');
}

// Check for Event Pass in session
$eventPass = Session::flash('event_pass');

// Fetch events
$today = date('Y-m-d');
if ($type === 'past') {
    $sql = "SELECT * FROM events WHERE event_date < ? AND is_active = 1 ORDER BY event_date DESC";
    $params = [$today];
} else {
    $sql = "SELECT * FROM events WHERE event_date >= ? AND is_active = 1 ORDER BY event_date ASC";
    $params = [$today];
}
$events = $cms->db->fetchAll($sql, $params);
$settings = $cms->getSiteSettings();
$events_bg = !empty($settings['events_hero_image']) ? uploadUrl('settings', $settings['events_hero_image']) : null;
?>

<!-- Breadcrumb Header -->
<div class="py-5 text-white mb-5 position-relative overflow-hidden" style="<?php echo $events_bg ? "background: linear-gradient(rgba(15, 23, 42, 0.8), rgba(15, 23, 42, 0.8)), url('{$events_bg}') center/cover no-repeat;" : "background-color: var(--primary-color);"; ?>">
    <div class="container position-relative z-1">
        <h1 class="fw-extrabold mb-1 text-white display-5">Events & Sub-Events Calendar</h1>
        <p class="lead opacity-75 mb-3">Hackathons, robotics wars, paper presentations, workshops, and cultural college fests</p>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index.php" class="text-white opacity-75 text-decoration-none">Home</a></li>
                <li class="breadcrumb-item active text-warning" aria-current="page">Events Calendar</li>
            </ol>
        </nav>
    </div>
</div>

<div class="container mb-5">
    <!-- EVENT REGISTRATION PASS TICKET DISPLAY CARD -->
    <?php if ($eventPass): ?>
        <div class="card border-0 shadow-lg mb-5 overflow-hidden border-top border-5 border-success">
            <div class="card-header bg-dark text-white p-3.5 d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="fw-bold text-white mb-0"><i class="fa-solid fa-ticket text-warning me-2"></i>Official Event Entry Pass Ticket</h5>
                    <small class="text-white-50">Keep or screenshot this entry pass for event check-in</small>
                </div>
                <span class="badge bg-warning text-dark fs-6 font-semibold px-3 py-2">
                    <i class="fa-solid fa-barcode me-1"></i> <?php echo htmlspecialchars($eventPass['registration_no']); ?>
                </span>
            </div>
            <div class="card-body p-4 bg-light">
                <div class="row g-3">
                    <div class="col-md-6 border-end">
                        <small class="text-muted text-uppercase fw-bold font-monospace d-block mb-1">Registration Pass Number</small>
                        <h4 class="fw-extrabold text-primary-color mb-3"><code><?php echo htmlspecialchars($eventPass['registration_no']); ?></code></h4>

                        <small class="text-muted text-uppercase fw-bold font-monospace d-block mb-1">Main Event</small>
                        <h5 class="fw-bold text-dark mb-2"><?php echo htmlspecialchars($eventPass['event_title']); ?></h5>

                        <small class="text-muted text-uppercase fw-bold font-monospace d-block mb-1">Selected Sub-Event / Track</small>
                        <p class="fw-bold text-success mb-3"><i class="fa-solid fa-flag me-1"></i> <?php echo htmlspecialchars($eventPass['sub_event_title']); ?></p>

                        <small class="text-muted text-uppercase fw-bold font-monospace d-block mb-1">Schedule & Venue</small>
                        <p class="mb-0 text-secondary">
                            <i class="fa-regular fa-calendar me-1 text-warning"></i> <?php echo formatDate($eventPass['date']); ?> |
                            <i class="fa-regular fa-clock me-1 text-warning"></i> <?php echo date('h:i A', strtotime($eventPass['time'])); ?><br>
                            <i class="fa-solid fa-location-dot me-1 text-danger"></i> <?php echo htmlspecialchars($eventPass['venue']); ?>
                        </p>
                    </div>

                    <div class="col-md-6 ps-md-4">
                        <small class="text-muted text-uppercase fw-bold font-monospace d-block mb-1">Participant Details</small>
                        <table class="table table-sm table-borderless mb-3">
                            <tr>
                                <td class="text-muted font-semibold" style="width: 130px;">Participant Name:</td>
                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($eventPass['participant_name']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted font-semibold">Email Address:</td>
                                <td><?php echo htmlspecialchars($eventPass['email']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted font-semibold">Phone Number:</td>
                                <td><?php echo htmlspecialchars($eventPass['phone']); ?></td>
                            </tr>
                            <tr>
                                <td class="text-muted font-semibold">Roll Number:</td>
                                <td><code><?php echo htmlspecialchars($eventPass['roll_number']); ?></code></td>
                            </tr>
                            <tr>
                                <td class="text-muted font-semibold">Branch/Dept:</td>
                                <td><?php echo htmlspecialchars($eventPass['department']); ?></td>
                            </tr>
                        </table>

                        <div class="d-flex gap-2 mt-4">
                            <button onclick="window.print()" class="btn btn-dark btn-sm font-semibold px-3">
                                <i class="fa-solid fa-print me-1"></i> Print / Save Entry Pass
                            </button>
                            <a href="events.php" class="btn btn-outline-secondary btn-sm px-3">Dismiss</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Toggle tab buttons & Search -->
    <div class="row g-3 mb-5 align-items-center">
        <div class="col-md-6 text-center text-md-start">
            <div class="btn-group shadow-sm rounded-pill overflow-hidden bg-light p-1">
                <a href="events.php?type=upcoming" class="btn px-4 py-2 rounded-pill <?php echo $type === 'upcoming' ? 'btn-primary' : 'btn-light'; ?>">
                    <i class="fa-regular fa-calendar-check me-1"></i> Upcoming Events &amp; Fests
                </a>
                <a href="events.php?type=past" class="btn px-4 py-2 rounded-pill <?php echo $type === 'past' ? 'btn-primary' : 'btn-light'; ?>">
                    <i class="fa-solid fa-history me-1"></i> Past Archive
                </a>
            </div>
        </div>
        <div class="col-md-6">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0 text-warning"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" id="eventSearchInput" class="form-control border-start-0" placeholder="Search event title, venue, or keywords...">
            </div>
        </div>
    </div>

    <!-- Events Cards Grid -->
    <div class="row g-4" id="eventsGrid">
        <?php if (empty($events)): ?>
            <div class="col-12 text-center text-muted py-5">
                <i class="fa-solid fa-calendar-xmark fa-3x mb-3 text-secondary"></i>
                <p>No <?php echo $type; ?> events scheduled at this moment. Check back soon!</p>
            </div>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <?php $subEvents = $cms->getSubEvents((int)$event['id']); ?>
                <div class="col-md-6 col-lg-4 event-card-col"
                     data-title="<?php echo strtolower(htmlspecialchars($event['title'])); ?>"
                     data-desc="<?php echo strtolower(htmlspecialchars($event['description'])); ?>"
                     data-venue="<?php echo strtolower(htmlspecialchars($event['venue'])); ?>">
                    <div class="card custom-card h-100 border-0 shadow-sm hover-elevate">
                        <?php if ($event['image_path']): ?>
                            <img src="<?php echo uploadUrl('events', $event['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($event['title']); ?>" style="height: 190px; object-fit: cover;">
                        <?php else: ?>
                            <img src="https://images.unsplash.com/photo-1540575467063-178a50c2df87?auto=format&fit=crop&w=600&q=80" class="card-img-top" alt="Event banner" style="height: 190px; object-fit: cover;">
                        <?php endif; ?>
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between align-items-center text-muted small mb-2">
                                <span><i class="fa-regular fa-calendar me-1 text-warning"></i><?php echo formatDate($event['event_date']); ?></span>
                                <span><i class="fa-regular fa-clock me-1 text-warning"></i><?php echo date('h:i A', strtotime($event['event_time'])); ?></span>
                            </div>
                            <h5 class="fw-bold text-primary-color mb-2"><?php echo htmlspecialchars($event['title']); ?></h5>
                            <p class="text-muted small mb-3"><?php echo htmlspecialchars($event['description']); ?></p>
                            
                            <!-- SUB-EVENTS BREAKDOWN BADGE & SCHEDULE -->
                            <?php if (!empty($subEvents)): ?>
                                <div class="bg-light p-2.5 rounded border mb-3">
                                    <div class="fw-bold text-dark small mb-2 d-flex justify-content-between align-items-center">
                                        <span><i class="fa-solid fa-diagram-project text-warning me-1"></i> Sub-Events / Competitions:</span>
                                        <span class="badge bg-warning text-dark px-2"><?php echo count($subEvents); ?> Tracks</span>
                                    </div>
                                    <ul class="list-unstyled mb-0 small text-secondary">
                                        <?php foreach (array_slice($subEvents, 0, 3) as $se): ?>
                                            <li class="mb-1 text-truncate">
                                                <i class="fa-solid fa-angle-right text-primary me-1"></i>
                                                <strong><?php echo htmlspecialchars($se['title']); ?></strong>
                                                <span class="text-muted"> (<?php echo htmlspecialchars($se['venue']); ?>)</span>
                                            </li>
                                        <?php endforeach; ?>
                                        <?php if (count($subEvents) > 3): ?>
                                            <li class="text-primary small font-semibold">+ <?php echo count($subEvents) - 3; ?> more sub-events</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>

                            <div class="text-muted small mb-3 mt-auto">
                                <p class="mb-1"><i class="fa-solid fa-location-dot me-1 text-danger"></i> <strong>Venue:</strong> <?php echo htmlspecialchars($event['venue']); ?></p>
                                <p class="mb-0"><i class="fa-solid fa-user-gear me-1 text-primary"></i> <strong>Organizer:</strong> <?php echo htmlspecialchars($event['organizer']); ?></p>
                            </div>
                            
                            <?php if ($type === 'upcoming' && $event['registration_required']): ?>
                                <button class="btn btn-sm btn-primary w-100 rounded fw-bold" data-bs-toggle="modal" data-bs-target="#regModal-<?php echo $event['id']; ?>">
                                    <i class="fa-solid fa-file-pen me-1"></i> Register Online
                                </button>
                            <?php elseif ($type === 'past'): ?>
                                <span class="badge bg-secondary w-100 py-2">Event Concluded</span>
                            <?php else: ?>
                                <span class="badge bg-info text-dark w-100 py-2">No Registration Required</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Registration & Sub-Event Selection Modal -->
                <?php if ($type === 'upcoming' && $event['registration_required']): ?>
                    <div class="modal fade" id="regModal-<?php echo $event['id']; ?>" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-dark text-white border-bottom border-warning">
                                    <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-calendar-check text-warning me-2"></i>Register for <?php echo htmlspecialchars($event['title']); ?></h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST" action="events.php">
                                    <?php echo Security::csrfField(); ?>
                                    <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                    <div class="modal-body">
                                        <div class="mb-3 text-secondary bg-light p-3 rounded small border">
                                            <strong>Main Event:</strong> <?php echo htmlspecialchars($event['title']); ?><br>
                                            <strong>Date & Time:</strong> <?php echo formatDate($event['event_date']); ?> at <?php echo date('h:i A', strtotime($event['event_time'])); ?><br>
                                            <strong>Venue:</strong> <?php echo htmlspecialchars($event['venue']); ?>
                                        </div>

                                        <?php if (!empty($subEvents)): ?>
                                            <div class="mb-3">
                                                <label class="form-label font-semibold text-primary-color"><i class="fa-solid fa-diagram-project text-warning me-1"></i> Select Sub-Event / Track (Optional)</label>
                                                <select name="sub_event_id" class="form-select">
                                                    <option value="">-- Participate in Main Event Overall --</option>
                                                    <?php foreach ($subEvents as $se): ?>
                                                        <option value="<?php echo $se['id']; ?>">📌 <?php echo htmlspecialchars($se['title']); ?> (Venue: <?php echo htmlspecialchars($se['venue']); ?>)</option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        <?php endif; ?>

                                        <div class="mb-3">
                                            <label class="form-label font-semibold">Full Name *</label>
                                            <input type="text" name="name" class="form-control" placeholder="Enter your full name" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label font-semibold">Email Address *</label>
                                            <input type="email" name="email" class="form-control" placeholder="Enter email address" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label font-semibold">Phone Number *</label>
                                            <input type="text" name="phone" class="form-control" placeholder="Enter 10-digit mobile number" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label font-semibold">Roll Number (For SOET Students)</label>
                                            <input type="text" name="roll_number" class="form-control" placeholder="e.g. 2026SOET104">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label font-semibold">Branch/Department</label>
                                            <input type="text" name="department" class="form-control" placeholder="e.g. B.Tech CSE">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                        <button type="submit" name="register_event" class="btn btn-primary fw-bold"><i class="fa-solid fa-paper-plane me-1"></i> Submit Registration</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('eventSearchInput');
    const cards = document.querySelectorAll('.event-card-col');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            cards.forEach(card => {
                const title = card.getAttribute('data-title') || '';
                const desc = card.getAttribute('data-desc') || '';
                const venue = card.getAttribute('data-venue') || '';

                if (!query || title.includes(query) || desc.includes(query) || venue.includes(query)) {
                    card.classList.remove('d-none');
                } else {
                    card.classList.add('d-none');
                }
            });
        });
    }
});
</script>

<?php include_once __DIR__ . '/includes/footer.php'; ?>
