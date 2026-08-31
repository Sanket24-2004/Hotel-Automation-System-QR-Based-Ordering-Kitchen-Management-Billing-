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
        case 'starter':       return ($id <= 4 || $id == 25) ? 'Papads' : (($id >= 5 && $id <= 12) || ($id >= 21 && $id <= 24) || ($id >= 26 && $id <= 32) ? 'Tandoor Starters' : 'Chinese Starters');
        case 'main_course':   return $id <= 418 ? 'Paneer Dishes' : ($id <= 424 ? 'Sweet Dishes' : ($id <= 461 ? 'Indian Dishes' : 'Dal & Lentils'));
        case 'bread':         return $id <= 211 ? 'Rotis & Chapatis' : ($id <= 219 ? 'Naans & Kulchas' : ($id <= 225 ? 'Bhakris & Laccha Paratha' : 'Special Parathas'));
        case 'rice_biryani':  return $id <= 315 ? 'Rice & Pulao' : ($id <= 320 ? 'Biryani Special' : ($id <= 326 ? 'Chinese Fried Rice' : 'Dals & Accompaniments'));
        case 'dessert':        return $id <= 109 ? 'Cups & Scoops' : ($id <= 119 ? 'Cones & Softies' : ($id <= 123 ? 'Royal Kulfis' : 'Traditional Sweets'));
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
        case 'thali':          return 'Thali Special';
        case 'special_dishes': return $id <= 1026 ? 'Handi Special' : 'Special Dishes';
        case 'chinese':        return $id <= 1106 ? 'Chinese Fried Rice' : 'Noodles & Chinese Bhel';
        case 'side_dish':      return $id <= 506 ? 'Soups' : ($id <= 511 ? 'Raitas & Salads' : 'Papads & Accompaniments');
        case 'water':         return 'Premium Hydration';
    }
    return '';
}

