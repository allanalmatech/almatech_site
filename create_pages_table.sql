-- Create pages table for dynamic page management
CREATE TABLE IF NOT EXISTS `pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `status` enum('published','draft') NOT NULL DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert core pages
INSERT INTO `pages` (`title`, `slug`, `content`, `status`) VALUES
('Home', 'home', '<h1>Welcome to Alma Tech Consults</h1><p>Your trusted technology partner in Uganda.</p>', 'published'),
('About', 'about', '<h1>About Us</h1><p>Learn more about our company and values.</p>', 'published'),
('Services', 'services', '<h1>Our Services</h1><p>Discover our comprehensive technology solutions.</p>', 'published'),
('Projects', 'projects', '<h1>Our Projects</h1><p>Explore our portfolio of successful projects.</p>', 'published'),
('Blog', 'blog', '<h1>Blog</h1><p>Read our latest insights and updates.</p>', 'published'),
('Contact', 'contact', '<h1>Contact Us</h1><p>Get in touch with our team.</p>', 'published')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`), `content` = VALUES(`content`), `status` = VALUES(`status`);

-- Show the created table structure
DESCRIBE `pages`;
