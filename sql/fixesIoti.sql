/* sql fix script run on beta after first ioti run- (one off) */

/*
UPDATE wp_38_terms SET name='Business Resources' where slug='business-resource' LIMIT 1;
UPDATE wp_38_terms SET slug='business-resources' where slug='business-resource' LIMIT 1;
UPDATE wp_38_term_taxonomy set description = 'Business Resources' where description='Business Resource' LIMIT 1;

UPDATE wp_38_terms set slug='connect-the-world-of-things-to-live-business' where name='Connect the World of Things to Live Business' LIMIT 1;
UPDATE wp_38_term_taxonomy set taxonomy='programs' where description='Connect the World of Things to Live Business' LIMIT 1;

UPDATE wp_38_terms set slug='exploring-iots-cutting-edge' where name='Exploring IoT\'s Cutting Edge' LIMIT 1;
UPDATE wp_38_term_taxonomy set taxonomy='programs' where description='Exploring IoT\'s Cutting Edge' LIMIT 1;

UPDATE wp_38_terms set slug='five2ndwindow' where name='Five2ndWindow' LIMIT 1;
UPDATE wp_38_term_taxonomy set taxonomy='programs' where description='Five2ndWindow' LIMIT 1;

UPDATE wp_38_terms set slug='hannover-messe-2016' where name='Hannover Messe 2016' LIMIT 1;
UPDATE wp_38_term_taxonomy set taxonomy='programs' where description='Hannover Messe 2016' LIMIT 1;

UPDATE wp_38_terms set slug='ideaxchange' where name='IdeaXchange' LIMIT 1;
UPDATE wp_38_term_taxonomy set taxonomy='programs' where description='IdeaXchange' LIMIT 1;

UPDATE wp_38_terms set slug='manufacturing-day' where name='Manufacturing Day' LIMIT 1;
UPDATE wp_38_term_taxonomy set taxonomy='programs' where description='Manufacturing Day' LIMIT 1;

UPDATE wp_38_terms set slug='ovum-viewpoints' where name='Ovum Viewpoints' LIMIT 1;
UPDATE wp_38_term_taxonomy set taxonomy='programs' where description='Ovum Viewpoints' LIMIT 1;

update wp_38_posts p join wp_38_postmeta m on p.ID = m.post_id set post_excerpt=m.meta_value where m.meta_key='penton_content_summary_value' and p.ID=m.post_id and p.post_excerpt = '';
*/