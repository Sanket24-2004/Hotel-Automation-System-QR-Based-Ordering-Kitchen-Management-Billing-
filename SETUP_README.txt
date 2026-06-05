GOLDEN STONE HOTEL — KITCHEN DASHBOARD SETUP GUIDE
====================================================
Version 1.0 | Setup for Kitchen PC / Server PC
====================================================

OVERVIEW
--------
The Kitchen Dashboard uses PHP + MySQL to receive orders from customer
mobile phones in real-time over your local WiFi network.

This guide covers:
  1. Installing XAMPP (free local server)
  2. Setting up the database
  3. Configuring your network
  4. Testing the full system


STEP 1 — DOWNLOAD & INSTALL XAMPP
-----------------------------------
1. Go to: https://www.apachefriends.org/download.html
2. Download: XAMPP for Windows (PHP 8.x version)
3. Run the installer as Administrator
4. During installation, select:
      [✓] Apache
      [✓] MySQL
      [✓] PHP
      (phpMyAdmin is optional but useful)
5. Install to: C:\xampp  (recommended, keep the default)
6. Click Finish when done.


STEP 2 — COPY YOUR PROJECT FILES
----------------------------------
1. Open: C:\xampp\htdocs\
2. Create a new folder named: hotel
   Full path: C:\xampp\htdocs\hotel\

3. Copy ALL files from:
      C:\Users\Sanket\OneDrive\Desktop\Hotel Automation\

   Into:
      C:\xampp\htdocs\hotel\

   The folder should look like:
      C:\xampp\htdocs\hotel\index.html
      C:\xampp\htdocs\hotel\kitchen.html
      C:\xampp\htdocs\hotel\api\db.php
      C:\xampp\htdocs\hotel\api\setup.php
      C:\xampp\htdocs\hotel\starters.html
      ... etc.


STEP 3 — START XAMPP SERVERS
------------------------------
1. Open: XAMPP Control Panel (from desktop shortcut or Start Menu)
2. Click "Start" next to Apache
3. Click "Start" next to MySQL
4. Both should show green "Running" status.

   If Apache fails to start → Port 80 may be in use:
   a. Click "Config" next to Apache
   b. Click "httpd.conf"
   c. Find: Listen 80
   d. Change to: Listen 8080
   e. Restart Apache
   f. Then use: http://localhost:8080/hotel/ in all URLs below


STEP 4 — CREATE THE DATABASE
------------------------------
1. Open Chrome or Edge on the kitchen PC
2. Visit: http://localhost/hotel/api/setup.php
3. You should see green checkmarks for each step:
      ✅ Create database — OK
      ✅ Create restaurant_tables — OK
      ✅ Create orders — OK
      ✅ Create order_items — OK
      ✅ Create order_status_log — OK
      ✅ Create daily_archive — OK

4. IMPORTANT SECURITY STEP:
   After setup succeeds, delete the file:
      C:\xampp\htdocs\hotel\api\setup.php
   OR rename it to: setup.php.bak


STEP 5 — TEST ON LOCAL PC FIRST
---------------------------------
1. Open: http://localhost/hotel/index.html
   → The Golden Stone Hotel welcome page should open

2. Select language → Select table → Order some items → Confirm Order

3. Open: http://localhost/hotel/kitchen.html
   → The Kitchen Dashboard should open

4. Your test order should appear within 3 seconds.

5. Click "Start Preparing" → "Mark Ready" → "Mark Served"
   → Order moves through status panels correctly


STEP 6 — CONNECT CUSTOMER PHONES (WiFi Setup)
----------------------------------------------
To allow customer phones to connect, both the kitchen PC and customer
phones must be on the SAME WiFi network.

FIND YOUR PC's LOCAL IP ADDRESS:
1. Open Command Prompt (Win+R → type "cmd" → Enter)
2. Type: ipconfig
3. Look for: IPv4 Address . . . . : 192.168.x.x
   (This is your kitchen PC's local IP address)
   Example: 192.168.1.5

SHARE THIS URL WITH CUSTOMERS:
   http://192.168.1.5/hotel/index.html
   (Replace 192.168.1.5 with your actual IP)

QR CODE FOR CUSTOMERS:
- Use any free QR code generator (e.g., qr.io or qrcode-monkey.com)
- Enter: http://192.168.x.x/hotel/index.html
- Print and place on each table

KITCHEN DASHBOARD URL:
   http://192.168.1.5/hotel/kitchen.html
   (Open this on the kitchen PC/monitor)


STEP 7 — DAILY OPERATIONS
---------------------------
Every day when the restaurant opens:
1. Start XAMPP (Apache + MySQL)
2. Open kitchen.html on the kitchen monitor
3. Share WiFi with customers

Orders from today are automatically shown.
Served orders are archived and cleared from active view at midnight.

To view database (optional):
   http://localhost/phpmyadmin
   Database: golden_stone_hotel


TROUBLESHOOTING
---------------
Problem: "Database connection failed"
Solution: Make sure MySQL is running in XAMPP Control Panel

Problem: Customer phone can't connect
Solution: 
  a. Check both phone and PC are on same WiFi
  b. Check Windows Firewall allows Apache:
     Windows Security → Firewall → Allow an app → Add httpd.exe
     (usually at C:\xampp\apache\bin\httpd.exe)

Problem: Apache won't start (port conflict)
Solution: Change Apache port to 8080 (see Step 3 above)

Problem: Orders not appearing in kitchen
Solution: Check browser console on kitchen.html for API errors
  Press F12 → Console tab → look for red errors

Problem: QR code doesn't work
Solution: Make sure customer phone is connected to the SAME WiFi as PC
  (not mobile data)


DATABASE BACKUP (DAILY RECOMMENDED)
-------------------------------------
1. Open: http://localhost/phpmyadmin
2. Click: golden_stone_hotel
3. Click: Export → Quick → Format: SQL → Go
4. Save the .sql file to a safe location


FILE STRUCTURE AFTER SETUP
----------------------------
hotel/
├── index.html           Customer landing page
├── kitchen.html         Kitchen Dashboard (this file)
├── table-select.html    Table selection
├── menu.html            Menu categories
├── starters.html        Starters menu
├── main.html            Main course menu
├── breads.html          Breads menu
├── rice.html            Rice & Biryani menu
├── dessert.html         Desserts menu
├── beverages.html       Beverages menu
├── sides.html           Side Dishes menu
├── water.html           Water Bottles menu
├── my_orders.html       Customer order history
├── api/
│   ├── db.php           Database connection
│   ├── place_order.php  Receive orders from customers
│   ├── get_orders.php   Send orders to kitchen dashboard
│   ├── update_status.php Update order status
│   ├── acknowledge_items.php Clear NEW ITEM badges
│   └── get_stats.php    Dashboard statistics
└── SETUP_README.txt     This file


SUPPORT
-------
For any issues, check the XAMPP logs at:
  C:\xampp\apache\logs\error.log
  C:\xampp\mysql\data\*.err

====================================================
Golden Stone Hotel Kitchen System v1.0
====================================================
