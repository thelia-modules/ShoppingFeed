-- ---------------------------------------------------------------------
-- shoppingfeed_product_marketplace_category
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `shoppingfeed_product_marketplace_category`;

CREATE TABLE `shoppingfeed_product_marketplace_category`
(
    `product_id` INTEGER NOT NULL,
    `category_id` INTEGER NOT NULL,
    PRIMARY KEY (`product_id`),
    INDEX `fi_shoppingfeed_product_marketplace_category_category_id` (`category_id`),
    CONSTRAINT `fk_shoppingfeed_product_marketplace_category_product_id`
        FOREIGN KEY (`product_id`)
            REFERENCES `product` (`id`)
            ON UPDATE RESTRICT
            ON DELETE CASCADE,
    CONSTRAINT `fk_shoppingfeed_product_marketplace_category_category_id`
        FOREIGN KEY (`category_id`)
            REFERENCES `category` (`id`)
            ON UPDATE RESTRICT
            ON DELETE CASCADE
) ENGINE=InnoDB;

