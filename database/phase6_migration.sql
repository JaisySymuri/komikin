-- KOMIKIN Phase 6 migration
-- Adds a return ledger so customers can return part of a multi-copy rental line.
-- Run this once in phpMyAdmin against the existing rental_komik database.

CREATE TABLE IF NOT EXISTS rental_item_returns (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    rental_detail_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    returned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    late_fee DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_returns_detail (rental_detail_id),
    CONSTRAINT fk_returns_rental_detail
        FOREIGN KEY (rental_detail_id) REFERENCES rental_details(id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
