<?php
require_once __DIR__ . '/../../core/bootstrap.php';
Auth::requirePermission('manage_events');

// Handle CSV Export for Event Registrations
if (isset($_GET['action']) && $_GET['action'] === 'export_registrations_csv' && isset($_GET['id'])) {
    $db = Database::getInstance();
    $event_id = (int)$_GET['id'];
    $event = $db->fetchOne("SELECT * FROM events WHERE id = ?", [$event_id]);
    $rows = $db->fetchAll(
        "SELECT r.id, r.registration_no, r.name, r.email, r.phone, r.roll_number, r.department, 
                s.title as sub_event_title, r.created_at 
         FROM event_registrations r 
         LEFT JOIN sub_events s ON r.sub_event_id = s.id 
         WHERE r.event_id = ? ORDER BY r.created_at DESC",
        [$event_id]
    );
    $eventTitleSlug = slugify($event['title'] ?? 'Event');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Event_Registrations_' . $eventTitleSlug . '_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Registration Pass ID', 'Participant Name', 'Email', 'Phone', 'Roll Number', 'Branch/Dept', 'Sub-Event Track', 'Registered At']);
    foreach ($rows as $r) {
        fputcsv($output, [
            $r['registration_no'] ?: ('EVT-REG-' . date('Y') . '-' . sprintf('%05d', $r['id'])),
            $r['name'],
            $r['email'],
            $r['phone'],
            $r['roll_number'] ?: 'N/A',
            $r['department'] ?: 'N/A',
            $r['sub_event_title'] ?: 'Main Overall Event',
            $r['created_at']
        ]);
    }
    fclose($output);
    exit;
}

$page_title = "Events & Sub-Events Manager";
include_once __DIR__ . '/../includes/header.php';

$db = Database::getInstance();
$cms = new ContentManager();
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$edit_event = null;
$edit_sub_events = [];
$view_registrations = null;

// Handle sub-event delete action
if ($action === 'delete_sub_event' && isset($_GET['sub_id'])) {
    $subId = (int)$_GET['sub_id'];
    $eventId = (int)($_GET['event_id'] ?? 0);
    $cms->deleteSubEvent($subId);
    setFlash('success', 'Sub-Event removed successfully.');
    redirect($eventId ? "events.php?action=edit&id={$eventId}" : "events.php");
}

if ($action === 'edit' && isset($_GET['id'])) {
    $eventId = (int)$_GET['id'];
    $edit_event = $db->fetchOne("SELECT * FROM events WHERE id = ?", [$eventId]);
    if ($edit_event) {
        $edit_sub_events = $cms->getSubEvents($eventId);
    }
}

if ($action === 'registrations' && isset($_GET['id'])) {
    $event_id = (int)$_GET['id'];
    $view_event = $db->fetchOne("SELECT * FROM events WHERE id = ?", [$event_id]);
    $registrations = $db->fetchAll(
        "SELECT r.*, s.title as sub_event_title FROM event_registrations r 
         LEFT JOIN sub_events s ON r.sub_event_id = s.id 
         WHERE r.event_id = ? ORDER BY r.created_at DESC", 
        [$event_id]
    );
}

