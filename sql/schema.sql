-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jan 30, 2026 at 06:18 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `almatech_site`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `email` varchar(190) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','staff') NOT NULL DEFAULT 'admin',
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin_users`
--

INSERT INTO `admin_users` (`id`, `name`, `email`, `password_hash`, `role`, `is_active`, `created_at`) VALUES
(1, 'Admin', 'admin@almatechconsults.com', '$2y$10$rdHJqy6oEoKNUVL5KKrmGu82hehz6eLGctoMKlsZ9W.v1eyxJnjx.', 'admin', 1, '2026-01-28 12:06:32');

-- --------------------------------------------------------

--
-- Table structure for table `leads`
--

CREATE TABLE `leads` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `phone` varchar(50) NOT NULL,
  `email` varchar(120) DEFAULT '',
  `service` varchar(120) NOT NULL,
  `subject` varchar(200) DEFAULT '',
  `message` text NOT NULL,
  `status` enum('new','in_progress','closed') NOT NULL DEFAULT 'new',
  `source` varchar(100) NOT NULL DEFAULT 'website',
  `created_at` datetime NOT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `leads`
--

INSERT INTO `leads` (`id`, `name`, `phone`, `email`, `service`, `subject`, `message`, `status`, `source`, `created_at`, `updated_at`) VALUES
(1, 'John Doe', '+256700000000', 'john@example.com', 'Website Design', 'Business Website Inquiry', 'I need a business website with an admin dashboard.', 'new', 'contact-form', '0000-00-00 00:00:00', '2026-01-28 19:34:20'),
(2, 'Ainamaani Allan Mwesigye', '+256700868939', 'allanomwesi70@gmail.com', 'IT Support & Maintenance', 'I need a website', 'Help me with a website', 'new', 'contact-form', '2026-01-28 19:51:08', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `maintenance_templates`
--

CREATE TABLE `maintenance_templates` (
  `id` int(11) NOT NULL,
  `title` varchar(190) NOT NULL,
  `slug` varchar(190) NOT NULL,
  `html` mediumtext DEFAULT NULL,
  `custom_css` mediumtext DEFAULT NULL,
  `custom_js` mediumtext DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `maintenance_templates`
--

INSERT INTO `maintenance_templates` (`id`, `title`, `slug`, `html`, `custom_css`, `custom_js`, `is_active`, `created_at`, `updated_at`) VALUES
(1, 'Closed', 'closed', 'We are closed na', '', '', 1, '2026-01-29 21:00:47', '2026-01-29 21:03:33');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `slug` varchar(255) NOT NULL,
  `content` longtext DEFAULT NULL,
  `layout_json` text DEFAULT NULL,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` text DEFAULT NULL,
  `status` enum('published','draft') NOT NULL DEFAULT 'published',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `title`, `slug`, `content`, `layout_json`, `meta_title`, `meta_description`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Home', 'home', '<h1>Welcome to Alma Tech Consults</h1><p>Your trusted technology partner in Uganda.</p>', NULL, NULL, NULL, 'published', '2026-01-29 16:01:48', '2026-01-29 16:01:48'),
