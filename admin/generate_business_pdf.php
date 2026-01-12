<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Business.php';
require_once '../models/Inspection.php';
require_once '../utils/access_control.php';
require_once '../vendor/autoload.php'; // For mPDF

// Check if user is logged in and has permission
requirePermission('businesses');

if (!isset($_GET['id'])) {
    die('Business ID not provided.');
}

$business_id = $_GET['id'];

$database = new Database();
$business = new Business($database);
$business->id = $business_id;
$business_data = $business->readOne();

if (!$business_data) {
    die('Business not found.');
}

// Fetch stats and inspections
$compliance_stats = $business->getComplianceStats($business_id);
$recent_inspections = $business->getRecentInspections($business_id, 10); 

// Logo path for mPDF (server path)
$logo_path = '../logo/logo.jpeg';

// Prepare HTML content
$html = '
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; color: #333; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #009688; padding-bottom: 10px; }
        .logo { width: 50px; vertical-align: middle; }
        .title { font-size: 20px; font-weight: bold; color: #009688; vertical-align: middle; margin-left: 10px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 14px; font-weight: bold; color: #00796B; border-bottom: 1px solid #B2DFDB; padding-bottom: 5px; margin-bottom: 10px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px; vertical-align: top; }
        .label { font-weight: bold; color: #555; width: 120px; }
        .value { color: #000; }
        
        .stats-table { width: 100%; border-collapse: separate; border-spacing: 5px; }
        .stat-box { background-color: #F2F9F9; padding: 10px; border: 1px solid #E0F2F1; text-align: center; border-radius: 5px; }
        .stat-value { font-size: 16px; font-weight: bold; color: #009688; display: block; }
        .stat-label { font-size: 9px; color: #666; text-transform: uppercase; margin-top: 5px; display: block; }

        table.inspections { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table.inspections th { background-color: #009688; color: white; padding: 6px; text-align: left; font-size: 11px; }
        table.inspections td { border-bottom: 1px solid #eee; padding: 6px; font-size: 11px; }
        
        .footer { position: fixed; bottom: 0; width: 100%; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <img src="' . $logo_path . '" class="logo">
        <span class="title">Business Compliance Report</span>
    </div>

    <div class="section">
        <div class="section-title">Business Information</div>
        <table class="info-table">
            <tr>
                <td class="label">Business Name:</td>
                <td class="value"><strong>' . htmlspecialchars($business_data['name']) . '</strong></td>
                <td class="label">Registration No:</td>
                <td class="value">' . htmlspecialchars($business_data['registration_number']) . '</td>
            </tr>
            <tr>
                <td class="label">Type:</td>
                <td class="value">' . htmlspecialchars($business_data['business_type']) . '</td>
                <td class="label">Contact:</td>
                <td class="value">' . htmlspecialchars($business_data['contact_number']) . '</td>
            </tr>
            <tr>
                <td class="label">Address:</td>
                <td class="value" colspan="3">' . htmlspecialchars($business_data['address']) . '</td>
            </tr>
            <tr>
                <td class="label">Email:</td>
                <td class="value" colspan="3">' . htmlspecialchars($business_data['email']) . '</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Compliance Overview</div>
        <table class="stats-table">
            <tr>
                <td class="stat-box">
                    <span class="stat-value">' . $compliance_stats['total_inspections'] . '</span>
                    <span class="stat-label">Total Inspections</span>
                </td>
                <td class="stat-box">
                    <span class="stat-value">' . $compliance_stats['avg_compliance'] . '%</span>
                    <span class="stat-label">Avg. Compliance</span>
                </td>
                <td class="stat-box">
                    <span class="stat-value">' . $compliance_stats['total_violations'] . '</span>
                    <span class="stat-label">Total Violations</span>
                </td>
                <td class="stat-box">
                    <span class="stat-value">' . $compliance_stats['compliance_rate'] . '%</span>
                    <span class="stat-label">Completion Rate</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Recent Inspection History</div>
        <table class="inspections">
            <thead>
                <tr>
                    <th width="20%">Date</th>
                    <th width="25%">Type</th>
                    <th width="25%">Inspector</th>
                    <th width="15%">Status</th>
                    <th width="15%">Score</th>
                </tr>
            </thead>
            <tbody>';

if (empty($recent_inspections)) {
    $html .= '<tr><td colspan="5" style="text-align: center; padding: 15px;">No inspections recorded.</td></tr>';
} else {
    foreach ($recent_inspections as $inspection) {
        $statusColor = '#666';
        if ($inspection['status'] == 'completed') $statusColor = '#10B981';
        if ($inspection['status'] == 'scheduled') $statusColor = '#3B82F6';
        if ($inspection['status'] == 'in_progress') $statusColor = '#F59E0B';
        
        $score = $inspection['compliance_score'] ? $inspection['compliance_score'] . '%' : 'N/A';
        
        $html .= '<tr>
            <td>' . date('M j, Y', strtotime($inspection['scheduled_date'])) . '</td>
            <td>' . htmlspecialchars($inspection['inspection_type']) . '</td>
            <td>' . htmlspecialchars($inspection['inspector_name']) . '</td>
            <td style="color: ' . $statusColor . '; font-weight: bold;">' . ucfirst($inspection['status']) . '</td>
            <td>' . $score . '</td>
        </tr>';
    }
}

$html .= '
            </tbody>
        </table>
    </div>

    <div class="footer">
        Generated on ' . date('F j, Y g:i A') . ' by ' . htmlspecialchars($_SESSION['user_name']) . ' | LGU Health & Safety Inspection Platform
    </div>
</body>
</html>';

// Initialize mPDF
try {
    $mpdf = new mPDF('utf-8', 'A4', 0, '', 15, 15, 15, 20, 10, 10);

    $mpdf->SetTitle('Business Report - ' . $business_data['name']);
    $mpdf->SetAuthor('LGU Health & Safety Platform');
    $mpdf->WriteHTML($html);
    
    // Output PDF
    $mpdf->Output('Business_Report_' . preg_replace('/[^a-zA-Z0-9]/', '_', $business_data['name']) . '.pdf', 'I');

} catch (MpdfException $e) {
    echo 'PDF Generation Error: ' . $e->getMessage();
}
?>