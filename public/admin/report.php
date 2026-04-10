<?php
session_start();
// Check if admin is logged in
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../signin.php');
    exit();
}

$page_title = 'Service Reports | Amuning Tokobe Enterprise';

// Include config for database connection
require_once '../includes/config.php';

// Initialize filter variables
$filter_service_type = isset($_GET['service_type']) ? $_GET['service_type'] : '';
$filter_action_type = isset($_GET['action_type']) ? $_GET['action_type'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$filter_service_id = isset($_GET['service_id']) ? $_GET['service_id'] : '';

// Build the query
$where_clauses = [];
$params = [];
$types = '';

if (!empty($filter_service_type)) {
    $where_clauses[] = "sh.service_type = ?";
    $params[] = $filter_service_type;
    $types .= 's';
}

if (!empty($filter_action_type)) {
    $where_clauses[] = "sh.action_type = ?";
    $params[] = $filter_action_type;
    $types .= 's';
}

if (!empty($filter_date_from)) {
    $where_clauses[] = "DATE(sh.changed_at) >= ?";
    $params[] = $filter_date_from;
    $types .= 's';
}

if (!empty($filter_date_to)) {
    $where_clauses[] = "DATE(sh.changed_at) <= ?";
    $params[] = $filter_date_to;
    $types .= 's';
}

if (!empty($filter_service_id)) {
    $where_clauses[] = "sh.service_id = ?";
    $params[] = intval($filter_service_id);
    $types .= 'i';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

// Get photo and print services for dropdown
$photo_services = [];
$print_services = [];

$photo_result = $conn->query("SELECT id, service_name FROM photo_services ORDER BY service_name");
while ($row = $photo_result->fetch_assoc()) {
    $photo_services[] = $row;
}

$print_result = $conn->query("SELECT id, service_name FROM print_services ORDER BY service_name");
while ($row = $print_result->fetch_assoc()) {
    $print_services[] = $row;
}

// Get service history records
$query = "SELECT 
            sh.id,
            sh.service_id,
            sh.service_type,
            sh.action_type,
            sh.old_values,
            sh.new_values,
            sh.changed_by,
            sh.changed_at,
            CASE 
                WHEN sh.service_type = 'photo' THEN ps.service_name
                WHEN sh.service_type = 'print' THEN prs.service_name
            END as service_name
          FROM service_history sh
          LEFT JOIN photo_services ps ON sh.service_type = 'photo' AND sh.service_id = ps.id
          LEFT JOIN print_services prs ON sh.service_type = 'print' AND sh.service_id = prs.id
          $where_sql
          ORDER BY sh.changed_at DESC
          LIMIT 1000";

$stmt = $conn->prepare($query);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();
$history_records = [];

while ($row = $result->fetch_assoc()) {
    $history_records[] = $row;
}

$stmt->close();

// Helper function to format action type
function formatActionType($action) {
    $actions = [
        'created' => 'Service Created',
        'edited' => 'Service Edited',
        'availability_changed' => 'Availability Changed',
        'deleted' => 'Service Deleted'
    ];
    return isset($actions[$action]) ? $actions[$action] : ucfirst(str_replace('_', ' ', $action));
}

// Helper function to highlight differences
function highlightChanges($old_value, $new_value) {
    if ($old_value === $new_value) {
        return htmlspecialchars($new_value);
    }
    return '<span class="highlight-change">' . htmlspecialchars($new_value) . '</span> <span class="change-badge">(changed)</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link rel="icon" type="image/x-icon" href="../../images/amuninglogo.ico">
    <link rel="shortcut icon" href="../../images/amuninglogo.ico" type="image/x-icon">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/admin-sidebar.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/orders.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="admin-container">
        <?php include '../includes/admin_sidebar.php'; ?>
        
        <main class="admin-main">
            <header class="main-header">
                <button class="sidebar-toggle" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Service Reports</h1>
            </header>

            <div class="main-content">
                <div class="content-wrapper">
                    <div class="reports-container">
                        <div class="reports-header">
                            <h1><i class="fas fa-chart-line"></i> Service Reports</h1>
                        </div>

                        <!-- Filters Section -->
                        <div class="reports-filters">
                            <form method="GET" action="report.php">
                                <div class="reports-filters-grid">
                                    <div class="reports-filter-group">
                                        <label for="service_type">Service Type</label>
                                        <select id="service_type" name="service_type">
                                            <option value="">All Types</option>
                                            <option value="photo" <?php echo $filter_service_type === 'photo' ? 'selected' : ''; ?>>Photo Services</option>
                                            <option value="print" <?php echo $filter_service_type === 'print' ? 'selected' : ''; ?>>Print Services</option>
                                        </select>
                                    </div>

                                    <div class="reports-filter-group">
                                        <label for="service_id">Service Name</label>
                                        <select id="service_id" name="service_id">
                                            <option value="">All Services</option>
                                            <?php if ($filter_service_type === 'photo' || empty($filter_service_type)): ?>
                                                <optgroup label="Photo Services">
                                                    <?php foreach ($photo_services as $service): ?>
                                                        <option value="<?php echo $service['id']; ?>" <?php echo $filter_service_id == $service['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($service['service_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endif; ?>
                                            <?php if ($filter_service_type === 'print' || empty($filter_service_type)): ?>
                                                <optgroup label="Print Services">
                                                    <?php foreach ($print_services as $service): ?>
                                                        <option value="<?php echo $service['id']; ?>" <?php echo $filter_service_id == $service['id'] ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($service['service_name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endif; ?>
                                        </select>
                                    </div>

                                    <div class="reports-filter-group">
                                        <label for="action_type">Action Type</label>
                                        <select id="action_type" name="action_type">
                                            <option value="">All Actions</option>
                                            <option value="created" <?php echo $filter_action_type === 'created' ? 'selected' : ''; ?>>Created</option>
                                            <option value="edited" <?php echo $filter_action_type === 'edited' ? 'selected' : ''; ?>>Edited</option>
                                            <option value="availability_changed" <?php echo $filter_action_type === 'availability_changed' ? 'selected' : ''; ?>>Availability Changed</option>
                                            <option value="deleted" <?php echo $filter_action_type === 'deleted' ? 'selected' : ''; ?>>Deleted</option>
                                        </select>
                                    </div>

                                    <div class="reports-filter-group">
                                        <label for="date_from">From Date</label>
                                        <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                                    </div>

                                    <div class="reports-filter-group">
                                        <label for="date_to">To Date</label>
                                        <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                                    </div>
                                </div>

                                <div class="reports-filter-buttons">
                                    <button type="submit" class="reports-btn-apply">
                                        <i class="fas fa-filter"></i> Apply Filters
                                    </button>
                                    <a href="report.php" class="reports-btn-reset">
                                        <i class="fas fa-redo"></i> Reset Filters
                                    </a>
                                    <button type="button" class="reports-btn-export" onclick="exportTableToCSV()">
                                        <i class="fas fa-download"></i> Export Report
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Records Section -->
                        <div class="reports-section">
                            <div class="reports-info">
                                <?php echo count($history_records); ?> record(s) found
                            </div>

                            <?php if (empty($history_records)): ?>
                                <div class="reports-no-data">
                                    <i class="fas fa-inbox" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem; display: block;"></i>
                                    <p>No service changes found matching your filters.</p>
                                </div>
                            <?php else: ?>
                                <table class="reports-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px;"></th>
                                            <th>Date & Time</th>
                                            <th>Service</th>
                                            <th>Type</th>
                                            <th>Action</th>
                                            <th>Changed By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($history_records as $record): ?>
                                            <tr>
                                                <td>
                                                    <button class="reports-expand-btn" onclick="toggleDetails(this)">
                                                        <i class="fas fa-chevron-right"></i>
                                                    </button>
                                                </td>
                                                <td>
                                                    <span class="reports-timestamp"><?php echo date('M d, Y H:i:s', strtotime($record['changed_at'])); ?></span>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($record['service_name'] ?? 'N/A'); ?>
                                                </td>
                                                <td>
                                                    <span class="service-type-badge type-<?php echo $record['service_type']; ?>">
                                                        <?php echo ucfirst($record['service_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="action-badge action-<?php echo $record['action_type']; ?>">
                                                        <?php echo formatActionType($record['action_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="reports-changed-by"><?php echo htmlspecialchars($record['changed_by']); ?></span>
                                                </td>
                                            </tr>
                                            <!-- Details Row -->
                                            <tr class="reports-details-row">
                                                <td colspan="6">
                                                    <div class="reports-details-content">
                                                        <?php
                                                        $old_values = json_decode($record['old_values'], true) ?? [];
                                                        $new_values = json_decode($record['new_values'], true) ?? [];
                                                        
                                                        $all_keys = array_unique(array_merge(array_keys($old_values), array_keys($new_values)));
                                                        ?>
                                                        
                                                        <div class="reports-details-grid">
                                                            <div class="reports-detail-section">
                                                                <h4>Previous Values</h4>
                                                                <?php if (!empty($old_values)): ?>
                                                                    <?php foreach ($all_keys as $key): ?>
                                                                        <?php if (isset($old_values[$key])): ?>
                                                                            <div class="reports-detail-item">
                                                                                <strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong><br>
                                                                                <span class="old-value">
                                                                                    <?php 
                                                                                    $val = $old_values[$key];
                                                                                    if (is_array($val)) {
                                                                                        echo implode(', ', $val);
                                                                                    } else {
                                                                                        echo htmlspecialchars((string)$val);
                                                                                    }
                                                                                    ?>
                                                                                </span>
                                                                            </div>
                                                                        <?php endif; ?>
                                                                    <?php endforeach; ?>
                                                                <?php else: ?>
                                                                    <p style="color: #94a3b8; font-style: italic;">Service newly created</p>
                                                                <?php endif; ?>
                                                            </div>

                                                            <div class="reports-detail-section">
                                                                <h4>New Values</h4>
                                                                <?php foreach ($all_keys as $key): ?>
                                                                    <?php if (isset($new_values[$key])): ?>
                                                                        <div class="reports-detail-item">
                                                                            <strong><?php echo ucfirst(str_replace('_', ' ', $key)); ?>:</strong><br>
                                                                            <span class="new-value">
                                                                                <?php 
                                                                                $val = $new_values[$key];
                                                                                if (is_array($val)) {
                                                                                    echo implode(', ', $val);
                                                                                } else {
                                                                                    echo htmlspecialchars((string)$val);
                                                                                }
                                                                                ?>
                                                                            </span>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        <?php include '../includes/admin_footer.php'; ?>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.querySelector('.admin-sidebar');
            sidebar.classList.toggle('collapsed');
        }

        function toggleDetails(button) {
            const row = button.closest('tr');
            const detailsRow = row.nextElementSibling;
            
            detailsRow.classList.toggle('show');
            button.querySelector('i').classList.toggle('fa-chevron-right');
            button.querySelector('i').classList.toggle('fa-chevron-down');
        }

        function exportTableToCSV() {
            const table = document.querySelector('.reports-table');
            let csv = [];
            
            // Add UTF-8 BOM for proper Excel/Google Sheets compatibility
            csv.push('\uFEFF');
            
            // Add header
            const headers = [];
            document.querySelectorAll('.reports-table thead th').forEach(th => {
                if (th.textContent.trim()) {
                    headers.push(escapeCSV(th.textContent.trim()));
                }
            });
            csv.push(headers.join(','));
            
            // Add data rows (only visible rows)
            document.querySelectorAll('.reports-table tbody tr:not(.reports-details-row)').forEach(tr => {
                const cells = [];
                tr.querySelectorAll('td').forEach((td, index) => {
                    if (index !== 0) { // Skip expand button column
                        const cellText = td.textContent.trim();
                        cells.push(escapeCSV(cellText));
                    }
                });
                if (cells.length > 0) {
                    csv.push(cells.join(','));
                }
            });
            
            // Create download link with proper encoding
            const csvContent = csv.join('\n');
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'service-report-' + new Date().toISOString().split('T')[0] + '.csv';
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }
        
        // Helper function to properly escape CSV values
        function escapeCSV(value) {
            if (value === null || value === undefined) {
                return '';
            }
            
            // Convert to string
            value = String(value);
            
            // If the value contains comma, newline, or double quotes, wrap in quotes
            if (value.includes(',') || value.includes('\n') || value.includes('"')) {
                // Escape double quotes by doubling them
                value = value.replace(/"/g, '""');
                return '"' + value + '"';
            }
            
            return value;
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        }
    </script>
</body>
</html>
