-- ============================================
-- SAMPLE SUBJECTS DATA FOR ALL GRADE LEVELS
-- ============================================
-- This script inserts sample subjects and their offerings
-- for Kinder 1 to Grade 12 including all strands
-- ============================================

-- Clear existing sample data (optional - comment out if you want to keep existing data)
-- DELETE FROM subject_offerings;
-- DELETE FROM subjects;
-- ALTER TABLE subjects AUTO_INCREMENT = 1;

-- ============================================
-- KINDER 1 & 2 SUBJECTS
-- ============================================
INSERT INTO subjects (code, name) VALUES 
('K-LIT', 'Literacy'),
('K-NUM', 'Numeracy'),
('K-ART', 'Arts and Crafts'),
('K-MUS', 'Music and Movement'),
('K-PE', 'Physical Education'),
('K-VAL', 'Values Education'),
('K-SCI', 'Science Exploration');

-- ============================================
-- ELEMENTARY (GRADES 1-6) SUBJECTS
-- ============================================
INSERT INTO subjects (code, name) VALUES 
('ELEM-FIL', 'Filipino'),
('ELEM-ENG', 'English'),
('ELEM-MATH', 'Mathematics'),
('ELEM-SCI', 'Science'),
('ELEM-AP', 'Araling Panlipunan'),
('ELEM-EPP', 'Edukasyon sa Pagpapakatao'),
('ELEM-MAPEH', 'MAPEH'),
('ELEM-TLE', 'Technology and Livelihood Education');

-- ============================================
-- JUNIOR HIGH SCHOOL (GRADES 7-10) SUBJECTS
-- ============================================
INSERT INTO subjects (code, name) VALUES 
('JHS-FIL7', 'Filipino 7'),
('JHS-FIL8', 'Filipino 8'),
('JHS-FIL9', 'Filipino 9'),
('JHS-FIL10', 'Filipino 10'),
('JHS-ENG7', 'English 7'),
('JHS-ENG8', 'English 8'),
('JHS-ENG9', 'English 9'),
('JHS-ENG10', 'English 10'),
('JHS-MATH7', 'Mathematics 7'),
('JHS-MATH8', 'Mathematics 8'),
('JHS-MATH9', 'Mathematics 9'),
('JHS-MATH10', 'Mathematics 10'),
('JHS-SCI7', 'Science 7'),
('JHS-SCI8', 'Science 8'),
('JHS-SCI9', 'Science 9'),
('JHS-SCI10', 'Science 10'),
('JHS-AP7', 'Araling Panlipunan 7'),
('JHS-AP8', 'Araling Panlipunan 8'),
('JHS-AP9', 'Araling Panlipunan 9'),
('JHS-AP10', 'Araling Panlipunan 10'),
('JHS-EPP7', 'Edukasyon sa Pagpapakatao 7'),
('JHS-EPP8', 'Edukasyon sa Pagpapakatao 8'),
('JHS-EPP9', 'Edukasyon sa Pagpapakatao 9'),
('JHS-EPP10', 'Edukasyon sa Pagpapakatao 10'),
('JHS-TLE7', 'Technology and Livelihood Education 7'),
('JHS-TLE8', 'Technology and Livelihood Education 8'),
('JHS-TLE9', 'Technology and Livelihood Education 9'),
('JHS-TLE10', 'Technology and Livelihood Education 10'),
('JHS-MAPEH7', 'MAPEH 7'),
('JHS-MAPEH8', 'MAPEH 8'),
('JHS-MAPEH9', 'MAPEH 9'),
('JHS-MAPEH10', 'MAPEH 10');

-- ============================================
-- SENIOR HIGH SCHOOL - CORE SUBJECTS (GRADES 11-12)
-- ============================================
INSERT INTO subjects (code, name) VALUES 
('SHS-ORAL', 'Oral Communication'),
('SHS-READ', 'Reading and Writing'),
('SHS-KOMUN', 'Komunikasyon at Pananaliksik'),
('SHS-PAGBASA', 'Pagbasa at Pagsusuri'),
('SHS-GENMATH', 'General Mathematics'),
('SHS-STAT', 'Statistics and Probability'),
('SHS-EARTH', 'Earth and Life Science'),
('SHS-PHYS', 'Physical Science'),
('SHS-PERS', 'Personal Development'),
('SHS-PHED1', 'Physical Education and Health 1'),
('SHS-PHED2', 'Physical Education and Health 2'),
('SHS-PHED3', 'Physical Education and Health 3'),
('SHS-PHED4', 'Physical Education and Health 4'),
('SHS-UCSP', 'Understanding Culture, Society and Politics'),
('SHS-PHILO', 'Introduction to Philosophy'),
('SHS-MEDIA', 'Media and Information Literacy'),
('SHS-ENTRE', 'Entrepreneurship'),
('SHS-INQUI', 'Inquiries, Investigations and Immersion');

