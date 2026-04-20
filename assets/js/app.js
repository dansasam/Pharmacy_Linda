const showMessage = (selector, message, isError = false) => {
    const el = document.querySelector(selector);
    if (!el) return;
    el.textContent = message;
    el.className = isError ? 'message error' : 'message';
};

const fetchJson = async (url, options = {}) => {
    const res = await fetch(url, options);
    const data = await res.json();
    if (!res.ok) {
        throw new Error(data.message || 'Request failed');
    }
    return data;
};

const loginForm = document.querySelector('#login-form');
if (loginForm) {
    loginForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(loginForm);
        try {
            const data = await fetchJson('api/auth.php?action=login', { method: 'POST', body: form });
            const role = data.role;
            if (role === 'Intern') window.location.href = 'dashboard_intern.php';
            if (role === 'HR Personnel') window.location.href = 'dashboard_hr.php';
            if (role === 'Pharmacist') window.location.href = 'dashboard_pharmacist.php';
            if (role === 'Pharmacy Technician') window.location.href = 'dashboard_technician.php';
        } catch (error) {
            showMessage('#login-message', error.message, true);
        }
    });
}

const registerForm = document.querySelector('#register-form');
if (registerForm) {
    registerForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const form = new FormData(registerForm);
        try {
            await fetchJson('api/auth.php?action=register', { method: 'POST', body: form });
            showMessage('#register-message', 'Registration successful. Redirecting...');
            setTimeout(() => window.location.href = 'choose_role.php', 900);
        } catch (error) {
            showMessage('#register-message', error.message, true);
        }
    });
}

const googleBtn = document.querySelector('#google-login-btn');
if (googleBtn) {
    googleBtn.addEventListener('click', () => {
        window.location.href = 'google_login.php';
    });
}

const pageRole = window.pageData?.role;

const statusBadge = (status) => {
    const lower = status.toLowerCase();
    let cls = 'badge pending';
    if (status === 'Approved' || status === 'Complete') cls = 'badge complete';
    if (status === 'Rejected' || status === 'Missing' || status === 'Incomplete') cls = 'badge rejected';
    if (status === 'Pending') cls = 'badge pending';
    return `<span class="${cls}">${status}</span>`;
};

const loadRequirementsPage = async () => {
    const response = await fetchJson('api/submissions.php?action=list_user');
    const items = response.items;
    const rows = items.map(item => {
        const status = item.status || 'Missing';
        return `<tr>
            <td>${item.title}</td>
            <td>${item.description}</td>
            <td>${item.filename ? `<a href="uploads/${item.filename}" target="_blank">View</a>` : 'No file'}</td>
            <td>${statusBadge(status)}</td>
            <td>${item.remarks || '—'}</td>
            <td>${item.uploaded_at ? new Date(item.uploaded_at).toLocaleDateString() : '—'}</td>
            <td>${item.filename ? `<form class="upload-form" data-id="${item.requirement_id}"><input type="file" name="document" required /><button type="submit" class="btn btn-primary">Replace</button></form>` : `<form class="upload-form" data-id="${item.requirement_id}"><input type="file" name="document" required /><button type="submit" class="btn btn-primary">Upload</button></form>`}</td>
        </tr>`;
    }).join('');

    document.querySelector('#requirements-list').innerHTML = `
        <table><thead><tr><th>Requirement</th><th>Description</th><th>File</th><th>Status</th><th>Remark</th><th>Uploaded</th><th>Action</th></tr></thead><tbody>${rows}</tbody></table>`;
    document.querySelectorAll('.upload-form').forEach(form => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const id = form.dataset.id;
            const file = form.querySelector('input[type=file]').files[0];
            if (!file) return;
            const payload = new FormData();
            payload.append('requirement_id', id);
            payload.append('document', file);
            try {
                await fetchJson('api/submissions.php?action=upload', { method: 'POST', body: payload });
                await loadRequirementsPage();
            } catch (err) {
                alert(err.message);
            }
        });
    });
};

