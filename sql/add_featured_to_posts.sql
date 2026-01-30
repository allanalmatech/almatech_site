-- Add is_featured column to posts table
ALTER TABLE posts ADD COLUMN is_featured INT NOT NULL DEFAULT 0 AFTER published_at;

-- Add index for better performance on featured queries
ALTER TABLE posts ADD INDEX idx_is_featured (is_featured);

-- Optional: Set some existing posts as featured (example)
-- UPDATE posts SET is_featured = 1 WHERE id IN (1, 2, 3) LIMIT 3;