-- ============================================
-- STEM STRAND SUBJECTS
-- ============================================
INSERT INTO subjects (code, name) VALUES 
('STEM-PRECAL', 'Pre-Calculus'),
('STEM-BASICCAL', 'Basic Calculus'),
('STEM-GENBIO1', 'General Biology 1'),
('STEM-GENBIO2', 'General Biology 2'),
('STEM-GENCHEM1', 'General Chemistry 1'),
('STEM-GENCHEM2', 'General Chemistry 2'),
('STEM-GENPHYS1', 'General Physics 1'),
('STEM-GENPHYS2', 'General Physics 2'),
('STEM-RESEARCH1', 'Research Project 1'),
('STEM-RESEARCH2', 'Research Project 2');

-- ============================================
-- ABM STRAND SUBJECTS
-- ============================================
INSERT INTO subjects (code, name) VALUES 
('ABM-FUNDACC1', 'Fundamentals of Accountancy 1'),
('ABM-FUNDACC2', 'Fundamentals of Accountancy 2'),
('ABM-BUSMATH', 'Business Mathematics'),
('ABM-BUSFINANCE', 'Business Finance'),
('ABM-ORGMGT', 'Organization and Management'),
('ABM-PRINMKT', 'Principles of Marketing'),
('ABM-BUSETHICS', 'Business Ethics and Social Responsibility'),
('ABM-APPECO', 'Applied Economics'),
('ABM-BUSENT1', 'Business Enterprise Simulation 1'),
('ABM-BUSENT2', 'Business Enterprise Simulation 2');

-- ============================================
-- HUMSS STRAND SUBJECTS
-- ============================================
INSERT INTO subjects (code, name) VALUES 
('HUMSS-CRETHINK', 'Creative Writing'),
('HUMSS-CREWRITE', 'Creative Nonfiction'),
('HUMSS-TRENDS', 'Trends, Networks and Critical Thinking'),
('HUMSS-PHILO', 'Introduction to World Religions'),
('HUMSS-DISREAD', 'Disciplines and Ideas in Social Sciences'),
('HUMSS-DISAPP', 'Disciplines and Ideas in Applied Social Sciences'),
('HUMSS-COMMRES1', 'Community Engagement, Solidarity and Citizenship 1'),
('HUMSS-COMMRES2', 'Community Engagement, Solidarity and Citizenship 2'),
('HUMSS-PHILHIST', 'Philippine Politics and Governance'),
('HUMSS-CONTEMP', 'Contemporary Philippine Arts from the Regions');

-- ============================================
-- GAS STRAND SUBJECTS
-- ============================================
INSERT INTO subjects (code, name) VALUES 
('GAS-HUMANI', 'Humanities 1'),
('GAS-HUMANI2', 'Humanities 2'),
('GAS-SOCSCI1', 'Social Science 1'),
('GAS-SOCSCI2', 'Social Science 2'),
('GAS-APPSCI1', 'Applied Science 1'),
('GAS-APPSCI2', 'Applied Science 2'),
('GAS-ELECTIVE1', 'Elective 1'),
('GAS-ELECTIVE2', 'Elective 2'),
('GAS-ELECTIVE3', 'Elective 3'),
('GAS-ELECTIVE4', 'Elective 4');

