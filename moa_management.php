<?php
require_once __DIR__ . '/common.php';
require_login();
require_role('HR Personnel');
$user = current_user();

// Handle MOA update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['moa_update'])) {
    $record_id = intval($_POST['record_id']);
    $company_rep = $_POST['company_rep_name'];
    $school = $_POST['school_name'];
    $end_date = $_POST['end_date'];
    $required_hours = intval($_POST['required_hours']);
    $is_signed = isset($_POST['is_moa_signed']) ? 1 : 0;
    $is_notarized = isset($_POST['is_notarized']) ? 1 : 0;
    $verification = $_POST['verification_status'];

    $moa_file_path = null;
    if (isset($_FILES['moa_file']) && $_FILES['moa_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/uploads/moa/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $filename = uniqid() . '_' . basename($_FILES['moa_file']['name']);
        $target_path = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['moa_file']['tmp_name'], $target_path)) {
            $moa_file_path = 'uploads/moa/' . $filename;
        }
    }

    $stmt = $pdo->prepare('UPDATE internship_records SET company_rep_name = ?, school_name = ?, end_date = ?, required_hours = ?, is_moa_signed = ?, is_notarized = ?, verification_status = ?, moa_file_path = COALESCE(?, moa_file_path) WHERE record_id = ?');
    $stmt->execute([$company_rep, $school, $end_date, $required_hours, $is_signed, $is_notarized, $verification, $moa_file_path, $record_id]);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>MOA Management</title>
    <link rel="stylesheet" href="assets/css/style.css" />
</head>
<body>
    <div class="dashboard-layout">
        <aside class="sidebar">
            <div class="sidebar-brand">Pharmacy Internship</div>
            <nav>
                <a href="dashboard_hr.php">Home</a>
                <a href="dashboard_hr.php#requirements">Manage Requirements</a>
                <a href="dashboard_hr.php#policies">Manage Policies</a>
                <a href="dashboard_hr.php#reviews">Review Submissions</a>
                <a href="dashboard_hr.php#approve">Approve Applicants</a>
                <a href="interview_management.php">Interview Management</a>
                <a href="schedule_management.php">Schedule Management</a>
                <a href="moa_management.php" class="active">MOA Management</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>MOA Management</h1>
                <div>Welcome, <?php echo sanitize_text($user['full_name']); ?></div>
            </header>
            <section class="section-card">
                <div class="section-header">
                    <h2>Internship Records</h2>
                </div>
                <div id="internship-records" class="table-scroll"></div>
            </section>
            <section class="section-card">
                <div class="section-header">
                    <h2>Update MOA Record</h2>
                </div>
                <form id="moa-form" class="compact-form" style="display:none;" enctype="multipart/form-data">
                    <input type="hidden" name="moa_update" value="1" />
                    <label>Record ID</label>
                    <input type="number" name="record_id" required readonly />
                    <label>Company Rep Name</label>
                    <input type="text" name="company_rep_name" />
                    <label>School Name</label>
                    <input type="text" name="school_name" />
                    <label>End Date</label>
                    <input type="date" name="end_date" />
                    <label>Required Hours</label>
                    <input type="number" name="required_hours" />
                    <label>MOA File</label>
                    <input type="file" name="moa_file" accept=".pdf,.doc,.docx" />
                    <label><input type="checkbox" name="is_moa_signed" /> MOA Signed</label>
                    <label><input type="checkbox" name="is_notarized" /> Notarized</label>
                    <label>Verification Status</label>
                    <select name="verification_status">
                        <option value="Pending">Pending</option>
                        <option value="Verified">Verified</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Update Record</button>
                </form>
            </section>
        </main>
    </div>
    <script>
        async function loadRecords() {
            const response = await fetch('api/moa.php?action=list');
            const data = await response.json();
            const rows = data.records.map(rec => `
                <tr>
                    <td>${rec.full_name}</td>
                    <td>${rec.company_rep_name}</td>
                    <td>${rec.school_name}</td>
                    <td>${rec.end_date}</td>
                    <td>${rec.required_hours}</td>
                    <td>${rec.is_moa_signed ? 'Yes' : 'No'}</td>
                    <td>${rec.is_notarized ? 'Yes' : 'No'}</td>
                    <td>${rec.verification_status}</td>
                    <td>${rec.moa_file_path ? `<a href="${rec.moa_file_path}" target="_blank">View</a>` : 'No file'}</td>
                    <td><button class="action-btn edit-moa" data-id="${rec.record_id}">Edit</button></td>
                </tr>
            `).join('');
            document.querySelector('#internship-records').innerHTML = `<table><thead><tr><th>Intern</th><th>Company Rep</th><th>School</th><th>End Date</th><th>Hours</th><th>Signed</th><th>Notarized</th><th>Status</th><th>MOA File</th><th>Action</th></tr></thead><tbody>${rows}</tbody></table>`;
            document.querySelectorAll('.edit-moa').forEach(btn => {
                btn.addEventListener('click', () => {
                    const form = document.querySelector('#moa-form');
                    form.record_id.value = btn.dataset.id;
                    // Load existing record
                    fetch('api/moa.php?action=get&id=' + btn.dataset.id)
                        .then(r => r.json())
                        .then(d => {
                            if (d.record) {
                                form.company_rep_name.value = d.record.company_rep_name;
                                form.school_name.value = d.record.school_name;
                                form.end_date.value = d.record.end_date;
                                form.required_hours.value = d.record.required_hours;
                                form.is_moa_signed.checked = d.record.is_moa_signed;
                                form.is_notarized.checked = d.record.is_notarized;
                                form.verification_status.value = d.record.verification_status;
                            }
                            form.style.display = 'grid';
                        });
                });
            });
        }
        loadRecords();
    </script>
</body>
</html>