// Handle Save Main Event & Sub-Events
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_event'])) {
    $csrf = $_POST[CSRF_TOKEN_NAME] ?? '';
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : null;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $venue = trim($_POST['venue']);
    $organizer = trim($_POST['organizer']);
    $reg_required = isset($_POST['registration_required']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (!Security::validateCSRF($csrf)) {
        setFlash('danger', 'CSRF validation failed.');
    } elseif (empty($title) || empty($description) || empty($event_date) || empty($venue) || empty($organizer)) {
        setFlash('danger', 'Please enter all mandatory main event fields.');
    } else {
        $uploaded_image = $edit_event ? $edit_event['image_path'] : null;
        $valid_upload = true;

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $errors = Security::validateUpload($_FILES['image'], ALLOWED_IMAGE_TYPES);
            if (!empty($errors)) {
                setFlash('danger', implode(' ', $errors));
                $valid_upload = false;
            } else {
                $uploaded_image = Security::uploadFile($_FILES['image'], 'events', 'event_');
                if (!$uploaded_image) {
                    setFlash('danger', 'Failed to upload event banner.');
                    $valid_upload = false;
                }
            }
        }

        if ($valid_upload) {
            $targetEventId = $event_id;
            if ($event_id) {
                // Update Main Event
                $db->update('events', [
                    'title' => $title,
                    'description' => $description,
                    'event_date' => $event_date,
                    'event_time' => $event_time,
                    'venue' => $venue,
                    'organizer' => $organizer,
                    'image_path' => $uploaded_image,
                    'registration_required' => $reg_required,
                    'is_active' => $is_active
                ], 'id = ?', [$event_id]);
                setFlash('success', 'Event schedule and sub-events updated successfully.');
            } else {
                // Insert Main Event
                $targetEventId = $db->insert('events', [
                    'title' => $title,
                    'description' => $description,
                    'event_date' => $event_date,
                    'event_time' => $event_time,
                    'venue' => $venue,
                    'organizer' => $organizer,
                    'image_path' => $uploaded_image,
                    'registration_required' => $reg_required,
                    'is_active' => $is_active,
                    'created_at' => date('Y-m-d H:i:s')
                ]);
                setFlash('success', 'Event schedule created successfully with sub-events.');
            }

            // Save Sub-Events submitted in form
            if ($targetEventId && !empty($_POST['sub_title']) && is_array($_POST['sub_title'])) {
                $subTitles = $_POST['sub_title'];
                $subIds = $_POST['sub_id'] ?? [];
                $subDates = $_POST['sub_date'] ?? [];
                $subTimes = $_POST['sub_time'] ?? [];
                $subVenues = $_POST['sub_venue'] ?? [];
                $subCoordinators = $_POST['sub_coordinator'] ?? [];
                $subMaxes = $_POST['sub_max'] ?? [];
                $subDescs = $_POST['sub_description'] ?? [];
                $subRules = $_POST['sub_rules'] ?? [];

                for ($i = 0; $i < count($subTitles); $i++) {
                    $st = trim($subTitles[$i]);
                    if (empty($st)) continue;

                    $subData = [
                        'event_id' => $targetEventId,
                        'title' => $st,
                        'sub_event_date' => !empty($subDates[$i]) ? $subDates[$i] : $event_date,
                        'sub_event_time' => !empty($subTimes[$i]) ? $subTimes[$i] : $event_time,
                        'venue' => !empty($subVenues[$i]) ? trim($subVenues[$i]) : $venue,
                        'coordinator' => !empty($subCoordinators[$i]) ? trim($subCoordinators[$i]) : $organizer,
                        'max_participants' => !empty($subMaxes[$i]) ? (int)$subMaxes[$i] : 0,
                        'description' => !empty($subDescs[$i]) ? trim($subDescs[$i]) : '',
                        'rules' => !empty($subRules[$i]) ? trim($subRules[$i]) : '',
                        'is_active' => 1
                    ];

                    $existingSubId = !empty($subIds[$i]) ? (int)$subIds[$i] : 0;
                    if ($existingSubId > 0) {
                        $subData['id'] = $existingSubId;
                    }
                    $cms->saveSubEvent($subData);
                }
            }

            redirect('events.php');
        }
    }
}

// Handle Delete Main Event
if ($action === 'delete' && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $db->delete('events', 'id = ?', [$target_id]);
    setFlash('success', 'Event canceled/deleted.');
    redirect('events.php');
}

// Fetch all events with sub-event counts and registrations count
$events = $db->fetchAll(
    "SELECT e.*, 
            (SELECT COUNT(*) FROM event_registrations WHERE event_id = e.id) as reg_count,
            (SELECT COUNT(*) FROM sub_events WHERE event_id = e.id AND is_active = 1) as sub_count
     FROM events e ORDER BY e.event_date DESC"
);
?>

