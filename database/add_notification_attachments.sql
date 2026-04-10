-- Add attachment support to notifications table
ALTER TABLE notifications 
ADD COLUMN attachment_path VARCHAR(500) NULL COMMENT 'Path to attached PDF or file' AFTER message;

-- Create index for better attachment queries
CREATE INDEX idx_attachment_path ON notifications (attachment_path);
