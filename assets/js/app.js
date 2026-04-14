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

const loadInternDashboard = async () => {
    const response = await fetchJson('api/submissions.php?action=list_user');
    const items = response.items;
    const total = items.length;
    let uploaded = 0;
    let approved = 0;
    let missing = 0;
    const rows = items.map(item => {
        const status = item.status || 'Missing';
        if (item.filename) uploaded++;
        if (status === 'Approved') approved++;
        if (status === 'Missing') missing++;
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

    document.querySelector('#stat-total').textContent = total;
    document.querySelector('#stat-uploaded').textContent = uploaded;
    document.querySelector('#stat-approved').textContent = approved;
    document.querySelector('#stat-missing').textContent = missing;
    const completePercent = total ? Math.round((approved / total) * 100) : 0;
    document.querySelector('#progress-bar').style.width = `${completePercent}%`;
    document.querySelector('#progress-text').textContent = `${completePercent}% complete`;
    document.querySelector('#requirements-list').innerHTML = `
        <table><thead><tr><th>Requirement</th><th>Description</th><th>File</th><th>Status</th><th>Remark</th><th>Uploaded</th><th>Action</th></tr></thead><tbody>${rows}</tbody></table>`;
    document.querySelector('#checklist-table').innerHTML = document.querySelector('#requirements-list').innerHTML;
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
                await loadInternDashboard();
            } catch (err) {
                alert(err.message);
            }
        });
    });
    const policyResponse = await fetchJson('api/policies.php?action=list');
    document.querySelector('#policies-list').innerHTML = policyResponse.policies.map(policy => `
        <div class="section-card" style="padding:1rem;margin-bottom:1rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;"><div><strong>${policy.title}</strong><div class="small-note">Category: ${policy.category}</div></div></div>
            <p>${policy.content}</p>
        </div>
    `).join('');
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

if (pageRole === 'Intern') {
    loadInternDashboard().catch(console.error);
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
