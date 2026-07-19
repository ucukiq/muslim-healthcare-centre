<?php
// Initialize the session
session_start();

// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'patient'){
    header("location: ../login.php");
    exit;
}

// Include config file
require_once "../includes/config.php";

// Get patient ID
$patient_id = '';
$sql = "SELECT id, full_name FROM patients WHERE user_id = ?";
if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $_SESSION["user_id"]);
    if($stmt->execute()){
        $result = $stmt->get_result();
        if($row = $result->fetch_assoc()){
            $patient_id = $row['id'];
            $patient_name = $row['full_name'];
        }
    }
    $stmt->close();
}

// Fetch patient's medical records
$medical_records = [];
$sql = "SELECT m.*, 
        d.full_name as doctor_name, 
        d.specialization,
        a.appointment_date,
        a.start_time
        FROM medical_records m
        JOIN doctors d ON m.doctor_id = d.id 
        LEFT JOIN appointments a ON m.appointment_id = a.id
        WHERE m.patient_id = ?
        ORDER BY m.visit_date DESC, m.created_at DESC";

if($stmt = $conn->prepare($sql)){
    $stmt->bind_param("i", $patient_id);
    if($stmt->execute()){
        $result = $stmt->get_result();
        $medical_records = $result->fetch_all(MYSQLI_ASSOC);
    }
    $stmt->close();
}

// Close connection
$conn->close();
?>

