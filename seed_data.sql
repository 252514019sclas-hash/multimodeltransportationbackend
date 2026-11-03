USE `multimodel transportation`;

-- Seed Tourist Places if empty
INSERT INTO `tourist_places` (`place_id`, `name`, `city`, `category`, `description`, `image_url`, `location_address`, `visiting_hours`, `entry_fee`, `rating`, `distance_km`, `duration_visit`) VALUES
(1, 'California Academy of Sciences', 'San Francisco', 'Museum', 'A world-class scientific and cultural institution with an aquarium, planetarium, rainforest, and natural history museum all under one living roof.', 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=800&q=80', '55 Music Concourse Dr, Golden Gate Park, SF', '9:30 AM - 5:00 PM', '$39.50', 4.8, 5.2, '3-4 hours'),
(2, 'Golden Gate Bridge & Vista Point', 'San Francisco', 'Historical', 'Iconic 1.7-mile suspension bridge connecting San Francisco to Marin County with sweeping panoramic views of the bay and city skyline.', 'https://images.unsplash.com/photo-1501594907352-04cda38ebc29?auto=format&fit=crop&w=800&q=80', 'Golden Gate Bridge, San Francisco, CA', 'Open 24 hours', 'Free (Pedestrian/Cycle)', 4.9, 7.8, '1-2 hours'),
(3, 'Kapaleeshwarar Temple', 'Chennai', 'Temple/Cultural', 'A historic 7th-century Dravidian-style Shiva temple renowned for its majestic 120-foot gopuram, intricate stucco sculptures, and sacred temple tank.', 'https://images.unsplash.com/photo-1582510003544-4d00b7f74220?auto=format&fit=crop&w=800&q=80', '12 Vadakku Mada Veethi, Mylapore, Chennai', '6:00 AM - 12:30 PM, 4:00 PM - 9:00 PM', 'Free', 4.9, 3.4, '1-2 hours'),
(4, 'Marina Beach & Promenade', 'Chennai', 'Beach/Park', 'One of the longest natural urban beaches in the world, stretching over 13 km along the Coromandel Coast with historic statues and sunset vistas.', 'https://images.unsplash.com/photo-1596176530529-78163a4f7af2?auto=format&fit=crop&w=800&q=80', 'Kamarajar Salai, Triplicane, Chennai', 'Open 24 hours', 'Free', 4.6, 2.8, '2-3 hours'),
(5, 'SF Museum of Modern Art (SFMOMA)', 'San Francisco', 'Museum', 'Dynamic modern art museum housing over 33,000 works of painting, sculpture, photography, and multimedia design in the vibrant SoMa district.', 'https://images.unsplash.com/photo-1566127444979-b3d2b654e3d7?auto=format&fit=crop&w=800&q=80', '151 3rd St, San Francisco, CA', '10:00 AM - 5:00 PM (Closed Wed)', '$25.00', 4.7, 1.8, '2-3 hours'),
(6, 'Shore Temple & Pancha Rathas', 'Chennai', 'Historical', 'UNESCO World Heritage monument constructed in the 8th century AD by the Pallava dynasty directly overlooking the Bay of Bengal.', 'https://images.unsplash.com/photo-1609766857041-ed402ea8069a?auto=format&fit=crop&w=800&q=80', 'Shore Temple Rd, Mahabalipuram, Chennai', '6:00 AM - 6:00 PM', '₹40 (Indians) / ₹600 (Foreigners)', 4.9, 14.5, '3-4 hours'),
(7, 'Dolores Park & Mission Murals', 'San Francisco', 'Beach/Park', 'A vibrant sunny community park offering palm trees, skyline panoramas, and access to the world-famous Mission cultural street art corridors.', 'https://images.unsplash.com/photo-1506146332389-18140dc7b2fb?auto=format&fit=crop&w=800&q=80', 'Dolores St & 19th St, San Francisco, CA', '6:00 AM - 10:00 PM', 'Free', 4.8, 3.1, '1-2 hours'),
(8, 'Guindy National Park & Snake Park', 'Chennai', 'Nature', 'One of the very few national parks situated inside a major metropolitan city, protecting blackbucks, spotted deer, and lush tropical dry evergreen flora.', 'https://images.unsplash.com/photo-1534567153574-2b12153a87f0?auto=format&fit=crop&w=800&q=80', 'Rangeguindy, Chennai, Tamil Nadu', '9:00 AM - 5:30 PM (Closed Tue)', '₹20 (Adults) / ₹5 (Children)', 4.5, 6.2, '2-3 hours')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Seed default journeys for test/demo users (so CO2 calculation and My Trips immediately show real active and past trips)
-- Let's add sample trips for user_id = 1 and user_id = 2
INSERT INTO `journeys` (`user_id`, `origin`, `destination`, `mode`, `route_title`, `duration_minutes`, `distance_km`, `fare`, `transfers`, `co2_saved_kg`, `co2_emitted_kg`, `status`, `journey_date`, `booking_reference`)
SELECT 2, 'Ferry Building', 'California Academy of Sciences', 'Metro', 'N Judah Line', 42, 6.8, 3.00, 0, 0.92, 0.24, 'upcoming', 'Today, 9:15 AM', 'MS-89241'
WHERE NOT EXISTS (SELECT 1 FROM `journeys` WHERE `user_id` = 2 AND `booking_reference` = 'MS-89241');

INSERT INTO `journeys` (`user_id`, `origin`, `destination`, `mode`, `route_title`, `duration_minutes`, `distance_km`, `fare`, `transfers`, `co2_saved_kg`, `co2_emitted_kg`, `status`, `journey_date`, `booking_reference`)
SELECT 2, 'Noe Valley', '2nd & Howard, SoMa', 'Metro', 'J Church + Walk', 35, 5.2, 3.00, 0, 0.70, 0.18, 'completed', 'Yesterday', 'MS-78112'
WHERE NOT EXISTS (SELECT 1 FROM `journeys` WHERE `user_id` = 2 AND `booking_reference` = 'MS-78112');

INSERT INTO `journeys` (`user_id`, `origin`, `destination`, `mode`, `route_title`, `duration_minutes`, `distance_km`, `fare`, `transfers`, `co2_saved_kg`, `co2_emitted_kg`, `status`, `journey_date`, `booking_reference`)
SELECT 2, 'Ferry Building', 'Dolores Park', 'Bicycle', 'Bay Wheels Cycling Route', 28, 4.4, 4.50, 0, 0.75, 0.00, 'completed', 'Last Week', 'MS-65049'
WHERE NOT EXISTS (SELECT 1 FROM `journeys` WHERE `user_id` = 2 AND `booking_reference` = 'MS-65049');

INSERT INTO `journeys` (`user_id`, `origin`, `destination`, `mode`, `route_title`, `duration_minutes`, `distance_km`, `fare`, `transfers`, `co2_saved_kg`, `co2_emitted_kg`, `status`, `journey_date`, `booking_reference`)
SELECT 2, 'SFMOMA', 'Noe Valley', 'Bus', 'Muni Bus 24', 20, 4.8, 3.00, 0, 0.49, 0.33, 'completed', '2 Weeks Ago', 'MS-51203'
WHERE NOT EXISTS (SELECT 1 FROM `journeys` WHERE `user_id` = 2 AND `booking_reference` = 'MS-51203');

-- Seed sample saved places for user 2
INSERT IGNORE INTO `saved_places` (`user_id`, `place_id`) VALUES (2, 1), (2, 2);

-- Seed sample payment methods for user 2
INSERT INTO `payment_methods` (`user_id`, `method_type`, `title`, `subtitle`, `is_default`)
SELECT 2, 'card', 'Visa •••• 4242', 'Expires 08/29 · Personal Card', 1
WHERE NOT EXISTS (SELECT 1 FROM `payment_methods` WHERE `user_id` = 2);

INSERT INTO `payment_methods` (`user_id`, `method_type`, `title`, `subtitle`, `is_default`)
SELECT 2, 'upi', 'Google Pay UPI', 'alex@okaxis · Instant Transit Pay', 0
WHERE NOT EXISTS (SELECT 1 FROM `payment_methods` WHERE `user_id` = 2 AND `method_type` = 'upi');

