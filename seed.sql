SET sql_mode = '';

USE ResortBookingDB;

INSERT INTO Roles (RoleName, Description, CreatedAt) VALUES
('Admin', 'System administrator with full access', NOW()),
('Staff', 'Resort staff for booking and check-in', NOW()),
('Guest', 'Customer booking rooms and activities', NOW());

INSERT INTO Users (RoleId, FirstName, LastName, Email, PasswordHash, PhoneNumber, Address, IsActive, CreatedAt, UpdatedAt) VALUES
(1, 'Juan', 'Dela Cruz', 'admin@resort.com', 'Password123!', '09171234567', 'Manila, Philippines', 1, NOW(), NOW()),
(2, 'Maria', 'Santos', 'maria@resort.com', 'Password123!', '09181234567', 'Cebu City, Philippines', 1, NOW(), NOW()),
(2, 'Jose', 'Reyes', 'jose@resort.com', 'Password123!', '09191234567', 'Davao City, Philippines', 1, NOW(), NOW()),
(3, 'Anna', 'Garcia', 'anna@gmail.com', 'Password123!', '09201234567', 'Quezon City, Philippines', 1, NOW(), NOW()),
(3, 'Mark', 'Lopez', 'mark@gmail.com', 'Password123!', '09211234567', 'Makati City, Philippines', 1, NOW(), NOW()),
(3, 'Lisa', 'Cruz', 'lisa@gmail.com', 'Password123!', '09221234567', 'Pasig City, Philippines', 1, NOW(), NOW());

INSERT INTO RoomTypes (TypeName, Description, BasePrice, MaxOccupancy, IsActive, CreatedAt) VALUES
('Standard Room', 'Basic room with fan and shared bathroom', 1200.00, 2, 1, NOW()),
('Deluxe Room', 'Air-conditioned room with queen bed', 2500.00, 3, 1, NOW()),
('Family Suite', 'Spacious suite with 2 bedrooms', 4500.00, 6, 1, NOW()),
('Beachfront Cottage', 'Nipa hut cottage on the beach', 3000.00, 4, 1, NOW()),
('Poolside Villa', 'Private villa with plunge pool', 5500.00, 4, 1, NOW());

INSERT INTO Rooms (RoomTypeId, RoomNumber, Floor, Status, CreatedAt, UpdatedAt) VALUES
(1, 'R101', 1, 0, NOW(), NOW()), (1, 'R102', 1, 0, NOW(), NOW()), (1, 'R103', 1, 2, NOW(), NOW()),
(2, 'R201', 2, 0, NOW(), NOW()), (2, 'R202', 2, 1, NOW(), NOW()), (2, 'R203', 2, 0, NOW(), NOW()),
(3, 'R301', 3, 0, NOW(), NOW()), (3, 'R302', 3, 3, NOW(), NOW()),
(4, 'C01', 0, 0, NOW(), NOW()), (4, 'C02', 0, 0, NOW(), NOW()),
(5, 'V01', 0, 0, NOW(), NOW()), (5, 'V02', 0, 1, NOW(), NOW());

INSERT INTO Activities (ActivityName, Description, PricePerHour, PricePerDay, MaxParticipants, IsActive, CreatedAt) VALUES
('Kayaking', 'Enjoy kayaking along the coastline', 250.00, 1500.00, 10, 1, NOW()),
('Jet Ski Rental', 'Ride the waves with our jet skis', 800.00, NULL, 2, 1, NOW()),
('Snorkeling Tour', 'Guided snorkeling to coral reefs', 350.00, NULL, 15, 1, NOW()),
('Island Hopping', 'Full-day tour visiting 3-4 islands', NULL, 2500.00, 20, 1, NOW()),
('Pool Access', 'Full day access to the resort pool', 150.00, 500.00, 50, 1, NOW()),
('Volleyball Court', 'Beach volleyball court rental', 200.00, 1000.00, 12, 1, NOW()),
('Fishing Trip', 'Guided deep-sea fishing experience', NULL, 3000.00, 8, 1, NOW()),
('Sunset Cruise', '2-hour scenic sunset cruise', 500.00, NULL, 30, 1, NOW());

INSERT INTO ActivitySchedules (ActivityId, ScheduleDate, StartTime, EndTime, AvailableSlots, Status, CreatedAt) VALUES
(1, '2026-09-01', '06:00:00', '08:00:00', 8, 0, NOW()),
(1, '2026-09-01', '15:00:00', '17:00:00', 10, 0, NOW()),
(2, '2026-09-01', '08:00:00', '10:00:00', 2, 0, NOW()),
(3, '2026-09-01', '07:00:00', '09:00:00', 12, 0, NOW()),
(4, '2026-09-02', '06:00:00', '16:00:00', 18, 0, NOW()),
(5, '2026-09-01', '08:00:00', '17:00:00', 45, 0, NOW()),
(8, '2026-09-01', '16:00:00', '18:00:00', 25, 0, NOW());

INSERT INTO Facilities (FacilityName, Description, RentalPrice, Capacity, IsActive, CreatedAt) VALUES
('Grand Pavilion', 'Large covered pavilion for events', 8000.00, 100, 1, NOW()),
('Gazebo', 'Small open-air gazebo near the beach', 1500.00, 8, 1, NOW()),
('Conference Room', 'Air-conditioned meeting room', 5000.00, 30, 1, NOW()),
('BBQ Area', 'Outdoor grilling station with tables', 2000.00, 20, 1, NOW());

INSERT INTO GuestRecords (UserId, FirstName, LastName, Email, PhoneNumber, CreatedAt) VALUES
(4, 'Anna', 'Garcia', 'anna@gmail.com', '09201234567', NOW()),
(5, 'Mark', 'Lopez', 'mark@gmail.com', '09211234567', NOW()),
(6, 'Lisa', 'Cruz', 'lisa@gmail.com', '09221234567', NOW());
