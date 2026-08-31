<?php
declare(strict_types=1);
require_once __DIR__ . '/../db.php';

$pdo = getDB();

/* ── 1) Ensure section column exists ── */
$col = $pdo->query("SHOW COLUMNS FROM menu_items LIKE 'section'")->fetch();
if (!$col) {
    $pdo->exec("ALTER TABLE menu_items ADD COLUMN section VARCHAR(100) DEFAULT NULL AFTER sort_order");
}

/* ── 2) Wipe everything ── */
$pdo->exec("SET FOREIGN_KEY_CHECKS=0");
$pdo->exec("TRUNCATE TABLE menu_items");
$pdo->exec("ALTER TABLE menu_items AUTO_INCREMENT = 1");
$pdo->exec("SET FOREIGN_KEY_CHECKS=1");

/* ── 3) Section resolver ── */
function sectionFor(int $id, string $cat): string {
    switch ($cat) {
        case 'starter':       return $id <= 4 ? 'Papads' : 'Starters';
        case 'main_course':   return $id <= 407 ? 'Paneer Specialties' : ($id <= 422 ? 'Veg Curries' : 'Dals');
        case 'bread':         return $id <= 209 ? 'Rotis & Chapatis' : ($id <= 215 ? 'Naans' : 'Kulchas & Bhakris');
        case 'rice_biryani':  return $id <= 307 ? 'Rice & Pulaos' : ($id <= 310 ? 'Fried Rice' : 'Biryanis');
        case 'dessert':       return $id <= 110 ? 'Traditional' : 'Ice Creams';
        case 'beverage':
            if ($id >= 617 && $id <= 620) return 'Hot Brews';
            if ($id >= 613 && $id <= 616) return 'Shakes & Coffee';
            if ($id >= 601 && $id <= 606) return 'Refreshers';
            return 'Mocktails & Juices';
        case 'welcome_drink':
            if ($id <= 804) return 'Hot Brews';
            if ($id <= 806) return 'Traditional Coolers';
            if ($id <= 808) return 'Refreshers';
            return 'Cold Drinks & Energy';
        case 'breakfast':
            if ($id <= 909) return 'Maharashtrian Snacks';
            if ($id <= 916) return 'South Indian Delicacies';
            if ($id <= 922) return 'Pav Bhaji & Chaat';
            return 'Upvas Specials';
        case 'side_dish':     return 'Accompaniments';
        case 'water':         return 'Premium Hydration';
    }
    return '';
}