<div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-4 border-bottom">
    <div>
        <h1 class="h2 fw-bold text-primary-color mb-0">Events & Sub-Events Management</h1>
        <small class="text-muted">Manage main college events, tech fests, cultural activities, and sub-event competitions</small>
    </div>
    <?php if ($action === 'list'): ?>
        <a href="events.php?action=add" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Schedule New Event</a>
    <?php else: ?>
        <a href="events.php" class="btn btn-outline-secondary btn-sm"><i class="fa-solid fa-angle-left me-1"></i> Back to List</a>
    <?php endif; ?>
</div>

<?php if ($action === 'list'): ?>
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>Event Title</th>
                            <th>Schedule Date & Time</th>
                            <th>Venue</th>
                            <th>Sub-Events / Tracks</th>
                            <th class="text-center">Reg. Count</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted p-4">No events scheduled.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($events as $ev): ?>
                                <?php $subList = $cms->getSubEvents((int)$ev['id']); ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-primary-color"><?php echo htmlspecialchars($ev['title']); ?></div>
                                        <small class="text-muted"><i class="fa-solid fa-building me-1"></i><?php echo htmlspecialchars($ev['organizer']); ?></small>
                                    </td>
                                    <td>
                                        <small class="d-block fw-bold"><i class="fa-regular fa-calendar me-1"></i><?php echo formatDate($ev['event_date']); ?></small>
                                        <small class="text-muted"><i class="fa-regular fa-clock me-1"></i><?php echo date('h:i A', strtotime($ev['event_time'])); ?></small>
                                    </td>
                                    <td><span class="small text-secondary"><?php echo htmlspecialchars($ev['venue']); ?></span></td>
                                    <td>
                                        <?php if ($ev['sub_count'] > 0): ?>
                                            <span class="badge bg-info text-dark font-semibold"><i class="fa-solid fa-diagram-project me-1"></i> <?php echo $ev['sub_count']; ?> Sub-Events</span>
                                            <button type="button" class="btn btn-link btn-sm p-0 ms-1" data-bs-toggle="modal" data-bs-target="#modalSubEvents<?php echo $ev['id']; ?>">View List</button>
                                            
                                            <!-- Sub-Events Modal -->
                                            <div class="modal fade" id="modalSubEvents<?php echo $ev['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                    <div class="modal-content">
                                                        <div class="modal-header bg-dark text-white">
                                                            <h5 class="modal-title fw-bold text-white"><i class="fa-solid fa-diagram-project text-warning me-2"></i>Sub-Events & Competitions for <?php echo htmlspecialchars($ev['title']); ?></h5>
                                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body p-0">
                                                            <ul class="list-group list-group-flush">
                                                                <?php foreach ($subList as $sub): ?>
                                                                    <li class="list-group-item p-3">
                                                                        <div class="d-flex justify-content-between align-items-start">
                                                                            <div>
                                                                                <h6 class="fw-bold text-primary-color mb-1">📌 <?php echo htmlspecialchars($sub['title']); ?></h6>
                                                                                <small class="text-muted d-block"><i class="fa-solid fa-clock me-1"></i>Date: <?php echo formatDate($sub['sub_event_date']); ?> at <?php echo date('h:i A', strtotime($sub['sub_event_time'])); ?> | Venue: <?php echo htmlspecialchars($sub['venue']); ?></small>
                                                                                <small class="text-secondary d-block mt-1">Coordinator: <strong><?php echo htmlspecialchars($sub['coordinator']); ?></strong> | Max Capacity: <?php echo $sub['max_participants'] ?: 'Unlimited'; ?></small>
                                                                                <?php if (!empty($sub['description'])): ?>
                                                                                    <p class="small text-muted mb-0 mt-1"><?php echo htmlspecialchars($sub['description']); ?></p>
                                                                                <?php endif; ?>
                                                                            </div>
                                                                            <a href="events.php?action=delete_sub_event&sub_id=<?php echo $sub['id']; ?>&event_id=<?php echo $ev['id']; ?>" class="btn btn-outline-danger btn-sm" onclick="return confirm('Delete this sub-event?');"><i class="fa-solid fa-trash me-1"></i> Delete</a>
                                                                        </div>
                                                                    </li>
                                                                <?php endforeach; ?>
                                                            </ul>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <a href="events.php?action=edit&id=<?php echo $ev['id']; ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus me-1"></i> Add / Edit Sub-Events</a>
                                                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted">Single Event</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($ev['registration_required']): ?>
                                            <a href="events.php?action=registrations&id=<?php echo $ev['id']; ?>" class="badge bg-primary text-decoration-none px-2 py-1">
                                                <i class="fa-solid fa-users me-1"></i> <?php echo $ev['reg_count']; ?> Registered
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-light text-muted">Public/Open</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?php echo statusBadge($ev['is_active'] ? 'active' : 'inactive'); ?></td>
                                    <td class="text-end text-nowrap">
                                        <a href="events.php?action=edit&id=<?php echo $ev['id']; ?>" class="btn btn-sm btn-outline-primary font-semibold me-1"><i class="fa-solid fa-pen-to-square me-1"></i> Edit / Sub-Events</a>
                                        <a href="events.php?action=delete&id=<?php echo $ev['id']; ?>" class="btn btn-sm btn-outline-danger btn-delete-confirm font-semibold" onclick="return confirm('Delete event and all its sub-events?');"><i class="fa-solid fa-trash-can me-1"></i> Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php elseif ($action === 'registrations'): ?>
    <!-- View Registrations List -->
    <div class="card border-0 shadow-sm p-4">
        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
            <div>
                <h4 class="fw-bold text-primary-color mb-0"><i class="fa-solid fa-users-viewfinder text-warning me-2"></i>Event Registrations &amp; Passes</h4>
                <small class="text-muted">Total Registered Participants: <strong><?php echo count($registrations); ?></strong></small>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <input type="text" id="regSearchInput" class="form-control form-control-sm" placeholder="Search participant, roll #, pass..." style="width: 240px;">
                <a href="events.php?action=export_registrations_csv&id=<?php echo $event_id; ?>" class="btn btn-sm btn-success">
                    <i class="fa-solid fa-file-excel me-1"></i> Export CSV
                </a>
            </div>
        </div>
        <div class="bg-light p-3 rounded small text-secondary mb-4 border">
            <strong>Main Event:</strong> <?php echo htmlspecialchars($view_event['title']); ?> |
            <strong>Schedule:</strong> <?php echo formatDate($view_event['event_date']); ?> at <?php echo date('h:i A', strtotime($view_event['event_time'])); ?> |
            <strong>Venue:</strong> <?php echo htmlspecialchars($view_event['venue']); ?>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover align-middle mb-0" id="registrationsTable">
                <thead class="table-light">
                    <tr>
                        <th>Registration Pass ID</th>
                        <th>Participant Name</th>
                        <th>Email Address</th>
                        <th>Phone Number</th>
                        <th>Selected Sub-Event / Track</th>
                        <th>Roll Number</th>
                        <th>Branch</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($registrations)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted p-4">No participants registered for this event yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($registrations as $r): ?>
                            <tr>
                                <td>
                                    <span class="badge bg-warning text-dark font-monospace"><i class="fa-solid fa-barcode me-1"></i> <?php echo htmlspecialchars($r['registration_no'] ?: ('EVT-REG-' . date('Y') . '-' . sprintf('%05d', $r['id']))); ?></span>
                                </td>
                                <td class="fw-bold"><?php echo htmlspecialchars($r['name']); ?></td>
                                <td><?php echo htmlspecialchars($r['email']); ?></td>
                                <td><?php echo htmlspecialchars($r['phone']); ?></td>
                                <td>
                                    <?php if (!empty($r['sub_event_title'])): ?>
                                        <span class="badge bg-info text-dark"><i class="fa-solid fa-flag me-1"></i> <?php echo htmlspecialchars($r['sub_event_title']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted">Main Event Overall</span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo htmlspecialchars($r['roll_number'] ?: 'N/A'); ?></code></td>
                                <td><?php echo htmlspecialchars($r['department'] ?: 'N/A'); ?></td>
                                <td><?php echo formatDateTime($r['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="text-end mt-4">
            <a href="events.php" class="btn btn-secondary px-4">Back to Events</a>
        </div>
    </div>
<?php else: ?>
    <!-- Add/Edit Main Event & Sub-Events Form -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm p-4">
                <h4 class="fw-bold text-primary-color mb-4"><i class="fa-solid fa-calendar-days text-warning me-2"></i><?php echo $edit_event ? 'Edit Event & Sub-Events' : 'Schedule New Event & Sub-Events'; ?></h4>
                <form method="POST" action="events.php" enctype="multipart/form-data" id="formEvent">
                    <?php echo Security::csrfField(); ?>
                    <?php if ($edit_event): ?>
                        <input type="hidden" name="event_id" value="<?php echo $edit_event['id']; ?>">
                    <?php endif; ?>
                    
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label font-semibold">Event Title *</label>
                            <input type="text" name="title" class="form-control" required value="<?php echo $edit_event ? htmlspecialchars($edit_event['title']) : ''; ?>" placeholder="e.g. Supernova 2026 / Tech Fest">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label font-semibold">Event Date *</label>
                            <input type="date" name="event_date" class="form-control" required value="<?php echo $edit_event ? $edit_event['event_date'] : date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Event Time *</label>
                            <input type="time" name="event_time" class="form-control" required value="<?php echo $edit_event ? $edit_event['event_time'] : '10:00'; ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label font-semibold">Venue Location *</label>
                            <input type="text" name="venue" class="form-control" required value="<?php echo $edit_event ? htmlspecialchars($edit_event['venue']) : ''; ?>" placeholder="e.g. Main Auditorium & Open Arena">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label font-semibold">Organizer Department / Group *</label>
                            <input type="text" name="organizer" class="form-control" required value="<?php echo $edit_event ? htmlspecialchars($edit_event['organizer']) : ''; ?>" placeholder="e.g. SOET Student Association / CSE Dept">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label font-semibold">Event Image/Banner</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                        </div>

                        <div class="col-12">
                            <label class="form-label font-semibold">Event Description *</label>
                            <textarea name="description" class="form-control" rows="4" required placeholder="Write main details about event schedules, overall fest theme, guest speakers..."><?php echo $edit_event ? htmlspecialchars($edit_event['description']) : ''; ?></textarea>
                        </div>
                        
                        <!-- DYNAMIC SUB-EVENTS MANAGER SECTION -->
                        <div class="col-12 mt-4">
                            <div class="card border border-warning bg-light p-3">
                                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                    <div>
                                        <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-diagram-project text-warning me-2"></i>Sub-Events / Tracks / Competitions</h5>
                                        <small class="text-muted">Add sub-events, hackathons, robotics wars, or paper presentations within this main event</small>
                                    </div>
                                    <button type="button" class="btn btn-warning btn-sm font-semibold" id="btnAddSubEvent">
                                        <i class="fa-solid fa-plus me-1"></i> Add Sub-Event
                                    </button>
                                </div>

                                <div id="subEventsContainer">
                                    <?php if (!empty($edit_sub_events)): ?>
                                        <?php foreach ($edit_sub_events as $idx => $se): ?>
                                            <div class="sub-event-row border bg-white p-3 rounded mb-3 shadow-sm position-relative">
                                                <input type="hidden" name="sub_id[]" value="<?php echo $se['id']; ?>">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <strong class="text-primary-color"><i class="fa-solid fa-flag text-warning me-1"></i> Sub-Event #<?php echo $idx + 1; ?></strong>
                                                    <a href="events.php?action=delete_sub_event&sub_id=<?php echo $se['id']; ?>&event_id=<?php echo $edit_event['id']; ?>" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="return confirm('Remove this sub-event?');"><i class="fa-solid fa-times me-1"></i> Remove</a>
                                                </div>
                                                <div class="row g-2">
                                                    <div class="col-md-6">
                                                        <input type="text" name="sub_title[]" class="form-control form-control-sm" placeholder="Sub-Event Title (e.g. Code-A-Thon)" required value="<?php echo htmlspecialchars($se['title']); ?>">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <input type="date" name="sub_date[]" class="form-control form-control-sm" value="<?php echo $se['sub_event_date']; ?>">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <input type="time" name="sub_time[]" class="form-control form-control-sm" value="<?php echo $se['sub_event_time']; ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="text" name="sub_venue[]" class="form-control form-control-sm" placeholder="Venue Location" value="<?php echo htmlspecialchars($se['venue']); ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="text" name="sub_coordinator[]" class="form-control form-control-sm" placeholder="Faculty/Student Coordinator" value="<?php echo htmlspecialchars($se['coordinator']); ?>">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <input type="number" name="sub_max[]" class="form-control form-control-sm" placeholder="Max Capacity (0 = Unlimited)" value="<?php echo $se['max_participants']; ?>">
                                                    </div>
                                                    <div class="col-md-6">
                                                        <textarea name="sub_description[]" class="form-control form-control-sm" rows="2" placeholder="Sub-Event Brief Description"><?php echo htmlspecialchars($se['description']); ?></textarea>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <textarea name="sub_rules[]" class="form-control form-control-sm" rows="2" placeholder="Competition Rules & Guidelines"><?php echo htmlspecialchars($se['rules']); ?></textarea>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="registration_required" id="isRegCheck" <?php echo (!$edit_event || $edit_event['registration_required']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold" for="isRegCheck">Registration Required (Displays register button on website)</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActiveCheck" <?php echo (!$edit_event || $edit_event['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label font-semibold" for="isActiveCheck">Mark as Active (Visible on website)</label>
                            </div>
                        </div>
                        
                        <div class="col-12 mt-4 text-end">
                            <a href="events.php" class="btn btn-secondary me-2">Cancel</a>
                            <button type="submit" name="save_event" class="btn btn-primary px-4"><i class="fa-solid fa-floppy-disk me-1"></i> Save Event & Sub-Events</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- JavaScript to Add Dynamic Sub-Event Rows -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var btnAdd = document.getElementById('btnAddSubEvent');
        var container = document.getElementById('subEventsContainer');
        var subCount = container.children.length;

        btnAdd.addEventListener('click', function() {
            subCount++;
            var div = document.createElement('div');
            div.className = 'sub-event-row border bg-white p-3 rounded mb-3 shadow-sm position-relative';
            div.innerHTML = `
                <input type="hidden" name="sub_id[]" value="0">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <strong class="text-primary-color"><i class="fa-solid fa-flag text-warning me-1"></i> Sub-Event #${subCount}</strong>
                    <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-remove-sub"><i class="fa-solid fa-times me-1"></i> Remove</button>
                </div>
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" name="sub_title[]" class="form-control form-control-sm" placeholder="Sub-Event Title (e.g. Robo Wars)" required>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="sub_date[]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-3">
                        <input type="time" name="sub_time[]" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="sub_venue[]" class="form-control form-control-sm" placeholder="Venue Location">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="sub_coordinator[]" class="form-control form-control-sm" placeholder="Coordinator Name">
                    </div>
                    <div class="col-md-4">
                        <input type="number" name="sub_max[]" class="form-control form-control-sm" placeholder="Max Capacity (0 = Unlimited)">
                    </div>
                    <div class="col-md-6">
                        <textarea name="sub_description[]" class="form-control form-control-sm" rows="2" placeholder="Sub-Event Brief Description"></textarea>
                    </div>
                    <div class="col-md-6">
                        <textarea name="sub_rules[]" class="form-control form-control-sm" rows="2" placeholder="Competition Rules & Guidelines"></textarea>
                    </div>
                </div>
            `;
            container.appendChild(div);

            div.querySelector('.btn-remove-sub').addEventListener('click', function() {
                div.remove();
            });
        });
    });
    </script>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const regSearch = document.getElementById('regSearchInput');
    const regTable = document.getElementById('registrationsTable');
    if (regSearch && regTable) {
        regSearch.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            regTable.querySelectorAll('tbody tr').forEach(row => {
                const text = row.innerText.toLowerCase();
                row.style.display = (!q || text.includes(q)) ? '' : 'none';
            });
        });
    }
});
</script>

<?php include_once __DIR__ . '/../includes/footer.php'; ?>
