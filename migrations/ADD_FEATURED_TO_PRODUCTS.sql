ALTER TABLE products
ADD COLUMN featured TINYINT(1) NOT NULL DEFAULT 0 AFTER archived;

CREATE INDEX idx_products_featured ON products(featured);
CREATE INDEX idx_products_featured_archived_parent ON products(featured, archived, parent_product_id);