/* ── 4) Items: [id, category, en, hi, mr, price, image, is_veg, sort_order, prep_time] ── */
$items = [
  // STARTERS
  [1,'starter','Roasted Papad','भुना पापड','भाजलेले पापड',30,'Starters/Roasted Papad.png',1,1,5],
  [2,'starter','Fried Papad','तला पापड','तळलेले पापड',40,'Starters/Fried Papad.png',1,2,5],
  [3,'starter','Masala Papad','मसाला पापड','मसाला पापड',60,'Starters/Masala Papad.png',1,3,5],
  [4,'starter','Cheese Masala Papad','चीज़ मसाला पापड','चीज मसाला पापड',90,'Starters/Cheese Masala Papad.png',1,4,5],
  [5,'starter','Paneer Tikka','पनीर टिक्का','पनीर टिक्का',220,'Starters/Paneer Tikka.png',1,5,5],
  [6,'starter','Paneer Malai Tikka','पनीर मलाई टिक्का','पनीर मलाई टिक्का',250,'Starters/Paneer Malai Tikka.png',1,6,5],
  [7,'starter','Hariyali Paneer Tikka','हरियाली पनीर टिक्का','हिरवा पनीर टिक्का',250,'Starters/Hariyali Paneer Tikka.png',1,7,5],
  [8,'starter','Hara Bhara Kebab','हरा भरा कबाब','हिरवा कबाब',180,'Starters/Hara Bhara Kebab.png',1,8,8],
  [9,'starter','Veg Seekh Kebab','वेज सीख कबाब','वेज सीख कबाब',220,'Starters/Veg Seekh Kebab.png',1,9,8],
  [10,'starter','Dahi Ke Kebab','दही के कबाब','दह्याचे कबाब',220,'Starters/Dahi Ke Kebab.png',1,10,8],
  [11,'starter','Tandoori Mushroom','तंदूरी मशरूम','तंदूरी मशरूम',220,'Starters/Tandoori Mushroom.png',1,11,8],
  [12,'starter','Stuffed Mushroom','स्टफ्ड मशरूम','भरलेले मशरूम',250,'Starters/Stuffed Mushroom.png',1,12,8],
  [13,'starter','Crispy Corn','क्रिस्पी मक्का','क्रिस्पी कॉर्न',180,'Starters/Crispy Corn.png',1,13,8],
  [14,'starter','Honey Chilli Potato','हनी चिली आलू','हनी चिली बटाटा',180,'Starters/Honey Chilli Potato.png',1,14,8],
  [15,'starter','Chilli Paneer Dry','चिली पनीर ड्राय','चिली पनीर ड्राय',220,'Starters/Chilli Paneer Dry.png',1,15,8],
  [16,'starter','Veg Manchurian Dry','वेज मंचूरियन ड्राय','वेज मंचूरियन ड्राय',180,'Starters/Veg Manchurian Dry.png',1,16,8],
  [17,'starter','Gobi 65','गोबी 65','फुलकोबी 65',180,'Starters/Gobi 65.png',1,17,8],
  [18,'starter','Spring Rolls','स्प्रिंग रोल','स्प्रिंग रोल',180,'Starters/Spring Rolls.png',1,18,8],
  [19,'starter','Cheese Corn Balls','चीज़ मक्का बॉल','चीज कॉर्न बॉल',220,'Starters/Cheese Corn Balls.png',1,19,8],
  [20,'starter','Veg Crispy','वेज क्रिस्पी','वेज क्रिस्पी',220,'Starters/Veg Crispy.png',1,20,8],
  [21,'starter','Soya Chaap Tikka','सोया चाप टिक्का','सोया चाप टिक्का',220,'Starters/Soya Chaap Tikka.png',1,21,10],
  [22,'starter','Malai Chaap','मलाई चाप','मलाई चाप',220,'Starters/Malai Chaap.png',1,22,10],
  [23,'starter','Tandoori Broccoli','तंदूरी ब्रोकली','तंदूरी ब्रोकोली',250,'Starters/Tandoori Broccoli.png',1,23,10],
  [24,'starter','Veg Tandoori Platter','वेज तंदूरी प्लेटर','वेज तंदूरी प्लेटर',350,'Starters/Veg Tandoori Platter.png',1,24,15],

  // MAIN COURSE
  [401,'main_course','Paneer Butter Masala','पनीर बटर मसाला','पनीर बटर मसाला',269,'Main Cource/Paneer Butter Masala.png',1,1,15],
  [402,'main_course','Paneer Tikka Masala','पनीर टिक्का मसाला','पनीर टिक्का मसाला',289,'Main Cource/Paneer Tikka Masala.png',1,2,15],
  [403,'main_course','Kadai Paneer','कढ़ाई पनीर','कढई पनीर',279,'Main Cource/Kadai Paneer.png',1,3,12],
  [404,'main_course','Shahi Paneer','शाही पनीर','शाही पनीर',279,'Main Cource/Shahi Paneer.png',1,4,12],
  [405,'main_course','Paneer Handi','पनीर हांडी','पनीर हांडी',289,'Main Cource/Paneer Handi.png',1,5,12],
  [406,'main_course','Palak Paneer','पालक पनीर','पालक पनीर',269,'Main Cource/Palak Paneer.png',1,6,12],
  [407,'main_course','Matar Paneer','मटर पनीर','मटार पनीर',259,'Main Cource/Matar Paneer.png',1,7,12],
  [408,'main_course','Kaju Curry','काजू करी','काजू करी',299,'Main Cource/Kaju Curry.png',1,8,12],
  [409,'main_course','Veg Kolhapuri','वेज कोल्हापुरी','व्हेज कोल्हापुरी',249,'Main Cource/Veg Kolhapuri.png',1,9,12],
  [410,'main_course','Veg Handi','वेज हांडी','व्हेज हांडी',259,'Main Cource/Veg Handi.png',1,10,12],
  [411,'main_course','Mix Veg Curry','मिक्स वेज करी','मिक्स व्हेज करी',229,'Main Cource/Mix Veg Curry.png',1,11,12],
  [412,'main_course','Veg Jaipuri','वेज जयपुरी','व्हेज जयपुरी',269,'Main Cource/Veg Jaipuri.png',1,12,12],
  [413,'main_course','Veg Hyderabadi','वेज हैदराबादी','व्हेज हैदराबादी',269,'Main Cource/Veg Hyderabadi.png',1,13,12],
  [414,'main_course','Malai Kofta','मलाई कोफ्ता','मलाई कोफ्ता',289,'Main Cource/Malai Kofta.png',1,14,12],
  [415,'main_course','Navratan Korma','नवरतन कोरमा','नवरत्न कोरमा',279,'Main Cource/Navratan Korma.png',1,15,12],
  [416,'main_course','Dum Aloo Kashmiri','दम आलू कश्मीरी','दम आलू काश्मिरी',249,'Main Cource/Dum Aloo Kashmiri.png',1,16,12],
  [417,'main_course','Aloo Mutter','आलू मटर','आलू मटार',219,'Main Cource/Aloo Mutter.png',1,17,10],
  [418,'main_course','Chana Masala','चना मसाला','चना मसाला',229,'Main Cource/Chana Masala.png',1,18,10],
  [419,'main_course','Sev Bhaji','सेव भाजी','शेव भाजी',199,'Main Cource/Sev Bhaji.png',1,19,10],
  [420,'main_course','Shev Tamatar','सेव टमाटर','शेव टोमॅटो',209,'Main Cource/Shev Tamatar.png',1,20,10],
  [421,'main_course','Lasooni Palak','लसूनी पालक','लसूणी पालक',239,'Main Cource/Lasooni Palak.png',1,21,10],
  [422,'main_course','Mushroom Masala','मशरूम मसाला','मशरूम मसाला',279,'Main Cource/Mushroom Masala.png',1,22,10],
  [423,'main_course','Dal Tadka','दाल तड़का','डाळ तडका',189,'Main Cource/Dal Tadka.png',1,23,10],
  [424,'main_course','Dal Fry','दाल फ्राई','डाळ फ्राय',179,'Main Cource/Dal Fry.png',1,24,10],
  [425,'main_course','Dal Makhani','दाल मखनी','डाळ मखनी',249,'Main Cource/Dal Makhani.png',1,25,10],

  // BREADS
  [201,'bread','Plain Roti','प्लेन रोटी','साधी पोळी',20,'Breads/Tandoori Roti.png',1,1,3],
  [202,'bread','Chapati','चपाती','चपाती',20,'Breads/Chapati.png',1,2,3],
  [203,'bread','Butter Roti','बटर रोटी','बटर पोळी',25,'Breads/Butter roti.png',1,3,3],
  [204,'bread','Tandoori Roti','तंदूरी रोटी','तंदूरी रोटी',25,'Breads/Tandoori Roti.png',1,4,3],
  [205,'bread','Butter Tandoori Roti','बटर तंदूरी रोटी','बटर तंदूरी रोटी',35,'Breads/Butter Tandoori Roti.png',1,5,3],
  [206,'bread','Multigrain Roti','मल्टीग्रेन रोटी','मल्टीग्रेन रोटी',50,'Breads/Multigrain Roti.png',1,6,3],
  [207,'bread','Rumali Roti','रुमाली रोटी','रुमाली रोटी',40,'Breads/Rumali Roti.png',1,7,5],
  [208,'bread','Khamiri Roti','खमीरी रोटी','खमीरी रोटी',60,'Breads/Khamiri Roti.png',1,8,8],
  [209,'bread','Missi Roti','मिस्सी रोटी','मिस्सी रोटी',60,'Breads/Missi Roti.png',1,9,5],
  [210,'bread','Plain Naan','प्लेन नान','साधा नान',40,'Breads/Plain Naan.png',1,10,5],
  [211,'bread','Butter Naan','बटर नान','बटर नान',50,'Breads/Butter Naan.png',1,11,5],
  [212,'bread','Garlic Naan','गार्लिक नान','लसूण नान',70,'Breads/Garlic Naan.png',1,12,5],
  [213,'bread','Garlic Butter Naan','गार्लिक बटर नान','लसूण बटर नान',80,'Breads/Garlic Butter Naan.png',1,13,5],
  [214,'bread','Cheese Naan','चीज़ नान','चीज नान',100,'Breads/Cheese Naan.png',1,14,5],
  [215,'bread','Stuffed Naan','स्टफ्ड नान','भरलेला नान',90,'Breads/Stuffed Naan.png',1,15,8],
  [216,'bread','Kulcha','कुलचा','कुलचा',60,'Breads/Kulcha.png',1,16,5],
  [217,'bread','Butter Kulcha','बटर कुलचा','बटर कुलचा',70,'Breads/Butter Kulcha.png',1,17,5],
  [218,'bread','Paneer Kulcha','पनीर कुलचा','पनीर कुलचा',120,'Breads/Paneer Kulcha.png',1,18,8],
  [219,'bread','Aloo Kulcha','आलू कुलचा','आलू कुलचा',90,'Breads/Aloo Kulcha.png',1,19,8],
  [220,'bread','Jowar Bhakri','ज्वार भाकरी','ज्वारीची भाकरी',40,'Breads/Jowar Bhakri.png',1,20,5],
  [221,'bread','Bajra Bhakri','बाजरा भाकरी','बाजरीची भाकरी',40,'Breads/Bajra Bhakri.png',1,21,5],

  // RICE & BIRYANI
  [301,'rice_biryani','Steamed Rice','स्टीम्ड राइस','स्टीम राईस',99,'Rice & Biryanies/Steamed Rice.png',1,1,10],
  [302,'rice_biryani','Jeera Rice','जीरा राइस','जीरा राईस',149,'Rice & Biryanies/Jeera Rice.png',1,2,10],
  [303,'rice_biryani','Veg Pulao','वेज पुलाव','व्हेज पुलाव',199,'Rice & Biryanies/Veg Pulao.png',1,3,15],
  [304,'rice_biryani','Peas Pulao','मटर पुलाव','मटार पुलाव',209,'Rice & Biryanies/Peas Pulao.png',1,4,15],
  [305,'rice_biryani','Paneer Pulao','पनीर पुलाव','पनीर पुलाव',249,'Rice & Biryanies/Paneer Pulao.png',1,5,15],
  [306,'rice_biryani','Kashmiri Pulao','कश्मीरी पुलाव','काश्मिरी पुलाव',269,'Rice & Biryanies/Kashmiri Pulao.png',1,6,15],
  [307,'rice_biryani','Tawa Pulao','तवा पुलाव','तवा पुलाव',219,'Rice & Biryanies/Tawa Pulao.png',1,7,15],
  [308,'rice_biryani','Veg Fried Rice','वेज फ्राइड राइस','व्हेज फ्राइड राईस',199,'Rice & Biryanies/Veg Fried Rice.png',1,8,15],
  [309,'rice_biryani','Schezwan Fried Rice','सेजवान फ्राइड राइस','शेजवान फ्राइड राईस',229,'Rice & Biryanies/Schezwan Fried Rice.png',1,9,15],
  [310,'rice_biryani','Paneer Fried Rice','पनीर फ्राइड राइस','पनीर फ्राइड राईस',249,'Rice & Biryanies/Paneer Fried Rice.png',1,10,15],
  [311,'rice_biryani','Veg Biryani','वेज बिरयानी','व्हेज बिर्याणी',249,'Rice & Biryanies/Veg Biryani.png',1,11,20],
  [312,'rice_biryani','Hyderabadi Veg Biryani','हैदराबादी वेज बिरयानी','हैदराबादी व्हेज बिर्याणी',279,'Rice & Biryanies/Hyderabadi Veg Biryani.png',1,12,20],
  [313,'rice_biryani','Paneer Biryani','पनीर बिरयानी','पनीर बिर्याणी',299,'Rice & Biryanies/Paneer Biryani.png',1,13,20],
  [314,'rice_biryani','Dum Veg Biryani','दम वेज बिरयानी','दम व्हेज बिर्याणी',289,'Rice & Biryanies/Dum Veg Biryani.png',1,14,20],
  [315,'rice_biryani','Mushroom Biryani','मशरूम बिरयानी','मशरूम बिर्याणी',299,'Rice & Biryanies/Mushroom Biryani.png',1,15,20],

  // DESSERTS
  [101,'dessert','Gulab Jamun (2 pcs)','गुलाब जामुन (2 नग)','गुलाब जामुन (२ नग)',79,'Desserts/Gulab Jamun (2 pcs).png',1,1,5],
  [102,'dessert','Kala Jamun (2 pcs)','काला जामुन (2 नग)','काळा जामुन (२ नग)',89,'Desserts/Kala Jamun (2 pcs).png',1,2,5],
  [103,'dessert','Rasmalai (2 pcs)','रसमलाई (2 नग)','रसमलाई (२ नग)',99,'Desserts/Rasmalai (2 pcs).png',1,3,5],
  [104,'dessert','Angoori Rasmalai','अंगूरी रसमलाई','अंगुरी रसमलाई',119,'Desserts/Angoori Rasmalai.png',1,4,5],
  [105,'dessert','Gajar Halwa','गाजर हलवा','गाजर हलवा',129,'Desserts/Gajar Halwa.png',1,5,8],
  [106,'dessert','Moong Dal Halwa','मूंग दाल हलवा','मुग डाळ हलवा',139,'Desserts/Moong Dal Halwa.png',1,6,8],
  [107,'dessert','Rabdi','रबड़ी','रबडी',129,'Desserts/Rabdi.png',1,7,5],
  [108,'dessert','Shrikhand','श्रीखंड','श्रीखंड',119,'Desserts/Shrikhand.png',1,8,5],
  [109,'dessert','Mango Shrikhand','मैंगो श्रीखंड','मँगो श्रीखंड',129,'Desserts/Mango Shrikhand (Seasonal).png',1,9,5],
  [110,'dessert','Fruit Salad','फ्रूट सलाद','फ्रुट सॅलड',119,'Desserts/Fruit Salad.png',1,10,5],
  [111,'dessert','Fruit Salad with Ice Cream','आइसक्रीम के साथ फ्रूट सलाद','आईस्क्रीमसह फ्रुट सॅलड',149,'Desserts/Fruit Salad with Ice Cream.png',1,11,8],
  [112,'dessert','Vanilla Ice Cream','वैनिला आइसक्रीम','व्हॅनिला आईस्क्रीम',79,'Desserts/Vanilla Ice Cream.png',1,12,2],
  [113,'dessert','Butterscotch Ice Cream','बटरस्कॉच आइसक्रीम','बटरस्कॉच आईस्क्रीम',89,'Desserts/Butterscotch Ice Cream.png',1,13,2],
  [114,'dessert','Chocolate Ice Cream','चॉकलेट आइसक्रीम','चॉकलेट आईस्क्रीम',89,'Desserts/Chocolate Ice Cream.png',1,14,2],
  [115,'dessert','Kesar Pista Ice Cream','केसर पिस्ता आइसक्रीम','केसर पिस्ता आईस्क्रीम',99,'Desserts/Kesar Pista Ice Cream.png',1,15,2],
  [116,'dessert','Brownie with Ice Cream','आइसक्रीम के साथ ब्राउनी','आईस्क्रीमसह ब्राउनी',179,'Desserts/Brownie with Ice Cream.png',1,16,8],
  [117,'dessert','Kulfi','कुल्फी','कुल्फी',99,'Desserts/Kulfi.png',1,17,5],
  [118,'dessert','Matka Kulfi','मटका कुल्फी','मटका कुल्फी',129,'Desserts/Matka Kulfi.png',1,18,5],

  // BEVERAGES (sort order grouped: hot brews, shakes&coffee, refreshers, mocktails)
  [617,'beverage','Tea','चाय','चहा',20,'Beverages/Tea.png',1,1,3],
  [618,'beverage','Masala Tea','मसाला चाय','मसाला चहा',25,'Beverages/Masala Tea.png',1,2,3],
  [619,'beverage','Coffee','कॉफी','कॉफी',25,'Beverages/Coffee.png',1,3,3],
  [620,'beverage','Cappuccino','कैपुचिनो','कॅपुचिनो',80,'Beverages/Cappuccino.png',1,4,5],
  [616,'beverage','Cold Coffee','कोल्ड कॉफी','कोल्ड कॉफी',80,'Beverages/Cold Coffee.png',1,5,5],
  [613,'beverage','Mango Milkshake','मैंगो मिल्कशेक','मँगो मिल्कशेक',90,'Beverages/Mango Milkshake.png',1,6,5],
  [614,'beverage','Chocolate Milkshake','चॉकलेट मिल्कशेक','चॉकलेट मिल्कशेक',100,'Beverages/Chocolate Milkshake.png',1,7,5],
  [615,'beverage','Vanilla Milkshake','वैनिला मिल्कशेक','व्हॅनिला मिल्कशेक',90,'Beverages/Vanilla Milkshake.png',1,8,5],
  [601,'beverage','Fresh Lime Soda','फ्रेश लाइम सोडा','फ्रेश लाईम सोडा',40,'Beverages/Fresh Lime Soda.png',1,9,3],
  [602,'beverage','Sweet Lime Soda','स्वीट लाइम सोडा','स्वीट लाईम सोडा',40,'Beverages/Sweet Lime Soda.png',1,10,3],
  [603,'beverage','Masala Chaas (Buttermilk)','मसाला छाछ','मसाला ताक',35,'Beverages/Masala Chaas (Buttermilk).png',1,11,3],
  [604,'beverage','Sweet Lassi','मीठी लस्सी','गोड लस्सी',60,'Beverages/Sweet Lassi.png',1,12,5],
  [605,'beverage','Mango Lassi','मैंगो लस्सी','मँगो लस्सी',70,'Beverages/Mango Lassi.png',1,13,5],
  [606,'beverage','Coconut Water','नारियल पानी','शहाळे पाणी',60,'Beverages/Coconut Water.png',1,14,3],
  [607,'beverage','Virgin Mojito','वर्जिन मोजितो','व्हर्जिन मोझिटो',90,'Beverages/Virgin Mojito.png',1,15,5],
  [608,'beverage','Blue Lagoon','ब्लू लैगून','ब्लू लगून',100,'Beverages/Blue Lagoon.png',1,16,5],
  [609,'beverage','Fresh Orange Juice','ताजा संतरे का जूस','संत्र्याचा ज्यूस',80,'Beverages/Fresh Orange Juice.png',1,17,5],
  [610,'beverage','Watermelon Juice','तरबूज का जूस','कलिंगड ज्यूस',70,'Beverages/Watermelon Juice.png',1,18,5],
  [611,'beverage','Pineapple Juice','अनानास का जूस','अननस ज्यूस',80,'Beverages/Pineapple Juice.png',1,19,5],
  [612,'beverage','Fruit Punch','फ्रूट पंच','फ्रूट पंच',100,'Beverages/Fruit Punch.png',1,20,5],

  // SIDE DISHES
  [501,'side_dish','Green Salad','ग्रीन सलाद','ग्रीन सॅलड',39,'Side Dishes/Green Salad.png',1,1,3],
  [502,'side_dish','Onion Salad','प्याज सलाद','कांदा कोशिंबीर',45,'Side Dishes/Onion Salad.png',1,2,3],
  [503,'side_dish','Cucumber Salad','खीरा सलाद','काकडी कोशिंबीर',49,'Side Dishes/Cucumber Salad.png',1,3,3],
  [504,'side_dish','Pickle & Chutney Combo','अचार और चटनी कॉम्बो','लोणचे आणि चटणी कॉम्बो',49,'Side Dishes/Pickle & Chutney Combo.png',1,4,3],
  [505,'side_dish','Veg Raita','वेज रायता','व्हेज रायता',59,'Side Dishes/Veg Raita.png',1,5,5],
  [506,'side_dish','Boondi Raita','बूंदी रायता','बुंदी रायता',65,'Side Dishes/Boondi Raita.png',1,6,5],
  [507,'side_dish','Plain Curd','प्लेन दही','साधे दही',49,'Side Dishes/Plain Curd.png',1,7,3],
  [508,'side_dish','Masala Curd','मसाला दही','मसाला दही',55,'Side Dishes/Masala Curd.png',1,8,3],

  // WATER
  [701,'water','Water Bottle (500 ml - Cold)','पानी की बोतल (500 मिली - ठंडी)','पाण्याची बाटली (500 मिली - थंड)',10,'Water Bottle/Water Bottle (500 ml - Cold).png',1,1,1],
  [702,'water','Water Bottle (500 ml - Normal)','पानी की बोतल (500 मिली - नॉर्मल)','पाण्याची बाटली (500 मिली - नॉर्मल)',10,'Water Bottle/Water Bottle (500 ml - Normal).png',1,2,1],
  [703,'water','Water Bottle (1 Litre - Cold)','पानी की बोतल (1 लीटर - ठंडी)','पाण्याची बाटली (1 लीटर - थंड)',20,'Water Bottle/Water Bottle (1 Litre - Cold).png',1,3,1],
  [704,'water','Water Bottle (1 Litre - Normal)','पानी की बोतल (1 लीटर - नॉर्मल)','पाण्याची बाटली (1 लीटर - नॉर्मल)',20,'Water Bottle/Water Bottle (1 Litre - Normal).png',1,4,1],

  // WELCOME DRINKS
  [801,'welcome_drink','Tea','चाय','चहा',15,'ALL Images/Tea_result.webp',1,1,3],
  [802,'welcome_drink','Lemon Tea','लेमन टी','लेमन टी',15,'ALL Images/Lemon Tea_result.webp',1,2,3],
  [803,'welcome_drink','Coffee','कॉफी','कॉफी',25,'ALL Images/Coffee_result.webp',1,3,3],
  [804,'welcome_drink','Milk','दूध','दूध',30,'ALL Images/Milk_result.webp',1,4,3],
  [805,'welcome_drink','Tak ( Plain / Masala )','ताक (सादा / मसाला)','ताक (प्लेन / मसाला)',30,'ALL Images/Tak_result.webp',1,5,3],
  [806,'welcome_drink','Lassi','लस्सी','लस्सी',40,'ALL Images/Lassi_result.webp',1,6,4],
  [807,'welcome_drink','Fresh Lime Soda','फ्रेश लाइम सोडा','फ्रेश लाईम सोडा',35,'ALL Images/Fresh Laim Soda_result.webp',1,7,3],
  [808,'welcome_drink','Mineral Water','मिनरल वाटर','मिनरल वॉटर',20,'ALL Images/Mineral Water_result.webp',1,8,1],
  [809,'welcome_drink','Cold Drink (200 ml)','कोल्ड ड्रिंक (200 मिली)','कोल्ड ड्रिंक (200 मिली)',20,'ALL Images/ColdDrinks 200 ml_result.webp',1,9,1],
  [810,'welcome_drink','Cold Drink (500 ml)','कोल्ड ड्रिंक (500 मिली)','कोल्ड ड्रिंक (500 मिली)',45,'ALL Images/ColdDrinks 500 ml_result.webp',1,10,1],
  [811,'welcome_drink','Cold Drink (750 ml / 1 L)','कोल्ड ड्रिंक (750 मिली / 1 ली)','कोल्ड ड्रिंक (750 मिली / 1 ली)',50,'ALL Images/ColdDrinks 1 L_result.webp',1,11,1],
  [812,'welcome_drink','Red Bull','रेड बुल','रेड बुल',130,'ALL Images/Red Bull_result.webp',1,12,1],
  [813,'welcome_drink','Sting','स्टिंग','स्टिंग',20,'ALL Images/Sting_result.webp',1,13,1],
  [814,'welcome_drink','Hell Energy Drink','हेल एनर्जी ड्रिंक','हेल एनर्जी ड्रिंक',60,'ALL Images/Hell_result.webp',1,14,1],

  // BREAKFAST
  [901,'breakfast','Pohe','पोहे','पोहे',50,'ALL Images/Pohe_result.webp',1,1,5],
  [902,'breakfast','Upit','उपीट (उपमा)','उपीट (उपमा)',60,'ALL Images/Upit_result.webp',1,2,5],
  [903,'breakfast','Sheera','शीरा (हलवा)','शिरा',50,'ALL Images/Sheera_result.webp',1,3,5],
  [904,'breakfast','Wada Pav','वड़ा पाव','वडा पाव',15,'ALL Images/Wada Pav_result.webp',1,4,3],
  [905,'breakfast','Misal Pav','मिसल पाव','मिसळ पाव',80,'ALL Images/Misal Pav_result.webp',1,5,8],
  [906,'breakfast','Wada Sample','वड़ा सॅम्पल','वडा सॅम्पल',70,'ALL Images/Wada Sample_result.webp',1,6,6],
  [907,'breakfast','Kadhi Wada','कढ़ी वड़ा','कढी वडा',20,'ALL Images/Kadhi Wada_result.webp',1,7,5],
  [908,'breakfast','Kanda Bhaji','कांदा भजी (प्याज़ पकोड़ा)','कांदा भजी',50,'ALL Images/Kanda Bhaji_result.webp',1,8,8],
  [909,'breakfast','Batata Bhaji','बटाटा भजी (आलू पकोड़ा)','बटाटा भजी',50,'ALL Images/Batata Bhaji_result.webp',1,9,8],
  [910,'breakfast','Idli Sambar','इडली सांभर','इडली सांबार',80,'ALL Images/Idli Sambar_result.webp',1,10,5],
  [911,'breakfast','Medu Wada','मेदू वड़ा','मेदू वडा',75,'ALL Images/Mendu Wada_result.webp',1,11,7],
  [912,'breakfast','Plain Dosa','प्लेन डोसा','प्लेन डोसा',80,'ALL Images/Plain dosa_result.webp',1,12,8],
  [913,'breakfast','Paper Plain Dosa','पेपर प्लेन डोसा','पेपर प्लेन डोसा',85,'ALL Images/Paper Plane Dosa_result.webp',1,13,8],
  [914,'breakfast','Masala Dosa','मसाला डोसा','मसाला डोसा',80,'ALL Images/Masala Dosa_result.webp',1,14,9],
  [915,'breakfast','Plain Uttappa','प्लेन उत्तपम','प्लेन उत्तप्पा',60,'ALL Images/Plain Uttappa_result.webp',1,15,8],
  [916,'breakfast','Masala Uttappa','मसाला उत्तपम','मसाला उत्तप्पा',80,'ALL Images/Masala Uttappa_result.webp',1,16,9],
  [917,'breakfast','Pav Bhaji','पाव भाजी','पाव भाजी',80,'ALL Images/Pav Bhaji_result.webp',1,17,10],
  [918,'breakfast','Cheese Butter Pav Bhaji','चीज़ बटर पाव भाजी','चीज बटर पाव भाजी',150,'ALL Images/Cheese Butter Pav Bhaji_result.webp',1,18,10],
  [919,'breakfast','Butter Pav','बटर पाव','बटर पाव',10,'ALL Images/Butter pav_result.webp',1,19,2],
  [920,'breakfast','Extra Pav','एक्स्ट्रा पाव','एक्स्ट्रा पाव',5,'ALL Images/Extra Pav_result.webp',1,20,1],
  [921,'breakfast','Matki Bhel','मटकी भेल','मटकी भेळ',50,'ALL Images/Matki Bhel_result.webp',1,21,5],
  [922,'breakfast','Oli Bhel','गीली भेल (ओली भेल)','ओली भेळ',60,'ALL Images/Oli Bhel_result.webp',1,22,5],
  [923,'breakfast','Sabudana Khichdi','साबूदाना खिचड़ी','साबुदाणा खिचडी',50,'ALL Images/Sabudana khichdi_result.webp',1,23,6],
  [924,'breakfast','Sabudana Wada','साबूदाना वड़ा','साबुदाणा वडा',60,'ALL Images/Sabudana Wada_result.webp',1,24,7],
  [925,'breakfast','Sabudana Kheer','साबूदाना खीर','साबुदाणा खीर',100,'ALL Images/Shabudana Kheer_result.webp',1,25,7],
  [926,'breakfast','Finger Chips','फिंगर चिप्स (फ्रेंच फ्राइज)','फिंगर चिप्स',60,'ALL Images/Finger Chips_result.webp',1,26,6],
];

/* ── 5) Insert with section + item_code ── */
$sql = "INSERT INTO menu_items
  (id, item_code, category, name_en, name_hi, name_mr, price, image_path, is_veg, is_available, sort_order, section, prep_time_min)
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?)";
$stmt = $pdo->prepare($sql);

$count = 0;
foreach ($items as [$id, $cat, $en, $hi, $mr, $price, $img, $veg, $sort, $prep]) {
    $section = sectionFor($id, $cat);
    $prefix = strtoupper(substr($cat, 0, 3));
    $code = $prefix . '-' . str_pad((string)$id, 3, '0', STR_PAD_LEFT);
    $stmt->execute([$id, $code, $cat, $en, $hi, $mr, $price, $img, $veg, $sort, $section, $prep]);
    $count++;
}

echo "<pre>Reset complete.\n$count items inserted with section values set.\nis_available = 1 for all items.</pre>";
