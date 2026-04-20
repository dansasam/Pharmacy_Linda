# Pharmacy Linda - Internship Management System

A comprehensive pharmacy management system with integrated sales, prescription management, and inventory tracking.

## 🚀 Features

### Customer Features
- Browse and purchase pharmacy products
- Upload prescriptions for Rx medications
- Multiple payment options (Cash, Card, GCash via PayMongo)
- Order tracking and receipt generation
- Shopping cart functionality

### Pharmacy Assistant Features
- Review and verify customer prescriptions
- Check product availability
- Process orders based on prescriptions
- Confirm payments (cash/card)
- Dispense orders with automatic inventory deduction
- View order history and receipts

### Pharmacist Features
- All assistant features
- Approve requisitions
- Manage purchase orders
- View stock reports

### Intern Features
- Create inventory reports
- View stock status
- Submit requirements
- Track internship progress

### HR Features
- Manage applicants
- Schedule interviews
- Process MOA documents
- Assign internship schedules

## 📋 Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache Web Server
- XAMPP (recommended)

## 🔧 Installation

### 1. Clone Repository
```bash
git clone https://github.com/YOUR_USERNAME/Pharmacy_Linda.git
cd Pharmacy_Linda
```

### 2. Setup XAMPP
1. Install XAMPP from https://www.apachefriends.org/
2. Copy project folder to `C:\xampp\htdocs\`
3. Start Apache and MySQL from XAMPP Control Panel

### 3. Create Database
1. Open phpMyAdmin: `http://localhost/phpmyadmin`
2. Create new database: `pharmacy_linda`
3. Import database file: `database/COMPLETE_DATABASE_WITH_SALES.sql`

### 4. Configure Database Connection
Edit `config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'pharmacy_linda');
```

### 5. Create Upload Folders
```bash
mkdir uploads/prescriptions
mkdir uploads/moa
mkdir uploads/requirements
```

### 6. Access System
Open browser and go to: `http://localhost/Pharmacy_Linda/`

## 👥 Test Accounts

### Customer
- Email: `customer@test.com`
- Password: `password123`

### Pharmacy Assistant
- Email: `assistant@test.com`
- Password: `password123`

### Pharmacist
- Email: `pharmacist@test.com`
- Password: `password123`

### Intern
- Email: `intern@test.com`
- Password: `password123`

## 💳 Payment Integration

The system integrates with PayMongo for online payments (GCash, Credit/Debit Cards).

To enable PayMongo:
1. Sign up at https://paymongo.com
2. Get your API keys
3. Update `config.php` with your keys

## 📁 Project Structure

```
Pharmacy_Linda/
├── api/                    # API endpoints
├── assets/                 # CSS, JS, images
│   ├── css/
│   └── js/
├── database/              # Database files
├── uploads/               # User uploads
├── config.php             # Database configuration
├── common.php             # Common functions
├── sales_helpers.php      # Sales system helpers
├── process10_14_helpers.php  # Navigation helpers
└── [various PHP pages]
```

## 🔐 Security Notes

- Change default passwords before deployment
- Update database credentials in `config.php`
- Ensure `uploads/` folder has proper permissions
- Use HTTPS in production
- Keep PayMongo API keys secure

## 📝 Database Tables

- `users` - User accounts
- `sales` - Customer orders
- `sale_items` - Order line items
- `prescriptions` - Customer prescriptions
- `product_inventory` - Product stock
- `product_logs` - Inventory audit trail
- `notifications` - User notifications
- And more...

## 🎯 Workflow

1. Customer uploads prescription
2. Assistant reviews and verifies prescription
3. Assistant checks availability and processes order
4. Customer receives notification and chooses payment method
5. Customer pays (cash/card at counter or GCash online)
6. Assistant confirms payment (for cash/card)
7. Assistant dispenses order (inventory auto-deducts)
8. Customer receives notification and picks up order

## 🛠️ Technologies Used

- PHP
- MySQL
- JavaScript
- Bootstrap Icons
- PayMongo API
- XAMPP

## 📄 License

This project is for educational purposes.

## 👨‍💻 Author

Pharmacy Linda Development Team

## 🤝 Contributing

This is an academic project. For questions or issues, please contact the development team.
