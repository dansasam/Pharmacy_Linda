<?php
/**
 * Intern Access Control Functions
 * Controls access to different features based on intern application status
 */

require_once 'common.php';

/**
 * Check if intern has completed all requirements
 */
function check_requirements_completion($user_id) {
    global $pdo;
    
    // Get total number of requirements
    $total_req = $pdo->query('SELECT COUNT(*) FROM internship_requirements')->fetchColumn();
    
    // Get approved submissions count
    $approved_stmt = $pdo->prepare('SELECT COUNT(*) FROM intern_submissions WHERE user_id = ? AND status = "Approved"');
    $approved_stmt->execute([$user_id]);
    $approved_count = $approved_stmt->fetchColumn();
    
    return intval($approved_count) === intval($total_req);
}

/**
 * Check if intern has been assigned a schedule
 */
function check_schedule_assignment($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM internship_schedules WHERE intern_id = ? AND total_hours > 0');
    $stmt->execute([$user_id]);
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Update intern application status based on current progress
 */
function update_intern_status($user_id) {
    global $pdo;
    
    $requirements_done = check_requirements_completion($user_id);
    $schedule_assigned = check_schedule_assignment($user_id);
    
    $status = 'pending';
    if ($requirements_done && $schedule_assigned) {
        $status = 'active';
    } elseif ($requirements_done) {
        $status = 'approved';
    } elseif (has_submitted_requirements($user_id)) {
        $status = 'requirements_submitted';
    }
    
    $stmt = $pdo->prepare('UPDATE users SET application_status = ?, requirements_completed = ?, schedule_assigned = ? WHERE id = ?');
    $stmt->execute([$status, $requirements_done, $schedule_assigned, $user_id]);
    
    return $status;
}

/**
 * Check if user has submitted any requirements
 */
function has_submitted_requirements($user_id) {
    global $pdo;
    
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM intern_submissions WHERE user_id = ?');
    $stmt->execute([$user_id]);
    
    return $stmt->fetchColumn() > 0;
}

/**
 * Check if intern can access inventory features
 */
function can_access_inventory($user = null) {
    if (!$user) {
        $user = current_user();
    }
    
    if (!$user || $user['role'] !== 'Intern') {
        return $user['role'] === 'Pharmacist' || $user['role'] === 'Pharmacy Technician';
    }
    
    // Update status first
    update_intern_status($user['id']);
    
    // Refresh user data
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $updated_user = $stmt->fetch();
    
    return $updated_user['application_status'] === 'active';
}

/**
 * Check if intern can access reports
 */
function can_access_reports($user = null) {
    return can_access_inventory($user);
}

/**
 * Check if intern can manage products
 */
function can_manage_products($user = null) {
    return can_access_inventory($user);
}

/**
 * Get user application status message
 */
function get_status_message($user = null) {
    if (!$user) {
        $user = current_user();
    }
    
    if (!$user || $user['role'] !== 'Intern') {
        return '';
    }
    
    update_intern_status($user['id']);
    
    // Refresh user data
    global $pdo;
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $updated_user = $stmt->fetch();
    
    switch ($updated_user['application_status']) {
        case 'pending':
            return 'Please complete your internship requirements to proceed.';
        case 'requirements_submitted':
            return 'Your requirements are under review. Please wait for approval.';
        case 'approved':
            return 'Requirements approved! Waiting for schedule assignment.';
        case 'active':
            return 'Welcome! You now have full access to the system.';
        case 'completed':
            return 'Your internship has been completed.';
        case 'rejected':
            return 'Your application has been rejected. Please contact HR.';
        default:
            return 'Application status unknown.';
    }
}

/**
 * Require active intern status or redirect
 */
function require_active_intern($redirect_url = 'requirements_upload.php') {
    $user = current_user();
    
    if (!$user) {
        header('Location: index.php');
        exit;
    }
    
    if ($user['role'] !== 'Intern') {
        return; // Non-interns can access
    }
    
    if (!can_access_inventory($user)) {
        header('Location: ' . $redirect_url . '?message=' . urlencode(get_status_message($user)));
        exit;
    }
}