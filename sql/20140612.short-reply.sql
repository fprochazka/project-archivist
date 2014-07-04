ALTER TABLE forum_posts ADD parent_post_id INT DEFAULT NULL;
ALTER TABLE forum_posts ADD CONSTRAINT FK_90291C2D39C1776A FOREIGN KEY (parent_post_id) REFERENCES forum_posts (id) NOT DEFERRABLE INITIALLY IMMEDIATE;
CREATE INDEX IDX_90291C2D39C1776A ON forum_posts (parent_post_id);
