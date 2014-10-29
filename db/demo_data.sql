delete from mdl_exalib_category;
INSERT INTO `mdl_exalib_category` (`id`, `parent_id`, `name`) VALUES
(1, 0, 'Type'),
(2, 0, 'Categories'),
(3, 0, 'Tags'),
(11, 1, 'Abstracts'),
(12, 1, 'Documents'),
(13, 1, 'Images'),
(14, 1, 'Podcasts'),
(15, 1, 'Webcasts'),
(	201,2, 'dummy group 1'),
(	202,2, 'dummy group 2'),
(	203,2, 'dummy group 3'),
(	204,2, 'dummy group 4'),
(	205,2, 'dummy group 5'),
(	206,2, 'dummy group 6'),
(	207,2, 'dummy group 7'),
(	208,2, 'dummy group 8'),
(	209,2, 'dummy group 9'),
(	210,2, 'dummy group 10'),
(	211,2, 'dummy group 11'),
(	212,2, 'dummy group 12'),
(	213,2, 'dummy group 13'),
(	214,2, 'dummy group 14'),
(	301,3, 'dummy group 15'),
(	302,3, 'dummy group 16'),
(	303,3, 'dummy group 17'),
(	304,3, 'dummy group 18'),
(	311,3, 'dummy group 19'),
(	312,3, 'dummy group 20'),
(	313,3, 'dummy group 21'),
(	321,3, 'dummy group 22'),
(	322,3, 'dummy group 23'),
(	323,3, 'dummy group 24'),
(	10101,101, 'dummy group 25'),
(	10102,101, 'dummy group 26'),
(	30101,301, 'dummy group 27'),
(	30102,301, 'dummy group 28'),
(	30103,3, 'dummy group 29'),
(	30104,303, 'dummy group 30'),
(	30105,303, 'dummy group 31'),
(	30106,303, 'dummy group 32'),
(	30107,303, 'dummy group 33'),
(	30108,303, 'dummy group 34'),
(	30109,303, 'dummy group 35'),
(	30201,302, 'dummy group 36'),
(	30202,302, 'dummy group 37'),
(	30203,3, 'dummy group 38'),
(	30204,304, 'dummy group 39'),
(	30205,304, 'dummy group 40'),
(	30206,304, 'dummy group 41'),
(	30207,304, 'dummy group 42'),
(	30208,304, 'dummy group 43'),
(	30209,304, 'dummy group 44'),
(	31101,311, 'dummy group 45'),
(	31102,311, 'dummy group 46'),
(	31103,311, 'dummy group 47'),
(	31104,311, 'dummy group 48'),
(	31105,311, 'dummy group 49'),
(	31106,311, 'dummy group 50'),
(	31301,313, 'dummy group 51'),
(	31302,313, 'dummy group 52'),
(	32201,322, 'dummy group 53'),
(	32202,322, 'dummy group 54'),
(	32203,322, 'dummy group 55'),
(	32204,322, 'dummy group 56'),
(	32207,322, 'dummy group 57'),
(	32208,322, 'dummy group 58'),
(	32209,322, 'dummy group 59'),
(	32210,322, 'dummy group 60'),
(	32211,322, 'dummy group 61'),
(	32301,323, 'dummy group 62'),
(	32302,323, 'dummy group 63'),
(	32303,323, 'dummy group 64'),
(	32304,323, 'dummy group 65'),
(	32305,323, 'dummy group 66'),
(	32306,323, 'dummy group 67'),
(	32401,324, 'dummy group 68'),
(	32402,324, 'dummy group 69'),
(	42012,2, 'dummy group 70'),
(	42013,2, 'dummy group 71'),
(	42014,2, 'dummy group 72');			


-- delete items
delete from mdl_exalib_item;

-- delete item_category
delete from mdl_exalib_item_category;

-- insert a dummy for each category
INSERT INTO mdl_exalib_item (id, name, link, content) 
	select
		id,
		CONCAT('dummy ', id),
		'',
		'some content'
	FROM mdl_exalib_category
;
INSERT INTO mdl_exalib_item (id, name, link, content) 
	select
		id + 100000, 
		CONCAT('dummy ', id+100000),
		'http://exabis.at',
		''
	FROM mdl_exalib_category
;
INSERT INTO mdl_exalib_item (id, name, link, content) 
	select
		id + 200000, 
		CONCAT('dummy ', id+200000),
		'http://exabis.at',
		''
	FROM mdl_exalib_category
;

-- hook each dummy with category
INSERT INTO mdl_exalib_item_category (item_id, category_id) select id, id AS tmp FROM mdl_exalib_category;
INSERT INTO mdl_exalib_item_category (item_id, category_id) select id+100000, id AS tmp FROM mdl_exalib_category;
INSERT INTO mdl_exalib_item_category (item_id, category_id) select id+200000, id AS tmp FROM mdl_exalib_category;
