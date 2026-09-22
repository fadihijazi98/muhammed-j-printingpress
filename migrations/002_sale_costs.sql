/*
 * Costs can now be attached to the sale that consumed them, so each sale shows
 * its own net profit. The column stays nullable: a purchase made for the shop
 * in general still belongs to no particular sale.
 */

ALTER TABLE material_purchases ADD COLUMN sale_id INTEGER REFERENCES sales(id);
ALTER TABLE worker_payments    ADD COLUMN sale_id INTEGER REFERENCES sales(id);

CREATE INDEX idx_material_purchases_sale ON material_purchases (sale_id);
CREATE INDEX idx_worker_payments_sale    ON worker_payments (sale_id);
