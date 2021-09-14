
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- shopping_feed_config
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `shopping_feed_config`;

CREATE TABLE `shopping_feed_config`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `store_id` VARCHAR(255),
    `country_id` INTEGER,
    `lang_id` INTEGER,
    `api_token` VARCHAR(255),
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `fi_shopping_feed_config_country_id` (`country_id`),
    INDEX `fi_shopping_feed_config_lang_id` (`lang_id`),
    CONSTRAINT `fk_shopping_feed_config_country_id`
        FOREIGN KEY (`country_id`)
        REFERENCES `country` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,
    CONSTRAINT `fk_shopping_feed_config_lang_id`
        FOREIGN KEY (`lang_id`)
        REFERENCES `lang` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- shopping_feed_order_data
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `shopping_feed_order_data`;

CREATE TABLE `shopping_feed_order_data`
(
    `id` INTEGER NOT NULL,
    `channel` VARCHAR(255),
    `external_reference` VARCHAR(255),
    `email` VARCHAR(255),
    PRIMARY KEY (`id`),
    CONSTRAINT `shopping_feed_order_data_id`
        FOREIGN KEY (`id`)
        REFERENCES `order` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