(2, 'About', 'about', '<p>This is the about page </p>', '{\"container\":\"container\",\"max_width\":920,\"padding\":32}', '', '', 'published', '2026-01-29 16:01:48', '2026-01-30 10:38:04'),
(3, 'Services', 'services', '<h1>Our Services</h1><p>Discover our comprehensive technology solutions.</p>', NULL, NULL, NULL, 'published', '2026-01-29 16:01:48', '2026-01-29 16:01:48'),
(4, 'Projects', 'projects', '<h1>Our Projects</h1><p>Explore our portfolio of successful projects.</p>', NULL, NULL, NULL, 'published', '2026-01-29 16:01:48', '2026-01-29 16:01:48'),
(5, 'Blog', 'blog', '<h1>Blog</h1><p>Read our latest insights and updates.</p>', NULL, NULL, NULL, 'published', '2026-01-29 16:01:48', '2026-01-29 16:01:48'),
(6, 'Contact', 'contact', '<h1>Contact Us</h1><p>Get in touch with our team.</p>', NULL, NULL, NULL, 'published', '2026-01-29 16:01:48', '2026-01-29 16:01:48'),
(8, 'Test It', 'test12', '<!-- Hero Section -->\r\n<div class=\"mb-5\">\r\n  <h2 class=\"fw-bold mb-3\">Welcome to Alma Tech Consults</h2>\r\n  <p class=\"lead text-muted\">\r\n    We design and build digital solutions that help businesses grow, scale, and succeed.\r\n  </p>\r\n  <a href=\"#services\" class=\"btn btn-primary me-2\">Our Services</a>\r\n  <a href=\"#contact\" class=\"btn btn-outline-secondary\">Contact Us</a>\r\n</div>\r\n\r\n<hr class=\"my-5\">\r\n\r\n<!-- Two Column Section -->\r\n<div class=\"row g-4 mb-5\">\r\n  <div class=\"col-md-6\">\r\n    <h4 class=\"fw-semibold mb-3\">Who We Are</h4>\r\n    <p>\r\n      Alma Tech Consults is an ICT and digital solutions company focused on\r\n      <strong>web development</strong>, <strong>branding</strong>,\r\n      and <strong>technology consulting</strong>.\r\n    </p>\r\n    <p>\r\n      We work with startups, NGOs, SMEs, and enterprises to turn ideas into\r\n      high-quality digital products.\r\n    </p>\r\n  </div>\r\n\r\n  <div class=\"col-md-6\">\r\n    <h4 class=\"fw-semibold mb-3\">What We Do</h4>\r\n    <ul>\r\n      <li>Custom websites & web applications</li>\r\n      <li>Digital marketing & online visibility</li>\r\n      <li>Brand identity & graphic design</li>\r\n      <li>System automation & integrations</li>\r\n    </ul>\r\n  </div>\r\n</div>\r\n\r\n<hr class=\"my-5\">\r\n\r\n<!-- Feature Cards -->\r\n<div class=\"row g-4 mb-5\">\r\n  <div class=\"col-md-4\">\r\n    <div class=\"card h-100 shadow-sm\">\r\n      <div class=\"card-body\">\r\n        <h5 class=\"card-title\">🚀 Fast Delivery</h5>\r\n        <p class=\"card-text\">\r\n          We deliver projects on time using modern tools and proven workflows.\r\n        </p>\r\n      </div>\r\n    </div>\r\n  </div>\r\n\r\n  <div class=\"col-md-4\">\r\n    <div class=\"card h-100 shadow-sm\">\r\n      <div class=\"card-body\">\r\n        <h5 class=\"card-title\">🔒 Secure Systems</h5>\r\n        <p class=\"card-text\">\r\n          Security is built into everything we design — from login systems to APIs.\r\n        </p>\r\n      </div>\r\n    </div>\r\n  </div>\r\n\r\n  <div class=\"col-md-4\">\r\n    <div class=\"card h-100 shadow-sm\">\r\n      <div class=\"card-body\">\r\n        <h5 class=\"card-title\">📈 Business Growth</h5>\r\n        <p class=\"card-text\">\r\n          Our solutions focus on measurable results, visibility, and growth.\r\n        </p>\r\n      </div>\r\n    </div>\r\n  </div>\r\n</div>\r\n\r\n<hr class=\"my-5\">\r\n\r\n<!-- Image + Text -->\r\n<div class=\"row g-4 align-items-center mb-5\">\r\n  <div class=\"col-md-6\">\r\n    <img\r\n      src=\"https://images.unsplash.com/photo-1522071820081-009f0129c71c\"\r\n      class=\"img-fluid rounded shadow-sm\"\r\n      alt=\"Team collaboration\">\r\n  </div>\r\n  <div class=\"col-md-6\">\r\n    <h4 class=\"fw-semibold mb-3\">Our Approach</h4>\r\n    <p>\r\n      We combine design thinking, engineering, and strategy to build solutions\r\n      that are not only beautiful but also effective.\r\n    </p>\r\n    <p>\r\n      Every project starts with understanding your business goals and ends with\r\n      a solution that supports long-term success.\r\n    </p>\r\n  </div>\r\n</div>\r\n\r\n<hr class=\"my-5\">\r\n\r\n<!-- Call to Action -->\r\n<div class=\"text-center py-5 bg-light rounded\">\r\n  <h3 class=\"fw-bold mb-3\">Ready to Start Your Project?</h3>\r\n  <p class=\"text-muted mb-4\">\r\n    Let’s discuss how Alma Tech Consults can help you bring your idea to life.\r\n  </p>\r\n  <a href=\"/contact\" class=\"btn btn-lg btn-orange\">\r\n    Get in Touch\r\n  </a>\r\n</div>', '{\"container\":\"container-fluid\",\"max_width\":1400,\"padding\":20}', '', '', 'published', '2026-01-29 16:48:01', '2026-01-29 17:19:23');

-- --------------------------------------------------------

--
-- Table structure for table `posts`
--

