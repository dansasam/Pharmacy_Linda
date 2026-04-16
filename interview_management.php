<?php
require_once __DIR__ . '/common.php';
require_login();
require_role('HR Personnel');
$user = current_user();

// Handle assessment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assessment'])) {
    try {
        // Debug: Log all POST data
        error_log("Assessment POST data: " . print_r($_POST, true));
        
        $intern_id = intval($_POST['intern_id'] ?? 0);
        $interview_date = $_POST['interview_date'] ?? '';
        $position_applied = $_POST['position_applied'] ?? '';
        $business_unit = $_POST['business_unit'] ?? '';
        $source = $_POST['source'] ?? '';
        $nationality = $_POST['nationality'] ?? '';
        
        // Validation
        if (!$intern_id) {
            throw new Exception('Intern ID is required');
        }
        if (empty($interview_date)) {
            throw new Exception('Interview date is required');
        }
        if (empty($position_applied)) {
            throw new Exception('Position applied is required');
        }
        
        $academic = isset($_POST['academic_qualifications']) ? intval($_POST['academic_qualifications']) : 0;
        $work_exp = isset($_POST['work_experience']) ? intval($_POST['work_experience']) : 0;
        $technical = isset($_POST['technical_knowledge']) ? intval($_POST['technical_knowledge']) : 0;
        $industry = isset($_POST['industry_knowledge']) ? intval($_POST['industry_knowledge']) : 0;
        $communication = isset($_POST['communication_skills']) ? intval($_POST['communication_skills']) : 0;
        $growth = isset($_POST['potential_for_growth']) ? intval($_POST['potential_for_growth']) : 0;
        $people = isset($_POST['people_management']) ? intval($_POST['people_management']) : 0;
        $culture = isset($_POST['culture_fit']) ? intval($_POST['culture_fit']) : 0;
        $problem = isset($_POST['problem_solving']) ? intval($_POST['problem_solving']) : 0;
        $comments = trim($_POST['interviewer_comments'] ?? '');
        
        $rowComments = [];
        $commentFields = [
            'Academic Qualifications' => $_POST['comment_academic'] ?? '',
            'Relevant Work Experience' => $_POST['comment_work_exp'] ?? '',
            'Technical Knowledge' => $_POST['comment_technical'] ?? '',
            'Industry Knowledge' => $_POST['comment_industry'] ?? '',
            'Communication Skills' => $_POST['comment_communication'] ?? '',
            'Potential for Growth' => $_POST['comment_growth'] ?? '',
            'People Management' => $_POST['comment_people'] ?? '',
            'Culture Fit' => $_POST['comment_culture'] ?? '',
            'Problem Solving' => $_POST['comment_problem'] ?? '',
        ];
        foreach ($commentFields as $title => $text) {
            $trimmed = trim($text);
            if ($trimmed !== '') {
                $rowComments[] = "$title: $trimmed";
            }
        }
        if ($rowComments) {
            $comments = trim($comments . "\n" . implode("\n", $rowComments));
        }
        $status = $_POST['hiring_status'] ?? 'Not Recommended';
        $panel = $_POST['panel_member_name'] ?? '';
        $salary = $_POST['expected_salary_benefits'] ?? '';
        $total = $academic + $work_exp + $technical + $industry + $communication + $growth + $people + $culture + $problem;

        // Insert assessment
        $stmt = $pdo->prepare('INSERT INTO employee_profiles (intern_id, interview_date, position_applied, business_unit, source, nationality, academic_qualifications, work_experience, technical_knowledge, industry_knowledge, communication_skills, potential_for_growth, people_management, culture_fit, problem_solving, interviewer_comments, hiring_status, panel_member_name, expected_salary_benefits, total_rating) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $result = $stmt->execute([$intern_id, $interview_date, $position_applied, $business_unit, $source, $nationality, $academic, $work_exp, $technical, $industry, $communication, $growth, $people, $culture, $problem, $comments, $status, $panel, $salary, $total]);
        
        if (!$result) {
            throw new Exception('Failed to insert assessment into database');
        }
        
        // Ensure intern is in pending_applicants table and update status
        $checkStmt = $pdo->prepare('SELECT id, status FROM pending_applicants WHERE intern_id = ?');
        $checkStmt->execute([$intern_id]);
        $existing = $checkStmt->fetch();
        
        if ($existing) {
            // Update existing record to Interviewed status
            $updateStmt = $pdo->prepare('UPDATE pending_applicants SET status = "Interviewed", position_applied = ? WHERE intern_id = ?');
            $updateResult = $updateStmt->execute([$position_applied, $intern_id]);
            
            if (!$updateResult) {
                throw new Exception('Failed to update applicant status to Interviewed');
            }
            
            error_log("Updated intern $intern_id status from '{$existing['status']}' to 'Interviewed'");
        } else {
            // Insert new record if not exists
            $userInfo = $pdo->prepare('SELECT full_name FROM users WHERE id = ? AND role = "Intern"');
            $userInfo->execute([$intern_id]);
            $fullName = $userInfo->fetchColumn();
            
            if (!$fullName) {
                throw new Exception('Intern not found or invalid role');
            }
            
            $insertStmt = $pdo->prepare('INSERT INTO pending_applicants (intern_id, name, position_applied, status, date_applied) VALUES (?, ?, ?, "Interviewed", NOW())');
            $insertResult = $insertStmt->execute([$intern_id, $fullName, $position_applied]);
            
            if (!$insertResult) {
                throw new Exception('Failed to add intern to pending applicants');
            }
            
            error_log("Added intern $intern_id to pending_applicants with 'Interviewed' status");
        }
        
        // Success - redirect to prevent resubmission
        header('Location: interview_management.php?success=1');
        exit;
        
    } catch (Exception $e) {
        $error_message = "Assessment submission failed: " . $e->getMessage();
        error_log($error_message);
        // Don't redirect on error, show the error message
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Interview Management</title>
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
                <a href="interview_management.php" class="active">Interview Management</a>
                <a href="schedule_management.php">Schedule Management</a>
                <a href="moa_management.php">MOA Management</a>
                <a href="logout.php">Logout</a>
            </nav>
        </aside>
        <main class="main-content">
            <header class="topbar">
                <h1>Interview Management</h1>
                <div>Welcome, <?php echo sanitize_text($user['full_name']); ?></div>
            </header>
            
            <?php if (isset($_GET['success'])): ?>
            <div class="ls-alert ls-alert-success" style="margin-bottom: 20px;">
                <strong>Success!</strong> Assessment submitted successfully! Candidate moved to Interviewed Candidates.
            </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
            <div class="ls-alert ls-alert-danger" style="margin-bottom: 20px;">
                <strong>Error!</strong> <?php echo htmlspecialchars($error_message); ?>
            </div>
            <?php endif; ?>
            
            <section class="section-card">
                <div class="section-tab-list">
                    <button type="button" class="tab-button active" data-tab="pending-panel">Pending Applicants</button>
                    <button type="button" class="tab-button" data-tab="interviewed-panel">Interviewed Candidates</button>
                    <button type="button" class="tab-button" data-tab="assessment-panel">Assessment Form</button>
                    <button type="button" class="tab-button" data-tab="schedule-panel">Online Interview Settings</button>
                </div>
                <div id="pending-panel" class="tab-panel">
                    <div class="section-header">
                        <h2>Pending Applicants</h2>
                    </div>
                    <div id="pending-applicants" class="table-scroll"></div>
                </div>
                <div id="interviewed-panel" class="tab-panel" style="display:none;">
                    <div class="section-header">
                        <h2>Interviewed Candidates</h2>
                    </div>
                    <div id="interviewed-applicants" class="table-scroll"></div>
                </div>
                <div id="assessment-panel" class="tab-panel" style="display:none;">
                    <div class="section-header">
                        <h2>Candidate Assessment Form</h2>
                    </div>
                    <form id="assessment-form" class="compact-form">
                        <input type="hidden" name="assessment" value="1" />
                        <input type="hidden" name="intern_id" required />
                        <div class="form-grid">
                            <div>
                                <label>Candidate Name</label>
                                <input type="text" name="candidate_name" readonly />
                            </div>
                            <div>
                                <label>Interview Date</label>
                                <input type="datetime-local" name="interview_date" readonly />
                                <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                                    Auto-filled from scheduled interview
                                </small>
                            </div>
                            <div>
                                <label>Position Applied</label>
                                <select name="position_applied" required>
                                    <option value="">-- Select Position --</option>
                                    <option value="Pharmacy Intern">Pharmacy Intern</option>
                                    <option value="Pharmacy Technician">Pharmacy Technician</option>
                                    <option value="Pharmacist Assistant">Pharmacist Assistant</option>
                                    <option value="Junior Pharmacist">Junior Pharmacist</option>
                                    <option value="Clinical Pharmacist">Clinical Pharmacist</option>
                                </select>
                            </div>
                            <div>
                                <label>Business Unit</label>
                                <select name="business_unit">
                                    <option value="">-- Select Unit --</option>
                                    <option value="Retail Pharmacy">Retail Pharmacy</option>
                                    <option value="Hospital Pharmacy">Hospital Pharmacy</option>
                                    <option value="Clinical Services">Clinical Services</option>
                                    <option value="Inventory Management">Inventory Management</option>
                                    <option value="Quality Assurance">Quality Assurance</option>
                                    <option value="Customer Service">Customer Service</option>
                                </select>
                            </div>
                            <div>
                                <label>Source</label>
                                <select name="source">
                                    <option value="">-- Select Source --</option>
                                    <option value="Job Fair">Job Fair</option>
                                    <option value="Online Application">Online Application</option>
                                    <option value="Referral">Referral</option>
                                    <option value="Walk-in">Walk-in</option>
                                    <option value="School Partnership">School Partnership</option>
                                    <option value="Social Media">Social Media</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label>Nationality</label>
                                <select name="nationality">
                                    <option value="">-- Select Nationality --</option>
                                    <option value="Filipino">Filipino</option>
                                    <option value="American">American</option>
                                    <option value="Chinese">Chinese</option>
                                    <option value="Japanese">Japanese</option>
                                    <option value="Korean">Korean</option>
                                    <option value="Indian">Indian</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>
                        <div class="assessment-legend">
                            <strong>Rating Scale:</strong>
                            <span>5 - Outstanding</span>
                            <span>4 - Excellent (Exceeds requirements)</span>
                            <span>3 - Competent (Acceptable proficiency)</span>
                            <span>2 - Below Average (Does not meet requirements)</span>
                            <span>1 - Unable to determine or not applicable</span>
                            <div style="width: 100%; margin-top: 8px; font-style: italic; color: var(--text-muted);">
                                <strong>Note:</strong> You can leave ratings unselected if not applicable or not assessed.
                            </div>
                        </div>
                        <table class="assessment-table">
                            <thead>
                                <tr>
                                    <th>Competency</th>
                                    <th>Description</th>
                                    <th>5</th>
                                    <th>4</th>
                                    <th>3</th>
                                    <th>2</th>
                                    <th>1</th>
                                    <th>Interviewer Comments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Academic Qualifications</td>
                                    <td>Does the candidate have the appropriate educational qualifications or training for this position?</td>
                                    <td><input type="radio" name="academic_qualifications" value="5" /></td>
                                    <td><input type="radio" name="academic_qualifications" value="4" /></td>
                                    <td><input type="radio" name="academic_qualifications" value="3" /></td>
                                    <td><input type="radio" name="academic_qualifications" value="2" /></td>
                                    <td><input type="radio" name="academic_qualifications" value="1" /></td>
                                    <td><textarea name="comment_academic" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>Relevant Work Experience</td>
                                    <td>Has the candidate acquired similar skills/qualifications throughout the past work experiences?</td>
                                    <td><input type="radio" name="work_experience" value="5" /></td>
                                    <td><input type="radio" name="work_experience" value="4" /></td>
                                    <td><input type="radio" name="work_experience" value="3" /></td>
                                    <td><input type="radio" name="work_experience" value="2" /></td>
                                    <td><input type="radio" name="work_experience" value="1" /></td>
                                    <td><textarea name="comment_work_exp" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>Technical Knowledge</td>
                                    <td>Does the candidate have the technical skills necessary for this position?</td>
                                    <td><input type="radio" name="technical_knowledge" value="5" /></td>
                                    <td><input type="radio" name="technical_knowledge" value="4" /></td>
                                    <td><input type="radio" name="technical_knowledge" value="3" /></td>
                                    <td><input type="radio" name="technical_knowledge" value="2" /></td>
                                    <td><input type="radio" name="technical_knowledge" value="1" /></td>
                                    <td><textarea name="comment_technical" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>Industry Knowledge</td>
                                    <td>Please rate if it is compatible with industry or similar.</td>
                                    <td><input type="radio" name="industry_knowledge" value="5" /></td>
                                    <td><input type="radio" name="industry_knowledge" value="4" /></td>
                                    <td><input type="radio" name="industry_knowledge" value="3" /></td>
                                    <td><input type="radio" name="industry_knowledge" value="2" /></td>
                                    <td><input type="radio" name="industry_knowledge" value="1" /></td>
                                    <td><textarea name="comment_industry" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>Communication Skills</td>
                                    <td>Please rate if responses were readily understood. Articulate, used good grammar & expressed thoughts concisely, professional appearance, body language.</td>
                                    <td><input type="radio" name="communication_skills" value="5" /></td>
                                    <td><input type="radio" name="communication_skills" value="4" /></td>
                                    <td><input type="radio" name="communication_skills" value="3" /></td>
                                    <td><input type="radio" name="communication_skills" value="2" /></td>
                                    <td><input type="radio" name="communication_skills" value="1" /></td>
                                    <td><textarea name="comment_communication" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>Potential for Growth</td>
                                    <td>Please rate if the candidate has the ingredients for career progression with track record of taking ownership of self-development.</td>
                                    <td><input type="radio" name="potential_for_growth" value="5" /></td>
                                    <td><input type="radio" name="potential_for_growth" value="4" /></td>
                                    <td><input type="radio" name="potential_for_growth" value="3" /></td>
                                    <td><input type="radio" name="potential_for_growth" value="2" /></td>
                                    <td><input type="radio" name="potential_for_growth" value="1" /></td>
                                    <td><textarea name="comment_growth" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>People Management</td>
                                    <td>Please rate if the candidate displayed the ability to coach & support others, delegate effectively and empower others to act.</td>
                                    <td><input type="radio" name="people_management" value="5" /></td>
                                    <td><input type="radio" name="people_management" value="4" /></td>
                                    <td><input type="radio" name="people_management" value="3" /></td>
                                    <td><input type="radio" name="people_management" value="2" /></td>
                                    <td><input type="radio" name="people_management" value="1" /></td>
                                    <td><textarea name="comment_people" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>Culture Fit</td>
                                    <td>Please rate if candidate exhibits core values and expected attitude.</td>
                                    <td><input type="radio" name="culture_fit" value="5" /></td>
                                    <td><input type="radio" name="culture_fit" value="4" /></td>
                                    <td><input type="radio" name="culture_fit" value="3" /></td>
                                    <td><input type="radio" name="culture_fit" value="2" /></td>
                                    <td><input type="radio" name="culture_fit" value="1" /></td>
                                    <td><textarea name="comment_culture" rows="1"></textarea></td>
                                </tr>
                                <tr>
                                    <td>Problem Solving</td>
                                    <td>Does the candidate take responsibilities, likes challenges, and accountable?</td>
                                    <td><input type="radio" name="problem_solving" value="5" /></td>
                                    <td><input type="radio" name="problem_solving" value="4" /></td>
                                    <td><input type="radio" name="problem_solving" value="3" /></td>
                                    <td><input type="radio" name="problem_solving" value="2" /></td>
                                    <td><input type="radio" name="problem_solving" value="1" /></td>
                                    <td><textarea name="comment_problem" rows="1"></textarea></td>
                                </tr>
                            </tbody>
                        </table>
                        <label>Additional Interviewer Comments</label>
                        <textarea name="interviewer_comments" rows="3"></textarea>
                        <label>Hiring Status</label>
                        <select name="hiring_status">
                            <option value="Recommended">Recommended</option>
                            <option value="With Reservations">With Reservations</option>
                            <option value="Not Recommended">Not Recommended</option>
                            <option value="Further Interview">Further Interview</option>
                        </select>
                        <label>Panel Member Name</label>
                        <input type="text" name="panel_member_name" />
                        <label>Expected Salary/Benefits</label>
                        <textarea name="expected_salary_benefits" rows="2"></textarea>
                        <button type="submit" class="btn btn-primary">Submit Assessment</button>
                    </form>
                </div>
                <div id="schedule-panel" class="tab-panel" style="display:none;">
                    <div class="section-header">
                        <h2>Online Interview Settings</h2>
                    </div>
                    <form id="schedule-form" class="compact-form">
                        <input type="hidden" name="schedule" value="1" />
                        <label>Intern ID</label>
                        <input type="number" name="intern_id" required readonly />
                        <label>Candidate Name</label>
                        <input type="text" name="candidate_name" readonly />
                        <label>Interview Date</label>
                        <input type="datetime-local" name="interview_date" required />
                        <label>Interview Mode</label>
                        <select name="interview_mode" id="interview-mode">
                            <option value="Online">Online</option>
                            <option value="Face to Face">Face to Face</option>
                        </select>
                        <div id="online-fields">
                            <label>Meeting Link</label>
                            <input type="url" name="interview_link" placeholder="https://zoom.us/j/123456789 or https://meet.google.com/abc-def-ghi" />
                            <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                                Enter the full meeting URL (Zoom, Google Meet, Teams, etc.)
                            </small>
                        </div>
                        <div id="face-fields" style="display:none;">
                            <label>Face-to-Face Location</label>
                            <input type="text" name="interview_location" placeholder="e.g. Main Clinic, 2nd Floor Conference Room" />
                            <small style="color: var(--text-muted); display: block; margin-top: 4px;">
                                Enter the physical location where the interview will take place
                            </small>
                            <button type="button" id="clear-location" style="margin-top: 8px; padding: 4px 8px; font-size: 0.8rem; background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 4px; cursor: pointer;">
                                Clear Field
                            </button>
                        </div>
                        <label>Notification Message</label>
                        <textarea name="notification_message" rows="3">Your interview has been scheduled. Please check the mode and time above.</textarea>
                        <button type="submit" class="btn btn-primary">Save Interview Schedule</button>
                    </form>
                </div>
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
            button.addEventListener('click', () => setActiveTab(button.dataset.tab));
        });

        // Load interviewed candidates when tab is clicked
        document.querySelector('[data-tab="interviewed-panel"]').addEventListener('click', loadInterviewedApplicants);

        function handleInterviewModeChange() {
            const mode = document.querySelector('#interview-mode').value;
            const onlineFields = document.querySelector('#online-fields');
            const faceFields = document.querySelector('#face-fields');
            const linkInput = document.querySelector('input[name="interview_link"]');
            const locationInput = document.querySelector('input[name="interview_location"]');
            
            if (mode === 'Online') {
                onlineFields.style.display = 'block';
                faceFields.style.display = 'none';
                linkInput.required = true;
                locationInput.required = false;
                locationInput.value = ''; // Clear location when switching to online
            } else {
                onlineFields.style.display = 'none';
                faceFields.style.display = 'block';
                linkInput.required = false;
                locationInput.required = true;
                linkInput.value = ''; // Clear link when switching to face-to-face
            }
        }

        // Add input validation for location field to prevent URLs
        function validateLocationInput(event) {
            const value = event.target.value;
            const urlPattern = /^https?:\/\//i;
            const warningDiv = document.querySelector('#location-warning') || createWarningDiv();
            
            if (urlPattern.test(value)) {
                event.target.setCustomValidity('Please enter a physical location, not a URL. URLs should only be used for Online interviews.');
                event.target.style.borderColor = '#ef4444';
                warningDiv.style.display = 'block';
                warningDiv.innerHTML = '⚠️ This appears to be a URL. For Face-to-Face interviews, please enter a physical location instead.';
            } else {
                event.target.setCustomValidity('');
                event.target.style.borderColor = '';
                warningDiv.style.display = 'none';
            }
        }

        function createWarningDiv() {
            const warningDiv = document.createElement('div');
            warningDiv.id = 'location-warning';
            warningDiv.style.cssText = 'display: none; background: #fef3c7; border: 1px solid #f59e0b; color: #92400e; padding: 8px 12px; border-radius: 6px; margin-top: 8px; font-size: 0.9rem;';
            document.querySelector('#face-fields').appendChild(warningDiv);
            return warningDiv;
        }

        // Add input validation for link field to ensure it's a valid URL
        function validateLinkInput(event) {
            const value = event.target.value;
            const urlPattern = /^https?:\/\/.+/i;
            
            if (value && !urlPattern.test(value)) {
                event.target.setCustomValidity('Please enter a valid URL starting with http:// or https://');
                event.target.style.borderColor = '#ef4444';
            } else {
                event.target.setCustomValidity('');
                event.target.style.borderColor = '';
            }
        }

        document.querySelector('#interview-mode').addEventListener('change', handleInterviewModeChange);
        
        // Add validation event listeners
        document.querySelector('input[name="interview_location"]').addEventListener('input', validateLocationInput);
        document.querySelector('input[name="interview_link"]').addEventListener('input', validateLinkInput);
        
        // Add clear button functionality
        document.querySelector('#clear-location').addEventListener('click', function() {
            const locationInput = document.querySelector('input[name="interview_location"]');
            locationInput.value = '';
            locationInput.style.borderColor = '';
            locationInput.setCustomValidity('');
        });
        
        handleInterviewModeChange();

        function populateAssessmentForm(internId, candidateName) {
            const form = document.querySelector('#assessment-form');
            form.intern_id.value = internId;
            form.candidate_name.value = candidateName;
            
            // Fetch interview schedule data for this intern
            fetchInterviewSchedule(internId);
            
            setActiveTab('assessment-panel');
        }

        async function fetchInterviewSchedule(internId) {
            try {
                const response = await fetch(`api/interviews.php?action=get_schedule&intern_id=${internId}`);
                const data = await response.json();
                
                if (data.success && data.schedule) {
                    const form = document.querySelector('#assessment-form');
                    
                    // Populate interview date if scheduled
                    if (data.schedule.interview_date) {
                        // Convert MySQL datetime to HTML datetime-local format
                        const date = new Date(data.schedule.interview_date);
                        const localDateTime = date.getFullYear() + '-' + 
                            String(date.getMonth() + 1).padStart(2, '0') + '-' + 
                            String(date.getDate()).padStart(2, '0') + 'T' + 
                            String(date.getHours()).padStart(2, '0') + ':' + 
                            String(date.getMinutes()).padStart(2, '0');
                        form.interview_date.value = localDateTime;
                    }
                    
                    // Pre-select position if available
                    if (data.schedule.position_applied) {
                        form.position_applied.value = data.schedule.position_applied;
                    }
                } else {
                    // If no schedule found, allow manual entry of interview date
                    const form = document.querySelector('#assessment-form');
                    form.interview_date.readOnly = false;
                    form.interview_date.required = true;
                }
            } catch (error) {
                console.error('Error fetching interview schedule:', error);
                // Allow manual entry if fetch fails
                const form = document.querySelector('#assessment-form');
                form.interview_date.readOnly = false;
                form.interview_date.required = true;
            }
        }

        function populateScheduleForm(internId, candidateName) {
            const form = document.querySelector('#schedule-form');
            form.intern_id.value = internId;
            form.candidate_name.value = candidateName;
            setActiveTab('schedule-panel');
        }

        async function loadPendingApplicants() {
            const response = await fetch('api/interviews.php?action=list_pending');
            const data = await response.json();
            const rows = data.applicants.map(app => `
                <tr>
                    <td>${app.name}</td>
                    <td>${app.position_applied}</td>
                    <td>${app.status}</td>
                    <td>${app.interview_date ? new Date(app.interview_date).toLocaleString() : '-'}</td>
                    <td>${app.interview_mode || '-'}</td>
                    <td>${app.interview_mode === 'Online' ? (app.interview_link ? `<a href="${app.interview_link}" target="_blank">Link</a>` : '-') : (app.interview_location || '-')}</td>
                    <td><button class="action-btn assess-btn" data-id="${app.intern_id}" data-name="${app.name}">Assess</button> <button class="action-btn schedule-btn" data-id="${app.intern_id}" data-name="${app.name}">Schedule</button></td>
                </tr>
            `).join('');
            document.querySelector('#pending-applicants').innerHTML = `<table><thead><tr><th>Name</th><th>Position</th><th>Status</th><th>Interview Date</th><th>Mode</th><th>Details</th><th>Action</th></tr></thead><tbody>${rows}</tbody></table>`;
            document.querySelectorAll('.assess-btn').forEach(btn => {
                btn.addEventListener('click', () => populateAssessmentForm(btn.dataset.id, btn.dataset.name));
            });
            document.querySelectorAll('.schedule-btn').forEach(btn => {
                btn.addEventListener('click', () => populateScheduleForm(btn.dataset.id, btn.dataset.name));
            });
        }

        async function loadInterviewedApplicants() {
            const response = await fetch('api/interviews.php?action=list_interviewed');
            const data = await response.json();
            if (!data.applicants || data.applicants.length === 0) {
                document.querySelector('#interviewed-applicants').innerHTML = '<p>No interviewed candidates.</p>';
                return;
            }
            const rows = data.applicants.map(app => `
                <tr>
                    <td>${app.full_name}</td>
                    <td>${app.position_applied || 'N/A'}</td>
                    <td>${app.total_rating || 'N/A'}</td>
                    <td><span class="status-badge status-${(app.hiring_status || 'unknown').toLowerCase().replace(' ', '-')}">${app.hiring_status || 'Pending'}</span></td>
                    <td>${app.interview_date ? new Date(app.interview_date).toLocaleDateString() : '-'}</td>
                    <td class="action-buttons">
                        <button class="action-btn view-profile-btn" data-id="${app.intern_id}" data-name="${app.full_name}" title="View Employee Profile">
                            👤 Profile
                        </button>
                        <button class="action-btn set-schedule-btn" data-id="${app.intern_id}" data-name="${app.full_name}" title="Set Work Schedule">
                            📅 Set Schedule
                        </button>
                    </td>
                </tr>
            `).join('');
            document.querySelector('#interviewed-applicants').innerHTML = `
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Position</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Interview Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>`;
            
            // Add event listeners for action buttons
            document.querySelectorAll('.view-profile-btn').forEach(btn => {
                btn.addEventListener('click', () => viewEmployeeProfile(btn.dataset.id, btn.dataset.name));
            });
            document.querySelectorAll('.set-schedule-btn').forEach(btn => {
                btn.addEventListener('click', () => setWorkSchedule(btn.dataset.id, btn.dataset.name));
            });
        }

        async function viewEmployeeProfile(internId, candidateName) {
            // Open profile in new page
            window.location.href = 'employee_profile_view.php?intern_id=' + internId;
        }

        async function issueMOA(internId, candidateName) {
            if (confirm(`Issue MOA for ${candidateName}?`)) {
                try {
                    const response = await fetch('api/interviews.php?action=send_moa', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ intern_id: internId })
                    });
                    const result = await response.json();
                    
                    if (result.success) {
                        alert(`MOA issued successfully for ${candidateName}!\n\n${result.message}`);
                        // Redirect to MOA management page for further processing
                        window.open('moa_management.php?intern_id=' + internId, '_blank');
                    } else {
                        alert(`Failed to issue MOA: ${result.message}`);
                    }
                } catch (error) {
                    alert('Error issuing MOA. Please try again.');
                    console.error('MOA Error:', error);
                }
            }
        }

        async function setWorkSchedule(internId, candidateName) {
            // Redirect to schedule management with pre-selected intern
            window.location.href = `schedule_management.php?intern_id=${internId}&name=${encodeURIComponent(candidateName)}`;
        }

        document.querySelector('#assessment-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.target;
            const data = new FormData(form);
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Submitting...';
            submitBtn.disabled = true;
            
            try {
                const response = await fetch('interview_management.php', {
                    method: 'POST',
                    body: data
                });
                
                if (response.ok) {
                    // Check if it's a redirect (success)
                    if (response.redirected || response.url.includes('success=1')) {
                        alert('✅ Assessment submitted successfully! Candidate moved to Interviewed Candidates.');
                        window.location.reload(); // Reload to show success message and updated data
                    } else {
                        // Check response text for errors
                        const text = await response.text();
                        if (text.includes('Error!')) {
                            // Extract error message
                            const errorMatch = text.match(/<strong>Error!<\/strong>\s*([^<]+)/);
                            const errorMsg = errorMatch ? errorMatch[1].trim() : 'Please check the form and try again.';
                            alert('❌ Assessment submission failed: ' + errorMsg);
                        } else {
                            alert('✅ Assessment submitted successfully! Candidate moved to Interviewed Candidates.');
                            form.reset();
                            await loadPendingApplicants();
                            await loadInterviewedApplicants();
                            setActiveTab('interviewed-panel'); // Switch to interviewed tab to see result
                        }
                    }
                } else {
                    alert('❌ Failed to submit assessment. Server returned error: ' + response.status);
                }
            } catch (error) {
                console.error('Assessment submission error:', error);
                alert('❌ Network error occurred while submitting the assessment. Please check your connection and try again.');
            } finally {
                // Restore button state
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }
        });

        document.querySelector('#schedule-form').addEventListener('submit', async (event) => {
            event.preventDefault();
            const form = event.target;
            const data = new FormData(form);
            const response = await fetch('api/interviews.php?action=schedule', {
                method: 'POST',
                body: data
            });
            const result = await response.json();
            if (result.success) {
                alert('Interview schedule saved. Intern will be notified by message.');
                form.reset();
                handleInterviewModeChange();
                loadPendingApplicants();
                setActiveTab('pending-panel');
            } else {
                alert(result.message || 'Unable to save schedule.');
            }
        });

        loadPendingApplicants();
    </script>
</body>
</html>