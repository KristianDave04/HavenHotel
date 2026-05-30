The HavenHotel Project is a dynamic, data-driven web application built using a classic LAMP/WAMP micro-architecture (PHP, MySQL/MariaDB via phpMyAdmin, HTML5, CSS3, and JavaScript/JSON). The system acts as a real-time reservation repository and inventory manager, allowing hotel administrators to control room configurations, monitor operations metrics, and audit guest communications from a centralized desktop interface.

To understand how the HavenHotel platform operates day-to-day, it can be viewed through three distinct structural angles:

1. The Guest's Journey (The Front End)
   Guests visit the HavenHotel platform to view real-time suite availability, compare pricing models across various room tiers (such as Standard Rooms, Deluxe Suites, or Executive Spaces), and secure reservations. When a booking is finalized, the system generates a unique cryptographic matching string (the booking_reference)  to track their reservation across the hotel network. After checkout, guests submit visual rating metrics and text reviews  directly to the platform to capture their real-world experiences.

2. The Administrator's Command Center (The Hub)
   The dashboard file you uploaded acts as the Master Control Hub for the hotel staff. Instead of checking multiple detached programs, an employee sits at a single screen that monitors the health of the hotel:  The Telemetry Panel: Instantly checks how many guests are checked into the database, catches incoming "Pending" reservations, and calculates the hotel's dynamic financial success ratio.  Live Inventory Control: If a room type becomes full, needs maintenance, or price adjustments are demanded by the season, admins can dynamically change status markers between Available, Limited, or Not Available.

3. The Automated "Silent Partner" (The Database & Logic)
    Behind the scenes, the system relies on an automated layout pipeline. For instance, when a room tier is created, the system uses an image-pool engine to pick random, high-quality, professional photography visuals  to represent the unit automatically. At the exact same time, a background logging system detects structural shifts (like room deletions) and automatically generates system notices  so the management team always has an audited, chronological history of hotel operations.  
------------------------------------------------------------------------------------------------------------------------------------------

*IMPORTANT*

In order to run this project, first download it as zip. 

Open xampp control panel and open mySqladmin

In mySqlAdmin, home -> beside database click sql and add these syntax

CREATE DATABASE IF NOT EXISTS `haven hotel` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `haven hotel`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(50) NOT NULL,
    `last_name` VARCHAR(50) NOT NULL,
    `user_email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(20) DEFAULT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `rooms` (
    `room_id` INT(11) NOT NULL AUTO_INCREMENT,
    `room_number` VARCHAR(10) NOT NULL UNIQUE,
    `room_type` VARCHAR(50) NOT NULL,
    `price_per_night` DECIMAL(10,2) NOT NULL,
    `status` ENUM('Available', 'Limited', 'Not Available') NOT NULL DEFAULT 'Available',
    `description` TEXT DEFAULT NULL,
    `image_url` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`room_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `bookings` (
    `booking_id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `room_id` INT(11) NOT NULL,
    `booking_reference` VARCHAR(50) NOT NULL UNIQUE,
    `room_type` VARCHAR(50) NOT NULL,
    `check_in_date` DATE NOT NULL,
    `check_out_date` DATE NOT NULL,
    `total_price` DECIMAL(10,2) NOT NULL,
    `booking_status` ENUM('Pending', 'Confirmed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    `special_requests` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`booking_id`),
    
    -- Foreign Key Constraints: Protects data structure and enables ON DELETE CASCADE
    CONSTRAINT `bookings_ibfk_1` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE,
        
    CONSTRAINT `bookings_ibfk_2` 
        FOREIGN KEY (`room_id`) REFERENCES `rooms` (`room_id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifications` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `message` TEXT NOT NULL,
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `testimonials` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `user_id` INT(11) NOT NULL,
    `rating` INT(1) NOT NULL,
    `review_text` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `testimonials_ibfk_1` 
        FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) 
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
