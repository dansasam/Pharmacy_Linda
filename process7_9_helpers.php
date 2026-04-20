<?php
require_once __DIR__ . '/common.php';

function ensure_process7_9_notifications_table(): void
{
    global $pdo;
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS notifications (
            notification_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function ensure_process7_9_tasks_table(): void
{
    ensure_process7_9_notifications_table();
    global $pdo;
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS tasks (
            task_id INT AUTO_INCREMENT PRIMARY KEY,
            employee_name VARCHAR(255) NOT NULL,
            task_name VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            status ENUM('To Do','In Progress','Done') NOT NULL DEFAULT 'To Do',
            start_date DATE NOT NULL,
            deadline DATE NOT NULL,
            attachment_path VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function ensure_process7_9_inventory_table(): void
{
    global $pdo;
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS product_inventory (
            product_id INT AUTO_INCREMENT PRIMARY KEY,
            drug_name VARCHAR(255) NOT NULL,
            manufacturer VARCHAR(255) NOT NULL,
            record_date DATE NOT NULL,
            invoice_no VARCHAR(100) NOT NULL,
            current_inventory INT NOT NULL,
            initial_comments TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );

    $count = (int) $pdo->query('SELECT COUNT(*) FROM product_inventory')->fetchColumn();
    if ($count === 0) {
        $stmt = $pdo->prepare(
            'INSERT INTO product_inventory (drug_name, manufacturer, record_date, invoice_no, current_inventory, initial_comments) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $samples = [
            ['Acetaminophen 500mg', 'PharmaCare Labs', date('Y-m-d'), 'INV-1001', 120, 'Initial stock entry for paracetamol.'],
            ['Amoxicillin 250mg', 'HealthSource Pharma', date('Y-m-d'), 'INV-1002', 85, 'Antibiotic stock for clinic use.'],
            ['Cetirizine 10mg', 'AllergyFree Inc.', date('Y-m-d'), 'INV-1003', 60, 'Allergy relief tablets inventory.']
        ];
        foreach ($samples as $sample) {
            $stmt->execute($sample);
        }
    }
}

function ensure_process7_9_orientation_table(): void
{
    global $pdo;
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS orientation_progress (
            progress_id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            item_key VARCHAR(100) NOT NULL,
            completed TINYINT(1) NOT NULL DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY user_item (user_id, item_key),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
    );
}

function get_process7_9_orientation_items(): array
{
    return [
        'company_overview' => 'Company overview presented',
        'workplace_rules' => 'Workplace rules and policies explained',
        'safety_briefing' => 'Safety and pharmacy handling briefing completed',
        'team_introduction' => 'Intern introduced to staff and work area',
        'system_walkthrough' => 'System and workflow walkthrough completed',
        'qa_session' => 'Questions and answers completed',
    ];
}

function get_intern_users(): array
{
    global $pdo;
    $stmt = $pdo->query("SELECT id, full_name FROM users WHERE role = 'Intern' ORDER BY full_name ASC");
    return $stmt->fetchAll();
}

function get_process7_9_notifications_for_user(int $userId): array
{
    global $pdo;
    $stmt = $pdo->prepare("SELECT notification_id, title, message, created_at, is_read FROM notifications WHERE user_id = ? ORDER BY notification_id DESC LIMIT 8");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function mark_process7_9_notifications_read(int $userId): void
{
    global $pdo;
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
}
