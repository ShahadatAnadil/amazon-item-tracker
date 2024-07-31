CREATE TABLE IF NOT EXISTS `user_favorites` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `item_id` INT NOT NULL,
    FOREIGN KEY (`user_id`) REFERENCES `Users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`item_id`) REFERENCES `IT202-S24-ProductDetails`(`id`) ON DELETE CASCADE
);