CREATE TABLE `posts` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(180) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `excerpt` varchar(255) DEFAULT NULL,
  `content` mediumtext DEFAULT NULL,
  `category` varchar(80) DEFAULT NULL,
  `status` enum('published','draft','archived','trashed') NOT NULL DEFAULT 'draft',
  `cover_image` varchar(255) DEFAULT NULL,
  `tags` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tags`)),
  `published_at` datetime DEFAULT NULL,
  `is_featured` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `posts`
--

INSERT INTO `posts` (`id`, `title`, `slug`, `excerpt`, `content`, `category`, `status`, `cover_image`, `tags`, `published_at`, `is_featured`, `created_at`, `updated_at`) VALUES
(1, 'How Custom Software Can Simplify Business Operations', 'custom-software', 'Discover how tailored systems reduce manual work, improve efficiency, and give businesses better control over their processes.', 'How Custom Software Can Simplify Business Operations\r\n\r\nMany businesses rely on manual processes or generic software that does not fully match how they operate. Custom software is designed specifically around your business workflow, making daily operations easier, faster, and more efficient.\r\n\r\nDesigned Around Your Business\r\n\r\nUnlike off-the-shelf solutions, custom software is built to fit your exact needs. Every feature is tailored to how your business works, eliminating unnecessary functions and reducing complexity.\r\n\r\nImproved Efficiency and Productivity\r\n\r\nCustom systems automate repetitive tasks such as data entry, reporting, and approvals. This reduces errors, saves time, and allows staff to focus on more valuable work.\r\n\r\nBetter Data Management\r\n\r\nWith a custom system, all business data is stored in one secure place. This makes it easier to access information, generate reports, and make informed decisions based on accurate data.\r\n\r\nScalability and Flexibility\r\n\r\nAs your business grows, your software can be updated and expanded. New features, integrations, and modules can be added without disrupting existing operations.\r\n\r\nEnhanced Security and Control\r\n\r\nCustom software allows you to define access levels, permissions, and security rules that match your organization’s structure. This ensures sensitive data is protected and only accessible to authorized users.\r\n\r\nConclusion\r\n\r\nCustom software simplifies operations by aligning technology with real business processes. It improves efficiency, enhances control, and supports long-term growth, making it a smart investment for any growing business.', 'Software', 'published', '/uploads/posts/post_1_20260130_142003_4b3c3f46.jpg', NULL, '2018-08-28 17:42:00', 1, '2026-01-28 17:42:52', '2026-01-30 16:20:03'),
(3, 'Starter', 'starter-2', 'Computing', 'Computing is good', 'Computing', 'trashed', '/uploads/posts/post_20260128_162915_98e89a99.png', NULL, '2026-01-28 08:01:00', 0, '2026-01-28 18:29:15', '2026-01-30 16:10:53'),
(4, 'Why Every Business Needs a Professional Website in 2025', 'why-every-business-needs-a-professional-website-in-2025', 'Learn how a well-designed website builds trust, attracts customers, and increases sales in today’s digital-first world.', 'In today’s digital world, a business without a professional website is often overlooked. Customers expect to find you online, learn about your services, and trust your brand before making contact. In 2025, a website is no longer optional — it is a core business tool.\r\n\r\nFirst Impressions Matter\r\n\r\nYour website is usually the first interaction a potential customer has with your business. A clean, well-structured, and modern website immediately builds trust. On the other hand, a slow or outdated site can cause visitors to leave within seconds.\r\n\r\nVisibility and Credibility\r\n\r\nA professional website allows your business to be visible 24/7. Customers can find your services, contact details, and portfolio at any time. Search engines like Google prioritize well-structured websites, making it easier for people to discover your business online.\r\n\r\nBetter Customer Engagement\r\n\r\nWebsites provide a platform to explain your services clearly, showcase your work, and answer common questions. Features such as contact forms, live chat, and call-to-action buttons make it easy for customers to reach you.\r\n\r\nCost-Effective Marketing\r\n\r\nUnlike traditional advertising, a website works continuously without recurring costs. It supports digital marketing efforts such as social media campaigns, search engine optimization (SEO), and email marketing, giving you better value for money.\r\n\r\nSupports Business Growth\r\n\r\nAs your business grows, your website can grow with you. You can add new services, integrate online payments, automate bookings, or connect custom systems that improve efficiency and customer experience.\r\n\r\nConclusion\r\n\r\nA professional website is an investment in your business’s future. It builds trust, increases visibility, and supports growth in an increasingly digital marketplace. In 2025, businesses that take their online presence seriously will always have an advantage.', 'Business', 'published', '/uploads/posts/post_20260130_141411_9c567c6b.jpg', NULL, '2016-02-12 10:11:00', 1, '2026-01-30 16:14:11', '2026-01-30 16:23:40'),
(5, 'Branding Matters: How Visual Identity Affects Customer Trust', 'branding-matters', 'Explore the role of logos, colors, and design consistency in shaping how customers perceive your brand.', 'Branding Matters: How Visual Identity Affects Customer Trust\r\n\r\nBranding is more than just a logo or color choice. It is how your business presents itself to the world and how customers perceive your professionalism, reliability, and credibility. A strong visual identity plays a major role in building customer trust.\r\n\r\nFirst Impressions Shape Perception\r\n\r\nCustomers often judge a business within seconds of seeing its branding. Consistent colors, clean layouts, and professional design signal that a business is serious and trustworthy. Poor or inconsistent branding can create doubt, even if the services are high quality.\r\n\r\nConsistency Builds Recognition\r\n\r\nWhen your branding is consistent across your website, social media, and marketing materials, customers begin to recognize and remember your business. Familiarity increases confidence and makes your brand easier to trust over time.\r\n\r\nVisual Identity Communicates Values\r\n\r\nDesign choices such as typography, colors, and imagery communicate your brand’s personality and values. Whether your business aims to appear modern, reliable, creative, or professional, visual identity helps deliver that message clearly.\r\n\r\nProfessional Design Increases Credibility\r\n\r\nWell-designed branding shows attention to detail. Customers are more likely to trust a business that invests in professional visuals than one that appears unpolished or outdated.\r\n\r\nBranding Supports Marketing and Growth\r\n\r\nStrong branding makes marketing more effective. Advertisements, social posts, and campaigns perform better when supported by a recognizable and consistent brand identity.\r\n\r\nConclusion\r\n\r\nVisual identity is a powerful trust-building tool. Businesses that invest in professional branding create stronger connections with customers, improve recognition, and position themselves for long-term success.', 'Branding', 'published', '/uploads/posts/post_20260130_142300_389c07a1.jpg', NULL, '2019-03-30 13:13:00', 1, '2026-01-30 16:23:00', NULL),
(6, 'The Importance of System Security for Modern Businesses', 'system-security', 'A practical guide on why data protection, access control, and system security should be a priority for every organization.', 'As businesses increasingly rely on digital systems, protecting data and operations has become more important than ever. System security is no longer just an IT concern — it is a core business requirement.\r\n\r\nProtecting Business and Customer Data\r\n\r\nBusinesses handle sensitive information such as customer details, financial records, and internal documents. Strong system security helps prevent unauthorized access, data leaks, and loss of critical information.\r\n\r\nPreventing Operational Disruptions\r\n\r\nCyber threats, system breaches, and unauthorized access can disrupt daily operations. Secure systems reduce downtime and ensure business processes continue smoothly without unexpected interruptions.\r\n\r\nAccess Control and Accountability\r\n\r\nModern systems allow businesses to define user roles and permissions. This ensures employees only access what they need, reducing the risk of internal misuse and improving accountability through activity logs and audit trails.\r\n\r\nBuilding Customer Trust\r\n\r\nCustomers are more likely to trust businesses that take data protection seriously. Secure platforms reassure clients that their information is safe, strengthening long-term relationships and brand reputation.\r\n\r\nCompliance and Legal Protection\r\n\r\nMany industries require businesses to follow data protection and security standards. Implementing proper security measures helps organizations remain compliant and avoid legal or financial penalties.\r\n\r\nConclusion\r\n\r\nSystem security protects more than just data — it protects business continuity, reputation, and customer trust. Investing in secure systems is essential for any business operating in a digital environment.', 'Security', 'published', '/uploads/posts/post_20260130_142552_6fde0f83.png', NULL, '2021-05-30 16:24:00', 1, '2026-01-30 16:25:52', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `projects`
--

CREATE TABLE `projects` (
  `id` int(10) UNSIGNED NOT NULL,
  `title` varchar(150) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `short_desc` varchar(255) NOT NULL,
  `full_desc` text DEFAULT NULL,
  `category` varchar(80) DEFAULT NULL,
  `status` enum('completed','ongoing','paused','draft') NOT NULL DEFAULT 'draft',
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `cover_image` varchar(255) DEFAULT NULL,
  `tech_stack` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`tech_stack`)),
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `projects`
--

INSERT INTO `projects` (`id`, `title`, `slug`, `short_desc`, `full_desc`, `category`, `status`, `is_featured`, `cover_image`, `tech_stack`, `created_at`, `updated_at`) VALUES
(9, 'Sauna Manager', 'sauna-manager', 'Sauna & Facility Management', 'Sauna Manager is a custom-built management system designed to streamline the day-to-day operations of sauna and wellness facilities. The system centralizes customer sessions, payments, staff activity, and operational records into one secure and easy-to-use platform.', 'ERP', 'completed', 1, '/uploads/projects/project_20260130_140902_9f06071c.jpg', '[\"PHP\",\"SQL\",\"JS\",\"CSS\",\"BOOTSTRAP\"]', '2026-01-30 16:09:02', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `project_images`
--

CREATE TABLE `project_images` (
  `id` int(10) UNSIGNED NOT NULL,
  `project_id` int(10) UNSIGNED NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `services`
--

CREATE TABLE `services` (
  `id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `short_desc` varchar(300) NOT NULL,
  `description` longtext NOT NULL,
  `icon` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `services`
