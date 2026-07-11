CREATE TABLE IF NOT EXISTS feedback_messages (
    feedback_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    category VARCHAR(50) NOT NULL DEFAULT 'general',
    message TEXT NOT NULL,
    source_page VARCHAR(100) NOT NULL DEFAULT 'index.php',
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_feedback_created_at (created_at),
    INDEX idx_feedback_category (category)
);