<?php $page_title = "My Medical Records"; ?>
<?php include('../includes/header.php'); ?>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0">My Medical Records</h2>
            <p class="text-muted mb-0">Your complete medical history</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
        </a>
    </div>

    <?php if (empty($medical_records)): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            No medical records found.
        </div>
    <?php else: ?>
        <div class="row">
            <div class="col-md-4 col-lg-3">
                <!-- Patient Summary Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Patient Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-3">
                            <div class="avatar avatar-xl bg-primary bg-opacity-10 text-primary rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 80px; height: 80px;">
                                <i class="fas fa-user-md fa-2x"></i>
                            </div>
                            <h5 class="mt-3 mb-1"><?php echo htmlspecialchars($patient_name); ?></h5>
                            <p class="text-muted">Patient ID: <?php echo $patient_id; ?></p>
                        </div>
                        <hr>
                        <div class="mb-2">
                            <p class="mb-1"><strong>Total Records:</strong> <?php echo count($medical_records); ?></p>
                            <?php 
                            $last_visit = !empty($medical_records) ? new DateTime($medical_records[0]['visit_date']) : null;
                            if ($last_visit): 
                            ?>
                                <p class="mb-0"><strong>Last Visit:</strong> <?php echo $last_visit->format('M j, Y'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">Filter Records</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Date Range</label>
                            <select class="form-select" id="dateFilter">
                                <option value="all">All Time</option>
                                <option value="today">Today</option>
                                <option value="week">This Week</option>
                                <option value="month">This Month</option>
                                <option value="year">This Year</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Doctor</label>
                            <select class="form-select" id="doctorFilter">
                                <option value="all">All Doctors</option>
                                <?php
                                $doctors = [];
                                foreach($medical_records as $record) {
                                    $doctors[$record['doctor_id']] = $record['doctor_name'];
                                }
                                foreach($doctors as $id => $name):
                                ?>
                                    <option value="<?php echo $id; ?>"><?php echo htmlspecialchars($name); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn btn-primary w-100" id="applyFilters">
                            <i class="fas fa-filter me-1"></i> Apply Filters
                        </button>
                    </div>
                </div>
            </div>

            <div class="col-md-8 col-lg-9">
                <!-- Records List -->
                <div class="card">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Medical History</h5>
                        <div>
                            <button class="btn btn-sm btn-outline-secondary me-1" id="listViewBtn" title="List View">
                                <i class="fas fa-list"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-secondary" id="timelineViewBtn" title="Timeline View">
                                <i class="fas fa-stream"></i>
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <!-- List View -->
                        <div id="listView">
                            <div class="list-group list-group-flush">
                                <?php foreach ($medical_records as $record): 
                                    $visit_date = new DateTime($record['visit_date']);
                                    $symptoms = $record['symptoms'] ? json_decode($record['symptoms'], true) : [];
                                ?>
                                <div class="list-group-item list-group-item-action p-3 record-item" 
                                     data-id="<?php echo $record['id']; ?>"
                                     data-date="<?php echo $visit_date->format('Y-m-d'); ?>"
                                     data-doctor="<?php echo $record['doctor_id']; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="me-3">
                                            <div class="d-flex align-items-center mb-1">
                                                <h6 class="mb-0 me-2">
                                                    <?php echo $visit_date->format('F j, Y'); ?>
                                                    <?php if ($record['appointment_date']): ?>
                                                        <span class="badge bg-primary ms-2">Appointment</span>
                                                    <?php endif; ?>
                                                </h6>
                                            </div>
                                            <p class="mb-1 text-muted">
                                                <i class="fas fa-user-md me-1"></i> 
                                                <?php 
                                                $doctor_name = $record['doctor_name'];
                                                if (strpos($doctor_name, 'Dr. ') !== 0) {
                                                    $doctor_name = 'Dr. ' . $doctor_name;
                                                }
                                                echo htmlspecialchars($doctor_name); 
                                                ?>
                                                <span class="ms-2">•</span>
                                                <span class="ms-2"><?php echo htmlspecialchars($record['specialization']); ?></span>
                                            </p>
                                            
                                            <?php if (!empty($symptoms) && is_array($symptoms)): ?>
                                                <div class="symptoms mb-2">
                                                    <span class="fw-semibold">Symptoms:</span>
                                                    <?php foreach($symptoms as $symptom): ?>
                                                        <span class="badge bg-light text-dark border me-1"><?php echo htmlspecialchars($symptom); ?></span>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($record['diagnosis'])): ?>
                                                <div class="diagnosis mb-2">
                                                    <span class="fw-semibold">Diagnosis:</span>
                                                    <span class="ms-1"><?php echo nl2br(htmlspecialchars($record['diagnosis'])); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <button class="btn btn-sm btn-outline-primary view-record" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#recordModal"
                                                    data-id="<?php echo $record['id']; ?>"
                                                    data-date="<?php echo $visit_date->format('F j, Y'); ?>"
                                                    data-doctor="<?php echo htmlspecialchars($record['doctor_name']); ?>"
                                                    data-specialization="<?php echo htmlspecialchars($record['specialization']); ?>"
                                                    data-symptoms="<?php echo htmlspecialchars($record['symptoms']); ?>"
                                                    data-diagnosis="<?php echo htmlspecialchars($record['diagnosis']); ?>"
                                                    data-treatment="<?php echo htmlspecialchars($record['treatment']); ?>"
                                                    data-notes="<?php echo htmlspecialchars($record['notes']); ?>">
                                                <i class="fas fa-eye me-1"></i> View
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <!-- Timeline View (initially hidden) -->
                        <div id="timelineView" style="display: none;">
                            <div class="timeline p-4">
                                <?php 
                                $current_year = null;
                                foreach ($medical_records as $record): 
                                    $visit_date = new DateTime($record['visit_date']);
                                    $year = $visit_date->format('Y');
                                    $month = $visit_date->format('F');
                                    $day = $visit_date->format('j');
                                    
                                    // Show year header if it's a new year
                                    if ($year !== $current_year): 
                                        $current_year = $year;
                                ?>
                                    <div class="timeline-year">
                                        <h5 class="text-center my-4"><?php echo $year; ?></h5>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="timeline-item" 
                                     data-id="<?php echo $record['id']; ?>"
                                     data-date="<?php echo $visit_date->format('Y-m-d'); ?>"
                                     data-doctor="<?php echo $record['doctor_id']; ?>">
                                    <div class="timeline-badge">
                                        <i class="fas fa-calendar-check"></i>
                                    </div>
                                    <div class="timeline-panel">
                                        <div class="timeline-heading">
                                            <div class="d-flex justify-content-between">
                                                <h6 class="timeline-title">
                                                    <?php echo $visit_date->format('F j, Y'); ?>
                                                    <?php if ($record['appointment_date']): ?>
                                                        <span class="badge bg-primary ms-2">Appointment</span>
                                                    <?php endif; ?>
                                                </h6>
                                                <button class="btn btn-sm btn-outline-primary btn-sm view-record" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#recordModal"
                                                        data-id="<?php echo $record['id']; ?>"
                                                        data-date="<?php echo $visit_date->format('F j, Y'); ?>"
                                                        data-doctor="<?php echo htmlspecialchars($record['doctor_name']); ?>"
                                                        data-specialization="<?php echo htmlspecialchars($record['specialization']); ?>"
                                                        data-symptoms="<?php echo htmlspecialchars($record['symptoms']); ?>"
                                                        data-diagnosis="<?php echo htmlspecialchars($record['diagnosis']); ?>"
                                                        data-treatment="<?php echo htmlspecialchars($record['treatment']); ?>"
                                                        data-notes="<?php echo htmlspecialchars($record['notes']); ?>">
                                                    View Details
                                                </button>
                                            </div>
                                            <p class="text-muted mb-2">
                                                <i class="fas fa-user-md me-1"></i> 
                                                <?php 
                                                $doctor_name = $record['doctor_name'];
                                                if (strpos($doctor_name, 'Dr. ') !== 0) {
                                                    $doctor_name = 'Dr. ' . $doctor_name;
                                                }
                                                echo htmlspecialchars($doctor_name); 
                                                ?>
                                                <span class="ms-2">•</span>
                                                <span class="ms-2"><?php echo htmlspecialchars($record['specialization']); ?></span>
                                            </p>
                                        </div>
                                        <div class="timeline-body">
                                            <?php if (!empty($record['diagnosis'])): ?>
                                                <p class="mb-2">
                                                    <strong>Diagnosis:</strong> 
                                                    <?php 
                                                    $diagnosis = $record['diagnosis'];
                                                    echo strlen($diagnosis) > 150 ? 
                                                        htmlspecialchars(substr($diagnosis, 0, 150)) . '...' : 
                                                        htmlspecialchars($diagnosis); 
                                                    ?>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <?php 
                                            $symptoms = $record['symptoms'] ? json_decode($record['symptoms'], true) : [];
                                            if (!empty($symptoms) && is_array($symptoms)): 
                                            ?>
                                                <div class="symptoms">
                                                    <strong>Symptoms:</strong>
                                                    <div class="mt-1">
                                                        <?php foreach(array_slice($symptoms, 0, 3) as $symptom): ?>
                                                            <span class="badge bg-light text-dark border me-1 mb-1">
                                                                <?php echo htmlspecialchars($symptom); ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                        <?php if (count($symptoms) > 3): ?>
                                                            <span class="badge bg-light text-muted border">
                                                                +<?php echo count($symptoms) - 3; ?> more
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Record Details Modal -->
<div class="modal fade" id="recordModal" tabindex="-1" aria-labelledby="recordModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recordModalLabel">Medical Record Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="record-header mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <h4>Healthcare Centre</h4>
                            <p class="mb-1">123 Medical Drive</p>
                            <p class="mb-1">City, State 12345</p>
                            <p class="mb-0">Phone: (123) 456-7890</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="mb-1"><strong>Date:</strong> <span id="recordDate"></span></p>
                            <p class="mb-1"><strong>Doctor:</strong> <span id="recordDoctor"></span></p>
                            <p class="mb-0"><strong>Specialization:</strong> <span id="recordSpecialization"></span></p>
                        </div>
                    </div>
                    <hr>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5>Patient Information</h5>
                            <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($patient_name); ?></p>
                            <p class="mb-0"><strong>Patient ID:</strong> <?php echo $patient_id; ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="symptoms-section mb-4">
                    <h5>Symptoms</h5>
                    <div id="recordSymptoms" class="p-3 bg-light rounded">
                        <div class="symptoms-list"></div>
                    </div>
                </div>
                
                <div class="diagnosis-section mb-4">
                    <h5>Diagnosis</h5>
                    <div id="recordDiagnosis" class="p-3 bg-light rounded"></div>
                </div>
                
                <div class="treatment-section mb-4">
                    <h5>Treatment</h5>
                    <div id="recordTreatment" class="p-3 bg-light rounded"></div>
                </div>
                
                <div class="notes-section">
                    <h5>Doctor's Notes</h5>
                    <div id="recordNotes" class="p-3 bg-light rounded"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i> Print
                </button>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle between list and timeline view
    const listViewBtn = document.getElementById('listViewBtn');
    const timelineViewBtn = document.getElementById('timelineViewBtn');
    const listView = document.getElementById('listView');
    const timelineView = document.getElementById('timelineView');
    
    listViewBtn.addEventListener('click', function() {
        listView.style.display = 'block';
        timelineView.style.display = 'none';
        listViewBtn.classList.add('active');
        timelineViewBtn.classList.remove('active');
    });
    
    timelineViewBtn.addEventListener('click', function() {
        listView.style.display = 'none';
        timelineView.style.display = 'block';
        listViewBtn.classList.remove('active');
        timelineViewBtn.classList.add('active');
    });
    
    // Initialize with list view active
    listViewBtn.classList.add('active');
    
    // Handle view record button click
    document.querySelectorAll('.view-record').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('recordDate').textContent = this.getAttribute('data-date');
            document.getElementById('recordDoctor').textContent = this.getAttribute('data-doctor');
            document.getElementById('recordSpecialization').textContent = this.getAttribute('data-specialization');
            
            // Handle symptoms (JSON array)
            const symptoms = JSON.parse(this.getAttribute('data-symptoms') || '[]');
            const symptomsContainer = document.querySelector('#recordSymptoms .symptoms-list');
            symptomsContainer.innerHTML = '';
            
            if (symptoms.length > 0) {
                symptoms.forEach(symptom => {
                    const badge = document.createElement('span');
                    badge.className = 'badge bg-light text-dark border me-1 mb-1';
                    badge.textContent = symptom;
                    symptomsContainer.appendChild(badge);
                });
            } else {
                symptomsContainer.innerHTML = '<p class="mb-0 text-muted">No symptoms recorded</p>';
            }
            
            // Set other fields
            document.getElementById('recordDiagnosis').innerHTML = this.getAttribute('data-diagnosis') || 
                '<p class="mb-0 text-muted">No diagnosis recorded</p>';
                
            document.getElementById('recordTreatment').innerHTML = this.getAttribute('data-treatment') || 
                '<p class="mb-0 text-muted">No treatment details recorded</p>';
                
            document.getElementById('recordNotes').innerHTML = this.getAttribute('data-notes') || 
                '<p class="mb-0 text-muted">No additional notes</p>';
        });
    });
    
    // Handle filter application
    document.getElementById('applyFilters').addEventListener('click', function() {
        const dateFilter = document.getElementById('dateFilter').value;
        const doctorFilter = document.getElementById('doctorFilter').value;
        const now = new Date();
        
        document.querySelectorAll('.record-item, .timeline-item').forEach(item => {
            const itemDate = new Date(item.getAttribute('data-date'));
            const itemDoctor = item.getAttribute('data-doctor');
            let showItem = true;
            
            // Apply date filter
            if (dateFilter !== 'all') {
                const itemYear = itemDate.getFullYear();
                const itemMonth = itemDate.getMonth();
                const itemDay = itemDate.getDate();
                
                const today = new Date();
                const startOfWeek = new Date(today);
                startOfWeek.setDate(today.getDate() - today.getDay()); // Start of current week (Sunday)
                const endOfWeek = new Date(today);
                endOfWeek.setDate(today.getDate() + (6 - today.getDay())); // End of current week (Saturday)
                
                const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
                const endOfMonth = new Date(today.getFullYear(), today.getMonth() + 1, 0);
                
                const startOfYear = new Date(today.getFullYear(), 0, 1);
                const endOfYear = new Date(today.getFullYear(), 11, 31);
                
                switch(dateFilter) {
                    case 'today':
                        showItem = itemDate.toDateString() === today.toDateString();
                        break;
                    case 'week':
                        showItem = itemDate >= startOfWeek && itemDate <= endOfWeek;
                        break;
                    case 'month':
                        showItem = itemDate >= startOfMonth && itemDate <= endOfMonth;
                        break;
                    case 'year':
                        showItem = itemDate >= startOfYear && itemDate <= endOfYear;
                        break;
                }
            }
            
            // Apply doctor filter
            if (showItem && doctorFilter !== 'all') {
                showItem = itemDoctor === doctorFilter;
            }
            
            // Show/hide item based on filters
            item.style.display = showItem ? '' : 'none';
        });
    });
});
</script>

