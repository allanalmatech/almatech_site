-- Seed about settings for Alma Tech Consults
-- Run this SQL in your database (phpMyAdmin, MySQL CLI, etc.)

INSERT INTO settings (`key`, `value`) VALUES 
('about_settings', '{"hero_title":"About Alma Tech Consults","hero_subtitle":"We help businesses build, fix, and scale with technology.","story":"We deliver modern websites, branding, ICT support, and digital growth strategies that are practical for real businesses in Uganda. Our team combines technical expertise with business understanding to deliver solutions that actually work.","mission":"To provide dependable ICT and digital services that help organizations operate efficiently, look professional online, and grow through technology.","vision":"To be a leading technology partner for businesses across Uganda and the region\u2014delivering systems, websites, and support that last.","values":"Integrity:We maintain honesty and transparency in all our dealings:bi-shield-check\\nQuality:We deliver excellence in every project we undertake:bi-star-fill\\nSpeed:We respond quickly and deliver on time, every time:bi-lightning-fill\\nSupport:We provide reliable assistance whenever you need us:bi-headset"}')
ON DUPLICATE KEY UPDATE `value` = VALUES(`value`);

-- Verify the insert
SELECT `key`, LEFT(`value`, 50) as 'preview' FROM settings WHERE `key` = 'about_settings';
