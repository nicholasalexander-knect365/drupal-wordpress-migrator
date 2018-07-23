--
-- example of sql statements to reset multisite for testing on local
-- 
UPDATE wp_blogs SET domain='ioti.multisite.local' where blog_id=38;
INSERT INTO wp_usermeta (user_id, meta_key, meta_value) VALUES (184136, 'wp_38_capabilities', 'a:1:{s:13:"administrator";s:1:"1";}');
drop table wp_domain_mapping;
UPDATE wp_38_options set option_value = 'http://ioti.multisite.local' where option_id=1 or option_id=2;

