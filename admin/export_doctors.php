<?php
// Initialize the session
session_start();
 
// Check if the user is logged in, if not then redirect to login page
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'admin'){
    header("location: ../login.php");
    exit;
}

// Include config file and functions
require_once "../includes/config.php";
require_once "doctor_functions.php";

// Get export type
$exportType = $_GET['type'] ?? 'excel';

// Get filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';
$specialization_filter = $_GET['specialization'] ?? 'all';

$filters = [
    'search' => $search,
    'status' => $status_filter,
    'specialization' => $specialization_filter
];

// Get doctors data
$doctors = exportDoctorsToExcel($conn, $filters);

if($exportType == 'excel') {
    // Export to Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="doctors_export_' . date('Y-m-d') . '.xls"');
    header('Cache-Control: max-age=0');
    
    echo "<table border='1'>";
    echo "<thead>";
    echo "<tr>";
    echo "<th>Doctor ID</th>";
    echo "<th>Full Name</th>";
    echo "<th>NRIC</th>";
    echo "<th>Age</th>";
    echo "<th>Email</th>";
    echo "<th>Phone</th>";
    echo "<th>Address</th>";
    echo "<th>Specialization</th>";
    echo "<th>Status</th>";
    echo "<th>Username</th>";
    echo "<th>Created At</th>";
    echo "</tr>";
    echo "</thead>";
    echo "<tbody>";
    
    foreach($doctors as $doctor) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($doctor['doctor_id']) . "</td>";
        echo "<td>" . htmlspecialchars($doctor['full_name']) . "</td>";
        echo "<td>" . htmlspecialchars($doctor['nric'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($doctor['age']) . "</td>";
        echo "<td>" . htmlspecialchars($doctor['email']) . "</td>";
        echo "<td>" . htmlspecialchars($doctor['phone']) . "</td>";
        echo "<td>" . htmlspecialchars($doctor['address'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($doctor['specialization']) . "</td>";
        echo "<td>" . htmlspecialchars($doctor['status']) . "</td>";
        echo "<td>" . htmlspecialchars($doctor['username']) . "</td>";
        echo "<td>" . htmlspecialchars($doctor['created_at']) . "</td>";
        echo "</tr>";
    }
    
    echo "</tbody>";
    echo "</table>";
    
} elseif($exportType == 'pdf') {
    // Export to PDF (using HTML to PDF conversion)
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment;filename="doctors_export_' . date('Y-m-d') . '.pdf"');
    header('Cache-Control: max-age=0');
    
    // Generate HTML for PDF
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Doctors Export</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        h1 { color: #333; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; font-weight: bold; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .header { text-align: center; margin-bottom: 30px; }
        .footer { text-align: center; margin-top: 30px; font-size: 12px; color: #666; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Muslim Healthcare Centre</h1>
        <h2>Doctors List Export</h2>
        <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>Doctor ID</th>
                <th>Full Name</th>
                <th>NRIC</th>
                <th>Age</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Address</th>
                <th>Specialization</th>
                <th>Status</th>
                <th>Username</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach($doctors as $doctor) {
        $html .= '<tr>';
        $html .= '<td>' . htmlspecialchars($doctor['doctor_id']) . '</td>';
        $html .= '<td>' . htmlspecialchars($doctor['full_name']) . '</td>';
        $html .= '<td>' . htmlspecialchars($doctor['nric'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($doctor['age']) . '</td>';
        $html .= '<td>' . htmlspecialchars($doctor['email']) . '</td>';
        $html .= '<td>' . htmlspecialchars($doctor['phone']) . '</td>';
        $html .= '<td>' . htmlspecialchars($doctor['address'] ?? '') . '</td>';
        $html .= '<td>' . htmlspecialchars($doctor['specialization']) . '</td>';
        $html .= '<td>' . htmlspecialchars($doctor['status']) . '</td>';
        $html .= '<td>' . htmlspecialchars($doctor['username']) . '</td>';
        $html .= '<td>' . htmlspecialchars($doctor['created_at']) . '</td>';
        $html .= '</tr>';
    }
    
    $html .= '</tbody>
    </table>
    
    <div class="footer">
        <p>Total Doctors: ' . count($doctors) . '</p>
        <p>&copy; ' . date('Y') . ' Muslim Healthcare Centre. All rights reserved.</p>
    </div>
</body>
</html>';
    
    // For PDF generation, you would typically use a library like TCPDF or DomPDF
    // For now, we'll output the HTML which can be converted to PDF
    echo $html;
}

exit;
?>