<style>
/* Timeline View Styles */
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-year {
    position: relative;
    margin: 2rem 0;
    text-align: center;
}

.timeline-year h5 {
    display: inline-block;
    background: #f8f9fa;
    padding: 0.5rem 1.5rem;
    border-radius: 20px;
    font-weight: 600;
    color: #495057;
    border: 1px solid #dee2e6;
}

.timeline:before {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    left: 10px;
    width: 2px;
    background: #e9ecef;
}

.timeline-item {
    position: relative;
    margin-bottom: 2rem;
    padding-left: 30px;
}

.timeline-badge {
    position: absolute;
    left: 0;
    top: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #0d6efd;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1;
    margin-left: -9px;
}

.timeline-panel {
    background: white;
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    padding: 1.25rem;
    position: relative;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.timeline-panel:before {
    content: '';
    position: absolute;
    top: 20px;
    left: -15px;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 10px 15px 10px 0;
    border-color: transparent #e9ecef transparent transparent;
}

.timeline-panel:after {
    content: '';
    position: absolute;
    top: 21px;
    left: -14px;
    width: 0;
    height: 0;
    border-style: solid;
    border-width: 9px 14px 9px 0;
    border-color: transparent white transparent transparent;
}

.timeline-title {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    color: #212529;
}

.timeline-body {
    color: #6c757d;
    font-size: 0.9rem;
}

/* Active state for view buttons */
#listViewBtn.active,
#timelineViewBtn.active {
    background-color: #0d6efd;
    color: white;
}

/* Print styles */
@media print {
    body * {
        visibility: hidden;
    }
    .modal,
    .modal * {
        visibility: visible;
    }
    .modal {
        position: absolute;
        left: 0;
        top: 0;
        margin: 0;
        padding: 0;
        min-height: 100%;
        width: 100%;
    }
    .modal-dialog {
        max-width: 100%;
        width: 100%;
        margin: 0;
    }
    .record-header {
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 1rem;
        margin-bottom: 1.5rem;
    }
    .symptoms-section, 
    .diagnosis-section, 
    .treatment-section, 
    .notes-section {
        margin-bottom: 1.5rem;
    }
    .modal-footer {
        display: none;
    }
    
    /* Timeline specific print styles */
    .timeline:before {
        display: none;
    }
    
    .timeline-item {
        padding-left: 0;
        margin-bottom: 1.5rem;
        page-break-inside: avoid;
    }
    
    .timeline-badge {
        display: none;
    }
    
    .timeline-panel {
        box-shadow: none;
        border: 1px solid #dee2e6;
        page-break-inside: avoid;
    }
    
    .timeline-panel:before,
    .timeline-panel:after {
        display: none;
    }
    
    .timeline-year {
        page-break-after: avoid;
    }
}
</style>