const loadChecklistPage = async () => {
    const response = await fetchJson('api/submissions.php?action=list_user');
    const items = response.items;
    const rows = items.map(item => {
        const status = item.status || 'Missing';
        return `<tr>
            <td>${item.title}</td>
            <td>${item.description}</td>
            <td>${item.filename ? `<a href="uploads/${item.filename}" target="_blank">View</a>` : 'No file'}</td>
            <td>${statusBadge(status)}</td>
            <td>${item.remarks || '—'}</td>
            <td>${item.uploaded_at ? new Date(item.uploaded_at).toLocaleDateString() : '—'}</td>
        </tr>`;
    }).join('');

    document.querySelector('#checklist-table').innerHTML = `
        <table><thead><tr><th>Requirement</th><th>Description</th><th>File</th><th>Status</th><th>Remark</th><th>Uploaded</th></tr></thead><tbody>${rows}</tbody></table>`;
};

const loadPoliciesPage = async () => {
    const policyResponse = await fetchJson('api/policies.php?action=list');
    document.querySelector('#policies-list').innerHTML = policyResponse.policies.map((policy, index) => `
        <div class="policy-title-box">
            <div class="policy-title" data-id="${policy.id}"><strong>${index + 1}. ${policy.title}</strong></div>
        </div>
        <div class="policy-content-box" id="content-${policy.id}" style="display:none;">
            ${policy.content}
        </div>
    `).join('');
    document.querySelectorAll('.policy-title').forEach(title => {
        title.addEventListener('click', () => {
            const contentId = 'content-' + title.dataset.id;
            const content = document.getElementById(contentId);
            content.style.display = content.style.display === 'none' ? 'block' : 'none';
        });
    });
};

const loadHRDashboard = async () => {
    const summary = await fetchJson('api/submissions.php?action=summary');
    document.querySelector('#hr-total-interns').textContent = summary.total_interns;
    document.querySelector('#hr-pending').textContent = summary.pending_submissions;
    document.querySelector('#hr-completed').textContent = summary.completed_interns;
    const requirements = await fetchJson('api/requirements.php?action=list');
    const requirementRows = requirements.requirements.map(item => `
        <tr>
            <td>${item.title}</td>
            <td>${item.description}</td>
            <td><button type="button" class="action-btn edit-requirement" data-id="${item.id}" data-title="${item.title}" data-description="${item.description}">Edit</button> | <button type="button" class="action-btn delete-requirement" data-id="${item.id}">Delete</button></td>
        </tr>
    `).join('');
    document.querySelector('#requirements-admin-list').innerHTML = `<table><thead><tr><th>Requirement</th><th>Description</th><th>Actions</th></tr></thead><tbody>${requirementRows}</tbody></table>`;
    document.querySelectorAll('.edit-requirement').forEach(button => {
        button.addEventListener('click', () => {
            const form = document.querySelector('#requirement-form');
            form.id.value = button.dataset.id;
            form.title.value = button.dataset.title;
            form.description.value = button.dataset.description;
        });
    });
    document.querySelectorAll('.delete-requirement').forEach(button => {
        button.addEventListener('click', async () => {
            if (!confirm('Delete this requirement?')) return;
            const form = new FormData();
            form.append('id', button.dataset.id);
            await fetchJson('api/requirements.php?action=delete', { method: 'POST', body: form });
            await loadHRDashboard();
        });
    });

    const policies = await fetchJson('api/policies.php?action=list');
    const policyRows = policies.policies.map(item => `
        <tr>
            <td>${item.title}</td>
            <td>${item.content}</td>
            <td><button type="button" class="action-btn edit-policy" data-id="${item.id}" data-title="${item.title}" data-content="${item.content}">Edit</button> | <button type="button" class="action-btn delete-policy" data-id="${item.id}">Delete</button></td>
        </tr>
    `).join('');
    document.querySelector('#policies-admin-list').innerHTML = `<table><thead><tr><th>Title</th><th>Content</th><th>Actions</th></tr></thead><tbody>${policyRows}</tbody></table>`;
    document.querySelectorAll('.edit-policy').forEach(button => {
        button.addEventListener('click', () => {
            const form = document.querySelector('#policy-form');
            form.id.value = button.dataset.id;
            form.title.value = button.dataset.title;
            form.content.value = button.dataset.content;
        });
    });
    document.querySelectorAll('.delete-policy').forEach(button => {
        button.addEventListener('click', async () => {
            if (!confirm('Delete this policy?')) return;
            const form = new FormData();
            form.append('id', button.dataset.id);
            await fetchJson('api/policies.php?action=delete', { method: 'POST', body: form });
            await loadHRDashboard();
        });
    });

    const submissions = await fetchJson('api/submissions.php?action=list_all');
    document.querySelector('#submission-review-table').innerHTML = `<table><thead><tr><th>Intern</th><th>Requirement</th><th>File</th><th>Status</th><th>Remarks</th><th>Review</th></tr></thead><tbody>${submissions.items.map(item => `
            <tr>
                <td>${item.full_name} <div class="small-note">${item.email}</div></td>
                <td>${item.title}</td>
                <td>${item.filename ? `<a href="uploads/${item.filename}" target="_blank">View</a>` : 'No file'}</td>
                <td>${statusBadge(item.status)}</td>
                <td>${item.remarks || '—'}</td>
                <td>
                    <form class="review-form" data-id="${item.id}">
                        <select name="status"><option value="Approved">Approved</option><option value="Rejected">Rejected</option><option value="Pending">Pending</option></select>
                        <input type="text" name="remarks" placeholder="Remark" />
                        <button class="btn btn-primary">Save</button>
                    </form>
                </td>
            </tr>`).join('')}</tbody></table>`;
    document.querySelectorAll('.review-form').forEach(form => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const id = form.dataset.id;
            const reviewData = new FormData(form);
            reviewData.append('id', id);
            await fetchJson('api/submissions.php?action=review', { method: 'POST', body: reviewData });
            await loadHRDashboard();
        });
    });
    // Load applicant approval
    const approvalResponse = await fetchJson('api/applicants.php?action=list_pending_approval');
    document.querySelector('#applicant-approval-table').innerHTML = `<table><thead><tr><th>Name</th><th>Email</th><th>Action</th></tr></thead><tbody>${approvalResponse.applicants.map(app => `
            <tr>
                <td>${app.full_name}</td>
                <td>${app.email}</td>
                <td><button class="action-btn approve-app" data-id="${app.id}">Approve for Interview</button></td>
            </tr>`).join('')}</tbody></table>`;
    document.querySelectorAll('.approve-app').forEach(btn => {
        btn.addEventListener('click', async () => {
            await fetchJson('api/applicants.php?action=approve&id=' + btn.dataset.id, { method: 'POST' });
            await loadHRDashboard();
        });
    });
};