-- ============================================
-- HE (HOME ECONOMICS) STRAND SUBJECTS
-- ============================================
INSERT INTO subjects (code, name) VALUES 
('HE-COOKERY', 'Cookery'),
('HE-BREAD', 'Bread and Pastry Production'),
('HE-HOUSEKEEP', 'Housekeeping'),
('HE-CAREGIVING', 'Caregiving'),
('HE-FOOD', 'Food and Beverage Services'),
('HE-DRESSMAKING', 'Dressmaking'),
('HE-HANDICRAFT', 'Handicraft Making'),
('HE-BEAUTY', 'Beauty Care and Wellness'),
('HE-PRACTICUM1', 'Work Immersion/Practicum 1'),
('HE-PRACTICUM2', 'Work Immersion/Practicum 2');

-- ============================================
-- ICT STRAND SUBJECTS
-- ============================================
INSERT INTO subjects (code, name) VALUES 
('ICT-PROG1', 'Computer Programming 1'),
('ICT-PROG2', 'Computer Programming 2'),
('ICT-WEBDEV1', 'Web Development 1'),
('ICT-WEBDEV2', 'Web Development 2'),
('ICT-NETWORK', 'Computer Systems Servicing'),
('ICT-DATABASE', 'Database Management'),
('ICT-ANIMATION', 'Animation'),
('ICT-GRAPHICS', 'Technical Drafting'),
('ICT-PRACTICUM1', 'Work Immersion/Practicum 1'),
('ICT-PRACTICUM2', 'Work Immersion/Practicum 2');

-- ============================================
-- SPORTS STRAND SUBJECTS
-- ============================================
INSERT INTO subjects (code, name) VALUES 
('SPORTS-ANATOMY', 'Anatomy and Physiology'),
('SPORTS-BIOMECH', 'Biomechanics'),
('SPORTS-NUTRITION', 'Sports Nutrition'),
('SPORTS-PSYCH', 'Sports Psychology'),
('SPORTS-COACHING', 'Coaching and Officiating'),
('SPORTS-FITNESS', 'Fitness and Conditioning'),
('SPORTS-FIRST', 'First Aid and Safety'),
('SPORTS-MANAGE', 'Sports Management'),
('SPORTS-PRACTICUM1', 'Sports Practicum 1'),
('SPORTS-PRACTICUM2', 'Sports Practicum 2');

-- ============================================
-- COLLEGE - BPED (BACHELOR OF PHYSICAL EDUCATION)
-- ============================================
INSERT INTO subjects (code, name) VALUES 
-- 1st Year
('BPED-PE101', 'Introduction to Physical Education'),
('BPED-ANAT101', 'Human Anatomy and Physiology'),
('BPED-HIST101', 'History and Philosophy of PE'),
('BPED-RHYTH101', 'Rhythmic Activities'),
('BPED-GYM101', 'Gymnastics'),
('BPED-GE101', 'Understanding the Self'),
('BPED-GE102', 'Purposive Communication'),
('BPED-GE103', 'Mathematics in the Modern World'),
-- 2nd Year
('BPED-KINES201', 'Kinesiology'),
('BPED-BIOMECH201', 'Biomechanics in Sports'),
('BPED-NUTRI201', 'Sports Nutrition'),
('BPED-TRACK201', 'Track and Field'),
('BPED-SWIM201', 'Swimming and Water Safety'),
('BPED-TEAM201', 'Team Sports'),
('BPED-GE201', 'Readings in Philippine History'),
('BPED-GE202', 'The Contemporary World'),
-- 3rd Year
('BPED-COACH301', 'Coaching Principles'),
('BPED-PSYCH301', 'Sports Psychology'),
('BPED-MANAGE301', 'Sports Management'),
('BPED-RECRE301', 'Recreation and Leisure'),
('BPED-DANCE301', 'Dance Education'),
('BPED-COMBAT301', 'Combat Sports'),
('BPED-ASSESS301', 'Physical Fitness Assessment'),
('BPED-GE301', 'Art Appreciation'),
-- 4th Year
('BPED-ADMIN401', 'PE Administration'),
('BPED-RESEARCH401', 'Research in Physical Education'),
('BPED-ADAPT401', 'Adapted Physical Education'),
('BPED-OFFIC401', 'Sports Officiating'),
('BPED-INTERN401', 'Teaching Internship'),
('BPED-SEMINAR401', 'Seminar in Physical Education');

