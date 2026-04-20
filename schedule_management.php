<?php
require_once __DIR__ . '/common.php';
require_login();
require_role('HR Personnel');
$user = current_user();

// Check for URL parameters to pre-select intern
$preselected_intern_id = isset($_GET['intern_id']) ? intval($_GET['intern_id']) : 0;
$preselected_name = isset($_GET['name']) ? $_GET['name'] : '';

$stmtUsers = $pdo->query("SELECT id, full_name, role FROM users WHERE role IN ('Intern','HR Personnel','Pharmacist','Pharmacy Technician','Pharmacist Assistant') ORDER BY role, full_name");
$allUsers = $stmtUsers->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Schedule Management</title>
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
                <a href="schedule_management.php" class="active">Schedule Management</a>
                <a href="moa_management.php">MOA Management</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Schedule Management</h1>
                <div>Welcome, <?php echo sanitize_text($user['full_name']); ?></div>
            </header>
            <section class="section-card">
                <div class="section-tab-list">
                    <button type="button" class="tab-button active" data-tab="view-schedule-panel">View Schedules</button>
                    <button type="button" class="tab-button" data-tab="organize-schedule-panel">Organize Schedules</button>
                </div>
                <div id="view-schedule-panel" class="tab-panel">
                    <div class="section-header">
                        <h2>Internship Schedules</h2>
                    </div>
                    <div id="intern-schedules" class="table-scroll"></div>
                </div>
                <div id="organize-schedule-panel" class="tab-panel" style="display:none;">
                    <div class="section-header">
                        <h2>Organize Schedules</h2>
                    </div>
                    <p>View and optimize intern work schedules across weekdays.</p>
                    <div id="organize-schedules" class="table-scroll"></div>
                </div>
            </section>
            <section class="section-card">
                <div class="section-header">
                    <h2>Create / Update Schedule</h2>
                </div>
                <p class="small-note">Select a user to add or update a work schedule. Use the Edit button in the table to load an existing record.</p>
                <button type="button" class="btn" id="show-new-schedule-form">Add New Schedule</button>
                <form id="schedule-form" class="compact-form" style="display:none;">
                    <label>User</label>
                    <select name="intern_id" required>
                        <option value="">Select a user</option>
                        <?php foreach ($allUsers as $userOption): ?>
                            <option value="<?php echo intval($userOption['id']); ?>"><?php echo sanitize_text($userOption['full_name'] . ' (' . $userOption['role'] . ')'); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <label>Monday</label>
                    <input type="text" name="monday" placeholder="e.g. 08:00-17:00" />
                    <label>Tuesday</label>
                    <input type="text" name="tuesday" />
                    <label>Wednesday</label>
                    <input type="text" name="wednesday" />
                    <label>Thursday</label>
                    <input type="text" name="thursday" />
                    <label>Friday</label>
                    <input type="text" name="friday" />
                    <label>Saturday</label>
                    <input type="text" name="saturday" />
                    <label>Sunday</label>
                    <input type="text" name="sunday" />
                    <label>Total Hours</label>
                    <input type="number" name="total_hours" min="0" placeholder="e.g. 40" />
                    <label>Notes</label>
                    <textarea name="notes" rows="2"></textarea>
                    <div style="display:flex; gap:0.75rem; flex-wrap:wrap;">
                        <button type="submit" class="btn btn-primary">Save Schedule</button>
                        <button type="button" class="btn" id="reset-schedule-form">Clear Form</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
    <script>
        function setActiveTab(tabId) {
            document.querySelectorAll('.tab-panel').forEach(panel => {
                panel.style.display = panel.id === tabId ? 'block' : 'none';
            });
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.tab === tabId);
            });
        }

        document.querySelectorAll('.tab-button').forEach(button => {
            button.addEventListener('click', () => {
                setActiveTab(button.dataset.tab);
                if (button.dataset.tab === 'organize-schedule-panel') {
                    loadOrganizeSchedules();
                }
            });
        });

        const scheduleForm = document.querySelector('#schedule-form');
        const resetScheduleButton = document.querySelector('#reset-schedule-form');
        const showNewScheduleButton = document.querySelector('#show-new-schedule-form');

        if (showNewScheduleButton) {
            showNewScheduleButton.addEventListener('click', () => {
                resetScheduleForm();
                scheduleForm.style.display = 'grid';
            });
        }

        if (scheduleForm) {
            scheduleForm.addEventListener('submit', async event => {
                event.preventDefault();
                const formData = new FormData(scheduleForm);
                try {
                    const response = await fetch('api/schedules.php?action=save', {
                        method: 'POST',
                        body: formData,
                    });
                    const result = await response.json();
                    if (!response.ok) {
                        throw new Error(result.message || 'Failed to save schedule.');
                    }
                    alert(result.message || 'Schedule saved successfully.');
                    resetScheduleForm();
                    loadSchedules();
                } catch (err) {
                    alert(err.message);
                }
            });
        }

        if (resetScheduleButton) {
            resetScheduleButton.addEventListener('click', resetScheduleForm);
        }

        function resetScheduleForm() {
            scheduleForm.reset();
            scheduleForm.style.display = 'none';
        }

        async function loadSchedules() {
            const response = await fetch('api/schedules.php?action=list_intern');
            const data = await response.json();
            const rows = data.schedules.map(sched => `
                <tr>
                    <td>${sched.full_name}</td>
                    <td>${sched.role || 'Intern'}</td>
                    <td>${sched.monday}</td>
                    <td>${sched.tuesday}</td>
                    <td>${sched.wednesday}</td>
                    <td>${sched.thursday}</td>
                    <td>${sched.friday}</td>
                    <td>${sched.saturday}</td>
                    <td>${sched.sunday}</td>
                    <td>${sched.total_hours || ''}</td>
                    <td>${sched.notes || ''}</td>
                    <td>
                        <button class="action-btn edit-sched" data-id="${sched.intern_id}">Edit</button>
                        <button class="action-btn notify-sched" data-id="${sched.intern_id}">Notify</button>
                        <button class="action-btn delete-sched" data-id="${sched.intern_id}">Delete</button>
                    </td>
                </tr>
            `).join('');
            document.querySelector('#intern-schedules').innerHTML = `<table class="schedule-table"><thead><tr><th>Staff Name</th><th>Role</th><th>Monday</th><th>Tuesday</th><th>Wednesday</th><th>Thursday</th><th>Friday</th><th>Saturday</th><th>Sunday</th><th>Total Hours</th><th>Notes</th><th>Actions</th></tr></thead><tbody>${rows}</tbody></table>`;

            document.querySelectorAll('.edit-sched').forEach(btn => {
                btn.addEventListener('click', () => {
                    const form = document.querySelector('#schedule-form');
                    form.intern_id.value = btn.dataset.id;
                    // Load existing schedule
                    fetch('api/schedules.php?action=get&intern_id=' + btn.dataset.id)
                        .then(r => r.json())
                        .then(d => {
                            if (d.schedule) {
                                form.intern_id.value = d.schedule.intern_id;
                                form.monday.value = d.schedule.monday;
                                form.tuesday.value = d.schedule.tuesday;
                                form.wednesday.value = d.schedule.wednesday;
                                form.thursday.value = d.schedule.thursday;
                                form.friday.value = d.schedule.friday;
                                form.saturday.value = d.schedule.saturday;
                                form.sunday.value = d.schedule.sunday;
                                form.total_hours.value = d.schedule.total_hours || '';
                                form.notes.value = d.schedule.notes;
                            }
                            form.style.display = 'grid';
                        });
                });
            });

            document.querySelectorAll('.notify-sched').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (!confirm('Send schedule notification to this user?')) {
                        return;
                    }
                    try {
                        const response = await fetch('api/schedules.php?action=notify&intern_id=' + btn.dataset.id, { method: 'POST' });
                        const result = await response.json();
                        if (!response.ok) {
                            throw new Error(result.message || 'Notification failed.');
                        }
                        alert(result.message || 'Notification sent.');
                    } catch (err) {
                        alert(err.message);
                    }
                });
            });

            document.querySelectorAll('.delete-sched').forEach(btn => {
                btn.addEventListener('click', async () => {
                    if (!confirm('Delete this schedule record?')) {
                        return;
                    }
                    try {
                        const response = await fetch('api/schedules.php?action=delete&intern_id=' + btn.dataset.id, { method: 'POST' });
                        const result = await response.json();
                        if (!response.ok) {
                            throw new Error(result.message || 'Delete failed.');
                        }
                        alert(result.message || 'Schedule deleted.');
                        loadSchedules();
                    } catch (err) {
                        alert(err.message);
                    }
                });
            });
        }

        async function loadOrganizeSchedules() {
            const response = await fetch('api/schedules.php?action=list_intern');
            const data = await response.json();
            if (!data.schedules || data.schedules.length === 0) {
                document.querySelector('#organize-schedules').innerHTML = '<p>No schedules to organize.</p>';
                return;
            }

            const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
            const dayKeys = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
            
            let organizedView = '<div style="margin-bottom: 2rem;">';
            
            for (let i = 0; i < days.length; i++) {
                const day = days[i];
                const dayKey = dayKeys[i];
                const schedulesByDay = data.schedules.filter(s => s[dayKey] && s[dayKey] !== 'Off');
                
                organizedView += `<div style="margin-bottom: 1.5rem; padding: 1rem; background: #f9f9f9; border-radius: 8px; border-left: 4px solid #6f42c1;">
                    <h4>${day} (${schedulesByDay.length} interns scheduled)</h4>
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #6f42c1; color: white;">
                                <th style="padding: 0.5rem; text-align: left;">Intern</th>
                                <th style="padding: 0.5rem; text-align: left;">Role</th>
                                <th style="padding: 0.5rem; text-align: left;">Hours</th>
                                <th style="padding: 0.5rem; text-align: left;">Total Hours</th>
                                <th style="padding: 0.5rem; text-align: left;">Notes</th>
                            </tr>
                        </thead>
                        <tbody>`;
                
                if (schedulesByDay.length > 0) {
                    schedulesByDay.forEach(sched => {
                        organizedView += `<tr style="border-bottom: 1px solid #ddd;">
                            <td style="padding: 0.5rem;">${sched.full_name}</td>
                            <td style="padding: 0.5rem;">${sched.role || 'Intern'}</td>
                            <td style="padding: 0.5rem;">${sched[dayKey] || '-'}</td>
                            <td style="padding: 0.5rem;">${sched.total_hours || '-'}</td>
                            <td style="padding: 0.5rem;">${sched.notes || '-'}</td>
                        </tr>`;
                    });
                } else {
                    organizedView += `<tr><td colspan="5" style="padding: 0.5rem; text-align: center;">No interns scheduled</td></tr>`;
                }
                
                organizedView += `</tbody></table></div>`;
            }
            
            organizedView += '</div>';
            organizedView += `<div style="margin-top: 1rem;">
                <button class="action-btn" onclick="exportScheduleToCSV()">Export to CSV</button>
                <button class="action-btn" onclick="printScheduleView()">Print Schedule</button>
            </div>`;
            
            document.querySelector('#organize-schedules').innerHTML = organizedView;
        }

        function exportScheduleToCSV() {
            const response = fetch('api/schedules.php?action=export_csv');
            response.then(r => r.blob()).then(blob => {
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'schedules.csv';
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                window.URL.revokeObjectURL(url);
            });
        }

        function printScheduleView() {
            const content = document.querySelector('#organize-schedules').innerHTML;
            const printWindow = window.open('', '', 'height=600,width=800');
            printWindow.document.write('<html><head><title>Schedule View</title>');
            printWindow.document.write('<style>body { font-family: Arial; } table { border-collapse: collapse; width: 100%; } th { background: #6f42c1; color: white; padding: 0.5rem; } td { padding: 0.5rem; border-bottom: 1px solid #ddd; }</style>');
            printWindow.document.write('</head><body>');
            printWindow.document.write(content);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.print();
        }

        loadSchedules();

        // Check for pre-selected intern from URL parameters
        <?php if ($preselected_intern_id): ?>
        // Auto-show form and pre-select intern when coming from interview management
        setTimeout(() => {
            const form = document.querySelector('#schedule-form');
            const showButton = document.querySelector('#show-new-schedule-form');
            
            if (form && showButton) {
                // Show the form
                form.style.display = 'grid';
                
                // Pre-select the intern
                form.intern_id.value = <?php echo $preselected_intern_id; ?>;
                
                // Switch to organize schedule tab
                setActiveTab('organize-schedule-panel');
                
                // Show notification
                alert('Setting schedule for: <?php echo addslashes($preselected_name); ?>');
            }
        }, 500);
        <?php endif; ?>
    </script>
</body>
</html>