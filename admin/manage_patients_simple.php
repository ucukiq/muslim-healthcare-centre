<?php
// Simple version without session check for testing
include '../includes/config.php';

// Fetch patients data
$patients_query = "SELECT * FROM patients ORDER BY full_name";
$patients_result = $conn->query($patients_query);
$patients = $patients_result->fetch_all(MYSQLI_ASSOC);

// Get statistics
$total_patients = count($patients);
$active_patients = 0;
$inactive_patients = 0;

foreach ($patients as $patient) {
    if ($patient['status'] == 'Active') {
        $active_patients++;
    } else {
        $inactive_patients++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Patients - Healthcare System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        .healthcare-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .patient-card {
            transition: transform 0.2s;
        }
        .patient-card:hover {
            transform: translateY(-2px);
        }
        .status-badge {
            font-size: 0.8em;
            padding: 4px 8px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="healthcare-header p-4 mb-4">
                    <h1><i class="fas fa-users me-2"></i>Manage Patients</h1>
                    <p class="mb-0">Patient Management System</p>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card patient-card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-users me-2"></i>Total Patients</h5>
                        <h3><?php echo $total_patients; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card patient-card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user-check me-2"></i>Active</h5>
                        <h3><?php echo $active_patients; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card patient-card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-user-times me-2"></i>Inactive</h5>
                        <h3><?php echo $inactive_patients; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card patient-card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-calendar me-2"></i>This Month</h5>
                        <h3>0</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Patients Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Patient List</h5>
                <button class="btn btn-primary" onclick="alert('Add Patient Feature')">
                    <i class="fas fa-plus me-1"></i>Add Patient
                </button>
            </div>
            <div class="card-body">
                <?php if (count($patients) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Age</th>
                                    <th>Gender</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patients as $patient): ?>
                                    <tr>
                                        <td><?php echo $patient['id']; ?></td>
                                        <td><?php echo $patient['full_name']; ?></td>
                                        <td><?php echo $patient['age']; ?></td>
                                        <td><?php echo $patient['gender']; ?></td>
                                        <td><?php echo $patient['phone']; ?></td>
                                        <td><?php echo $patient['email']; ?></td>
                                        <td>
                                            <span class="badge status-badge <?php echo $patient['status'] == 'Active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $patient['status']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-info" onclick="alert('View Patient: <?php echo $patient['full_name']; ?>')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button class="btn btn-sm btn-warning" onclick="alert('Edit Patient: <?php echo $patient['full_name']; ?>')">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger" onclick="alert('Delete Patient: <?php echo $patient['full_name']; ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5>No Patients Found</h5>
                        <p class="text-muted">Start by adding your first patient</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Navigation -->
        <div class="mt-4">
            <a href="dashboard.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Back to Dashboard
            </a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