--

INSERT INTO `services` (`id`, `title`, `slug`, `short_desc`, `description`, `icon`, `is_active`, `created_at`) VALUES
(2, 'Quick Website Design', 'quick-website-design', 'Fast, professional website design delivered in 7 days or less', 'We design modern, responsive, and conversion-focused websites tailored to your business goals. Every page is crafted to attract visitors, build trust, and turn potential customers into real leads. Our quick website design service delivers a complete, professional website within 7 days or less, ideal for startups and growing businesses. Packages start from $119 and include mobile responsiveness, basic SEO setup, and performance optimization. We also provide online radio hosting and audio advertising through our Alma-Flix Radio platform.', 'service_697cb7dd0ca7f3.93844090.png', 1, '2026-01-29 12:57:27'),
(3, 'Database Management', 'database-management', 'Reliable database management and support for cloud and on-premise systems', 'Our database management services provide 24×7 access to experienced DBAs, system architects, and engineers. We handle performance tuning, backups, security, monitoring, and optimization for both cloud-based and on-premise databases. This ensures your systems remain fast, secure, and available at all times while reducing downtime and operational risks.', 'service_697cb7ad8bf861.06967100.png', 1, '2026-01-29 12:57:27'),
(4, 'Product Sales', 'product-sales', 'Computers, laptops, accessories, software, and technical support', 'We supply quality desktop computers, laptops, accessories, and licensed software for individuals and businesses. Our services include hardware upgrades, system maintenance, installations, and ongoing technical support. Customers can visit our physical shop at GBK Plaza Mbarara (Shop B.9) for professional assistance and reliable ICT products backed by expert support.', 'service_697cb75f5adb00.83195729.png', 1, '2026-01-29 12:57:27'),
(5, 'ICT Support', 'ict-support', 'Professional ICT support, troubleshooting, and community learning', 'We offer reliable ICT support services designed to help individuals and organizations solve technical challenges efficiently. Our support model includes direct assistance, system troubleshooting, maintenance, and access to a collaborative learning forum where users can share knowledge, ask questions, and grow their technical skills. Support is not limited by time or issue complexity.', 'service_697cb6d84551c8.52609358.png', 1, '2026-01-29 12:57:27'),
(6, 'Social Media Marketing', 'social-media-marketing', 'Grow your brand visibility and engagement across social media platforms', 'Our social media marketing services help businesses increase visibility, engagement, and traffic through platforms such as Facebook, Instagram, Twitter, and more. We create targeted campaigns, branded content, and growth strategies aligned with your business objectives. We also provide affordable bulk SMS services to help businesses reach customers directly and effectively.', 'service_697cb689b20829.26878255.png', 1, '2026-01-29 12:57:27'),
(7, 'Graphics Design', 'graphics-design', 'Creative graphic design that builds strong and recognizable brands', 'We provide professional graphic design services ranging from logo creation and brand identity to advertising materials, promotional designs, and visual content. Our designs are crafted to communicate your brand message clearly, enhance credibility, and create a lasting impression. Strong visual identity helps your business stand out and remain memorable in competitive markets.', 'service_697cb5fa860062.75651559.png', 1, '2026-01-29 12:57:27');

