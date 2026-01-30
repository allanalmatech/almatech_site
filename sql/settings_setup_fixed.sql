-- Create settings table with proper column naming
CREATE TABLE IF NOT EXISTS settings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  `key` VARCHAR(100) NOT NULL UNIQUE,
  `value` LONGTEXT NULL,
  updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Optional seed data
INSERT INTO settings (`key`,`value`)
VALUES ('about_settings', JSON_OBJECT(
  'hero_title','About Alma Tech Consults',
  'hero_subtitle','We deliver ICT and digital solutions for business growth.',
  'story_title','Our Story',
  'story_body','Write your story here...',
  'mission','Our mission...',
  'vision','Our vision...',
  'values', JSON_ARRAY('Quality','Speed','Support')
))
ON DUPLICATE KEY UPDATE `value` = `value`;

-- Additional seed data for other sections
INSERT INTO settings (`key`,`value`)
VALUES 
('company_name', 'Alma Tech Consults'),
('company_motto', 'ICT solutions, web development, branding, and digital growth services in Uganda.'),
('company_address', 'Mbarara, Uganda'),
('contact_email', 'info@almatechconsults.com'),
('contact_phone', '+256 XXX XXX XXX'),
('footer_note', 'Built with <span class="text-orange">❤</span> in Uganda.')
ON DUPLICATE KEY UPDATE `value` = `value`;

-- Social links settings
INSERT INTO settings (`key`,`value`)
VALUES ('social_links', JSON_OBJECT(
  'facebook', 'https://facebook.com/almatechconsults',
  'instagram', 'https://instagram.com/almatechconsults',
  'twitter', 'https://twitter.com/almatechconsults',
  'linkedin', 'https://linkedin.com/company/almatechconsults',
  'youtube', 'https://youtube.com/almatechconsults',
  'tiktok', ''
))
ON DUPLICATE KEY UPDATE `value` = `value`;

-- Home page settings
INSERT INTO settings (`key`,`value`)
VALUES ('home_settings', JSON_OBJECT(
  'hero', JSON_OBJECT(
    'title', 'We build websites, brands, and systems that grow your business.',
    'subtitle', 'Professional ICT solutions for modern businesses in Uganda.',
    'cta_text', 'Get Started',
    'cta_link', 'contact.php'
  ),
  'quick_request', JSON_OBJECT(
    'title', 'Quick Request',
    'subtitle', 'Get a quote within 24 hours',
    'submit_text', 'Send Request'
  ),
  'services_preview', JSON_OBJECT(
    'title', 'Our Services',
    'subtitle', 'Comprehensive solutions for your business',
    'cards', JSON_ARRAY(
      JSON_OBJECT('title', 'Web Design', 'icon', 'bi bi-globe', 'desc', 'Modern, responsive websites'),
      JSON_OBJECT('title', 'Branding', 'icon', 'bi bi-palette', 'desc', 'Professional brand identity'),
      JSON_OBJECT('title', 'IT Support', 'icon', 'bi bi-headset', 'desc', '24/7 technical support'),
      JSON_OBJECT('title', 'Digital Marketing', 'icon', 'bi bi-megaphone', 'desc', 'Grow your online presence')
    )
  ),
  'stats', JSON_OBJECT(
    'title', 'Our Impact',
    'subtitle', 'Numbers that speak for themselves',
    'items', JSON_ARRAY(
      JSON_OBJECT('number', '150+', 'label', 'Projects Completed'),
      JSON_OBJECT('number', '98%', 'label', 'Client Satisfaction'),
      JSON_OBJECT('number', '50+', 'label', 'Happy Clients'),
      JSON_OBJECT('number', '5+', 'label', 'Years Experience')
    )
  ),
  'cta', JSON_OBJECT(
    'title', 'Ready to Grow Your Business?',
    'subtitle', 'Let\'s discuss how we can help you achieve your goals.',
    'button_text', 'Get Started Today',
    'button_link', 'contact.php'
  )
))
ON DUPLICATE KEY UPDATE `value` = `value`;
