<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../api/auth/auth_guard.php';
include('../../database/db.php');

// Set timezone
date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");

// In SQL queries:
CONVERT_TZ(q.queued_at, '+00:00', '+08:00') as queued_at
CONVERT_TZ(q.dispatch_at, '+00:00', '+08:00') as dispatch_at

// Get filter parameters
$filterStatus = isset($_GET['status']) ? $_GET['status'] : 'all';
$filterDate = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$searchDriver = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination setup
$limit = 15;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Build WHERE clause
$whereConditions = [];
if (!empty($filterDate)) {
    $whereConditions[] = "DATE(q.queued_at) = '" . $conn->real_escape_string($filterDate) . "'";
}
if (!empty($searchDriver)) {
    $searchEscaped = $conn->real_escape_string($searchDriver);
    $whereConditions[] = "(d.firstname LIKE '%$searchEscaped%' 
                          OR d.lastname LIKE '%$searchEscaped%' 
                          OR d.tricycle_number LIKE '%$searchEscaped%')";
}
$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : '';

// ✅ Updated query — include dispatch_at
$sql = "
    SELECT q.id, q.status, q.queued_at, q.dispatch_at,
           d.firstname, d.lastname, d.tricycle_number, d.profile_pic
    FROM queue q
    INNER JOIN drivers d ON q.driver_id = d.id
    $whereClause
    ORDER BY q.queued_at DESC
    LIMIT $limit OFFSET $offset
";
$result = $conn->query($sql);