-- ============================================
-- COLLEGE - BECED (BACHELOR OF EARLY CHILDHOOD EDUCATION)
-- ============================================
INSERT INTO subjects (code, name) VALUES 
-- 1st Year
('BECED-INTRO101', 'Introduction to Early Childhood Education'),
('BECED-CHILD101', 'Child and Adolescent Development'),
('BECED-CURRIC101', 'Curriculum Development'),
('BECED-TECH101', 'Technology for Teaching and Learning'),
('BECED-ASSESS101', 'Assessment in Learning 1'),
('BECED-GE101', 'Understanding the Self'),
('BECED-GE102', 'Purposive Communication'),
('BECED-GE103', 'Mathematics in the Modern World'),
-- 2nd Year
('BECED-FOUND201', 'Foundation of Early Childhood Education'),
('BECED-PLAY201', 'Play and Learning'),
('BECED-LANG201', 'Language and Literacy Development'),
('BECED-MATH201', 'Mathematics for Young Children'),
('BECED-SCI201', 'Science for Young Children'),
('BECED-ASSESS201', 'Assessment in Learning 2'),
('BECED-GE201', 'Readings in Philippine History'),
('BECED-GE202', 'The Contemporary World'),
-- 3rd Year
('BECED-ARTS301', 'Arts and Creative Expression'),
('BECED-MUSIC301', 'Music and Movement'),
('BECED-HEALTH301', 'Health, Safety and Nutrition'),
('BECED-FAMILY301', 'Family and Community Engagement'),
('BECED-SPECIAL301', 'Teaching Children with Special Needs'),
('BECED-MANAGE301', 'Classroom Management'),
('BECED-FIELD301', 'Field Study 1'),
('BECED-GE301', 'Art Appreciation'),
-- 4th Year
('BECED-RESEARCH401', 'Research in Early Childhood Education'),
('BECED-ADMIN401', 'School Administration and Management'),
('BECED-FIELD401', 'Field Study 2'),
('BECED-PRACTICE401', 'Practice Teaching'),
('BECED-SEMINAR401', 'Seminar in Early Childhood Education'),
('BECED-CAPSTONE401', 'Capstone Project');

-- ============================================
-- SUBJECT OFFERINGS - KINDER 1
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Kinder 1', '', '1st', '', 1 FROM subjects WHERE code IN ('K-LIT', 'K-NUM', 'K-ART', 'K-MUS', 'K-PE', 'K-VAL', 'K-SCI');

INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Kinder 1', '', '2nd', '', 1 FROM subjects WHERE code IN ('K-LIT', 'K-NUM', 'K-ART', 'K-MUS', 'K-PE', 'K-VAL', 'K-SCI');

-- ============================================
-- SUBJECT OFFERINGS - KINDER 2
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Kinder 2', '', '1st', '2024-2025', 1 FROM subjects WHERE code IN ('K-LIT', 'K-NUM', 'K-ART', 'K-MUS', 'K-PE', 'K-VAL', 'K-SCI');

INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Kinder 2', '', '2nd', '2024-2025', 1 FROM subjects WHERE code IN ('K-LIT', 'K-NUM', 'K-ART', 'K-MUS', 'K-PE', 'K-VAL', 'K-SCI');

-- ============================================
-- SUBJECT OFFERINGS - GRADES 1-6 (ELEMENTARY)
-- ============================================
-- Grade 1
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 1', '', '1st', '2024-2025', 1 FROM subjects WHERE code IN ('ELEM-FIL', 'ELEM-ENG', 'ELEM-MATH', 'ELEM-SCI', 'ELEM-AP', 'ELEM-EPP', 'ELEM-MAPEH');

INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 1', '', '2nd', '2024-2025', 1 FROM subjects WHERE code IN ('ELEM-FIL', 'ELEM-ENG', 'ELEM-MATH', 'ELEM-SCI', 'ELEM-AP', 'ELEM-EPP', 'ELEM-MAPEH');

-- Grade 2
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 2', '', '1st', '2024-2025', 1 FROM subjects WHERE code IN ('ELEM-FIL', 'ELEM-ENG', 'ELEM-MATH', 'ELEM-SCI', 'ELEM-AP', 'ELEM-EPP', 'ELEM-MAPEH');

INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 2', '', '2nd', '2024-2025', 1 FROM subjects WHERE code IN ('ELEM-FIL', 'ELEM-ENG', 'ELEM-MATH', 'ELEM-SCI', 'ELEM-AP', 'ELEM-EPP', 'ELEM-MAPEH');

