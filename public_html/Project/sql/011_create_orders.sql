CREATE TABLE `Orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT,
    `total_price` DECIMAL(10, 2),
    `created` DATETIME,
    FOREIGN KEY (`user_id`) REFERENCES `Users`(`id`)
);