-- --------------------------------------------------------

--
-- Table structure for table `settings`
--

CREATE TABLE `settings` (
  `id` int(11) NOT NULL,
  `key` varchar(120) NOT NULL,
  `value` longtext DEFAULT NULL,
  `updated_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `settings`
--

INSERT INTO `settings` (`id`, `key`, `value`, `updated_at`) VALUES
(1, 'contact_location', 'Mbarara, Uganda', NULL),
(2, 'contact_email', 'almatechuganda@gmail.com', '2026-01-30 15:01:47'),
(3, 'contact_phone', '+256 783 016 411', '2026-01-30 15:01:47'),
(4, 'contact_whatsapp', '256700868939', '2026-01-30 15:01:47'),
(5, 'contact_map_embed', '<iframe src=\"https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3989.5942719165814!2d30.6605708!3d-0.6072031999999999!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x19d91b9abec5e7a5%3A0x2005bfb03e791583!2sAlma%20Tech%20Consults%20Uganda%20Limited!5e0!3m2!1sen!2sug!4v1769770987077!5m2!1sen!2sug\" width=\"400\" height=\"300\" style=\"border:0;\" allowfullscreen=\"\" loading=\"lazy\" referrerpolicy=\"no-referrer-when-downgrade\"></iframe>', '2026-01-30 15:01:47'),
(6, 'company_name', 'Alma Tech Consults', '2026-01-30 15:01:47'),
(7, 'company_motto', 'Positive Impact of Technology', '2026-01-30 15:01:47'),
(8, 'company_address', 'GBK Plaza Shop B09, Mckanisingh Street/Ntare Rd', '2026-01-30 15:01:47'),
(9, 'company_working_hours', 'Mon-Sat | 8:30AM - 7:00PM, Sun 1:00PM - 5:00PM', '2026-01-30 15:01:47'),
(10, 'social_links', '{\"facebook\":\"facebook.com/almatechuganda\",\"instagram\":\"instagram.com/almatechuganda\",\"twitter\":\"x.com/almatechuganda\",\"linkedin\":\"linkedin.com/\",\"youtube\":\"youtube.com/\",\"tiktok\":\"tiktok.com/\"}', '2026-01-30 15:01:47'),
(11, 'brand_logo', 'assets/brand/logo_20260130_122950_05d3fc17.png', '2026-01-30 15:01:47'),
(12, 'brand_favicon', 'assets/brand/favicon_20260130_123050_b00f4105.png', '2026-01-30 15:01:47'),
(13, 'brand_primary_color', '#ff7a18', '2026-01-30 15:01:47'),
(14, 'brand_secondary_color', '#0b1220', '2026-01-30 15:01:47'),
(15, 'brand_accent_color', '#f3f4f6', '2026-01-30 15:01:47'),
(16, 'footer_note', 'Crafted with ❤️ By Allan. <a href=\"https://wa.link/fskd5l\">Rate Me!</a> 🌟', '2026-01-30 15:01:47'),
(17, 'visible_links', '{\"home\":true,\"about\":true,\"services\":true,\"projects\":true,\"blog\":true,\"contact\":true}', '2026-01-30 15:01:47'),
(18, 'under_construction', '0', NULL),
(19, 'home_settings', '{\"hero\":{\"badge_icon\":\"\",\"badge_text\":\"\",\"title\":\"Get Found Online. Get More Customers.\",\"subtitle\":\"Web development, software systems, branding, and digital strategy — designed for real businesses, real users, and real results.\",\"btn1_text\":\"Get Started\",\"btn1_link\":\"services.php\",\"btn2_text\":\"Talk to Our Team\",\"btn2_link\":\"contact.php\"},\"slider\":{\"enabled\":true,\"interval\":5000,\"transition\":\"fade\",\"min_height\":420,\"overlay_enabled\":true,\"overlay_opacity\":1,\"overlay_gradient\":\"linear-gradient(120deg, rgba(0,0,0,.65), rgba(0,0,0,.15))\",\"show_indicators\":true,\"show_arrows\":false,\"slides\":[{\"is_active\":true,\"sort_order\":1,\"image\":\"slide_20260130_110226_bf6792e9.jpg\",\"alt\":\"Smart Websites & Custom Systems\",\"caption_title\":\"Smart Websites & Custom Systems\",\"caption_text\":\"We design and build fast, secure, and scalable websites and software tailored to your business needs — from simple websites to complex enterprise systems.\",\"btn1_text\":\"View Our Work\",\"btn1_link\":\"projects.php\",\"btn2_text\":\"\",\"btn2_link\":\"\",\"caption_align\":\"left\"},{\"is_active\":true,\"sort_order\":2,\"image\":\"slide_20260130_105845_dca78bfd.jpg\",\"alt\":\"Automate. Optimize. Scale.\",\"caption_title\":\"Automate. Optimize. Scale.\",\"caption_text\":\"We help businesses save time and money through automation, digital tools, and smart integrations that improve efficiency and decision-making.\",\"btn1_text\":\"Explore Solutions\",\"btn1_link\":\"services.php\",\"btn2_text\":\"Get a quote\",\"btn2_link\":\"contact.php\",\"caption_align\":\"right\"},{\"is_active\":true,\"sort_order\":3,\"image\":\"uploads/slider/slide_20260130_131812_98fd971e.jpg\",\"alt\":\"Build a Brand People Trust\",\"caption_title\":\"Build a Brand People Trust\",\"caption_text\":\"Professional branding, logos, graphics, and UI/UX designs that communicate credibility, clarity, and confidence across all platforms.\",\"btn1_text\":\"Find out more about us\",\"btn1_link\":\"about.php\",\"btn2_text\":\"\",\"btn2_link\":\"\",\"caption_align\":\"center\"},{\"is_active\":true,\"sort_order\":4,\"image\":\"slide_20260130_131812_98fd971e.jpg\",\"alt\":\"Support & Partnerships\",\"caption_title\":\"Support & Partnerships\",\"caption_text\":\"We don’t just deliver projects — we provide ongoing support, improvements, and guidance as your business grows.\",\"btn1_text\":\"\",\"btn1_link\":\"\",\"btn2_text\":\"Work With Us\",\"btn2_link\":\"contact.php\",\"caption_align\":\"center\"}]},\"quick_request\":{\"title\":\"Need Help or Advice?\",\"subtitle\":\"Talk to our experts for guidance, consultation, or technical support — no pressure, just solutions.\",\"consent_text\":\"\",\"services\":[\"Website Design & Development\",\"Custom Software Development\",\"Mobile App Development\",\"Digital Marketing & Online Growth\",\"Branding & Graphic Design\",\"System Automation & Integration\",\"IT Consultancy & Technical Support\",\"Hosting, Domains & System Deployment\",\"Other\"],\"enabled\":false,\"btn_text\":\"\",\"btn_link\":\"\"},\"services_preview\":{\"title\":\"Services that we are ready to offer\",\"subtitle\":\"These and more, of course!\",\"view_all_text\":\"View All\",\"view_all_link\":\"services.php\",\"cards\":[{\"icon\":\"\",\"title\":\"\",\"desc\":\"\",\"btn_text\":\"\",\"btn_link\":\"\"},{\"icon\":\"\",\"title\":\"\",\"desc\":\"\",\"btn_text\":\"\",\"btn_link\":\"\"},{\"icon\":\"\",\"title\":\"\",\"desc\":\"\",\"btn_text\":\"\",\"btn_link\":\"\"},{\"icon\":\"\",\"title\":\"\",\"desc\":\"\",\"btn_text\":\"\",\"btn_link\":\"\"}],\"enabled\":false},\"stats\":[{\"value\":\"142\",\"label\":\"Projects Successfully Delivered\",\"icon\":\"bi-graph-up\"},{\"value\":\"347\",\"label\":\"Happy Clients & Partners\",\"icon\":\"bi-graph-up\"},{\"value\":\"12+\",\"label\":\"Years of Hands-On Experience\",\"icon\":\"bi-graph-up\"},{\"value\":\"24/7\",\"label\":\"Technical Support Availability\",\"icon\":\"bi-graph-up\"}],\"cta\":{\"title\":\"Not Sure Where to Start?\",\"subtitle\":\"Talk to our experts and get honest advice, clear direction, and the right solution for your business.\",\"btn_text\":\"Consult our experts\",\"btn_link\":\"contact.php\",\"enabled\":false}}', '2026-01-30 16:36:33'),
(24, 'maintenance_settings', '{\"enabled\":false,\"message\":\"\",\"custom_html\":\"\",\"custom_css\":\"\",\"custom_js\":\"\",\"image_path\":\"uploads/maintenance/maint_20260129_193328_e5ffdc3e.jpg\",\"image_mode\":\"cover\",\"template_id\":null}', '2026-01-30 15:01:47'),
(189, 'about_settings', '{\"hero_title\":\"About Alma Tech Consults\",\"hero_subtitle\":\"We help businesses build, fix, and scale with technology.\",\"story\":\"Alma Tech is more of a verb than a noun. Since 2015 we have provided top-tier ICT services to communities, financial\\/manufacturing firms and other organizations.\\r\\n\\\"We deliver double your expectation!\\\" \\r\\n\\r\\n~ CEO\",\"mission\":\"To provide dependable ICT and digital services that help organizations operate efficiently, look professional online, and grow through technology.\",\"vision\":\"To be a leading technology partner for businesses across Uganda and the globe delivering systems, websites, and support that last.\",\"values\":\"Integrity & Transparency:We believe in honest communication, clear processes, and doing what’s right — with our clients, partners, and team.:bi-briefcase-fill\\r\\nQuality & Excellence:We focus on delivering reliable, secure, and high-performing solutions that meet real business needs and long-term goals.:bi-star-fill\\r\\nInnovation with Purpose:We use technology thoughtfully — not for hype, but to solve real problems and create meaningful impact.:bi-gem\"}', '2026-01-30 15:52:58'),
(428, 'nav_order', '[\"home\",\"about\",\"services\",\"projects\",\"blog\",\"team\",\"testimonials\",\"contact\",\"test12\"]', '2026-01-30 15:01:47');

-- --------------------------------------------------------

--
-- Table structure for table `team_members`
--

CREATE TABLE `team_members` (
  `id` int(11) NOT NULL,
  `name` varchar(120) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `role` varchar(120) NOT NULL,
  `short_desc` varchar(255) DEFAULT NULL,
  `bio` longtext DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `phone` varchar(50) DEFAULT NULL,
  `linkedin` varchar(255) DEFAULT NULL,
  `github` varchar(255) DEFAULT NULL,
  `website` varchar(255) DEFAULT NULL,
  `skills` text DEFAULT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `status` enum('active','hidden') NOT NULL DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `team_members`
--

INSERT INTO `team_members` (`id`, `name`, `slug`, `role`, `short_desc`, `bio`, `photo`, `email`, `phone`, `linkedin`, `github`, `website`, `skills`, `sort_order`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Ainamaani Allan Mwesigye', 'ainamaani-allan-mwesigye', 'CEO', 'Software Engineer', 'Coming Soon', '20260129_150028_30fd2b0c74b9.png', NULL, NULL, '', '', '', 'PHP, Sql', 0, 'active', '2026-01-29 13:35:47', '2026-01-29 14:00:28');

-- --------------------------------------------------------

--
-- Table structure for table `testimonials`
--

CREATE TABLE `testimonials` (
  `id` int(11) NOT NULL,
  `client_name` varchar(120) NOT NULL,
  `client_title` varchar(120) DEFAULT NULL,
  `company` varchar(160) DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `message` text NOT NULL,
  `rating` tinyint(4) DEFAULT NULL,
  `is_featured` tinyint(1) NOT NULL DEFAULT 0,
  `status` enum('published','draft') NOT NULL DEFAULT 'published',
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `project_id` int(11) DEFAULT NULL,
  `show_project_link` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `testimonials`
--

INSERT INTO `testimonials` (`id`, `client_name`, `client_title`, `company`, `photo`, `message`, `rating`, `is_featured`, `status`, `sort_order`, `project_id`, `show_project_link`, `created_at`, `updated_at`) VALUES
(3, 'Mwesigye N', 'Director', 'Nelux Gardens', '20260130_140322_a9ce5be0.jpeg', 'The management systems implemented for our sauna, CCTV, and general operations have greatly improved efficiency and security. Everything is now well organized, monitored, and easy to manage.', 5, 0, 'published', 0, 9, 1, '2026-01-30 13:03:22', '2026-01-30 13:09:23'),
(4, 'Ampumuza B', 'Director', 'Beta Blend Beverages', '20260130_140413_aa97dbb8.jpeg', 'The graphics designed for Beta Blend Juice completely transformed our brand. From labels to promotional designs, everything looks clean, fresh, and professional. Our customers now recognize the brand instantly.', 5, 0, 'published', 0, NULL, 0, '2026-01-30 13:04:13', '2026-01-30 13:04:13');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `leads`
--
ALTER TABLE `leads`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_leads_status` (`status`),
  ADD KEY `idx_leads_service` (`service`),
  ADD KEY `idx_leads_created` (`created_at`),
  ADD KEY `idx_leads_email` (`email`),
  ADD KEY `idx_leads_phone` (`phone`);

--
-- Indexes for table `maintenance_templates`
--
ALTER TABLE `maintenance_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `posts`
--
ALTER TABLE `posts`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_posts_slug` (`slug`),
  ADD KEY `idx_posts_status` (`status`),
  ADD KEY `idx_posts_category` (`category`),
  ADD KEY `idx_posts_published` (`published_at`),
  ADD KEY `idx_is_featured` (`is_featured`);

--
-- Indexes for table `projects`
--
ALTER TABLE `projects`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_status` (`status`),
  ADD KEY `idx_category` (`category`),
  ADD KEY `idx_featured` (`is_featured`);

--
-- Indexes for table `project_images`
--
ALTER TABLE `project_images`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project` (`project_id`);

--
-- Indexes for table `services`
--
ALTER TABLE `services`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `settings`
--
ALTER TABLE `settings`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `setting_key` (`key`),
  ADD UNIQUE KEY `key` (`key`);

--
-- Indexes for table `team_members`
--
ALTER TABLE `team_members`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_team_status` (`status`),
  ADD KEY `idx_team_sort` (`sort_order`);

--
-- Indexes for table `testimonials`
--
ALTER TABLE `testimonials`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_project_id` (`project_id`),
  ADD KEY `idx_test_status` (`status`),
  ADD KEY `idx_test_sort` (`sort_order`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `leads`
--
ALTER TABLE `leads`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `maintenance_templates`
--
ALTER TABLE `maintenance_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `posts`
--
ALTER TABLE `posts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `projects`
--
ALTER TABLE `projects`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT for table `project_images`
--
ALTER TABLE `project_images`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `services`
--
ALTER TABLE `services`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT for table `settings`
--
ALTER TABLE `settings`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1929;

--
-- AUTO_INCREMENT for table `team_members`
--
ALTER TABLE `team_members`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `testimonials`
--
ALTER TABLE `testimonials`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `project_images`
--
ALTER TABLE `project_images`
  ADD CONSTRAINT `fk_project_images_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
