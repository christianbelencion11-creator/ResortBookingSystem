# 🌴 Resort Booking & Activity Management System
## Gabay sa Pag-Transfer at Pag-Setup sa Laptop ng Kaklase (System Checking Ready)

Ang gabay na ito ay ginawa upang maging **mabilis, madali, at 100% walang error** ang paglipat ng buong system sa laptop ng iyong kaklase bago o habang may system checking sa paaralan.

---

## 📋 Mga Kailangan sa Laptop ng Kaklase (Prerequisites)
1. **XAMPP** (naka-install na karaniwang nasa `C:\xampp`)
   - Kailangan lang buksan ang **Apache** at **MySQL**.
2. **Browser** (Google Chrome, Microsoft Edge, o Brave).

> [!TIP]
> **Hindi na kailangan ng Composer o .NET SDK!** Ang system na ito ay pure native PHP 8+ at MySQL na agad tatakbo sa kahit anong karaniwang XAMPP setup sa paaralan.

---

## 🚀 Paraan 1: Paglipat Gamit ang GitHub (Pinaka-mabilis kung may Internet)

1. Sa laptop ng kaklase, buksan ang browser at pumunta sa repository link:
   ```
   https://github.com/christianbelencion11-creator/ResortBookingSystem
   ```
2. Pindutin ang berdeng button na **`<> Code`** -> Piliin ang **`Download ZIP`**.
3. I-extract ang na-download na ZIP file sa **Desktop** o sa `C:\xampp\htdocs\ResortBookingSystem`.

*(O kung may Git Bash/Terminal, patakbuhin lang:)*
```bash
git clone https://github.com/christianbelencion11-creator/ResortBookingSystem.git
```

---

## 💾 Paraan 2: Paglipat Gamit ang USB Flash Drive (Kung Walang Internet)

1. Sa iyong laptop, kopyahin ang buong folder na `ResortBookingSystem` papunta sa USB Flash Drive.
2. Isaksak ang USB sa laptop ng iyong kaklase at kopyahin ang folder sa kanyang **Desktop** o sa `C:\xampp\htdocs\`.

---

## 🗄️ Pag-Setup ng Database (1-Minute Setup sa phpMyAdmin)

1. Buksan ang **XAMPP Control Panel** sa laptop ng kaklase.
2. Pindutin ang **Start** sa tabi ng **Apache** at **MySQL** (dapat maging kulay berde pareho).
3. Buksan ang browser at pumunta sa:
   ```
   http://localhost/phpmyadmin
   ```
4. Pindutin ang tab na **Import** sa itaas na menu.
5. Pindutin ang **Choose File** (Piliin ang File) at hanapin ang file na:
   ```
   ResortBookingSystem/database/resort_db.sql
   ```
6. Mag-scroll pababa at pindutin ang button na **Import** (o **Go**).
7. Lalabas ang berdeng checkmark: *"Import has been successfully finished!"*

> [!NOTE]
> Kusa nang gagawin ng script ang database na `ResortBookingDB` kasama ang lahat ng 17 tables at kumpletong sample data!

---

## ⚡ Paano Patakbuhin ang System

### Opsyon A: 1-Click Batch File (Pinaka-mabilis at Sigurado)
1. Buksan ang folder ng `ResortBookingSystem`.
2. I-double click lang ang file na:
   ```
   start_server.bat
   ```
3. Kusa nitong sisimulan ang PHP server at bubuksan agad ang iyong browser sa:
   ```
   http://localhost:8000
   ```

### Opsyon B: Gamit ang XAMPP Apache
1. Ilagay ang folder ng project sa `C:\xampp\htdocs\ResortBookingSystem`.
2. Buksan ang browser at pumunta sa:
   ```
   http://localhost/ResortBookingSystem
   ```

---

## 🔑 Mga Demo Accounts para sa System Checking

Maaari mong gamitin ang mga sumusunod na account para ipakita sa guro o evaluator:

| Role | Email Address | Password | Mga Kakayahan / Features |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin@resort.com` | `Password123!` | Buong access (Users, Settings, Pricing, Discounts, Audit Log, Executive Reports) |
| **Staff** | `maria@resort.com` | `Password123!` | Front-desk operations (Check-In/Out, Walk-In, Payments, Receipts, Rooms) |
| **Guest** | `anna@gmail.com` | `Password123!` | Guest reservations, stay history, and personal booking requests |

*(May **Quick Demo Logins** button din sa Login page para sa 1-click auto-fill during presentation!)*

---

## 🌟 Mga Tampok na Features na Maipapakita sa Guro:
1. **Executive Dashboard**: Real-time room counts, active stays, monthly revenue, pending payments, at room status pie/badges.
2. **Interactive Availability Calendar**: Visual calendar na nagpapakita ng okupadong kwarto araw-araw.
3. **Walk-In Front Desk**: Mabilisang pag-register ng guest, pagpili ng bakanteng kwarto, at automatic check-in.
4. **Multi-Item Booking Engine**: Pagsasama ng Rooms + Activity Tours + Event Venues sa iisang booking na may discount voucher support.
5. **Printable Official Receipt**: Pindutin ang "Print / Save as PDF" para sa malinis na resibo ng resort.
6. **Expense & Profit Margin Tracker**: Pagkwenta ng Revenue bawas Expenses para sa Net Income.
7. **Visual Analytics with Chart.js**: 6-month revenue bar chart, room status doughnut, at payment channel distribution.
8. **Dark / Light Mode Toggle**: Pindutin ang Sun/Moon icon sa kanang itaas para magpalit ng tema na may persistence.
9. **Tamper-Resistant Audit Trail**: Nakatala bawat galaw sa system (Logins, Bookings, Payments, Check-Ins).
10. **Data Export**: 1-click download ng CSV reports para sa reservations, payments, at expenses.

---

## 🛠️ Pag-Troubleshoot (Kung May Tanong o Issue)

- **Problem: "Access denied for user 'root'@'localhost'"**
  - *Dahilan:* May password ang MySQL sa laptop.
  - *Solusyon:* Walang problema! Naka-program ang ating `config/database.php` na awtomatikong subukan ang parehong walang password (`""`), `'admin123'`, at `'root'`. Kung iba ang password ng kaklase mo, maaari itong i-update sa `config/database.php`.
- **Problem: "Port 8000 already in use"**
  - *Solusyon:* I-edit ang `start_server.bat` at palitan ang `8000` ng `8080`, o gamitin ang Opsyon B sa XAMPP Apache (`http://localhost/ResortBookingSystem`).