/* ── 4) Items: [id, category, en, hi, mr, price, image, is_veg, sort_order, prep_time] ── */
$items = [
  // STARTERS - Papads
  [1,'starter','Roasted Papad','भुना पापड़','भाजलेला पापड',20,'ALL Images/Roasted Papad.webp',1,1,3],
  [2,'starter','Fried Papad','तला पापड़','तळलेला पापड',25,'ALL Images/Fried Papad.webp',1,2,3],
  [3,'starter','Masala Papad','मसाला पापड़','मसाला पापड',30,'ALL Images/Masala Papad.webp',1,3,4],
  [4,'starter','Cheese Masala Papad','चीज़ मसाला पापड़','चीज मसाला पापड',50,'ALL Images/Cheese Masala Papad.webp',1,4,4],
  [25,'starter','Nachni Papad','नाचनी पापड़','नाचणी पापड',40,'ALL Images/Roasted Papad.webp',1,5,3],

  // STARTERS - Tandoor Starters
  [5,'starter','Paneer Tikka','पनीर टिक्का','पनीर टिक्का',180,'ALL Images/Paneer Tikka.webp',1,6,12],
  [26,'starter','Paneer Achari','पनीर अचारी टिक्का','पनीर अचारी टिक्का',180,'ALL Images/paneer achari_result.webp',1,7,12],
  [27,'starter','Paneer Angara','पनीर अंगारा टिक्का','पनीर अंगारा टिक्का',200,'ALL Images/Paneer Angara._result.webp',1,8,12],
  [28,'starter','Paneer Pahadi','पनीर पहाड़ी टिक्का','पनीर पहाडी टिक्का',170,'ALL Images/Paneer Pahadi_result.webp',1,9,12],
  [29,'starter','Paneer Lasooni Tikka','पनीर लसूनी टिक्का','पनीर लसुणी टिक्का',180,'ALL Images/paneer lasooni tikka_result.webp',1,10,12],
  [30,'starter','Paneer Pudina Tikka','पनीर पुदीना टिक्का','पनीर पुदिना टिक्का',180,'ALL Images/Paneer Pudina Tikka_result.webp',1,11,12],
  [31,'starter','Paneer Banjara Tikka','पनीर बंजारा टिक्का','पनीर बंजारा टिक्का',190,'ALL Images/paneer banjara tikka_result.webp',1,12,12],
  [32,'starter','Paneer Sole Kebab','पनीर शोले कबाब','पनीर शोले कबाब',250,'ALL Images/paneer sole kabab_result.webp',1,13,14],
  [6,'starter','Paneer Malai Tikka','पनीर मलाई टिक्का','पनीर मलाई टिक्का',250,'ALL Images/Paneer Malai Tikka.webp',1,14,12],
  [7,'starter','Hariyali Paneer Tikka','हरियाली पनीर टिक्का','हिरवा पनीर टिक्का',250,'ALL Images/Hariyali Paneer Tikka.webp',1,15,12],
  [8,'starter','Hara Bhara Kebab','हरा भरा कबाब','हिरवा कबाब',180,'ALL Images/Hara Bhara Kebab.webp',1,16,10],
  [9,'starter','Veg Seekh Kebab','वेज सीख कबाब','व्हेज सीख कबाब',220,'ALL Images/Veg Seekh Kebab.webp',1,17,12],
  [10,'starter','Dahi Ke Kebab','दही के कबाब','दह्याचे कबाब',220,'ALL Images/Dahi Ke Kebab.webp',1,18,12],
  [11,'starter','Tandoori Mushroom','तंदूरी मशरूम','तंदूरी मशरूम',220,'ALL Images/Tandoori Mushroom.webp',1,19,12],
  [12,'starter','Stuffed Mushroom','स्टफ्ड मशरूम','भरलेले मशरूम',250,'ALL Images/Stuffed Mushroom.webp',1,20,12],
  [21,'starter','Soya Chaap Tikka','सोया चाप टिक्का','सोया चाप टिक्का',220,'ALL Images/Soya Chaap Tikka.webp',1,21,12],
  [22,'starter','Malai Chaap','मलाई चाप','मलाई चाप',220,'ALL Images/Malai Chaap.webp',1,22,12],
  [23,'starter','Tandoori Broccoli','तंदूरी ब्रोकली','तंदूरी ब्रोकोली',250,'ALL Images/Tandoori Broccoli.webp',1,23,12],
  [24,'starter','Veg Tandoori Platter','वेज तंदूरी प्लेटर','व्हेज तंदूरी प्लेटर',400,'ALL Images/Veg Tandoori Platter.webp',1,24,15],

  // STARTERS - Chinese Starters
  [16,'starter','Veg Manchurian Dry','वेज मंचूरियन ड्राय','व्हेज मंचूरियन ड्राय',110,'ALL Images/Veg Manchurian Dry.webp',1,25,10],
  [33,'starter','Veg Manchurian Gravy','वेज मंचूरियन ग्रेवी','व्हेज मंचूरियन ग्रेव्ही',140,'ALL Images/Veg Manchurian gravy_result.webp',1,26,10],
  [34,'starter','Veg 65','वेज 65','व्हेज ६५',150,'ALL Images/veg 65_result.webp',1,27,10],
  [17,'starter','Gobi 65','गोबी 65','फुलकोबी ६५',160,'ALL Images/Gobi 65.webp',1,28,10],
  [36,'starter','Gobi Manchurian','गोबी मंचूरियन','गोबी मंचूरियन',160,'ALL Images/Gobi Manchurian_result.webp',1,29,10],
  [35,'starter','Mushroom Chilly','मशरूम चिली','मशरूम चिली',160,'ALL Images/mushroom chilly_result.webp',1,30,10],
  [37,'starter','Baby Corn Mushroom','बेबी कॉर्न मशरूम','बेबी कॉर्न मशरूम',170,'ALL Images/Bebycorn mushroom_result.webp',1,31,10],
  [38,'starter','Soyabean Chilly','सोयाबीन चिली','सोयाबीन चिली',120,'ALL Images/Soyaben chilly_result.webp',1,32,8],
  [15,'starter','Paneer Chilly Dry','पनीर चिली ड्राय','पनीर चिली ड्राय',150,'ALL Images/Paneer chilly dry_result.webp',1,33,10],
  [39,'starter','Paneer Chilly Gravy','पनीर चिली ग्रेवी','पनीर चिली ग्रेव्ही',160,'ALL Images/Paneer chilly Gravy_result.webp',1,34,10],
  [40,'starter','Paneer Manchurian','पनीर मंचूरियन','पनीर मंचूरियन',160,'ALL Images/Paneer Manchurian_result.webp',1,35,10],
  [41,'starter','Paneer 65','पनीर 65','पनीर ६५',170,'ALL Images/Paneer 65_result.webp',1,36,10],
  [42,'starter','Paneer Crispy','पनीर क्रिस्पी','पनीर क्रिस्पी',170,'ALL Images/paneer crispy_result.webp',1,37,10],
  [43,'starter','Paneer Satay','पनीर साते','पनीर साते',180,'ALL Images/Paneer sate_result.webp',1,38,12],
  [44,'starter','Paneer Hot Pan','पनीर हॉट पैन','पनीर हॉट पॅन',180,'ALL Images/paneer hot pan_result.webp',1,39,12],
  [45,'starter','Paneer Spring Roll','पनीर स्प्रिंग रोल','पनीर स्प्रिंग रोल',190,'ALL Images/Paneer Spining Roll_result.webp',1,40,12],
  [46,'starter','Baby Corn Mushroom Salt & Pepper','बेबी कॉर्न मशरूम साल्ट पेपर','बेबी कॉर्न मशरूम सॉल्ट पेपर',190,'ALL Images/baby corn mushroom solt paper_result.webp',1,41,12],
  [20,'starter','Veg Crispy','वेज क्रिस्पी','व्हेज क्रिस्पी',160,'ALL Images/Veg Crispy.webp',1,42,10],
  [47,'starter','Veg Crunchy','वेज क्रंची','व्हेज क्रंची',160,'ALL Images/Veg crunchy_result.webp',1,43,10],
  [48,'starter','Paneer Crunchy','पनीर क्रंची','पनीर क्रंची',170,'ALL Images/paneer crunchy_result.webp',1,44,10],
  [49,'starter','Paneer Garlic','पनीर गार्लिक','पनीर गार्लिक',190,'ALL Images/paneer garlic_result.webp',1,45,12],
  [50,'starter','Paneer Schezwan','पनीर शेज़वान','पनीर शेजवान',180,'ALL Images/paneer Schezwan_result.webp',1,46,12],
  [51,'starter','Chinese Platter','चाइनीज प्लेटर','चायनीज प्लेटर',300,'ALL Images/Chinese plater_result.webp',1,47,15],
  [52,'starter','Veg Lollipop','वेज लॉलीपॉप','व्हेज लॉलीपॉप',160,'ALL Images/Veg lolypop_result.webp',1,48,10],
  [13,'starter','Crispy Corn','क्रिस्पी मक्का','क्रिस्पी कॉर्न',180,'ALL Images/Crispy Corn.webp',1,49,10],
  [14,'starter','Honey Chilli Potato','हनी चिली आलू','हनी चिली बटाटा',180,'ALL Images/Honey Chilli Potato.webp',1,50,10],
  [18,'starter','Spring Rolls','स्प्रिंग रोल','स्प्रिंग रोल',180,'ALL Images/Spring Rolls.webp',1,51,10],
  [19,'starter','Cheese Corn Balls','चीज़ मक्का बॉल','चीज कॉर्न बॉल',220,'ALL Images/Cheese Corn Balls.webp',1,52,10],

  // MAIN COURSE
  // ── 1. PANEER DISHES (sort 1..18) ──
  [401,'main_course','Paneer Masala','पनीर मसाला','पनीर मसाला',130.00,'ALL Images/paneer masala_result.webp',1,1,12],
  [402,'main_course','Paneer Tikka Masala','पनीर टिक्का मसाला','पनीर टिक्का मसाला',160.00,'ALL Images/Paneer Tikka Masala.webp',1,2,12],
  [403,'main_course','Paneer Tawa','पनीर तवा','पनीर तवा',180.00,'ALL Images/paneer Tawa_result.webp',1,3,12],
  [404,'main_course','Paneer Angara','पनीर अंगारा','पनीर अंगारा',180.00,'ALL Images/paneer angara_result.webp',1,4,12],
  [405,'main_course','Paneer Bhurji','पनीर भुर्जी','पनीर भुर्जी',170.00,'ALL Images/paneer bhurji_result.webp',1,5,10],
  [406,'main_course','Kaju Paneer','काजू पनीर','काजू पनीर',170.00,'ALL Images/Kaju Paneer_result.webp',1,6,12],
  [407,'main_course','Paneer Kadhai','पनीर कड़ाही','पनीर कढाई',170.00,'ALL Images/paneer kadai_result.webp',1,7,12],
  [408,'main_course','Palak Paneer','पालक पनीर','पालक पनीर',150.00,'ALL Images/Palak Paneer.webp',1,8,12],
  [409,'main_course','Mushroom Paneer','मशरूम पनीर','मशरूम पनीर',160.00,'ALL Images/mushroom paneer_result.webp',1,9,12],
  [410,'main_course','Mushroom Masala','मशरूम मसाला','मशरूम मसाला',150.00,'ALL Images/Mashroom Masala_result.webp',1,10,12],
  [411,'main_course','Lasuni Palak','लहसुनी पालक','लसूणी पालक',140.00,'ALL Images/lasuni palak_result.webp',1,11,10],
  [412,'main_course','Lasuni Palak Tadka','लहसुनी पालक तड़का','लसूणी पालक तडका',150.00,'ALL Images/Lasoni Palak Tadka_result.webp',1,12,10],
  [413,'main_course','Paneer Kolhapuri','पनीर कोल्हापुरी','पनीर कोल्हापुरी',150.00,'ALL Images/Paneer Kolhapuri_result.webp',1,13,12],
  [414,'main_course','Paneer Chatpata','पनीर चटपटा','पनीर चटपटा',170.00,'ALL Images/Paneer Chatpata_result.webp',1,14,12],
  [415,'main_course','Lasun Methi Fry','लहसुन मेथी फ्राई','लसूण मेथी फ्राय',150.00,'ALL Images/Lassun Methi fry_result.webp',1,15,10],
  [416,'main_course','Matar Paneer','मटर पनीर','मटार पनीर',150.00,'ALL Images/matar paneer_result.webp',1,16,12],
  [417,'main_course','Paneer Chingari','पनीर चिंगारी','पनीर चिंगारी',180.00,'ALL Images/Paneer Chingari_result.webp',1,17,12],
  [418,'main_course','Paneer Tufani','पनीर तूफानी','पनीर तुफानी',180.00,'ALL Images/Paneer Tufani_result.webp',1,18,12],

  // ── 2. SWEET DISHES (sort 19..24) ──
  [419,'main_course','Paneer Butter Masala','पनीर बटर मसाला','पनीर बटर मसाला',160.00,'ALL Images/Paneer Butter Masala.webp',1,19,12],
  [420,'main_course','Kaju Curry','काजू करी','काजू करी',170.00,'ALL Images/Kaju Curry.webp',1,20,12],
  [421,'main_course','Malai Kofta','मलाई कोफ्ता','मलाई कोफ्ता',200.00,'ALL Images/Malai Kofta.webp',1,21,12],
  [422,'main_course','Shahi Paneer','शाही पनीर','शाही पनीर',190.00,'ALL Images/Shahi Paneer.webp',1,22,12],
  [423,'main_course','Navratan Korma','नवरतन कोरमा','नवरत्न कोरमा',270.00,'ALL Images/Navratan Korma.webp',1,23,12],
  [424,'main_course','Methi Mutter Malai','मेथी मटर मलाई','मेथी मटार मलाई',190.00,'ALL Images/methi mutter malai_result.webp',1,24,12],

  // ── 3. INDIAN DISHES (sort 25..61) ──
  [425,'main_course','Shev Bhaji','शेव भाजी','शेव भाजी',100.00,'ALL Images/Shev Bhaji Kala masala_result.webp',1,25,10],
  [426,'main_course','Chana Masala','चना मसाला','चना मसाला',120.00,'ALL Images/Chana Masala.webp',1,26,10],
  [427,'main_course','Mataki Masala','मटकी मसाला','मटकी मसाला',130.00,'ALL Images/Mataki masala_result.webp',1,27,10],
  [428,'main_course','Baingan Masala','बैंगन मसाला','वांगी मसाला',130.00,'ALL Images/Baingan Masala_result.webp',1,28,10],
  [429,'main_course','Alu Mutter','आलू मटर','बटाटा मटार',130.00,'ALL Images/Alu mutter_result.webp',1,29,10],
  [430,'main_course','Green Peace Fry','ग्रीन पीस फ्राई','मटार फ्राय',140.00,'ALL Images/Green peace fry_result.webp',1,30,10],
  [431,'main_course','Plain Palak','सादा पालक','प्लेन पालक',130.00,'ALL Images/Plain Palak_result.webp',1,31,10],
  [432,'main_course','Shev Bhaji Kala Masala','शेव भाजी काला मसाला','शेव भाजी काळा मसाला',130.00,'ALL Images/Shev Bhaji Kala masala_result.webp',1,32,10],
  [433,'main_course','Green Peas Masala','ग्रीन पीस मसाला','मटार मसाला',140.00,'ALL Images/Green peas masala_result.webp',1,33,10],
  [434,'main_course','Green Peas Curry','ग्रीन पीस करी','मटार उसळ',130.00,'ALL Images/Green peas_result.webp',1,34,10],
  [435,'main_course','Bhendi Fry','भिंडी फ्राई','भेंडी फ्राय',140.00,'ALL Images/Bhendi fry_result.webp',1,35,10],
  [436,'main_course','Bhendi Masala','भिंडी मसाला','भेंडी मसाला',130.00,'ALL Images/Bhendi Masala_result.webp',1,36,10],
  [437,'main_course','Mataki Fry','मटकी फ्राई','मटकी फ्राय',140.00,'ALL Images/Matki fry_result.webp',1,37,10],
  [438,'main_course','Baingan Bharta','बैंगन भरता','वांग्याचे भरीत',150.00,'ALL Images/Baigan bharta_result.webp',1,38,10],
  [439,'main_course','Mix Veg','मिक्स वेज','व्हेज मिक्स भाजी',130.00,'ALL Images/Mix veg_result.webp',1,39,10],
  [440,'main_course','Veg Kolhapuri','वेज कोल्हापुरी','व्हेज कोल्हापुरी',140.00,'ALL Images/Veg Kolhapuri.webp',1,40,10],
  [441,'main_course','Shev Tamatar','शेव टमाटर','शेव टोमॅटो',130.00,'ALL Images/Shev Tamatar.webp',1,41,10],
  [442,'main_course','Pithala','पिठला','झणझणीत पिठलं',150.00,'ALL Images/Pithala_result.webp',1,42,10],
  [443,'main_course','Kaju Masala','काजू मसाला','काजू मसाला',150.00,'ALL Images/Kaju Masala_result.webp',1,43,12],
  [444,'main_course','Shevga Masala','शेवगा मसाला','शेवगा मसाला',140.00,'ALL Images/Shevga Masala_result.webp',1,44,10],
  [445,'main_course','Milk Shev Bhaji','मिल्क शेव भाजी','मिल्क शेव भाजी',140.00,'ALL Images/Milk shev Bhaji_result.webp',1,45,10],
  [446,'main_course','Veg Maratha','वेज मराठा','व्हेज मराठा',150.00,'ALL Images/Veg maratha_result.webp',1,46,12],
  [447,'main_course','Veg Bhuna','वेज भुना','व्हेज भुना',160.00,'ALL Images/Veg bhuna_result.webp',1,47,12],
  [448,'main_course','Veg Tawa','वेज तवा','व्हेज तवा मसाला',170.00,'ALL Images/Veg Tawa_result.webp',1,48,12],
  [449,'main_course','Veg Hariyali','वेज हरियाली','व्हेज हरियाली',180.00,'ALL Images/Veg Hariyali_result.webp',1,49,12],
  [450,'main_course','Veg Jaipuri','वेज जयपुरी','व्हेज जयपुरी',190.00,'ALL Images/Veg Jaypuri_result.webp',1,50,12],
  [451,'main_course','Veg Dum Aloo Punjabi','वेज दम आलू पंजाबी','व्हेज दम आलू पंजाबी',190.00,'ALL Images/Veg daam alu panjabi_result.webp',1,51,12],
  [452,'main_course','Veg Kadhai','वेज कड़ाही','व्हेज कढाई',170.00,'ALL Images/Veg Kadhai_result.webp',1,52,12],
  [453,'main_course','Methi Masala','मेथी मसाला','मेथी मसाला',120.00,'ALL Images/methi masala_result.webp',1,53,10],
  [454,'main_course','Veg Chilly Milly Masala','वेज चिली मिली मसाला','व्हेज चिली मिली मसाला',150.00,'ALL Images/veg chilly milly masala_result.webp',1,54,12],
  [455,'main_course','Tomato Chatani','टमाटर चटनी','टोमॅटो चटणी',140.00,'ALL Images/tomato chatini_result.webp',1,55,8],
  [456,'main_course','Soyabean Masala','सोयाबीन मसाला','सोयाबीन मसाला',120.00,'ALL Images/soyabin masala_result.webp',1,56,10],
  [457,'main_course','Jeera Aloo','जीरा आलू','जिरा बटाटा',140.00,'ALL Images/jeera aalu_result.webp',1,57,10],
  [458,'main_course','Aloo Masala','आलू मसाला','बटाटा मसाला',130.00,'ALL Images/aalu masala_result.webp',1,58,10],
  [459,'main_course','Akkha Masoor','अक्खा मसूर','अख्खा मसूर',120.00,'ALL Images/akka masur_result.webp',1,59,10],
  [460,'main_course','Veg Khasiyat','वेज खासियत','व्हेज खासियत',170.00,'ALL Images/veg khasiyat_result.webp',1,60,12],
  [461,'main_course','Aloo Gobi','आलू गोभी','बटाटा फ्लॉवर',150.00,'ALL Images/aalu gobi_result.webp',1,61,10],

  // ── 4. DAL & LENTILS (sort 62..68) ──
  [462,'main_course','Dal Tadka','दाल तड़का','डाळ तडका',100.00,'ALL Images/Dal Tadka.webp',1,62,8],
  [463,'main_course','Dal Fry','दाल फ्राई','डाळ फ्राय',95.00,'ALL Images/Dal Fry.webp',1,63,8],
  [464,'main_course','Jeera Dal','जीरा दाल','जिरा डाळ',110.00,'ALL Images/Dal Fry.webp',1,64,8],
  [465,'main_course','Butter Dal','बटर दाल','बटर डाळ',120.00,'ALL Images/Dal Fry.webp',1,65,8],
  [466,'main_course','Palak Dal','पालक दाल','पालक डाळ',130.00,'ALL Images/Dal Tadka.webp',1,66,8],
  [467,'main_course','Dal Makhani','दाल मखनी','दाल मखनी',250.00,'ALL Images/Dal Makhani.webp',1,67,10],
  [468,'main_course','Lasuni Dal','लहसुनी दाल','लसूणी डाळ',140.00,'ALL Images/Dal Tadka.webp',1,68,8],

  // BREADS
  // ── 1. ROTIS & CHAPATIS (sort 1..11) ──
  [201,'bread','Plain Roti','सादी रोटी','साधी पोळी',10.00,'ALL Images/Breads_Roti.webp',1,1,3],
  [202,'bread','Butter Roti','बटर रोटी','बटर पोळी',20.00,'ALL Images/Butter roti.webp',1,2,3],
  [203,'bread','Chapati','चपाती','घरगुती चपाती',15.00,'ALL Images/Chapati.webp',1,3,3],
  [204,'bread','Butter Chapati','बटर चपाती','बटर चपाती',25.00,'ALL Images/Chapati.webp',1,4,3],
  [205,'bread','Gehu Roti (Wheat)','गेहूं रोटी','गव्हाची पोळी',15.00,'ALL Images/Breads_Roti.webp',1,5,3],
  [206,'bread','Butter Gehu Roti','बटर गेहूं रोटी','बटर गव्हाची पोळी',20.00,'ALL Images/Butter roti.webp',1,6,3],
  [207,'bread','Tandoori Roti','तंदूरी रोटी','तंदूरी रोटी',25.00,'ALL Images/Tandoori Roti.webp',1,7,4],
  [208,'bread','Butter Tandoori Roti','बटर तंदूरी रोटी','बटर तंदूरी रोटी',35.00,'ALL Images/Butter Tandoori Roti.webp',1,8,4],
  [209,'bread','Rumali Roti','रुमाली रोटी','रुमाली रोटी',40.00,'ALL Images/Rumali Roti.webp',1,9,5],
  [210,'bread','Missi Roti','मिस्सी रोटी','मिस्सी रोटी',20.00,'ALL Images/Missi Roti.webp',1,10,5],
  [211,'bread','Butter Missi Roti','बटर मिस्सी रोटी','बटर मिस्सी रोटी',25.00,'ALL Images/Missi Roti.webp',1,11,5],

  // ── 2. NAANS & KULCHAS (sort 12..19) ──
  [212,'bread','Plain Naan','सादा नान','साधा नान',40.00,'ALL Images/Plain Naan.webp',1,12,5],
  [213,'bread','Butter Naan','बटर नान','बटर नान',45.00,'ALL Images/Butter Naan.webp',1,13,5],
  [214,'bread','Garlic Naan','लहसुन नान','लसूण नान',60.00,'ALL Images/Garlic Naan.webp',1,14,5],
  [215,'bread','Butter Garlic Naan','बटर लहसुन नान','बटर लसूण नान',70.00,'ALL Images/Garlic Butter Naan.webp',1,15,5],
  [216,'bread','Cheese Naan','चीज़ नान','चीज नान',40.00,'ALL Images/Cheese Naan.webp',1,16,5],
  [217,'bread','Cheese Garlic Naan','चीज़ लहसुन नान','चीज लसूण नान',50.00,'ALL Images/Cheese Naan.webp',1,17,5],
  [218,'bread','Plain Kulcha','सादा कुलचा','साधा कुलचा',40.00,'ALL Images/Kulcha.webp',1,18,5],
  [219,'bread','Butter Kulcha','बटर कुलचा','बटर कुलचा',50.00,'ALL Images/Butter Kulcha.webp',1,19,5],

  // ── 3. BHAKRIS & LACCHA PARATHA (sort 20..25) ──
  [220,'bread','Jowar Bhakri','ज्वार भाकरी','ज्वारीची भाकरी',20.00,'ALL Images/Jowar Bhakri.webp',1,20,5],
  [221,'bread','Butter Jowar Bhakri','बटर ज्वार भाकरी','बटर ज्वारीची भाकरी',30.00,'ALL Images/Jowar Bhakri.webp',1,21,5],
  [222,'bread','Bajra Bhakri','बाजरा भाकरी','बाजरीची भाकरी',20.00,'ALL Images/Bajra Bhakri.webp',1,22,5],
  [223,'bread','Butter Bajra Bhakri','बटर बाजरा भाकरी','बटर बाजरीची भाकरी',30.00,'ALL Images/Bajra Bhakri.webp',1,23,5],
  [224,'bread','Laccha Paratha','लच्छा पराठा','लच्छा पराठा',60.00,'ALL Images/Multigrain Roti.webp',1,24,5],
  [225,'bread','Butter Laccha Paratha','बटर लच्छा पराठा','बटर लच्छा पराठा',70.00,'ALL Images/Multigrain Roti.webp',1,25,5],

  // ── 4. SPECIAL PARATHAS (sort 26..32) ──
  [226,'bread','Aloo Paratha','आलू पराठा','बटाटा पराठा',80.00,'ALL Images/Aloo paratha._result.webp',1,26,8],
  [227,'bread','Gobi Paratha','गोभी पराठा','फ्लॉवर पराठा',80.00,'ALL Images/Gobi Paratha_result.webp',1,27,8],
  [228,'bread','Paneer Paratha','पनीर पराठा','पनीर पराठा',100.00,'ALL Images/paneer paratha_result.webp',1,28,8],
  [229,'bread','Kashmiri Paratha','कश्मीरी पराठा','काश्मिरी पराठा',90.00,'ALL Images/Kashmiri paratha_result.webp',1,29,8],
  [230,'bread','Mix Paratha','मिक्स पराठा','मिक्स पराठा',90.00,'ALL Images/mix paratha._result.webp',1,30,8],
  [231,'bread','Methi Paratha','मेथी पराठा','मेथी पराठा',90.00,'ALL Images/Methi paratha_result.webp',1,31,8],
  [232,'bread','Thecha Paratha','ठेचा पराठा','झणझणीत ठेचा पराठा',70.00,'ALL Images/Thecha Paratha_result.webp',1,32,8],

  // RICE & BIRYANI
  // ── 1. RICE & PULAO (sort 1..15) ──
  [301,'rice_biryani','Plain Rice (Full)','सादा चावल (फुल)','स्टीम राईस (फुल)',90.00,'ALL Images/Steamed Rice.webp',1,1,8],
  [302,'rice_biryani','Plain Rice (Half)','सादा चावल (हाफ)','स्टीम राईस (हाफ)',50.00,'ALL Images/Steamed Rice (Half)_result.webp',1,2,8],
  [303,'rice_biryani','Jeera Rice (Full)','जीरा राइस (फुल)','जिरा राईस (फुल)',100.00,'ALL Images/Jeera Rice.webp',1,3,8],
  [304,'rice_biryani','Jeera Rice (Half)','जीरा राइस (हाफ)','जिरा राईस (हाफ)',50.00,'ALL Images/Jeera Rice (Half)_result.webp',1,4,8],
  [305,'rice_biryani','Dal Khichadi','दाल खिचड़ी','गरमागरम डाळ खिचडी',130.00,'ALL Images/dal khichadi_result.webp',1,5,12],
  [306,'rice_biryani','Curd Rice','दही भात','दही भात',160.00,'ALL Images/curd rice_result.webp',1,6,10],
  [307,'rice_biryani','Masala Rice','मसाला राइस','मसाले भात',150.00,'ALL Images/masala rice_result.webp',1,7,12],
  [308,'rice_biryani','Butter Jeera Rice','बटर जीरा राइस','बटर जिरा राईस',130.00,'ALL Images/butter jeera rice_result.webp',1,8,10],
  [309,'rice_biryani','Veg Pulao','वेज पुलाव','व्हेज पुलाव',140.00,'ALL Images/Veg Pulao.webp',1,9,12],
  [310,'rice_biryani','Paneer Pulao','पनीर पुलाव','पनीर पुलाव',160.00,'ALL Images/Paneer Pulao.webp',1,10,12],
  [311,'rice_biryani','Green Peas Pulao','मटर पुलाव','मटार पुलाव',150.00,'ALL Images/Peas Pulao.webp',1,11,12],
  [312,'rice_biryani','Jeera Pulao','जीरा पुलाव','जिरा पुलाव',150.00,'ALL Images/jeera pulao_result.webp',1,12,12],
  [313,'rice_biryani','Kashmiri Pulao','कश्मीरी पुलाव','काश्मिरी पुलाव',160.00,'ALL Images/Kashmiri Pulao.webp',1,13,12],
  [314,'rice_biryani','Lemon Rice','लेमन राइस','लेमन राईस',140.00,'ALL Images/lemon rice_result.webp',1,14,10],
  [315,'rice_biryani','Cheese Peas Pulao','चीज़ मटर पुलाव','चीज मटार पुलाव',180.00,'ALL Images/cheese piece pulao_result.webp',1,15,12],

  // ── 2. BIRYANI SPECIAL (sort 16..20) ──
  [316,'rice_biryani','Veg Biryani','वेज बिरयानी','व्हेज बिर्याणी',150.00,'ALL Images/Veg Biryani.webp',1,16,15],
  [317,'rice_biryani','Veg Dum Biryani','दम वेज बिरयानी','दम व्हेज बिर्याणी',160.00,'ALL Images/Dum Veg Biryani.webp',1,17,15],
  [318,'rice_biryani','Paneer Tikka Biryani','पनीर टिक्का बिरयानी','पनीर टिक्का बिर्याणी',170.00,'ALL Images/Paneer Biryani.webp',1,18,15],
  [319,'rice_biryani','Paneer Dum Biryani','पनीर दम बिरयानी','पनीर दम बिर्याणी',170.00,'ALL Images/Paneer Biryani.webp',1,19,15],
  [320,'rice_biryani','Paneer Biryani','पनीर बिरयानी','पनीर बिर्याणी',160.00,'ALL Images/Paneer Biryani.webp',1,20,15],

  // ── 3. CHINESE FRIED RICE (sort 21..26) ──
  [321,'rice_biryani','Veg Fried Rice','वेज फ्राइड राइस','व्हेज फ्राइड राईस',140.00,'ALL Images/Veg Fried Rice.webp',1,21,12],
  [322,'rice_biryani','Schezwan Fried Rice','सेजवान फ्राइड राइस','शेजवान फ्राइड राईस',150.00,'ALL Images/Schezwan Fried Rice.webp',1,22,12],
  [323,'rice_biryani','Mushroom Fried Rice','मशरूम फ्राइड राइस','मशरूम फ्राइड राईस',160.00,'ALL Images/Rice_and_Biryani.webp',1,23,12],
  [324,'rice_biryani','Veg Triple Schezwan Rice','वेज ट्रिपल सेजवान राइस','व्हेज ट्रिपल शेजवान राईस',180.00,'ALL Images/Schezwan Fried Rice.webp',1,24,15],
  [325,'rice_biryani','Veg Hongkong Rice','वेज हांगकांग राइस','व्हेज हाँगकाँग राईस',200.00,'ALL Images/Veg Fried Rice.webp',1,25,15],
  [326,'rice_biryani','Singapore Rice','सिंगापुर राइस','सिंगापूर राईस',190.00,'ALL Images/Rice_and_Biryani.webp',1,26,15],

  // ── 4. DALS & ACCOMPANIMENTS (sort 27..37) ──
  [327,'rice_biryani','Dal Tadka','दाल तड़का','डाळ तडका',100.00,'ALL Images/Dal Tadka.webp',1,27,8],
  [328,'rice_biryani','Dal Fry','दाल फ्राई','डाळ फ्राय',95.00,'ALL Images/Dal Fry.webp',1,28,8],
  [329,'rice_biryani','Jeera Dal','जीरा दाल','जिरा डाळ',110.00,'ALL Images/jeera dal_result.webp',1,29,8],
  [330,'rice_biryani','Butter Dal','बटर दाल','बटर डाळ',120.00,'ALL Images/butter dal_result.webp',1,30,8],
  [331,'rice_biryani','Palak Dal','पालक दाल','पालक डाळ',130.00,'ALL Images/Palak Dal_result.webp',1,31,8],
  [332,'rice_biryani','Dal Makhani','दाल मखनी','डाळ मखनी',250.00,'ALL Images/Dal Makhani.webp',1,32,10],
  [333,'rice_biryani','Lasuni Dal','लहसुनी दाल','लसूणी डाळ',140.00,'ALL Images/Lasooni dal._result.webp',1,33,8],
  [334,'rice_biryani','Dal Vati','दाल वाटी','डाळ वाटी',30.00,'ALL Images/Dal wati_result.webp',1,34,3],
  [335,'rice_biryani','Tup Vati','तूप वाटी','तूप वाटी',40.00,'ALL Images/Tup Wati_result.webp',1,35,2],
  [336,'rice_biryani','Tadka Vati','तड़का वाटी','तडका वाटी',30.00,'ALL Images/tadka wati_result.webp',1,36,3],
  [337,'rice_biryani','Rassa Plate','रस्सा प्लेट','झणझणीत रस्सा प्लेट',80.00,'ALL Images/Rassa plate_result.webp',1,37,5],

  // DESSERTS & ICE CREAMS
  // ── 1. CUPS & SCOOPS (sort 1..9) ──
  [101,'dessert','Rajbhog Ice Cream (Cup)','राजभोग आइसक्रीम (कप)','राजभोग आईस्क्रीम (कप)',60.00,'ALL Images/Rajbhog_result.webp',1,1,2],
  [102,'dessert','Sitaphal Ice Cream (Cup)','सीताफल आइसक्रीम (कप)','सीताफळ आईस्क्रीम (कप)',50.00,'ALL Images/Sitaphal Ice Cream_result.webp',1,2,2],
  [103,'dessert','Manpasand Ice Cream (Cup)','मनपसंद आइसक्रीम (कप)','मनपसंत आईस्क्रीम (कप)',25.00,'ALL Images/Manpasand Ice Cream_result.webp',1,3,2],
  [104,'dessert','Vanilla Ice Cream (Cup)','वैनिला आइसक्रीम (कप)','व्हॅनिला आईस्क्रीम (कप)',35.00,'ALL Images/Vanilla Ice Cream_result.webp',1,4,2],
  [105,'dessert','Butterscotch Ice Cream (Cup)','बटरस्कॉच आइसक्रीम (कप)','बटरस्कॉच आईस्क्रीम (कप)',40.00,'ALL Images/Butterscotch Ice Cream_result.webp',1,5,2],
  [106,'dessert','Mango Ice Cream (Cup)','मैंगो आइसक्रीम (कप)','मँगो आईस्क्रीम (कप)',40.00,'ALL Images/Mango Ice Cream_result.webp',1,6,2],
  [107,'dessert','Vanilla Ice Cream (Small Cup)','वैनिला आइसक्रीम (छोटा कप)','व्हॅनिला आईस्क्रीम (लहान कप)',20.00,'ALL Images/Vanilla Ice Cream_result.webp',1,7,2],
  [108,'dessert','Pista Ice Cream (Small Cup)','पिस्ता आइसक्रीम (छोटा कप)','पिस्ता आईस्क्रीम (लहान कप)',20.00,'ALL Images/Vanilla Ice Cream_result.webp',1,8,2],
  [109,'dessert','Strawberry Ice Cream (Small Cup)','स्ट्रॉबेरी आइसक्रीम (छोटा कप)','स्ट्रॉबेरी आईस्क्रीम (लहान कप)',20.00,'ALL Images/strawberry ice cream - cone_result.webp',1,9,2],

  // ── 2. CONES & SOFTIES (sort 10..19) ──
  [110,'dessert','Vanilla Ice Cream (Cone)','वैनिला आइसक्रीम (कोन)','व्हॅनिला आईस्क्रीम (कोन)',40.00,'ALL Images/Vanilla Ice Cream - cone_result.webp',1,10,2],
  [111,'dessert','Pista Ice Cream (Cone)','पिस्ता आइसक्रीम (कोन)','पिस्ता आईस्क्रीम (कोन)',40.00,'ALL Images/Vanilla Ice Cream - cone_result.webp',1,11,2],
  [112,'dessert','Butterscotch Ice Cream (Cone)','बटरस्कॉच आइसक्रीम (कोन)','बटरस्कॉच आईस्क्रीम (कोन)',40.00,'ALL Images/Butterscotch ice Cream - cone_result.webp',1,12,2],
  [113,'dessert','Chocolate Ice Cream (Cone)','चॉकलेट आइसक्रीम (कोन)','चॉकलेट आईस्क्रीम (कोन)',40.00,'ALL Images/Chocolate Ice Cream - cone_result.webp',1,13,2],
  [114,'dessert','Choco Pop Ice Cream (Cone)','चोको पॉप आइसक्रीम (कोन)','चोको पॉप आईस्क्रीम (कोन)',40.00,'ALL Images/Choco Pop ice cream - cone_result.webp',1,14,2],
  [115,'dessert','Strawberry Ice Cream (Cone)','स्ट्रॉबेरी आइसक्रीम (कोन)','स्ट्रॉबेरी आईस्क्रीम (कोन)',40.00,'ALL Images/strawberry ice cream - cone_result.webp',1,15,2],
  [116,'dessert','Wine Orange Ice Cream (Cone)','वाइन ऑरेंज आइसक्रीम (कोन)','वाईन ऑरेंज आईस्क्रीम (कोन)',40.00,'ALL Images/Wine orange - cone_result.webp',1,16,2],
  [117,'dessert','Chocolate Softy','चॉकलेट सॉफ्टी','चॉकलेट सॉफ्टी',45.00,'ALL Images/Chocolate Softy_result.webp',1,17,2],
  [118,'dessert','Mango Softy','मैंगो सॉफ्टी','मँगो सॉफ्टी',20.00,'ALL Images/Mango Softy_result.webp',1,18,2],
  [119,'dessert','Blackcurrant Softy','ब्लैककरेंट सॉफ्टी','ब्लॅककरंट सॉफ्टी',50.00,'ALL Images/Blackcurrant Softy_result.webp',1,19,2],

  // ── 3. ROYAL KULFIS (sort 20..23) ──
  [120,'dessert','Matka Kulfi','मटका कुल्फी','मटका कुल्फी',50.00,'ALL Images/Matka Kulfi_result.webp',1,20,2],
  [121,'dessert','Dry Fruit Kulfi','ड्राई फ्रूट कुल्फी','ड्रायफ्रूट कुल्फी',30.00,'ALL Images/Dry Fruit Kulfi_result.webp',1,21,2],
  [122,'dessert','Mawa Kulfi','मावा कुल्फी','मावा कुल्फी',30.00,'ALL Images/Mawa Kulfi_result.webp',1,22,2],
  [123,'dessert','Chocobar Kulfi','चोकोबार','चोकोबार',20.00,'ALL Images/Chocobar Kulfi_result.webp',1,23,2],

  // ── 4. TRADITIONAL SWEETS (sort 24..27) ──
  [124,'dessert','Gulab Jamun (2 pcs)','गुलाब जामुन (2 नग)','गुलाब जामुन (२ नग)',80.00,'ALL Images/Dessert_Rasmalai.webp',1,24,2],
  [125,'dessert','Rasmalai (2 pcs)','रसमलाई (2 नग)','रसमलाई (२ नग)',100.00,'ALL Images/Dessert_Rasmalai.webp',1,25,2],
  [126,'dessert','Gajar Halwa','गाजर का हलवा','गाजर हलवा',120.00,'ALL Images/Dessert_Rasmalai.webp',1,26,2],
  [127,'dessert','Rabdi','रबड़ी','रबडी',120.00,'ALL Images/Dessert_Rasmalai.webp',1,27,2],

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

  // THALI SPECIAL
  [951,'thali','Veg Thali','वेज थाली','व्हेज थाळी',160,'ALL Images/Veg Thali_result.webp',1,1,15],
  [952,'thali','Special Veg Thali','स्पेशल वेज थाली','स्पेशल व्हेज थाळी',180,'ALL Images/Special Veg Thali_result.webp',1,2,15],
  [953,'thali','Gujarati Thali','गुजराती थाली','गुजराती थाळी',200,'ALL Images/Gujarati Thali_result.webp',1,3,15],
  [954,'thali','Punjabi Thali','पंजाबी थाली','पंजाबी थाळी',190,'ALL Images/Panjabi Thali_result.webp',1,4,15],
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
