<?php
/**
 * sync_webp_images.php — Hotel Tulsi
 * Accurately maps all menu items in MySQL database to their respective WebP images in 'ALL Images/'
 */
declare(strict_types=1);
require_once __DIR__ . '/db.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $pdo = getDB();
    $items = $pdo->query("SELECT id, item_code, name_en, category, image_path FROM menu_items ORDER BY id ASC")->fetchAll();

    // Scan all image files in ALL Images
    $imgDir = __DIR__ . '/../ALL Images';
    $files = scandir($imgDir);
    $imgMap = []; // lowercase stripped key => actual relative path

    foreach ($files as $f) {
        if ($f === '.' || $f === '..' || is_dir($imgDir . '/' . $f)) continue;
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, ['webp', 'png', 'jpg', 'jpeg'])) continue;

        $base = pathinfo($f, PATHINFO_FILENAME);
        $normKey = strtolower(preg_replace('/[^a-z0-9]/i', '', $base));
        $imgMap[$normKey] = 'ALL Images/' . $f;
    }

    // Specific manual overrides for fuzzy names
    $customOverrides = [
        'water bottle (500 ml - cold)'   => 'ALL Images/Water Bottle (500 ml - Cold) new.webp',
        'water bottle (500 ml - normal)' => 'ALL Images/Water Bottle (500 ml - Normal) new.webp',
        'water bottle (1 litre - cold)'  => 'ALL Images/Water Bottle (1 Litre - Cold) new.webp',
        'water bottle (1 litre - normal)'=> 'ALL Images/Water Bottle (1 Litre - Normal) new.webp',
        'water bottle (500ml cold)'      => 'ALL Images/Water Bottle (500 ml - Cold) new.webp',
        'water bottle (500ml normal)'    => 'ALL Images/Water Bottle (500 ml - Normal) new.webp',
        'water bottle (1l cold)'         => 'ALL Images/Water Bottle (1 Litre - Cold) new.webp',
        'water bottle (1l normal)'       => 'ALL Images/Water Bottle (1 Litre - Normal) new.webp',
        'mango shrikhand'                => 'ALL Images/Mango Shrikhand (Seasonal).webp',
        'mango shrikhand (seasonal)'     => 'ALL Images/Mango Shrikhand (Seasonal).webp',
        'plain roti'                     => 'ALL Images/Breads_Roti.webp',
        'butter roti'                    => 'ALL Images/Butter roti.webp',
        'spring roll'                    => 'ALL Images/Spring Rolls.webp',
        'spring rolls'                   => 'ALL Images/Spring Rolls.webp',
        'gulab jamun'                    => 'ALL Images/Gulab Jamun (2 pcs).webp',
        'gulab jamun (2 pcs)'            => 'ALL Images/Gulab Jamun (2 pcs).webp',
        'kala jamun'                     => 'ALL Images/Kala Jamun (2 pcs).webp',
        'kala jamun (2 pcs)'             => 'ALL Images/Kala Jamun (2 pcs).webp',
        'rasmalai'                       => 'ALL Images/Rasmalai (2 pcs).webp',
        'rasmalai (2 pcs)'               => 'ALL Images/Rasmalai (2 pcs).webp',
        'masala chaas'                   => 'ALL Images/Masala Chaas (Buttermilk).webp',
        'masala chaas (buttermilk)'      => 'ALL Images/Masala Chaas (Buttermilk).webp',
        'sweet lassi'                    => 'ALL Images/Sweet Lassi.webp',
        'roasted papad'                  => 'ALL Images/Roasted Papad.webp',
        'fried papad'                    => 'ALL Images/Fried Papad.webp',
        'masala papad'                   => 'ALL Images/Masala Papad.webp',
        'cheese masala papad'            => 'ALL Images/Cheese Masala Papad.webp',
        'paneer tikka'                   => 'ALL Images/Paneer Tikka.webp',
        'paneer malai tikka'             => 'ALL Images/Paneer Malai Tikka.webp',
        'hariyali paneer tikka'          => 'ALL Images/Hariyali Paneer Tikka.webp',
        'hara bhara kebab'               => 'ALL Images/Hara Bhara Kebab.webp',
        'veg seekh kebab'                => 'ALL Images/Veg Seekh Kebab.webp',
        'dahi ke kebab'                  => 'ALL Images/Dahi Ke Kebab.webp',
        'tandoori mushroom'              => 'ALL Images/Tandoori Mushroom.webp',
        'stuffed mushroom'               => 'ALL Images/Stuffed Mushroom.webp',
        'crispy corn'                    => 'ALL Images/Crispy Corn.webp',
        'honey chilli potato'            => 'ALL Images/Honey Chilli Potato.webp',
        'chilli paneer dry'              => 'ALL Images/Chilli Paneer Dry.webp',
        'veg manchurian dry'             => 'ALL Images/Veg Manchurian Dry.webp',
        'gobi 65'                        => 'ALL Images/Gobi 65.webp',
        'cheese corn balls'              => 'ALL Images/Cheese Corn Balls.webp',
        'veg crispy'                     => 'ALL Images/Veg Crispy.webp',
        'soya chaap tikka'               => 'ALL Images/Soya Chaap Tikka.webp',
        'malai chaap'                    => 'ALL Images/Malai Chaap.webp',
        'tandoori broccoli'              => 'ALL Images/Tandoori Broccoli.webp',
        'veg tandoori platter'           => 'ALL Images/Veg Tandoori Platter.webp',
        'lemon tea'                      => 'ALL Images/Lemon Tea_result.webp',
        'milk'                           => 'ALL Images/Milk_result.webp',
        'tak ( plain / masala )'         => 'ALL Images/Tak_result.webp',
        'tak'                            => 'ALL Images/Tak_result.webp',
        'taak'                           => 'ALL Images/Tak_result.webp',
        'fresh lime soda'                => 'ALL Images/Fresh Laim Soda_result.webp',
        'fresh laim soda'                => 'ALL Images/Fresh Laim Soda_result.webp',
        'mineral water'                  => 'ALL Images/Mineral Water_result.webp',
        'cold drink (200 ml)'            => 'ALL Images/ColdDrinks 200 ml_result.webp',
        'cold drink (500 ml)'            => 'ALL Images/ColdDrinks 500 ml_result.webp',
        'cold drink (750 ml / 1 l)'      => 'ALL Images/ColdDrinks 1 L_result.webp',
        'red bull'                       => 'ALL Images/Red Bull_result.webp',
        'sting'                          => 'ALL Images/Sting_result.webp',
        'hell'                           => 'ALL Images/Hell_result.webp',
        'hell energy drink'              => 'ALL Images/Hell_result.webp',
        'medu wada'                      => 'ALL Images/Mendu Wada_result.webp',
        'mendu wada'                     => 'ALL Images/Mendu Wada_result.webp',
        'plain dosa'                     => 'ALL Images/Plain dosa_result.webp',
        'paper plain dosa'               => 'ALL Images/Paper Plane Dosa_result.webp',
        'butter pav'                     => 'ALL Images/Butter pav_result.webp',
        'sabudana khichdi'               => 'ALL Images/Sabudana khichdi_result.webp',
        'sabudana kheer'                 => 'ALL Images/Shabudana Kheer_result.webp',
        'shabudana kheer'                => 'ALL Images/Shabudana Kheer_result.webp',
        'paneer achari'                  => 'ALL Images/paneer achari_result.webp',
        'paneer angara'                  => 'ALL Images/Paneer Angara._result.webp',
        'paneer pahadi'                  => 'ALL Images/Paneer Pahadi_result.webp',
        'paneer lasooni tikka'           => 'ALL Images/paneer lasooni tikka_result.webp',
        'paneer pudina tikka'            => 'ALL Images/Paneer Pudina Tikka_result.webp',
        'paneer banjara tikka'           => 'ALL Images/paneer banjara tikka_result.webp',
        'paneer sole kebab'              => 'ALL Images/paneer sole kabab_result.webp',
        'paneer sole kabab'              => 'ALL Images/paneer sole kabab_result.webp',
        'veg manchurian gravy'           => 'ALL Images/Veg Manchurian gravy_result.webp',
        'veg 65'                         => 'ALL Images/veg 65_result.webp',
        'mushroom chilly'                => 'ALL Images/mushroom chilly_result.webp',
        'gobi manchurian'                => 'ALL Images/Gobi Manchurian_result.webp',
        'baby corn mushroom'             => 'ALL Images/Bebycorn mushroom_result.webp',
        'soyabean chilly'                => 'ALL Images/Soyaben chilly_result.webp',
        'paneer chilly dry'              => 'ALL Images/Paneer chilly dry_result.webp',
        'paneer chilly gravy'            => 'ALL Images/Paneer chilly Gravy_result.webp',
        'paneer manchurian'              => 'ALL Images/Paneer Manchurian_result.webp',
        'paneer 65'                      => 'ALL Images/Paneer 65_result.webp',
        'paneer crispy'                  => 'ALL Images/paneer crispy_result.webp',
        'paneer satay'                   => 'ALL Images/Paneer sate_result.webp',
        'paneer hot pan'                 => 'ALL Images/paneer hot pan_result.webp',
        'paneer spring roll'             => 'ALL Images/Paneer Spining Roll_result.webp',
        'baby corn mushroom salt & pepper' => 'ALL Images/baby corn mushroom solt paper_result.webp',
        'veg crunchy'                    => 'ALL Images/Veg crunchy_result.webp',
        'paneer crunchy'                 => 'ALL Images/paneer crunchy_result.webp',
        'paneer garlic'                  => 'ALL Images/paneer garlic_result.webp',
        'paneer schezwan'                => 'ALL Images/paneer Schezwan_result.webp',
        'chinese platter'                => 'ALL Images/Chinese plater_result.webp',
        'veg lollipop'                   => 'ALL Images/Veg lolypop_result.webp',
        // Thali Special
        'veg thali'                      => 'ALL Images/Veg Thali_result.webp',
        'special veg thali'              => 'ALL Images/Special Veg Thali_result.webp',
        'spe. veg thali'                 => 'ALL Images/Special Veg Thali_result.webp',
        'gujarati thali'                 => 'ALL Images/Gujarati Thali_result.webp',
        'gujrati thali'                  => 'ALL Images/Gujarati Thali_result.webp',
        'punjabi thali'                  => 'ALL Images/Panjabi Thali_result.webp',
        'panjabi thali'                  => 'ALL Images/Panjabi Thali_result.webp',
        // Main Course Indian Dishes
        'shev bhaji'                     => 'ALL Images/Shev Bhaji Kala masala_result.webp',
        'shev bhaji kala masala'         => 'ALL Images/Shev Bhaji Kala masala_result.webp',
        'milk shev bhaji'                => 'ALL Images/Milk shev Bhaji_result.webp',
        'mataki masala'                  => 'ALL Images/Mataki masala_result.webp',
        'mataki fry'                     => 'ALL Images/Matki fry_result.webp',
        'baingan masala'                 => 'ALL Images/Baingan Masala_result.webp',
        'baingan bharta'                 => 'ALL Images/Baigan bharta_result.webp',
        'alu mutter'                     => 'ALL Images/Alu mutter_result.webp',
        'green peace fry'                => 'ALL Images/Green peace fry_result.webp',
        'green peas masala'              => 'ALL Images/Green peas masala_result.webp',
        'green peas curry'               => 'ALL Images/Green peas_result.webp',
        'green peas'                     => 'ALL Images/Green peas_result.webp',
        'bhendi fry'                     => 'ALL Images/Bhendi fry_result.webp',
        'bhendi masala'                  => 'ALL Images/Bhendi Masala_result.webp',
        'plain palak'                    => 'ALL Images/Plain Palak_result.webp',
        'pithala'                        => 'ALL Images/Pithala_result.webp',
        'kaju masala'                    => 'ALL Images/Kaju Masala_result.webp',
        'shevga masala'                  => 'ALL Images/Shevga Masala_result.webp',
        'veg maratha'                    => 'ALL Images/Veg maratha_result.webp',
        'veg bhuna'                      => 'ALL Images/Veg bhuna_result.webp',
        'veg tawa'                       => 'ALL Images/Veg Tawa_result.webp',
        'veg hariyali'                   => 'ALL Images/Veg Hariyali_result.webp',
        'veg jaipuri'                    => 'ALL Images/Veg Jaypuri_result.webp',
        'veg dum aloo punjabi'           => 'ALL Images/Veg daam alu panjabi_result.webp',
        'veg kadhai'                     => 'ALL Images/Veg Kadhai_result.webp',
        'methi masala'                   => 'ALL Images/methi masala_result.webp',
        'veg chilly milly masala'        => 'ALL Images/veg chilly milly masala_result.webp',
        'tomato chatani'                 => 'ALL Images/tomato chatini_result.webp',
        'soyabean masala'                => 'ALL Images/soyabin masala_result.webp',
        'jeera aloo'                     => 'ALL Images/jeera aalu_result.webp',
        'aloo masala'                    => 'ALL Images/aalu masala_result.webp',
        'akkha masoor'                   => 'ALL Images/akka masur_result.webp',
        'veg khasiyat'                   => 'ALL Images/veg khasiyat_result.webp',
        'aloo gobi'                      => 'ALL Images/aalu gobi_result.webp',
        'mix veg'                        => 'ALL Images/Mix veg_result.webp',
        // Main Course Paneer Specialties
        'paneer masala'                  => 'ALL Images/paneer masala_result.webp',
        'paneer tawa'                    => 'ALL Images/paneer Tawa_result.webp',
        'paneer angara'                  => 'ALL Images/paneer angara_result.webp',
        'paneer bhurji'                  => 'ALL Images/paneer bhurji_result.webp',
        'kaju paneer'                    => 'ALL Images/Kaju Paneer_result.webp',
        'paneer kadhai'                  => 'ALL Images/paneer kadai_result.webp',
        'mushroom paneer'                => 'ALL Images/mushroom paneer_result.webp',
        'mushroom masala'                => 'ALL Images/Mashroom Masala_result.webp',
        'lasuni palak'                   => 'ALL Images/lasuni palak_result.webp',
        'lasuni palak tadka'             => 'ALL Images/Lasoni Palak Tadka_result.webp',
        'paneer kolhapuri'               => 'ALL Images/Paneer Kolhapuri_result.webp',
        'paneer chatpata'                => 'ALL Images/Paneer Chatpata_result.webp',
        'lasun methi fry'                => 'ALL Images/Lassun Methi fry_result.webp',
        'matar paneer'                   => 'ALL Images/matar paneer_result.webp',
        'paneer chingari'                => 'ALL Images/Paneer Chingari_result.webp',
        'paneer tufani'                  => 'ALL Images/Paneer Tufani_result.webp',
        'methi mutter malai'             => 'ALL Images/methi mutter malai_result.webp',
        // Dals
        'jeera dal'                      => 'ALL Images/jeera dal_result.webp',
        'butter dal'                     => 'ALL Images/butter dal_result.webp',
        'palak dal'                      => 'ALL Images/Palak Dal_result.webp',
        'lasuni dal'                     => 'ALL Images/Lasooni dal._result.webp',
        // Special Parathas
        'aloo paratha'                   => 'ALL Images/Aloo paratha._result.webp',
        'gobi paratha'                   => 'ALL Images/Gobi Paratha_result.webp',
        'paneer paratha'                 => 'ALL Images/paneer paratha_result.webp',
        'kashmiri paratha'               => 'ALL Images/Kashmiri paratha_result.webp',
        'mix paratha'                    => 'ALL Images/mix paratha._result.webp',
        'methi paratha'                  => 'ALL Images/Methi paratha_result.webp',
        'thecha paratha'                 => 'ALL Images/Thecha Paratha_result.webp',
        'techa paratha'                  => 'ALL Images/Thecha Paratha_result.webp',
        // Rotis, Naans, Kulchas, Bhakris
        'plain roti'                     => 'ALL Images/Breads_Roti.webp',
        'butter roti'                    => 'ALL Images/Butter roti.webp',
        'chapati'                        => 'ALL Images/Chapati.webp',
        'butter chapati'                 => 'ALL Images/Chapati.webp',
        'gehu roti (wheat)'              => 'ALL Images/Breads_Roti.webp',
        'butter gehu roti'               => 'ALL Images/Butter roti.webp',
        'tandoori roti'                  => 'ALL Images/Tandoori Roti.webp',
        'butter tandoori roti'           => 'ALL Images/Butter Tandoori Roti.webp',
        'rumali roti'                    => 'ALL Images/Rumali Roti.webp',
        'missi roti'                     => 'ALL Images/Missi Roti.webp',
        'butter missi roti'              => 'ALL Images/Missi Roti.webp',
        'plain naan'                     => 'ALL Images/Plain Naan.webp',
        'butter naan'                    => 'ALL Images/Butter Naan.webp',
        'garlic naan'                    => 'ALL Images/Garlic Naan.webp',
        'butter garlic naan'             => 'ALL Images/Garlic Butter Naan.webp',
        'cheese naan'                    => 'ALL Images/Cheese Naan.webp',
        'cheese garlic naan'             => 'ALL Images/Cheese Naan.webp',
        'plain kulcha'                   => 'ALL Images/Kulcha.webp',
        'kulcha'                         => 'ALL Images/Kulcha.webp',
        'butter kulcha'                  => 'ALL Images/Butter Kulcha.webp',
        'jowar bhakri'                   => 'ALL Images/Jowar Bhakri.webp',
        'butter jowar bhakri'            => 'ALL Images/Jowar Bhakri.webp',
        'bajra bhakri'                   => 'ALL Images/Bajra Bhakri.webp',
        'butter bajra bhakri'            => 'ALL Images/Bajra Bhakri.webp',
        'laccha paratha'                 => 'ALL Images/Multigrain Roti.webp',
        'butter laccha paratha'          => 'ALL Images/Multigrain Roti.webp',
        // Handi Special & Special Dishes
        'veg handi (full)'               => 'ALL Images/veg handi (full)_result.webp',
        'veg handi (half)'               => 'ALL Images/veg handi (half)_result.webp',
        'paneer handi (full)'            => 'ALL Images/paneer handi (full)_result.webp',
        'paneer handi (half)'            => 'ALL Images/paneer handi (half)_result.webp',
        'paneer tikka handi (full)'      => 'ALL Images/paneer tikka handi (full)_result.webp',
        'paneer tikka handi (half)'      => 'ALL Images/paneer tikka handi (half)_result.webp',
        'maratha handi (full)'           => 'ALL Images/maratha handi (full)_result.webp',
        'maratha handi (half)'           => 'ALL Images/maratha handi (half)_result.webp',
        'kaju paneer handi (full)'       => 'ALL Images/kaju paneer handi (full)_result.webp',
        'kaju paneer handi (half)'       => 'ALL Images/kaju paneer handi (half)_result.webp',
        'shev handi (full)'              => 'ALL Images/shev handi (full)_result.webp',
        'shev handi (half)'              => 'ALL Images/shev handi (half)_result.webp',
        'veg diwani handi (full)'        => 'ALL Images/veg diwani handi (full)_result.webp',
        'veg diwani handi (half)'        => 'ALL Images/veg diwani handi (half)_result.webp',
        'mutter paneer handi (full)'     => 'ALL Images/mutter paneer handi (full)_result.webp',
        'mutter paneer handi (half)'     => 'ALL Images/mutter paneer handi (half)_result.webp',
        'paneer hyderabadi handi (full)' => 'ALL Images/paneer hyadrabadi handi(full)_result.webp',
        'paneer hyderabadi handi (half)' => 'ALL Images/paneer hyadrabadi handi (half)_result.webp',
        'modak handi (full)'             => 'ALL Images/modak handi (full)_result.webp',
        'modak handi (half)'             => 'ALL Images/modal handi (half)_result.webp',
        'shev bhaji kala masala handi (full)' => 'ALL Images/shev bhaji (kala masala) (full)_result.webp',
        'shev bhaji kala masala handi (half)' => 'ALL Images/shev bhaji (kala masala) (half)_result.webp',
        'shevga handi (full)'            => 'ALL Images/shevaga handi (kala masala)(full)_result.webp',
        'shevga handi (half)'            => 'ALL Images/shevaga handi (kala masala) (half)_result.webp',
        'shevga handi kala masala (full)' => 'ALL Images/shevaga handi (kala masala)(full)_result.webp',
        'shevga handi kala masala (half)' => 'ALL Images/shevaga handi (kala masala) (half)_result.webp',
        'veg patiala'                    => 'ALL Images/veg Patiyala_result.webp',
        'paneer patiala'                 => 'ALL Images/Paneer Patiyala_result.webp',
        'paneer khas diwani'             => 'ALL Images/Paneer Khas dipani_result.webp',
        'veg jalfrezi'                   => 'ALL Images/veg jal fridi_result.webp',
        'veg maharaja'                   => 'ALL Images/Veg Maharaja_result.webp',
        'veg angara'                     => 'ALL Images/veg angara_result.webp',
        'kaju paneer tufani'             => 'ALL Images/kaju paneer tufani_result.webp',
        'paneer kaleji'                  => 'ALL Images/paneer kaleji_result.webp',
        'veg tiranga'                    => 'ALL Images/veg tiranga_result.webp',
        'paneer kabuli'                  => 'ALL Images/paneer kabuli_result.webp',
        'paneer banjara masala'          => 'ALL Images/paneer banjara masala_result.webp',
        'paneer lazeez'                  => 'ALL Images/paneer lajij_result.webp',
        'paneer kofta'                   => 'ALL Images/paneer kofta_result.webp',
        'paneer dilruba'                 => 'ALL Images/paneer dilruba_result.webp',
        'paneer pasanda'                 => 'ALL Images/paneer pasanda_result.webp',
        'veg nathkrupa'                  => 'ALL Images/Veg Maharaja_result.webp',
        'veg sham savera'                => 'ALL Images/veg sam savera_result.webp',
        'paneer nawabi'                  => 'ALL Images/paneer navabi_result.webp',
        'paneer mumtaz'                  => 'ALL Images/paneer mumtaj_result.webp',
        'veg amritsari'                  => 'ALL Images/veg amritsari_result.webp',
        'paneer amritsari'               => 'ALL Images/paneer amritsari_result.webp',
        'paneer do pyaza'                => 'ALL Images/paneer do pyaja_result.webp',
        'papadwadi rassa'                => 'ALL Images/papadwadi rasa_result.webp',
        'veg pahadi'                     => 'ALL Images/veg pahadi_result.webp',
        'paneer junglee'                 => 'ALL Images/paneer jangali_result.webp',
        'paneer ghotala'                 => 'ALL Images/paneer kotala_result.webp',
        // Rice, Pulao & Dals
        'plain rice (full)'              => 'ALL Images/Steamed Rice.webp',
        'plain rice (half)'              => 'ALL Images/Steamed Rice (Half)_result.webp',
        'jeera rice (full)'              => 'ALL Images/Jeera Rice.webp',
        'jeera rice (half)'              => 'ALL Images/Jeera Rice (Half)_result.webp',
        'dal khichadi'                   => 'ALL Images/dal khichadi_result.webp',
        'curd rice'                      => 'ALL Images/curd rice_result.webp',
        'masala rice'                    => 'ALL Images/masala rice_result.webp',
        'butter jeera rice'              => 'ALL Images/butter jeera rice_result.webp',
        'veg pulao'                      => 'ALL Images/Veg Pulao.webp',
        'paneer pulao'                   => 'ALL Images/Paneer Pulao.webp',
        'green peas pulao'               => 'ALL Images/Peas Pulao.webp',
        'jeera pulao'                    => 'ALL Images/jeera pulao_result.webp',
        'kashmiri pulao'                 => 'ALL Images/Kashmiri Pulao.webp',
        'lemon rice'                     => 'ALL Images/lemon rice_result.webp',
        'cheese peas pulao'              => 'ALL Images/cheese piece pulao_result.webp',
        'veg biryani'                    => 'ALL Images/Veg Biryani.webp',
        'veg dum biryani'                => 'ALL Images/Dum Veg Biryani.webp',
        'paneer tikka biryani'           => 'ALL Images/Paneer Biryani.webp',
        'paneer dum biryani'             => 'ALL Images/Paneer Biryani.webp',
        'paneer biryani'                 => 'ALL Images/Paneer Biryani.webp',
        'veg fried rice'                 => 'ALL Images/Veg Fried Rice.webp',
        'schezwan fried rice'            => 'ALL Images/Schezwan Fried Rice.webp',
        'mushroom fried rice'            => 'ALL Images/Rice_and_Biryani.webp',
        'veg triple schezwan rice'       => 'ALL Images/Schezwan Fried Rice.webp',
        'veg hongkong rice'              => 'ALL Images/Veg Fried Rice.webp',
        'singapore rice'                 => 'ALL Images/Rice_and_Biryani.webp',
        'dal vati'                       => 'ALL Images/Dal wati_result.webp',
        'tup vati'                       => 'ALL Images/Tup Wati_result.webp',
        'tadka vati'                     => 'ALL Images/tadka wati_result.webp',
        'rassa plate'                    => 'ALL Images/Rassa plate_result.webp',
        // Chinese Rice & Noodles
        'veg fried rice'                 => 'ALL Images/Veg Fried Rice_result.webp',
        'schezwan fried rice'            => 'ALL Images/Shezwan Rice_result.webp',
        'mushroom fried rice'            => 'ALL Images/Mushroom Fried Rice_result.webp',
        'veg triple schezwan rice'       => 'ALL Images/Veg Triple Schezwan Rice._result.webp',
        'veg hongkong rice'              => 'ALL Images/HonKong rice_result.webp',
        'singapore rice'                 => 'ALL Images/Singapore Rice._result.webp',
        'veg hakka noodles'              => 'ALL Images/Veg Hakka Noodles_result.webp',
        'veg schezwan noodles'           => 'ALL Images/Veg Shezwan Noodles._result.webp',
        'veg triple schezwan noodles'    => 'ALL Images/Veg Triple Schezwan Noodles_result.webp',
        'singapore noodles'              => 'ALL Images/Singapore Noodles_result.webp',
        'chinese bhel'                   => 'ALL Images/Chinese Bhel_result.webp',
        // Soups & Raitas
        'veg manchow soup'               => 'ALL Images/Veg Manchow Soup_result.webp',
        'veg clear soup'                 => 'ALL Images/Veg Clear Soup_result.webp',
        'veg hot & sour soup'            => 'ALL Images/Veg Hot and Sour Soup_result.webp',
        'tomato soup'                    => 'ALL Images/Tomato Soup_result.webp',
        'palak corn soup'                => 'ALL Images/Palak Corn Soup_result.webp',
        'cream of mushroom soup'         => 'ALL Images/cream of mushroom soup_result.webp',
        'dahi (plain curd)'              => 'ALL Images/Side_dishes_Raita.webp',
        'pineapple raita'                => 'ALL Images/Side_dishes_Raita.webp',
        'nachni papad'                   => 'ALL Images/Side_dishes_Raita.webp',

        // Ice Creams & Desserts
        'rajbhog ice cream (cup)'        => 'ALL Images/Rajbhog_result.webp',
        'sitaphal ice cream (cup)'       => 'ALL Images/Sitaphal Ice Cream_result.webp',
        'manpasand ice cream (cup)'      => 'ALL Images/Manpasand Ice Cream_result.webp',
        'vanilla ice cream (cup)'        => 'ALL Images/Vanilla Ice Cream_result.webp',
        'butterscotch ice cream (cup)'   => 'ALL Images/Butterscotch Ice Cream_result.webp',
        'mango ice cream (cup)'          => 'ALL Images/Mango Ice Cream_result.webp',
        'vanilla ice cream (small cup)'  => 'ALL Images/Vanilla Ice Cream_result.webp',
        'pista ice cream (small cup)'    => 'ALL Images/Vanilla Ice Cream_result.webp',
        'strawberry ice cream (small cup)' => 'ALL Images/strawberry ice cream - cone_result.webp',
        'vanilla ice cream (cone)'       => 'ALL Images/Vanilla Ice Cream - cone_result.webp',
        'pista ice cream (cone)'         => 'ALL Images/Vanilla Ice Cream - cone_result.webp',
        'butterscotch ice cream (cone)'  => 'ALL Images/Butterscotch ice Cream - cone_result.webp',
        'chocolate ice cream (cone)'     => 'ALL Images/Chocolate Ice Cream - cone_result.webp',
        'choco pop ice cream (cone)'     => 'ALL Images/Choco Pop ice cream - cone_result.webp',
        'strawberry ice cream (cone)'    => 'ALL Images/strawberry ice cream - cone_result.webp',
        'wine orange ice cream (cone)'   => 'ALL Images/Wine orange - cone_result.webp',
        'chocolate softy'                => 'ALL Images/Chocolate Softy_result.webp',
        'mango softy'                    => 'ALL Images/Mango Softy_result.webp',
        'blackcurrant softy'             => 'ALL Images/Blackcurrant Softy_result.webp',
        'matka kulfi'                    => 'ALL Images/Matka Kulfi_result.webp',
        'dry fruit kulfi'                => 'ALL Images/Dry Fruit Kulfi_result.webp',
        'mawa kulfi'                     => 'ALL Images/Mawa Kulfi_result.webp',
        'chocobar kulfi'                 => 'ALL Images/Chocobar Kulfi_result.webp',
    ];

    $categoryDefaults = [
        'starter'     => 'ALL Images/Starter_Paneer_Tikka.webp',
        'main_course' => 'ALL Images/MainCourse_panner_butter_Masala.webp',
        'bread'       => 'ALL Images/Breads_Roti.webp',
        'rice_biryani'=> 'ALL Images/Rice_and_Biryani.webp',
        'dessert'     => 'ALL Images/Rajbhog_result.webp',
        'side_dish'   => 'ALL Images/Veg Manchow Soup_result.webp',
        'salad'       => 'ALL Images/Side_dishes_Raita.webp',
        'water'       => 'ALL Images/Water Bottle (500 ml - Normal) new.webp',
        'welcome_drink'=> 'ALL Images/Tea_result.webp',
        'breakfast'   => 'ALL Images/Pohe_result.webp',
        'thali'       => 'ALL Images/Special Veg Thali_result.webp',
        'special_dishes' => 'ALL Images/veg handi (full)_result.webp',
        'chinese'        => 'ALL Images/Veg Triple Schezwan Rice._result.webp',
    ];

    $updateStmt = $pdo->prepare("UPDATE menu_items SET image_path = ? WHERE id = ?");
    $updatedCount = 0;

    echo "Mapping all menu items to their respective images in 'ALL Images/'...\n";
    echo "===================================================================\n";

    foreach ($items as $row) {
        $id = $row['id'];
        $nameEn = trim($row['name_en']);
        $cat = $row['category'];
        $nameLower = strtolower($nameEn);
        $normKey = strtolower(preg_replace('/[^a-z0-9]/i', '', $nameEn));

        $newPath = null;

        // 1. Custom override check
        if (isset($customOverrides[$nameLower])) {
            $targetOverride = __DIR__ . '/../' . $customOverrides[$nameLower];
            if (file_exists($targetOverride)) {
                $newPath = $customOverrides[$nameLower];
            }
        }

        // 2. Result images check for welcome_drink, breakfast, starter
        if (!$newPath && in_array($cat, ['welcome_drink', 'breakfast', 'starter'])) {
            $resultPath = $imgDir . '/' . $nameEn . '_result.webp';
            if (file_exists($resultPath)) {
                $newPath = 'ALL Images/' . $nameEn . '_result.webp';
            }
        }

        // 3. Direct file check
        if (!$newPath) {
            $exactPath = $imgDir . '/' . $nameEn . '.webp';
            if (file_exists($exactPath)) {
                $newPath = 'ALL Images/' . $nameEn . '.webp';
            }
        }

        // 3. Normalized key check
        if (!$newPath && isset($imgMap[$normKey])) {
            $newPath = $imgMap[$normKey];
        }

        // 4. Fallback if still not found
        if (!$newPath) {
            $newPath = $categoryDefaults[$cat] ?? 'ALL Images/Starter_Paneer_Tikka.webp';
        }

        $updateStmt->execute([$newPath, $id]);
        $updatedCount++;
        echo sprintf("[%s] %-35s -> %s\n", $row['item_code'], $nameEn, $newPath);
    }

    echo "===================================================================\n";
    echo "Successfully mapped {$updatedCount} menu items to their respective images!\n";

} catch (Exception $e) {
    echo "Error syncing images: " . $e->getMessage() . "\n";
}