-- Grade 3
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 3', '', '1st', '2024-2025', 1 FROM subjects WHERE code IN ('ELEM-FIL', 'ELEM-ENG', 'ELEM-MATH', 'ELEM-SCI', 'ELEM-AP', 'ELEM-EPP', 'ELEM-MAPEH', 'ELEM-TLE');

INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 3', '', '2nd', '2024-2025', 1 FROM subjects WHERE code IN ('ELEM-FIL', 'ELEM-ENG', 'ELEM-MATH', 'ELEM-SCI', 'ELEM-AP', 'ELEM-EPP', 'ELEM-MAPEH', 'ELEM-TLE');

-- Grade 4
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 4', '', '1st', '2024-2025', 1 FROM subjects WHERE code IN ('ELEM-FIL', 'ELEM-ENG', 'ELEM-MATH', 'ELEM-SCI', 'ELEM-AP', 'ELEM-EPP', 'ELEM-MAPEH', 'ELEM-TLE');

INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 4', '', '2nd', '2024-2025', 1 FROM subjects WHERE code IN ('ELEM-FIL', 'ELEM-ENG', 'ELEM-MATH', 'ELEM-SCI', 'ELEM-AP', 'ELEM-EPP', 'ELEM-MAPEH', 'ELEM-TLE');

-- Grade 5
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 5', '', '1st', '2024-2025', 1 FROM subjects WHERE code IN ('ELEM-FIL', 'ELEM-ENG', 'ELEM-MATH', 'ELEM-SCI', 'ELEM-AP', 'ELEM-EPP', 'ELEM-MAPEH', 'ELEM-TLE');

INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 5', '', '2nd', '2024-2025', 1 FROM subjects WHERE code IN ('ELEM-FIL', 'ELEM-ENG', 'ELEM-MATH', 'ELEM-SCI', 'ELEM-AP', 'ELEM-EPP', 'ELEM-MAPEH', 'ELEM-TLE');

-- Grade 6
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 6', '', '1st', '2024-2025', 1 FROM subjects WHERE code IN ('ELEM-FIL', 'ELEM-ENG', 'ELEM-MATH', 'ELEM-SCI', 'ELEM-AP', 'ELEM-EPP', 'ELEM-MAPEH', 'ELEM-TLE');

INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 6', '', '2nd', '2024-2025', 1 FROM subjects WHERE code IN ('ELEM-FIL', 'ELEM-ENG', 'ELEM-MATH', 'ELEM-SCI', 'ELEM-AP', 'ELEM-EPP', 'ELEM-MAPEH', 'ELEM-TLE');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 7 (JUNIOR HIGH)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 7', '', '1st', '2024-2025', 1 FROM subjects WHERE code IN ('JHS-FIL7', 'JHS-ENG7', 'JHS-MATH7', 'JHS-SCI7', 'JHS-AP7', 'JHS-EPP7', 'JHS-TLE7', 'JHS-MAPEH7');

INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 7', '', '2nd', '2024-2025', 1 FROM subjects WHERE code IN ('JHS-FIL7', 'JHS-ENG7', 'JHS-MATH7', 'JHS-SCI7', 'JHS-AP7', 'JHS-EPP7', 'JHS-TLE7', 'JHS-MAPEH7');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 8 (JUNIOR HIGH)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 8', '', '1st', '2024-2025', 1 FROM subjects WHERE code IN ('JHS-FIL8', 'JHS-ENG8', 'JHS-MATH8', 'JHS-SCI8', 'JHS-AP8', 'JHS-EPP8', 'JHS-TLE8', 'JHS-MAPEH8');

INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 8', '', '2nd', '2024-2025', 1 FROM subjects WHERE code IN ('JHS-FIL8', 'JHS-ENG8', 'JHS-MATH8', 'JHS-SCI8', 'JHS-AP8', 'JHS-EPP8', 'JHS-TLE8', 'JHS-MAPEH8');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 9 (JUNIOR HIGH)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 9', '', '1st', '2024-2025', 1 FROM subjects WHERE code IN ('JHS-FIL9', 'JHS-ENG9', 'JHS-MATH9', 'JHS-SCI9', 'JHS-AP9', 'JHS-EPP9', 'JHS-TLE9', 'JHS-MAPEH9');

INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 9', '', '2nd', '2024-2025', 1 FROM subjects WHERE code IN ('JHS-FIL9', 'JHS-ENG9', 'JHS-MATH9', 'JHS-SCI9', 'JHS-AP9', 'JHS-EPP9', 'JHS-TLE9', 'JHS-MAPEH9');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 10 (JUNIOR HIGH)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 10', '', '1st', '2024-2025', 1 FROM subjects WHERE code IN ('JHS-FIL10', 'JHS-ENG10', 'JHS-MATH10', 'JHS-SCI10', 'JHS-AP10', 'JHS-EPP10', 'JHS-TLE10', 'JHS-MAPEH10');

INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 10', '', '2nd', '2024-2025', 1 FROM subjects WHERE code IN ('JHS-FIL10', 'JHS-ENG10', 'JHS-MATH10', 'JHS-SCI10', 'JHS-AP10', 'JHS-EPP10', 'JHS-TLE10', 'JHS-MAPEH10');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 11 STEM (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 11', 'STEM', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-ORAL', 'SHS-KOMUN', 'SHS-GENMATH', 'SHS-EARTH', 'SHS-PERS', 'SHS-PHED1', 'STEM-PRECAL', 'STEM-GENBIO1', 'STEM-GENCHEM1');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 11 STEM (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 11', 'STEM', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-READ', 'SHS-PAGBASA', 'SHS-STAT', 'SHS-PHYS', 'SHS-PHED2', 'STEM-BASICCAL', 'STEM-GENBIO2', 'STEM-GENCHEM2', 'STEM-GENPHYS1');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 12 STEM (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 12', 'STEM', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-UCSP', 'SHS-MEDIA', 'SHS-PHED3', 'STEM-GENPHYS2', 'STEM-RESEARCH1', 'SHS-PHILO');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 12 STEM (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 12', 'STEM', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-ENTRE', 'SHS-PHED4', 'STEM-RESEARCH2', 'SHS-INQUI');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 11 ABM (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 11', 'ABM', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-ORAL', 'SHS-KOMUN', 'SHS-GENMATH', 'SHS-EARTH', 'SHS-PERS', 'SHS-PHED1', 'ABM-FUNDACC1', 'ABM-BUSMATH', 'ABM-ORGMGT');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 11 ABM (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 11', 'ABM', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-READ', 'SHS-PAGBASA', 'SHS-STAT', 'SHS-PHYS', 'SHS-PHED2', 'ABM-FUNDACC2', 'ABM-BUSFINANCE', 'ABM-PRINMKT', 'ABM-APPECO');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 12 ABM (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 12', 'ABM', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-UCSP', 'SHS-MEDIA', 'SHS-PHED3', 'ABM-BUSETHICS', 'ABM-BUSENT1', 'SHS-PHILO');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 12 ABM (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 12', 'ABM', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-ENTRE', 'SHS-PHED4', 'ABM-BUSENT2', 'SHS-INQUI');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 11 HUMSS (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 11', 'HUMSS', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-ORAL', 'SHS-KOMUN', 'SHS-GENMATH', 'SHS-EARTH', 'SHS-PERS', 'SHS-PHED1', 'HUMSS-CRETHINK', 'HUMSS-PHILO', 'HUMSS-DISREAD');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 11 HUMSS (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 11', 'HUMSS', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-READ', 'SHS-PAGBASA', 'SHS-STAT', 'SHS-PHYS', 'SHS-PHED2', 'HUMSS-CREWRITE', 'HUMSS-TRENDS', 'HUMSS-DISAPP', 'HUMSS-PHILHIST');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 12 HUMSS (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 12', 'HUMSS', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-UCSP', 'SHS-MEDIA', 'SHS-PHED3', 'HUMSS-COMMRES1', 'HUMSS-CONTEMP', 'SHS-PHILO');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 12 HUMSS (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 12', 'HUMSS', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-ENTRE', 'SHS-PHED4', 'HUMSS-COMMRES2', 'SHS-INQUI');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 11 GAS (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 11', 'GAS', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-ORAL', 'SHS-KOMUN', 'SHS-GENMATH', 'SHS-EARTH', 'SHS-PERS', 'SHS-PHED1', 'GAS-HUMANI', 'GAS-SOCSCI1', 'GAS-APPSCI1');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 11 GAS (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 11', 'GAS', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-READ', 'SHS-PAGBASA', 'SHS-STAT', 'SHS-PHYS', 'SHS-PHED2', 'GAS-HUMANI2', 'GAS-SOCSCI2', 'GAS-APPSCI2', 'GAS-ELECTIVE1');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 12 GAS (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 12', 'GAS', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-UCSP', 'SHS-MEDIA', 'SHS-PHED3', 'GAS-ELECTIVE2', 'GAS-ELECTIVE3', 'SHS-PHILO');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 12 GAS (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 12', 'GAS', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-ENTRE', 'SHS-PHED4', 'GAS-ELECTIVE4', 'SHS-INQUI');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 11 HE (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 11', 'HE', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-ORAL', 'SHS-KOMUN', 'SHS-GENMATH', 'SHS-EARTH', 'SHS-PERS', 'SHS-PHED1', 'HE-COOKERY', 'HE-HOUSEKEEP', 'HE-DRESSMAKING');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 11 HE (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 11', 'HE', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-READ', 'SHS-PAGBASA', 'SHS-STAT', 'SHS-PHYS', 'SHS-PHED2', 'HE-BREAD', 'HE-CAREGIVING', 'HE-FOOD', 'HE-HANDICRAFT');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 12 HE (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 12', 'HE', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-UCSP', 'SHS-MEDIA', 'SHS-PHED3', 'HE-BEAUTY', 'HE-PRACTICUM1', 'SHS-PHILO');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 12 HE (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 12', 'HE', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-ENTRE', 'SHS-PHED4', 'HE-PRACTICUM2', 'SHS-INQUI');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 11 ICT (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 11', 'ICT', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-ORAL', 'SHS-KOMUN', 'SHS-GENMATH', 'SHS-EARTH', 'SHS-PERS', 'SHS-PHED1', 'ICT-PROG1', 'ICT-WEBDEV1', 'ICT-NETWORK');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 11 ICT (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 11', 'ICT', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-READ', 'SHS-PAGBASA', 'SHS-STAT', 'SHS-PHYS', 'SHS-PHED2', 'ICT-PROG2', 'ICT-WEBDEV2', 'ICT-DATABASE', 'ICT-GRAPHICS');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 12 ICT (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 12', 'ICT', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-UCSP', 'SHS-MEDIA', 'SHS-PHED3', 'ICT-ANIMATION', 'ICT-PRACTICUM1', 'SHS-PHILO');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 12 ICT (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 12', 'ICT', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-ENTRE', 'SHS-PHED4', 'ICT-PRACTICUM2', 'SHS-INQUI');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 11 SPORTS (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 11', 'SPORTS', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-ORAL', 'SHS-KOMUN', 'SHS-GENMATH', 'SHS-EARTH', 'SHS-PERS', 'SHS-PHED1', 'SPORTS-ANATOMY', 'SPORTS-BIOMECH', 'SPORTS-FITNESS');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 11 SPORTS (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 11', 'SPORTS', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-READ', 'SHS-PAGBASA', 'SHS-STAT', 'SHS-PHYS', 'SHS-PHED2', 'SPORTS-NUTRITION', 'SPORTS-PSYCH', 'SPORTS-COACHING', 'SPORTS-FIRST');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 12 SPORTS (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 12', 'SPORTS', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-UCSP', 'SHS-MEDIA', 'SHS-PHED3', 'SPORTS-MANAGE', 'SPORTS-PRACTICUM1', 'SHS-PHILO');

