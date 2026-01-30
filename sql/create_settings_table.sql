-- Create settings table if it doesn't exist
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(255) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert some default home settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('home_settings', '{"hero":{"badge_icon":"bi-lightning-charge-fill","badge_text":"Fast, reliable ICT & digital solutions","title":"We build websites, brands, and systems that grow your business.","subtitle":"From web design and digital marketing to IT support and connectivity — we help you look professional online and perform better offline.","btn1_text":"Explore Services","btn1_link":"services.php","btn2_text":"View Our Work","btn2_link":"projects.php"},"quick_request":{"title":"Quick Request","subtitle":"Get a quote fast","services":["Web Design","IT Support","Digital Marketing","Branding"],"consent_text":"I agree to be contacted"},"services_preview":{"title":"Our Services","subtitle":"What we offer","view_all_text":"View All Services","view_all_link":"services.php","cards":[{"icon":"bi-globe","title":"Web Design","desc":"Professional websites that convert visitors into customers"},{"icon":"bi-shield-check","title":"IT Support","desc":"24/7 technical support and maintenance"},{"icon":"bi-megaphone","title":"Digital Marketing","desc":"SEO, social media, and online advertising"},{"icon":"bi-phone","title":"Consulting","desc":"Business technology consulting"}]},"stats":[{"value":"100+","label":"Projects Completed"},{"value":"50+","label":"Happy Clients"},{"value":"5+","label":"Years Experience"},{"value":"24/7","label":"Support Available"}],"cta":{"title":"Ready to grow your business?","subtitle":"Let\'s discuss how we can help you achieve your goals","btn_text":"Get Started","btn_link":"contact.php"}}')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Insert other common settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('company_name', 'Alma Tech Consults'),
('company_motto', 'Smart Digital Solutions'),
('company_address', 'Mbarara, Uganda'),
('company_working_hours', 'Mon-Fri: 9AM-6PM'),
('contact_email', 'info@almatechconsults.com'),
('contact_phone', '+256 XXX XXX XXX'),
('contact_whatsapp', '256XXXXXXXXX'),
('contact_map_embed', ''),
('brand_logo', 'assets/img/logo.png'),
('brand_favicon', 'assets/img/favicon.png'),
('brand_primary_color', '#ff7a18'),
('brand_secondary_color', '#0b1220'),
('brand_accent_color', '#f3f4f6'),
('footer_note', '&copy; 2026 Alma Tech Consults. All rights reserved.'),
('under_construction', '0'),
('social_links', '{"facebook":"","instagram":"","twitter":"","linkedin":"","youtube":"","tiktok":""}'),
('visible_links', '{"services":true,"projects":true,"blog":true,"about":true,"contact":true}')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);