const loadPharmacistDashboard = async () => {
    const response = await fetchJson('api/submissions.php?action=intern_report');
    document.querySelector('#intern-monitoring-list').innerHTML = `<table><thead><tr><th>Intern</th><th>Status</th><th>Approved Docs</th><th>Completion</th></tr></thead><tbody>${response.interns.map(item => `
            <tr>
                <td>${item.full_name}<div class="small-note">${item.email}</div></td>
                <td>${statusBadge(item.status)}</td>
                <td>${item.approved}</td>
                <td>${item.percentage}%</td>
            </tr>`).join('')}</tbody></table>`;
    document.querySelector('#intern-report-table').innerHTML = `<table><thead><tr><th>Intern</th><th>Completion Percentage</th><th>Status</th></tr></thead><tbody>${response.interns.map(item => `
            <tr>
                <td>${item.full_name}</td>
                <td>${item.percentage}%</td>
                <td>${statusBadge(item.status)}</td>
            </tr>`).join('')}</tbody></table>`;
};

const loadInternDashboard = async () => {
    const userId = window.pageData?.userId;
    if (!userId) return;

    // Load intern's schedule
    try {
        const scheduleResponse = await fetchJson('api/schedules.php?action=get');
        if (scheduleResponse.success && scheduleResponse.schedule) {
            const schedule = scheduleResponse.schedule;
            const scheduleHtml = `
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>Day</th>
                            <th>Schedule</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td>Monday</td><td>${schedule.monday || 'Off'}</td></tr>
                        <tr><td>Tuesday</td><td>${schedule.tuesday || 'Off'}</td></tr>
                        <tr><td>Wednesday</td><td>${schedule.wednesday || 'Off'}</td></tr>
                        <tr><td>Thursday</td><td>${schedule.thursday || 'Off'}</td></tr>
                        <tr><td>Friday</td><td>${schedule.friday || 'Off'}</td></tr>
                        <tr><td>Saturday</td><td>${schedule.saturday || 'Off'}</td></tr>
                        <tr><td>Sunday</td><td>${schedule.sunday || 'Off'}</td></tr>
                    </tbody>
                </table>
                ${schedule.total_hours ? `<p><strong>Total Hours:</strong> ${schedule.total_hours}</p>` : ''}
                ${schedule.notes ? `<p><strong>Notes:</strong> ${schedule.notes}</p>` : ''}
            `;
            document.querySelector('#my-schedule').innerHTML = scheduleHtml;
        } else {
            document.querySelector('#my-schedule').innerHTML = '<p class="empty-state">No schedule has been assigned yet. Please contact HR for your work schedule.</p>';
        }
    } catch (error) {
        document.querySelector('#my-schedule').innerHTML = '<p class="empty-state">Unable to load schedule. Please try again later.</p>';
        console.error('Error loading schedule:', error);
    }

    // Load interview schedule
    try {
        const interviewResponse = await fetchJson('api/interviews.php?action=intern_schedule');
        if (interviewResponse.success && interviewResponse.schedule) {
            const interview = interviewResponse.schedule;
            const interviewHtml = `
                <div class="interview-card">
                    <h4>${interview.interview_mode || 'Online'} Interview</h4>
                    <p><strong>Date:</strong> ${interview.interview_date ? new Date(interview.interview_date).toLocaleDateString() : 'TBD'}</p>
                    ${interview.interview_location ? `<p><strong>Location:</strong> ${interview.interview_location}</p>` : ''}
                    ${interview.interview_link ? `<p><strong>Link:</strong> <a href="${interview.interview_link}" target="_blank">${interview.interview_link}</a></p>` : ''}
                    ${interview.notification_message ? `<p><strong>Message:</strong> ${interview.notification_message}</p>` : ''}
                </div>
            `;
            document.querySelector('#interview-schedule').innerHTML = interviewHtml;
        } else {
            document.querySelector('#interview-schedule').innerHTML = '<p class="empty-state">No upcoming interviews scheduled.</p>';
        }
    } catch (error) {
        document.querySelector('#interview-schedule').innerHTML = '<p class="empty-state">Unable to load interview schedule.</p>';
        console.error('Error loading interview:', error);
    }

    // Load requirements stats
    try {
        const statsResponse = await fetchJson('api/submissions.php?action=list_user');
        if (statsResponse.success && statsResponse.items) {
            const items = statsResponse.items;
            const total = items.length;
            const uploaded = items.filter(item => item.filename).length;
            const approved = items.filter(item => item.status === 'Approved').length;
            const missing = total - uploaded;

            document.querySelector('#stat-total').textContent = total;
            document.querySelector('#stat-uploaded').textContent = uploaded;
            document.querySelector('#stat-approved').textContent = approved;
            document.querySelector('#stat-missing').textContent = missing;

            const percentage = total > 0 ? Math.round((approved / total) * 100) : 0;
            document.querySelector('#progress-bar').style.width = `${percentage}%`;
            document.querySelector('#progress-text').textContent = `${percentage}% complete`;
        }
    } catch (error) {
        console.error('Error loading stats:', error);
    }
};

