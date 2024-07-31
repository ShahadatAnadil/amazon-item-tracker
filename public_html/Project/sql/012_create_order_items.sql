CREATE TABLE `OrderItems` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT,
    `item_id` INT,
    `quantity` INT,
    `unit_price` DECIMAL(10, 2),
    FOREIGN KEY (`order_id`) REFERENCES `Orders`(`id`),
    FOREIGN KEY (`item_id`) REFERENCES `IT202-S24-ProductDetails`(`id`)
);