// Apply filter logic
$filteredResults = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($filterStatus === 'all') {
            $filteredResults[] = $row;
        } elseif ($filterStatus === 'in' && $row['status'] !== 'Dispatched') {
            $filteredResults[] = $row;
        } elseif ($filterStatus === 'out' && $row['status'] === 'Dispatched') {
            $filteredResults[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queue Reports - LTODA</title>
    <link rel="stylesheet" href="../../assets/css/navbar/navbar.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:"Poppins",sans-serif;
    background:#f5f7fa;
    min-height:100vh;
}

.container{
    max-width:1400px;
    margin:0 auto;
    padding:2rem;
}

/* FILTER SECTION */
.filter-section{
    background: linear-gradient(135deg, #ecfdf5 0%, #d1fae5 100%);
    padding:1.5rem;
    border-radius:12px;
    border: 2px solid #10b981;
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
    margin-bottom:2rem;
}
.filter-form{
    display:grid;
    grid-template-columns:200px 400px 1fr auto;
    gap:1rem;
    align-items:end;
}

.form-group{display:flex;flex-direction:column;}
.form-group label{
    font-size:0.85rem;
    font-weight:500;
    color:#374151;
    margin-bottom:0.5rem;
}

.form-control{
    padding:0.7rem 1rem;
    border: 2px solid #10b981;
    border-radius:12px;
    font-size:0.95rem;
    background:white;
    transition:all 0.2s ease;
}

.form-control:focus{
    border: 2px solid #10b981;
    outline:none;
    box-shadow:0 0 0 3px rgba(16,185,129,0.12);
}

/* BUTTONS */
.btn{
    padding:0.7rem 1.5rem;
    border:none;
    border-radius:12px;
    font-size:0.95rem;
    font-weight:500;
    cursor:pointer;
    transition:all 0.2s;
    display:inline-flex;
    align-items:center;
    gap:0.5rem;
    text-decoration:none;
}

.btn-primary{
    background:linear-gradient(135deg,#10b981 0%,#059669 100%);
    color:white;
}
.btn-primary:hover{
    transform:translateY(-2px);
    box-shadow:0 4px 12px rgba(16,185,129,0.3);
}

.button-group{
    display:flex;
    justify-content:flex-end;
    gap:0.75rem;
    flex-wrap:wrap;
}

.btn-secondary{
    background:white;
    color:#374151;
    border:1px solid #d1d5db;
}
.btn-secondary:hover{background:#f9fafb;}

.btn-print{
    background:linear-gradient(135deg,#10b981 0%,#059669 100%);
    color:white;
}

/* TABLE SECTION */
.table-section{
    background:white;
    border-radius:12px;
    border: 2px solid #10b981;
    box-shadow:0 4px 20px rgba(0,0,0,0.08);
    overflow:hidden;
}

/* Table responsive wrapper */
.table-responsive{
    width:100%;
    overflow-x:auto;
    -webkit-overflow-scrolling:touch;
}

table{
    width:100%;
    border-collapse:collapse;
    min-width:800px;
}

thead{
    background:linear-gradient(135deg,#ecfdf5 0%,#d1fae5 100%);
}

th{
    padding:1rem;
    text-align:left;
    font-size:0.85rem;
    font-weight:600;
    color:#065f46;
    text-transform:uppercase;
    border-bottom:2px solid #10b981;
    white-space:nowrap;
}

td{
    padding:1rem;
    border-bottom:1px solid #f1f3f4;
    font-size:0.9rem;
    color:#374151;
}

tbody tr:hover{
    background:#f9fafb;
}

/* DRIVER INFO */
.driver-info{
    display:flex;
    align-items:center;
    gap:0.75rem;
}

.driver-pic{
    width:45px;
    height:45px;
    border-radius:50%;
    object-fit:cover;
    border:2px solid #e5e7eb;
    flex-shrink:0;
}

.driver-placeholder{
    width:45px;
    height:45px;
    background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);
    border-radius:50%;
    display:flex;
    align-items:center;
    justify-content:center;
    color:white;
    font-weight:600;
    flex-shrink:0;
}

.no-records{
    text-align:center;
    padding:3rem;
    color:#9ca3af;
}
.no-records i{
    font-size:3rem;
    margin-bottom:1rem;
    opacity:0.3;
}

/* ========================================
   RESPONSIVE DESIGN - ALL DEVICES
   ======================================== */

/* Tablet (768px - 1024px) */
@media screen and (max-width: 1024px) {
    .container{
        padding:1.5rem;
    }

    .filter-form{
        grid-template-columns:1fr 1fr;
        gap:1rem;
    }

    .button-group{
        grid-column:1 / -1;
        justify-content:center;
    }

    table{
        min-width:700px;
    }
}

/* Mobile Large (481px - 768px) */
@media screen and (max-width: 768px) {
    .container{
        padding:1rem;
    }

    .filter-section{
        padding:1rem;
    }

    .filter-form{
        grid-template-columns:1fr;
        gap:0.75rem;
    }

    .form-group label{
        font-size:0.8rem;
    }

    .form-control{
        padding:0.6rem 0.8rem;
        font-size:0.9rem;
    }

    .button-group{
        flex-direction:column;
        gap:0.5rem;
    }

    .btn{
        width:100%;
        justify-content:center;
        padding:0.6rem 1rem;
        font-size:0.9rem;
    }

    /* Table responsive */
    .table-responsive{
        margin:0 -1rem;
        padding:0 1rem;
    }

    table{
        font-size:0.85rem;
        min-width:650px;
    }

    th{
        padding:0.75rem 0.5rem;
        font-size:0.75rem;
    }

    td{
        padding:0.75rem 0.5rem;
    }

    .driver-pic,
    .driver-placeholder{
        width:35px;
        height:35px;
    }

    .driver-info{
        gap:0.5rem;
        font-size:0.85rem;
    }
}

/* Mobile Small (321px - 480px) */
@media screen and (max-width: 480px) {
    .container{
        padding:0.75rem;
    }

    .filter-section{
        padding:0.75rem;
        margin-bottom:1rem;
    }

    .filter-form{
        gap:0.5rem;
    }

    .form-control{
        padding:0.5rem 0.7rem;
        font-size:0.85rem;
    }

    .btn{
        padding:0.5rem 0.8rem;
        font-size:0.85rem;
    }

    .table-responsive{
        margin:0 -0.75rem;
        padding:0 0.75rem;
    }

    table{
        font-size:0.75rem;
        min-width:600px;
    }

    th{
        padding:0.6rem 0.4rem;
        font-size:0.7rem;
    }

    td{
        padding:0.6rem 0.4rem;
    }

    .driver-pic,
    .driver-placeholder{
        width:30px;
        height:30px;
        font-size:0.75rem;
    }

    .driver-info{
        gap:0.4rem;
        font-size:0.75rem;
    }

    .no-records{
        padding:2rem 1rem;
    }

    .no-records i{
        font-size:2rem;
    }
}

/* Extra Small (< 321px) */
@media screen and (max-width: 320px) {
    .container{
        padding:0.5rem;
    }

    .filter-section{
        padding:0.5rem;
    }

    table{
        font-size:0.7rem;
        min-width:550px;
    }

    th, td{
        padding:0.5rem 0.3rem;
    }

    .driver-pic,
    .driver-placeholder{
        width:28px;
        height:28px;
    }
}

/* Landscape Mode */
@media screen and (max-height: 500px) and (orientation: landscape) {
    .container{
        padding:1rem;
    }

    .filter-section{
        padding:1rem;
    }

    .filter-form{
        grid-template-columns:repeat(4, 1fr);
    }

    .button-group{
        grid-column:auto;
    }
}

/* Touch Devices */
@media (hover: none) and (pointer: coarse) {
    .btn{
        min-height:44px;
    }

    .table-responsive{
        -webkit-overflow-scrolling:touch;
    }
}

/* PRINT MODE */
@media print {
    body { background:white; }
    nav, .filter-section, .btn, .button-group { display:none !important; }
    .container { max-width:100%; padding:0; margin:0; }
    .table-section { box-shadow:none; border:none; }
    table { font-size:0.9rem; min-width:auto; }
    th { background:#10b981 !important; color:white !important; }
    th:first-child, td:first-child { display:none; }
    @page { margin:1cm; }
}
  </style>
</head>
<body>
<?php include('../../assets/components/navbar.php'); ?>

<div class="container">
    <!-- Filter Section -->
    <div class="filter-section">
        <form class="filter-form" method="GET" action="reports.php">
            <div class="form-group">
                <label for="date">Select Date</label>
                <input type="date" id="date" name="date" class="form-control" 
                    value="<?= htmlspecialchars($filterDate); ?>" 
                    max="<?= date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label for="search">Search Driver</label>
                <input type="text" id="search" name="search" class="form-control" 
                    placeholder="Name or Plate Number..." 
                    value="<?= htmlspecialchars($searchDriver); ?>">
            </div>
            <div class="button-group">
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> Filter</button>
                <a href="reports.php" class="btn btn-secondary"><i class="fa-solid fa-rotate-right"></i> Reset</a>
                <button type="button" class="btn btn-print" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
            </div>
        </form>
    </div>

    <!-- Table Section with responsive wrapper -->
    <div class="table-section">
        <div class="table-responsive">
            <table id="reportsTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Driver Name</th>
                        <th>Plate Number</th>
                        <th>Queue Time</th>
                        <th>Dispatch Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($filteredResults)): ?>
                        <?php $count = 1; foreach ($filteredResults as $row): ?>
                            <tr>
                                <td><?= $count++; ?></td>
                                <td>
                                    <div class="driver-info">
                                        <?php if (!empty($row['profile_pic']) && file_exists('../../' . $row['profile_pic'])): ?>
                                            <img src="<?= '../../' . htmlspecialchars($row['profile_pic']); ?>" class="driver-pic" alt="Driver">
                                        <?php else: ?>
                                            <div class="driver-placeholder">
                                                <?= strtoupper(substr($row['firstname'], 0, 1) . substr($row['lastname'], 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                        <span><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']); ?></span>
                                    </div>
                                </td>
                                <td><?= htmlspecialchars($row['tricycle_number']); ?></td>
                                <td>
                                    <span>
                                        Queue <?= date('M d, Y h:i A', strtotime($row['queued_at'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($row['status'] === 'Dispatched' && !empty($row['dispatch_at'])): ?>
                                        <span>
                                           Dispatch <?= date('M d, Y h:i A', strtotime($row['dispatch_at'])); ?>
                                        </span>
                                    <?php else: ?>
                                        <span>—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>   
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="no-records">
                                <i class="fa-solid fa-inbox"></i><br>No records found for selected filters
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://kit.fontawesome.com/4c7e22a859.js" crossorigin="anonymous"></script>
<script src="../../assets/js/reports/reports.js"></script>
</body>
</html>