if (pageRole === 'Intern') {
    // Check which page we're on and load appropriate data
    if (document.querySelector('#requirements-list')) {
        loadRequirementsPage().catch(console.error);
    } else if (document.querySelector('#checklist-table')) {
        loadChecklistPage().catch(console.error);
    } else if (document.querySelector('#policies-list')) {
        loadPoliciesPage().catch(console.error);
    } else {
        // Main dashboard
        loadInternDashboard().catch(console.error);
    }
}
if (pageRole === 'HR Personnel') {
    loadHRDashboard().catch(console.error);
    const reqForm = document.querySelector('#requirement-form');
    if (reqForm) {
        reqForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const payload = new FormData(reqForm);
            const action = payload.get('id') ? 'update' : 'create';
            await fetchJson(`api/requirements.php?action=${action}`, { method: 'POST', body: payload });
            reqForm.reset();
            await loadHRDashboard();
        });
    }
    const policyForm = document.querySelector('#policy-form');
    if (policyForm) {
        policyForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            const payload = new FormData(policyForm);
            const action = payload.get('id') ? 'update' : 'create';
            await fetchJson(`api/policies.php?action=${action}`, { method: 'POST', body: payload });
            policyForm.reset();
            await loadHRDashboard();
        });
    }
}
if (pageRole === 'Pharmacist') {
    loadPharmacistDashboard().catch(console.error);
}
