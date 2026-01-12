<?php
require_once '../utils/session_manager.php';
require_once '../config/database.php';
require_once '../models/User.php';
require_once '../models/Business.php';
require_once '../models/Inspection.php';
require_once '../utils/access_control.php';

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

// Logo path
$logo_path = '../logo/logo.jpeg';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Business Report - <?php echo htmlspecialchars($business_data['name']); ?></title>
    <style>
        body { font-family: sans-serif; color: #333; font-size: 12px; max-width: 800px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #009688; padding-bottom: 10px; }
        .logo { width: 50px; vertical-align: middle; }
        .title { font-size: 20px; font-weight: bold; color: #009688; vertical-align: middle; margin-left: 10px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 14px; font-weight: bold; color: #00796B; border-bottom: 1px solid #B2DFDB; padding-bottom: 5px; margin-bottom: 10px; }
        .info-table { width: 100%; border-collapse: collapse; }
        .info-table td { padding: 5px; vertical-align: top; }
        .label { font-weight: bold; color: #555; width: 120px; }
        .value { color: #000; }
        
        .stats-container { display: flex; justify-content: space-between; gap: 10px; }
        .stat-box { background-color: #F2F9F9; padding: 10px; border: 1px solid #E0F2F1; text-align: center; border-radius: 5px; flex: 1; }
        .stat-value { font-size: 16px; font-weight: bold; color: #009688; display: block; }
        .stat-label { font-size: 9px; color: #666; text-transform: uppercase; margin-top: 5px; display: block; }

        table.inspections { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table.inspections th { background-color: #009688; color: white; padding: 6px; text-align: left; font-size: 11px; }
        table.inspections td { border-bottom: 1px solid #eee; padding: 6px; font-size: 11px; }
        
        .footer { margin-top: 30px; text-align: center; font-size: 9px; color: #999; border-top: 1px solid #eee; padding-top: 10px; }
        
        /* Print specific styles */
        @media print {
            .no-print { display: none !important; }
            body { padding: 0; max-width: 100%; }
            .stat-box { background-color: #f0fdfa !important; -webkit-print-color-adjust: exact; }
            table.inspections th { background-color: #009688 !important; -webkit-print-color-adjust: exact; }
        }
        
        .print-controls {
            background: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            margin: -20px -20px 20px -20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            font-size: 13px;
            display: inline-flex;
            align-items: center;
        }
        .btn-print { background: #009688; color: white; border: none; }
        .btn-print:hover { background: #00796B; }
        .btn-close { background: white; color: #555; border: 1px solid #ccc; margin-right: 10px; }
        .btn-close:hover { background: #f3f4f6; }
    </style>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="print-controls no-print">
        <div style="font-weight: bold; font-size: 14px; color: #333;">Print Preview</div>
        <div>
            <button onclick="window.close()" class="btn btn-close">Close</button>
            <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print" style="margin-right: 8px;"></i> Print Report</button>
        </div>
    </div>

    <div class="header">
        <img src="<?php echo $logo_path; ?>" class="logo">
        <span class="title">Business Compliance Report</span>
    </div>

    <div class="section">
        <div class="section-title">Business Information</div>
        <table class="info-table">
            <tr>
                <td class="label">Business Name:</td>
                <td class="value"><strong><?php echo htmlspecialchars($business_data['name']); ?></strong></td>
                <td class="label">Registration No:</td>
                <td class="value"><?php echo htmlspecialchars($business_data['registration_number']); ?></td>
            </tr>
            <tr>
                <td class="label">Type:</td>
                <td class="value"><?php echo htmlspecialchars($business_data['business_type']); ?></td>
                <td class="label">Contact:</td>
                <td class="value"><?php echo htmlspecialchars($business_data['contact_number']); ?></td>
            </tr>
            <tr>
                <td class="label">Address:</td>
                <td class="value" colspan="3"><?php echo htmlspecialchars($business_data['address']); ?></td>
            </tr>
            <tr>
                <td class="label">Email:</td>
                <td class="value" colspan="3"><?php echo htmlspecialchars($business_data['email']); ?></td>
            </tr>
        </table>
    </div>

    <div class="section">
        <div class="section-title">Compliance Overview</div>
        <div class="stats-container">
            <div class="stat-box">
                <span class="stat-value"><?php echo $compliance_stats['total_inspections']; ?></span>
                <span class="stat-label">Total Inspections</span>
            </div>
            <div class="stat-box">
                <span class="stat-value"><?php echo $compliance_stats['avg_compliance']; ?>%</span>
                <span class="stat-label">Avg. Compliance</span>
            </div>
            <div class="stat-box">
                <span class="stat-value"><?php echo $compliance_stats['total_violations']; ?></span>
                <span class="stat-label">Total Violations</span>
            </div>
            <div class="stat-box">
                <span class="stat-value"><?php echo $compliance_stats['compliance_rate']; ?>%</span>
                <span class="stat-label">Completion Rate</span>
            </div>
        </div>
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
            <tbody>
            <?php if (empty($recent_inspections)): ?>
                <tr><td colspan="5" style="text-align: center; padding: 15px;">No inspections recorded.</td></tr>
            <?php else: ?>
                <?php foreach ($recent_inspections as $inspection): 
                    $statusColor = '#666';
                    if ($inspection['status'] == 'completed') $statusColor = '#10B981';
                    if ($inspection['status'] == 'scheduled') $statusColor = '#3B82F6';
                    if ($inspection['status'] == 'in_progress') $statusColor = '#F59E0B';
                    
                    $score = $inspection['compliance_score'] ? $inspection['compliance_score'] . '%' : 'N/A';
                ?>
                <tr>
                    <td><?php echo date('M j, Y', strtotime($inspection['scheduled_date'])); ?></td>
                    <td><?php echo htmlspecialchars($inspection['inspection_type']); ?></td>
                    <td><?php echo htmlspecialchars($inspection['inspector_name']); ?></td>
                    <td style="color: <?php echo $statusColor; ?>; font-weight: bold;"><?php echo ucfirst($inspection['status']); ?></td>
                    <td><?php echo $score; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="footer">
        Generated on <?php echo date('F j, Y g:i A'); ?> by <?php echo htmlspecialchars($_SESSION['user_name']); ?> | LGU Health & Safety Inspection Platform
    </div>
</body>
</html>