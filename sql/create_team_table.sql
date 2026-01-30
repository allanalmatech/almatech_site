-- Create team_members table
CREATE TABLE IF NOT EXISTS team_members (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  slug VARCHAR(255) NOT NULL UNIQUE,
  role VARCHAR(255) NOT NULL,
  short_desc TEXT NULL,
  bio TEXT NULL,
  photo VARCHAR(255) NULL,
  skills VARCHAR(500) NULL,
  linkedin VARCHAR(500) NULL,
  github VARCHAR(500) NULL,
  website VARCHAR(500) NULL,
  status ENUM('active', 'inactive') DEFAULT 'active',
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  INDEX idx_status (status),
  INDEX idx_sort (sort_order),
  INDEX idx_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: Insert sample team member
INSERT INTO team_members (name, slug, role, short_desc, bio, status, sort_order) VALUES 
('John Doe', 'john-doe', 'Lead Developer', 'Full-stack developer with 5+ years experience', 'John is an experienced developer who specializes in building scalable web applications. He has expertise in PHP, JavaScript, and modern web frameworks.', 'active', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);
