<?php
require_once __DIR__ . "/config/config.php";

$places = [
    [
        "name" => "Marina Beach & Promenade",
        "city" => "Chennai",
        "category" => "Beaches",
        "description" => "One of the longest natural urban beaches in the world, stretching along the Bay of Bengal with historic statues, sea breeze, and vibrant local stalls.",
        "image_url" => "https://images.unsplash.com/photo-1596176530529-78163a4f7af2?auto=format&fit=crop&w=800&q=80",
        "location_address" => "Kamarajar Salai, Triplicane, Chennai",
        "visiting_hours" => "Open 24 hours",
        "entry_fee" => "Free",
        "rating" => 4.7,
        "distance_km" => 2.5,
        "duration_visit" => "2-3 hours"
    ],
    [
        "name" => "Kapaleeshwarar Temple",
        "city" => "Chennai",
        "category" => "Temples",
        "description" => "A historic 7th-century Dravidian Shiva temple renowned for its majestic 120-foot gopuram, sacred temple tank, and intricate stucco sculptures.",
        "image_url" => "https://images.unsplash.com/photo-1582510003544-4d00b7f74220?auto=format&fit=crop&w=800&q=80",
        "location_address" => "12 Vadakku Mada Veethi, Mylapore, Chennai",
        "visiting_hours" => "6:00 AM - 12:30 PM, 4:00 PM - 9:00 PM",
        "entry_fee" => "Free",
        "rating" => 4.9,
        "distance_km" => 3.4,
        "duration_visit" => "1-2 hours"
    ],
    [
        "name" => "Meenakshi Amman Temple",
        "city" => "Madurai",
        "category" => "Temples",
        "description" => "World-famous architectural wonder featuring 14 towering colourful gopurams, the Hall of Thousand Pillars, and ancient cultural heritage in Madurai's heart.",
        "image_url" => "https://images.unsplash.com/photo-1609766857041-ed402ea8069a?auto=format&fit=crop&w=800&q=80",
        "location_address" => "Madurai Main, Madurai, Tamil Nadu",
        "visiting_hours" => "5:00 AM - 12:30 PM, 4:00 PM - 9:30 PM",
        "entry_fee" => "Free (Special Darshan ₹50/₹100)",
        "rating" => 4.9,
        "distance_km" => 12.0,
        "duration_visit" => "2-3 hours"
    ],
    [
        "name" => "Government Botanical Garden",
        "city" => "Ooty",
        "category" => "Nature",
        "description" => "Sprawling across 55 acres on the lower slopes of Doddabetta peak, featuring over 650 species of exotic flora, fossil tree trunks, and lush terraced lawns.",
        "image_url" => "https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80",
        "location_address" => "Vannarapettai, Ooty, Nilgiris, Tamil Nadu",
        "visiting_hours" => "7:00 AM - 6:30 PM",
        "entry_fee" => "₹40 (Adults), ₹20 (Kids)",
        "rating" => 4.7,
        "distance_km" => 18.5,
        "duration_visit" => "2-3 hours"
    ],
    [
        "name" => "Ooty Lake & Boathouse",
        "city" => "Ooty",
        "category" => "Nature",
        "description" => "Artificial lake constructed in 1824 by John Sullivan, surrounded by eucalyptus groves, offering paddle, motor, and row boating in cool mountain air.",
        "image_url" => "https://images.unsplash.com/photo-1589182373726-e4f658ab50f0?auto=format&fit=crop&w=800&q=80",
        "location_address" => "North Lake Road, Ooty, Nilgiris",
        "visiting_hours" => "9:00 AM - 6:00 PM",
        "entry_fee" => "₹30 Entry + Boating fee",
        "rating" => 4.6,
        "distance_km" => 19.2,
        "duration_visit" => "2 hours"
    ],
    [
        "name" => "Coaker's Walk",
        "city" => "Kodaikanal",
        "category" => "Nature",
        "description" => "A 1-km paved pedestrian path constructed on the edge of steep mountain slopes offering breathtaking views of Dolphin's Nose and the Pambar river valley.",
        "image_url" => "https://images.unsplash.com/photo-1519681393784-d120267933ba?auto=format&fit=crop&w=800&q=80",
        "location_address" => "Coaker's Walk, Kodaikanal, Dindigul",
        "visiting_hours" => "7:00 AM - 7:00 PM",
        "entry_fee" => "₹30",
        "rating" => 4.8,
        "distance_km" => 24.0,
        "duration_visit" => "1-2 hours"
    ],
    [
        "name" => "Kodaikanal Lake",
        "city" => "Kodaikanal",
        "category" => "Nature",
        "description" => "Iconic star-shaped man-made lake at 2,285m elevation, surrounded by lush Palani hills, famous for cycling, horse riding, and peaceful evening walks.",
        "image_url" => "https://images.unsplash.com/photo-1470071459604-3b5ec3a7fe05?auto=format&fit=crop&w=800&q=80",
        "location_address" => "Lake Road, Kodaikanal, Tamil Nadu",
        "visiting_hours" => "6:00 AM - 7:00 PM",
        "entry_fee" => "Free (Boating ₹50 - ₹200)",
        "rating" => 4.7,
        "distance_km" => 22.5,
        "duration_visit" => "2-3 hours"
    ],
    [
        "name" => "Ramanathaswamy Temple",
        "city" => "Rameswaram",
        "category" => "Temples",
        "description" => "One of the 12 Jyotirlinga shrines, celebrated for its monumental pillared corridors spanning over 1,200 meters and 22 holy freshwater theertham wells.",
        "image_url" => "https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80",
        "location_address" => "Rameswaram, Ramanathapuram, Tamil Nadu",
        "visiting_hours" => "5:00 AM - 1:00 PM, 3:00 PM - 9:00 PM",
        "entry_fee" => "Free",
        "rating" => 4.9,
        "distance_km" => 35.0,
        "duration_visit" => "2-3 hours"
    ],
    [
        "name" => "Pamban Bridge & Sea View",
        "city" => "Rameswaram",
        "category" => "Historical",
        "description" => "India's first sea bridge, an iconic cantilever railway marvel opened in 1914 spanning the Palk Strait, offering majestic panoramic ocean views.",
        "image_url" => "https://images.unsplash.com/photo-1507525428034-b723cf961d3e?auto=format&fit=crop&w=800&q=80",
        "location_address" => "NH 87, Pamban, Tamil Nadu",
        "visiting_hours" => "Open 24 hours (Viewpoint)",
        "entry_fee" => "Free",
        "rating" => 4.9,
        "distance_km" => 32.0,
        "duration_visit" => "1 hour"
    ],
    [
        "name" => "Vivekananda Rock Memorial",
        "city" => "Kanyakumari",
        "category" => "Historical",
        "description" => "Iconic monument built in 1970 on two adjacent rocks 500 meters off the southern tip of India where the Bay of Bengal, Arabian Sea, and Indian Ocean meet.",
        "image_url" => "https://images.unsplash.com/photo-1506146332389-18140dc7b2fb?auto=format&fit=crop&w=800&q=80",
        "location_address" => "Kanyakumari, Tamil Nadu",
        "visiting_hours" => "8:00 AM - 4:00 PM",
        "entry_fee" => "₹70 (Ferry ticket) + ₹20 (Entry)",
        "rating" => 4.9,
        "distance_km" => 48.0,
        "duration_visit" => "2-3 hours"
    ],
    [
        "name" => "Brihadeeswarar Temple",
        "city" => "Thanjavur",
        "category" => "Historical",
        "description" => "UNESCO World Heritage Site built by Raja Raja Chola I in 1010 CE, famous for its 216-foot vimana topped by a massive 80-tonne single granite capstone.",
        "image_url" => "https://images.unsplash.com/photo-1582510003544-4d00b7f74220?auto=format&fit=crop&w=800&q=80",
        "location_address" => "Membalam Rd, Balaganapathy Nagar, Thanjavur",
        "visiting_hours" => "6:00 AM - 12:30 PM, 4:00 PM - 8:30 PM",
        "entry_fee" => "Free",
        "rating" => 4.9,
        "distance_km" => 28.0,
        "duration_visit" => "2-3 hours"
    ],
    [
        "name" => "Shore Temple & Pancha Rathas",
        "city" => "Mahabalipuram",
        "category" => "Historical",
        "description" => "8th-century Pallava dynasty monolithic rock-cut monument overlooking the Bay of Bengal, depicting extraordinary carvings of Arjuna's Penance.",
        "image_url" => "https://images.unsplash.com/photo-1501594907352-04cda38ebc29?auto=format&fit=crop&w=800&q=80",
        "location_address" => "Shore Temple Rd, Mahabalipuram, Tamil Nadu",
        "visiting_hours" => "6:00 AM - 6:00 PM",
        "entry_fee" => "₹40 (Indians), ₹600 (Foreigners)",
        "rating" => 4.9,
        "distance_km" => 14.5,
        "duration_visit" => "3-4 hours"
    ],
    [
        "name" => "Yercaud Lake & Emerald Park",
        "city" => "Yercaud",
        "category" => "Nature",
        "description" => "Picturesque mountain lake nestled in the Shevaroy Hills of Eastern Ghats, flanked by gardens, deer park, and surrounding fruit orchards and coffee estates.",
        "image_url" => "https://images.unsplash.com/photo-1534567153574-2b12153a87f0?auto=format&fit=crop&w=800&q=80",
        "location_address" => "Yercaud, Salem District, Tamil Nadu",
        "visiting_hours" => "8:30 AM - 5:30 PM",
        "entry_fee" => "Free (Boating ₹110 - ₹250)",
        "rating" => 4.6,
        "distance_km" => 16.0,
        "duration_visit" => "2 hours"
    ],
    [
        "name" => "Hogenakkal Falls & Boating",
        "city" => "Hogenakkal",
        "category" => "Nature",
        "description" => "Known as the 'Niagara of India', where the Kaveri river splits into multiple breathtaking cascades across carbonatite rocks, famous for Coracle boat rides.",
        "image_url" => "https://images.unsplash.com/photo-1432405972618-c60b0225b8f9?auto=format&fit=crop&w=800&q=80",
        "location_address" => "Dharmapuri District, Tamil Nadu",
        "visiting_hours" => "8:00 AM - 5:30 PM",
        "entry_fee" => "₹20 Entry (Coracle ride ₹500 - ₹800)",
        "rating" => 4.8,
        "distance_km" => 42.0,
        "duration_visit" => "3-4 hours"
    ]
];

try {
    $pdo->exec("DELETE FROM tourist_places");
    
    $stmt = $pdo->prepare("
        INSERT INTO tourist_places 
        (name, city, category, description, image_url, location_address, visiting_hours, entry_fee, rating, distance_km, duration_visit)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($places as $p) {
        $stmt->execute([
            $p["name"],
            $p["city"],
            $p["category"],
            $p["description"],
            $p["image_url"],
            $p["location_address"],
            $p["visiting_hours"],
            $p["entry_fee"],
            $p["rating"],
            $p["distance_km"],
            $p["duration_visit"]
        ]);
    }
    
    echo json_encode(["success" => true, "count" => count($places), "message" => "Tamil Nadu tourist places populated successfully."]);
} catch (Exception $e) {
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