-- ============================================
-- SUBJECT OFFERINGS - GRADE 12 SPORTS (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, 'Grade 12', 'SPORTS', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('SHS-ENTRE', 'SHS-PHED4', 'SPORTS-PRACTICUM2', 'SHS-INQUI');

-- ============================================
-- COLLEGE OFFERINGS - BPED 1ST YEAR (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '1st Year', 'BPED', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('BPED-PE101', 'BPED-ANAT101', 'BPED-HIST101', 'BPED-RHYTH101', 'BPED-GE101', 'BPED-GE102');

-- ============================================
-- COLLEGE OFFERINGS - BPED 1ST YEAR (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '1st Year', 'BPED', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('BPED-GYM101', 'BPED-GE103', 'BPED-KINES201', 'BPED-BIOMECH201', 'BPED-GE201');

-- ============================================
-- COLLEGE OFFERINGS - BPED 2ND YEAR (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '2nd Year', 'BPED', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('BPED-NUTRI201', 'BPED-TRACK201', 'BPED-SWIM201', 'BPED-TEAM201', 'BPED-GE202');

-- ============================================
-- COLLEGE OFFERINGS - BPED 2ND YEAR (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '2nd Year', 'BPED', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('BPED-COACH301', 'BPED-PSYCH301', 'BPED-MANAGE301', 'BPED-RECRE301', 'BPED-GE301');

-- ============================================
-- COLLEGE OFFERINGS - BPED 3RD YEAR (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '3rd Year', 'BPED', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('BPED-DANCE301', 'BPED-COMBAT301', 'BPED-ASSESS301', 'BPED-ADMIN401', 'BPED-RESEARCH401');

-- ============================================
-- COLLEGE OFFERINGS - BPED 3RD YEAR (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '3rd Year', 'BPED', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('BPED-ADAPT401', 'BPED-OFFIC401', 'BPED-INTERN401');

-- ============================================
-- COLLEGE OFFERINGS - BPED 4TH YEAR (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '4th Year', 'BPED', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('BPED-SEMINAR401', 'BPED-INTERN401');

-- ============================================
-- COLLEGE OFFERINGS - BPED 4TH YEAR (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '4th Year', 'BPED', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('BPED-INTERN401');

-- ============================================
-- COLLEGE OFFERINGS - BECED 1ST YEAR (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '1st Year', 'BECED', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('BECED-INTRO101', 'BECED-CHILD101', 'BECED-CURRIC101', 'BECED-TECH101', 'BECED-GE101', 'BECED-GE102');

-- ============================================
-- COLLEGE OFFERINGS - BECED 1ST YEAR (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '1st Year', 'BECED', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('BECED-ASSESS101', 'BECED-GE103', 'BECED-FOUND201', 'BECED-PLAY201', 'BECED-GE201');

-- ============================================
-- COLLEGE OFFERINGS - BECED 2ND YEAR (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '2nd Year', 'BECED', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('BECED-LANG201', 'BECED-MATH201', 'BECED-SCI201', 'BECED-ASSESS201', 'BECED-GE202');

-- ============================================
-- COLLEGE OFFERINGS - BECED 2ND YEAR (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '2nd Year', 'BECED', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('BECED-ARTS301', 'BECED-MUSIC301', 'BECED-HEALTH301', 'BECED-FAMILY301', 'BECED-GE301');

-- ============================================
-- COLLEGE OFFERINGS - BECED 3RD YEAR (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '3rd Year', 'BECED', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('BECED-SPECIAL301', 'BECED-MANAGE301', 'BECED-FIELD301', 'BECED-RESEARCH401', 'BECED-ADMIN401');

-- ============================================
-- COLLEGE OFFERINGS - BECED 3RD YEAR (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '3rd Year', 'BECED', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('BECED-FIELD401', 'BECED-PRACTICE401', 'BECED-CAPSTONE401');

-- ============================================
-- COLLEGE OFFERINGS - BECED 4TH YEAR (1ST SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '4th Year', 'BECED', '1st', '2024-2025', 1 FROM subjects WHERE code IN 
('BECED-SEMINAR401', 'BECED-PRACTICE401');

-- ============================================
-- COLLEGE OFFERINGS - BECED 4TH YEAR (2ND SEM)
-- ============================================
INSERT INTO subject_offerings (subject_id, grade_level, strand, semester, school_year_term, active) 
SELECT id, '4th Year', 'BECED', '2nd', '2024-2025', 1 FROM subjects WHERE code IN 
('BECED-PRACTICE401');

-- ============================================
-- COMPLETION MESSAGE
-- ============================================
SELECT 'Sample subjects data inserted successfully!' AS Status;
SELECT COUNT(*) AS 'Total Subjects' FROM subjects;
SELECT COUNT(*) AS 'Total Subject Offerings' FROM subject_offerings;
