
# This is a fix for InnoDB in MySQL >= 4.1.x
# It "suspends judgement" for fkey relationships until are tables are set.
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- shoppingfeed_feed
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `shoppingfeed_feed`;

CREATE TABLE `shoppingfeed_feed`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `store_id` VARCHAR(255),
    `country_id` INTEGER,
    `lang_id` INTEGER,
    `api_token` VARCHAR(255),
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `fi_shoppingfeed_feed_country_id` (`country_id`),
    INDEX `fi_shoppingfeed_feed_lang_id` (`lang_id`),
    CONSTRAINT `fk_shoppingfeed_feed_country_id`
        FOREIGN KEY (`country_id`)
        REFERENCES `country` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,
    CONSTRAINT `fk_shoppingfeed_feed_lang_id`
        FOREIGN KEY (`lang_id`)
        REFERENCES `lang` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- shoppingfeed_order_data
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `shoppingfeed_order_data`;

CREATE TABLE `shoppingfeed_order_data`
(
    `id` INTEGER NOT NULL,
    `feed_id` INTEGER NOT NULL,
    `channel` VARCHAR(255),
    `external_reference` VARCHAR(255),
    `email` VARCHAR(255),
    PRIMARY KEY (`id`),
    INDEX `fi_ppingfeed_order_data_feed_id` (`feed_id`),
    CONSTRAINT `shoppingfeed_order_data_id`
        FOREIGN KEY (`id`)
        REFERENCES `order` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE,
    CONSTRAINT `shoppingfeed_order_data_feed_id`
        FOREIGN KEY (`feed_id`)
        REFERENCES `shoppingfeed_feed` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------
-- shoppingfeed_log
-- ---------------------------------------------------------------------

DROP TABLE IF EXISTS `shoppingfeed_log`;

CREATE TABLE `shoppingfeed_log`
(
    `id` INTEGER NOT NULL AUTO_INCREMENT,
    `feed_id` INTEGER NOT NULL,
    `separation` TINYINT(1) NOT NULL,
    `level` INTEGER NOT NULL,
    `object_id` INTEGER,
    `object_type` VARCHAR(255),
    `object_ref` VARCHAR(255),
    `message` TEXT NOT NULL,
    `help` TEXT,
    `created_at` DATETIME,
    `updated_at` DATETIME,
    PRIMARY KEY (`id`),
    INDEX `fi_ppingfeed_log_feed_id` (`feed_id`),
    CONSTRAINT `shoppingfeed_log_feed_id`
        FOREIGN KEY (`feed_id`)
        REFERENCES `shoppingfeed_feed` (`id`)
        ON UPDATE RESTRICT
        ON DELETE CASCADE
) ENGINE=InnoDB;

# This restores the fkey checks, after having unset them earlier
SET FOREIGN_KEY_CHECKS = 1;
