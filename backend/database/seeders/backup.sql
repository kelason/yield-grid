--
-- PostgreSQL database dump
--

-- Dumped from database version 16.4 (Debian 16.4-1.pgdg110+2)
-- Dumped by pg_dump version 16.4 (Debian 16.4-1.pgdg110+2)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- Data for Name: chat_conversations; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.chat_conversations VALUES (2, '2026-09-29 11:18:05', '2026-09-29 11:18:00', '2026-09-29 11:18:05');
INSERT INTO public.chat_conversations VALUES (1, '2026-10-01 03:04:08', '2026-09-29 10:11:00', '2026-10-01 03:04:08');


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.users VALUES (8, 'Kevin Sapang', 'kevin.sapang@gmail.com', '2026-09-29 04:05:52', '$2y$12$/5UH7OPyqOjDqACHsSGuyuOcgoxKRBFQmWRuMDDKqAuGJmxs2lUVS', 'farmer', NULL, NULL, NULL, NULL, '2026-09-29 04:05:43', '2026-09-29 04:05:52');
INSERT INTO public.users VALUES (9, 'Carolyn Arceo', 'carolyn.arceo@gmail.com', '2026-09-29 09:50:18', '$2y$12$Mma9kz7S9Tca0pTcrvkm9ur13V3Albs99x1hgnjjghYwin3zVsltm', 'buyer', NULL, NULL, NULL, NULL, '2026-09-29 09:49:58', '2026-09-29 09:50:18');
INSERT INTO public.users VALUES (10, 'Toby Maguire', 'toby.maguire@gmail.com', '2026-09-29 10:53:45', '$2y$12$b48X779vALCb0Ec7luTwbe/4tUBIj120Wxsh0LSwUtzbst00zLiW6', 'farmer', NULL, NULL, NULL, NULL, '2026-09-29 10:53:38', '2026-09-29 10:53:45');


--
-- Data for Name: chat_messages; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.chat_messages VALUES (1, 1, 9, 'yooooh', '2026-09-29 10:11:06', '2026-09-29 10:11:06');
INSERT INTO public.chat_messages VALUES (2, 1, 8, 'hello', '2026-09-29 10:11:57', '2026-09-29 10:11:57');
INSERT INTO public.chat_messages VALUES (3, 2, 10, 'bugok you?', '2026-09-29 11:18:05', '2026-09-29 11:18:05');
INSERT INTO public.chat_messages VALUES (4, 1, 9, 'xczczxczxc', '2026-10-01 03:03:42', '2026-10-01 03:03:42');
INSERT INTO public.chat_messages VALUES (5, 1, 8, 'fdfsf', '2026-10-01 03:04:08', '2026-10-01 03:04:08');


--
-- Data for Name: chat_attachments; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: chat_participants; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.chat_participants VALUES (4, 2, 9, NULL, '2026-09-29 11:18:00', '2026-09-29 11:18:00');
INSERT INTO public.chat_participants VALUES (3, 2, 10, '2026-09-29 11:18:00', '2026-09-29 11:18:00', '2026-09-29 11:18:00');
INSERT INTO public.chat_participants VALUES (2, 1, 8, '2026-10-01 03:03:56', '2026-09-29 10:11:00', '2026-10-01 03:03:56');
INSERT INTO public.chat_participants VALUES (1, 1, 9, '2026-10-01 03:27:14', '2026-09-29 10:11:00', '2026-10-01 03:27:14');


--
-- Data for Name: contact_messages; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: user_addresses; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.user_addresses VALUES (1, 9, NULL, '030000000', '036900000', '036907000', '036907005', 'Purok 4', 15.4007850, 120.6999740, true, '2026-09-29 09:49:58', '2026-09-29 09:49:58', '0101000020E61000003883BF5FCC2C5E40697407B133CD2E40');
INSERT INTO public.user_addresses VALUES (2, 8, 'Kevin farm', '030000000', '034900000', '034903000', '034903011', 'Purok 4', 15.4884680, 120.9815960, true, '2026-09-29 09:56:47', '2026-09-29 09:56:47', '0101000020E61000009A780778D23E5E40F9484A7A18FA2E40');
INSERT INTO public.user_addresses VALUES (3, 10, NULL, '010000000', '012800000', '012816000', '012816031', '123', 18.0435840, 120.5255560, true, '2026-09-29 10:53:38', '2026-09-29 10:53:38', '0101000020E6100000DC0DA2B5A2215E40FDA02E52280B3240');


--
-- Data for Name: crop_demands; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.crop_demands VALUES (1, 9, 1, '600kg Fresh Tomato', 'Sample test sdadadsadasdasdasdadasdasdsad adsadasdas dad a', 'Tomato', 300.00, 0.00, 15.00, 4500.00, 'PHP', '2026-10-10', '2026-10-10', 'fully_allocated', '2026-09-29 09:52:20', '2026-09-29 11:17:01');
INSERT INTO public.crop_demands VALUES (2, 9, 1, '500kg Fresh Mango', 'ssssas acasdasdas', 'Mango', 500.00, 450.00, 8.00, 4000.00, 'PHP', '2026-10-10', '2026-10-10', 'open', '2026-09-29 11:29:37', '2026-09-29 11:36:09');


--
-- Data for Name: crop_demand_offers; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.crop_demand_offers VALUES (1, 1, 8, 200.00, 13.00, 2600.00, 'PHP', 'hello', 'withdrawn', NULL, NULL, NULL, NULL, '2026-09-29 09:57:05', '2026-09-29 10:03:39');
INSERT INTO public.crop_demand_offers VALUES (2, 1, 8, 200.00, 13.00, 2600.00, 'PHP', 'asdasdasd', 'completed', '2026-09-29 10:10:51', '2026-09-29 10:12:17', '2026-09-29 10:42:14', '2026-09-29 11:16:58', '2026-09-29 10:04:00', '2026-09-29 11:16:58');
INSERT INTO public.crop_demand_offers VALUES (4, 2, 10, 50.00, 8.00, 400.00, 'PHP', NULL, 'completed', '2026-09-29 11:36:09', '2026-09-29 11:56:55', '2026-09-29 11:57:44', '2026-09-29 12:07:30', '2026-09-29 11:34:06', '2026-09-29 12:07:30');
INSERT INTO public.crop_demand_offers VALUES (3, 1, 10, 100.00, 15.00, 1500.00, 'PHP', 'bugok', 'delivered', '2026-09-29 11:17:01', '2026-09-29 11:17:27', '2026-09-29 12:21:19', NULL, '2026-09-29 10:54:11', '2026-09-29 12:21:19');


--
-- Data for Name: farms; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.farms VALUES (4, 8, 'Kevin''s Farm', '123', 'La Paz', 'Tarlac', '2314', 22, '2026-09-29 09:27:03', '2026-09-29 09:27:03', 'Philippines');


--
-- Data for Name: plots; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.plots VALUES (4, 4, 'Plot 1', '0103000020E61000000100000005000000FE7E315BB22C5E4033DE567A6DCE2E400CB265F9BA2C5E4020B58993FBCD2E40399D64ABCB2C5E40A3073E062BCE2E4023111AC1C62C5E4014D1AFAD9FCE2E40FE7E315BB22C5E4033DE567A6DCE2E40', 'clay', 1.3653202785603702, '2026-09-29 09:28:13', '2026-09-29 09:28:21', '6abb84b54e13cb166ffaf3a9');


--
-- Data for Name: crop_recommendations; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.crop_recommendations VALUES (4, 4, 'Rice (Lowland Paddy)', 98, 'La Paz, Tarlac is a premier rice-producing region in Central Luzon. The 1.3653202785604 hectare plot size is ideal for medium-scale commercial lowland rice farming. The heavy clay soil possesses excellent water retention capabilities, minimizing percolation losses and maintaining the flooded conditions required for optimal paddy rice growth under the warm, humid local climate.', '6.14 tons total (4.5 tons/ha on 1.3653202785604 ha)', 'pending', '2026-09-29 09:28:29', '2026-09-29 09:28:29');
INSERT INTO public.crop_recommendations VALUES (5, 4, 'Sugarcane', 95, 'Tarlac is historically renowned for extensive sugarcane production, supported by nearby milling infrastructure. The 1.3653202785604 hectare area accommodates a profitable commercial block of sugarcane. Clay soils are well-suited for sugarcane due to their high nutrient-holding capacity, which feeds the crop throughout its long maturation cycle, paired with the high ambient temperatures of La Paz.', '95.57 tons total (70 tons/ha on 1.3653202785604 ha)', 'pending', '2026-09-29 09:28:29', '2026-09-29 09:28:29');
INSERT INTO public.crop_recommendations VALUES (6, 4, 'Corn (Yellow)', 90, 'Yellow corn is a major rotation and cash crop in Tarlac, vital for the local livestock feed industry. For a 1.3653202785604 hectare plot, corn offers straightforward mechanization and solid commercial returns. While clay soils require proper drainage management to prevent waterlogging during heavy tropical rains, they provide exceptional fertility and moisture retention during dry spells.', '6.83 tons total (5.0 tons/ha on 1.3653202785604 ha)', 'pending', '2026-09-29 09:28:29', '2026-09-29 09:28:29');
INSERT INTO public.crop_recommendations VALUES (7, 4, 'Cassava', 88, 'Cassava thrives in the Tarlac agricultural landscape as a resilient root crop used for food and industrial starch. The 1.3653202785604 hectare plot size allows for efficient tuber production. Although clay can compact, well-managed clay soils in La Paz provide robust structural support and nutrient reserves that yield heavy, starch-dense roots.', '34.13 tons total (25 tons/ha on 1.3653202785604 ha)', 'pending', '2026-09-29 09:28:29', '2026-09-29 09:28:29');
INSERT INTO public.crop_recommendations VALUES (8, 4, 'Sweet Potato (Kamote)', 85, 'Sweet potato is widely cultivated across Central Luzon for local wet markets and commercial snack processing. A 1.3653202785604 hectare plot provides a highly manageable scale for ridge-planted sweet potato. The clay soil helps retain the consistent moisture needed for root initiation, while ridging mitigates the density of the clay to allow proper tuber expansion.', '19.11 tons total (14 tons/ha on 1.3653202785604 ha)', 'pending', '2026-09-29 09:28:29', '2026-09-29 09:28:29');
INSERT INTO public.crop_recommendations VALUES (9, 4, 'Eggplant (Talong)', 83, 'Eggplant is a staple vegetable intensely favored in Philippine agriculture and heavily traded in Tarlac markets. On 1.3653202785604 hectares, intensive vegetable cultivation can yield high revenues. Clay soils excel in supplying the continuous moisture and heavy potassium/nitrogen nutrition that prolific fruiting eggplants demand in the humid local climate.', '21.84 tons total (16 tons/ha on 1.3653202785604 ha)', 'pending', '2026-09-29 09:28:29', '2026-09-29 09:28:29');
INSERT INTO public.crop_recommendations VALUES (10, 4, 'Mung Bean (Munggo)', 80, 'Mung bean is a popular post-rice or rotation crop in Tarlac due to its short maturity cycle and nitrogen-fixing abilities. Cultivating 1.3653202785604 hectares of mung beans optimizes land use intensity. Clay soils retain residual moisture from the wet season perfectly, allowing the crop to mature successfully with minimal supplemental irrigation.', '1.64 tons total (1.2 tons/ha on 1.3653202785604 ha)', 'pending', '2026-09-29 09:28:29', '2026-09-29 09:28:29');
INSERT INTO public.crop_recommendations VALUES (11, 4, 'Bitter Gourd (Ampalaya)', 78, 'Bitter gourd is a high-value vine vegetable commonly grown by farmers in La Paz, Tarlac using trellis systems. A 1.3653202785604 hectare commercial trellis setup can be exceptionally lucrative. The clay soil''s superior nutrient retention supports the heavy vegetative growth and continuous fruiting of the crop, provided surface drainage is maintained.', '13.65 tons total (10 tons/ha on 1.3653202785604 ha)', 'pending', '2026-09-29 09:28:29', '2026-09-29 09:28:29');
INSERT INTO public.crop_recommendations VALUES (12, 4, 'Watermelon', 75, 'Watermelon is a profitable seasonal cash crop extensively grown in Central Luzon river basins and flatlands following grain harvests. The 1.3653202785604 hectare plot size is ideal for managing vine spread and manual harvesting. Clay soils, when mounded, supply the steady moisture reservoir required to size up large, sweet melons during warm weather.', '24.58 tons total (18 tons/ha on 1.3653202785604 ha)', 'pending', '2026-09-29 09:28:29', '2026-09-29 09:28:29');
INSERT INTO public.crop_recommendations VALUES (13, 4, 'Peanut (Mani)', 72, 'Peanuts are traditionally grown in parts of Tarlac as an alternative legume crop. Managing 1.3653202785604 hectares of peanuts offers good market demand locally. While loams are often preferred, clay soils with good organic matter management provide ample calcium and moisture for robust pod development and kernel filling.', '2.18 tons total (1.6 tons/ha on 1.3653202785604 ha)', 'pending', '2026-09-29 09:28:29', '2026-09-29 09:28:29');


--
-- Data for Name: forum_attachments; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: forum_categories; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.forum_categories VALUES (3, 'General Discussion', 'general', 'Open conversation about farming and agriculture', '💬', 10, '2026-09-29 04:07:06', '2026-09-29 04:07:06');
INSERT INTO public.forum_categories VALUES (4, 'Crop Help & Advice', 'crop-help', 'Ask questions about crop health, planting, and harvesting', '🌱', 20, '2026-09-29 04:07:06', '2026-09-29 04:07:06');
INSERT INTO public.forum_categories VALUES (5, 'Soil & Land Management', 'soil-management', 'Soil types, fertilization, land preparation topics', '🏔️', 30, '2026-09-29 04:07:06', '2026-09-29 04:07:06');
INSERT INTO public.forum_categories VALUES (6, 'Weather & Climate', 'weather', 'Weather patterns, climate adaptation, seasonal planning', '🌦️', 40, '2026-09-29 04:07:06', '2026-09-29 04:07:06');
INSERT INTO public.forum_categories VALUES (7, 'Marketplace Talk', 'marketplace', 'Buying, selling, pricing, and contract discussions', '🛒', 50, '2026-09-29 04:07:06', '2026-09-29 04:07:06');
INSERT INTO public.forum_categories VALUES (8, 'Tools & Equipment', 'tools-equipment', 'Farm equipment, tools, and technology recommendations', '🔧', 60, '2026-09-29 04:07:06', '2026-09-29 04:07:06');
INSERT INTO public.forum_categories VALUES (9, 'Pest & Disease Control', 'pest-control', 'Managing insects, weeds, and plant diseases', '🐛', 45, '2026-09-29 04:07:06', '2026-09-29 04:07:06');
INSERT INTO public.forum_categories VALUES (10, 'Irrigation & Water', 'irrigation', 'Water management, pumps, and irrigation systems', '💧', 47, '2026-09-29 04:07:06', '2026-09-29 04:07:06');
INSERT INTO public.forum_categories VALUES (11, 'Organic & Sustainable', 'organic', 'Organic farming practices and sustainability', '🌿', 55, '2026-09-29 04:07:06', '2026-09-29 04:07:06');
INSERT INTO public.forum_categories VALUES (12, 'Livestock & Poultry', 'livestock', 'Animal husbandry and care', '🐄', 65, '2026-09-29 04:07:06', '2026-09-29 04:07:06');
INSERT INTO public.forum_categories VALUES (13, 'Success Stories', 'success-stories', 'Share your wins — great harvests, solved problems', '🏆', 70, '2026-09-29 04:07:06', '2026-09-29 04:07:06');
INSERT INTO public.forum_categories VALUES (14, 'Farm Finance & Grants', 'finance', 'Discuss loans, subsidies, and farm accounting', '💵', 80, '2026-09-29 04:07:06', '2026-09-29 04:07:06');


--
-- Data for Name: forum_threads; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.forum_threads VALUES (4, 8, 4, NULL, 'hello world', 'dsad asdasd sad sadasd sad sad asd d asdasd ada s', 0, 0, false, false, true, '2026-09-29 04:12:29', '2026-09-29 04:12:29', '2026-09-29 04:12:29', NULL);
INSERT INTO public.forum_threads VALUES (5, 8, 3, NULL, 'Goodbye RareJob', 'Paalam sa lahat mga kaibigan', 1, 0, false, false, false, '2026-10-01 03:04:54', '2026-10-01 03:04:54', '2026-10-01 03:08:53', NULL);


--
-- Data for Name: forum_replies; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: forum_reports; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: forum_tags; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.forum_tags VALUES (1, 'Rice', 'rice', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (2, 'Corn', 'corn', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (3, 'Vegetables', 'vegetables', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (4, 'Fruits', 'fruits', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (5, 'Organic', 'organic', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (6, 'Pest Control', 'pest-control', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (7, 'Fertilizer', 'fertilizer', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (8, 'Irrigation', 'irrigation', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (9, 'Harvest', 'harvest', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (10, 'Market Price', 'market-price', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (11, 'Contract', 'contract', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (12, 'Logistics', 'logistics', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (13, 'Weather Alert', 'weather-alert', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (14, 'Pesticides', 'pesticides', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (15, 'Insects', 'insects', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (16, 'Weeds', 'weeds', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (17, 'Disease', 'disease', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (18, 'Pumps', 'pumps', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (19, 'Drip Irrigation', 'drip-irrigation', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (20, 'Water Supply', 'water-supply', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (21, 'Compost', 'compost', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (22, 'Sustainable', 'sustainable', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (23, 'Permaculture', 'permaculture', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (24, 'Cattle', 'cattle', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (25, 'Poultry', 'poultry', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (26, 'Swine', 'swine', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (27, 'Animal Feed', 'animal-feed', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (28, 'Loans', 'loans', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (29, 'Grants', 'grants', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (30, 'Subsidies', 'subsidies', '2026-09-29 04:07:07', '2026-09-29 04:07:07');
INSERT INTO public.forum_tags VALUES (31, 'Insurance', 'insurance', '2026-09-29 04:07:07', '2026-09-29 04:07:07');


--
-- Data for Name: forum_thread_tag; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.forum_thread_tag VALUES (4, 24);
INSERT INTO public.forum_thread_tag VALUES (4, 29);
INSERT INTO public.forum_thread_tag VALUES (4, 10);
INSERT INTO public.forum_thread_tag VALUES (4, 13);
INSERT INTO public.forum_thread_tag VALUES (5, 9);
INSERT INTO public.forum_thread_tag VALUES (5, 21);
INSERT INTO public.forum_thread_tag VALUES (5, 18);
INSERT INTO public.forum_thread_tag VALUES (5, 13);


--
-- Data for Name: forward_contracts; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: harvest_listings; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.harvest_listings VALUES (2, 8, 'Sakura Rice', 'Japanese rice for affordable price', 'Rice', 500.00, 20.76, 10380.00, 'PHP', '2026-09-29', '2027-03-28', 'available', 180, true, '2026-09-29 09:29:30', '2026-10-01 03:02:56');
INSERT INTO public.harvest_listings VALUES (3, 8, 'Sakura Rice', 'Japanese rice for affordable price', 'Rice', 150.00, 20.76, 3114.00, 'PHP', '2026-09-29', '2027-03-28', 'sold', 180, true, '2026-10-01 03:02:56', '2026-10-01 03:03:33');


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: purchases; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.purchases VALUES (3, 9, NULL, NULL, NULL, 'cash', 260.00, 'PHP', 'completed', '2026-09-29 10:12:17', '2026-09-29 10:11:32', '2026-09-29 10:12:17', NULL, 200.00, 'fully_paid', 2600.00, '2026-09-29 10:12:17', true, 2600.00, 2);
INSERT INTO public.purchases VALUES (4, 9, NULL, 'pay_GtDcqdtuPzrQxtZWaMMp9hYd', 'cs_5cb3dba95b931ad3d414a703', 'gcash', 150.00, 'PHP', 'completed', '2026-09-29 11:17:27', '2026-09-29 11:17:05', '2026-09-29 11:17:27', NULL, 100.00, NULL, 0.00, NULL, true, 1500.00, 3);
INSERT INTO public.purchases VALUES (5, 9, NULL, NULL, 'cs_31e89719067d495788f1ca2f', 'gcash', 40.00, 'PHP', 'failed', NULL, '2026-09-29 11:36:14', '2026-09-29 11:55:14', NULL, 50.00, NULL, 0.00, NULL, true, 400.00, 4);
INSERT INTO public.purchases VALUES (6, 9, NULL, NULL, 'cs_e3fd493d16282abc95259e2e', 'gcash', 40.00, 'PHP', 'failed', NULL, '2026-09-29 11:55:40', '2026-09-29 11:55:47', NULL, 50.00, NULL, 0.00, NULL, true, 400.00, 4);
INSERT INTO public.purchases VALUES (7, 9, NULL, 'pay_ureknTHk81Gd8Eo49td7pndt', 'cs_ea5fb31b85c903c1833eed85', 'paymaya', 400.00, 'PHP', 'completed', '2026-09-29 11:56:55', '2026-09-29 11:56:18', '2026-09-29 12:12:57', NULL, 50.00, NULL, 0.00, NULL, true, 400.00, 4);
INSERT INTO public.purchases VALUES (8, 9, NULL, 'pay_kxzmvYs21xav8JZHYJ3ctwfz', 'cs_23b420882cd97bd94836ebaf', 'paymaya', 3114.00, 'PHP', 'completed', '2026-10-01 03:03:33', '2026-10-01 03:02:56', '2026-10-01 03:03:33', 3, 150.00, NULL, 0.00, NULL, false, 3114.00, NULL);


--
-- Data for Name: reply_votes; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: restricted_zones; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: spatial_ref_sys; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: thread_votes; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.thread_votes VALUES (1, 9, 5, 1, '2026-10-01 03:08:53', '2026-10-01 03:08:53');


--
-- Data for Name: weather_cache; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.weather_cache VALUES (1, 15.4029000, 120.6992000, '{"soil": {"dt": 1790640000, "t0": 300.276, "t10": 299.905, "moisture": 0.292}, "weather": {"dt": 1790674102, "main": {"temp": 304.26, "humidity": 78, "pressure": 1011, "temp_max": 304.26, "temp_min": 303.92, "sea_level": 1011, "feels_like": 311.26, "grnd_level": 1007}, "wind": {"deg": 343, "gust": 1.31, "speed": 1.41}, "clouds": {"all": 96}, "weather": [{"id": 804, "icon": "04d", "main": "Clouds", "description": "overcast clouds"}]}}', '2026-09-29 11:28:23', '2026-09-29 09:28:23', '2026-09-29 09:28:23');


--
-- Name: chat_attachments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.chat_attachments_id_seq', 1, false);


--
-- Name: chat_conversations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.chat_conversations_id_seq', 2, true);


--
-- Name: chat_messages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.chat_messages_id_seq', 5, true);


--
-- Name: chat_participants_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.chat_participants_id_seq', 4, true);


--
-- Name: contact_messages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.contact_messages_id_seq', 1, false);


--
-- Name: crop_demand_offers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.crop_demand_offers_id_seq', 4, true);


--
-- Name: crop_demands_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.crop_demands_id_seq', 2, true);


--
-- Name: crop_recommendations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.crop_recommendations_id_seq', 13, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 4, true);


--
-- Name: farms_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.farms_id_seq', 4, true);


--
-- Name: forum_attachments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.forum_attachments_id_seq', 1, false);


--
-- Name: forum_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.forum_categories_id_seq', 14, true);


--
-- Name: forum_replies_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.forum_replies_id_seq', 1, false);


--
-- Name: forum_reports_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.forum_reports_id_seq', 1, false);


--
-- Name: forum_tags_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.forum_tags_id_seq', 31, true);


--
-- Name: forum_threads_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.forum_threads_id_seq', 5, true);


--
-- Name: forward_contracts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.forward_contracts_id_seq', 3, true);


--
-- Name: harvest_listings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.harvest_listings_id_seq', 3, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.migrations_id_seq', 22, true);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 32, true);


--
-- Name: plots_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.plots_id_seq', 4, true);


--
-- Name: purchases_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.purchases_id_seq', 8, true);


--
-- Name: reply_votes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.reply_votes_id_seq', 1, false);


--
-- Name: restricted_zones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.restricted_zones_id_seq', 1, false);


--
-- Name: thread_votes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.thread_votes_id_seq', 1, true);


--
-- Name: user_addresses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.user_addresses_id_seq', 3, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.users_id_seq', 10, true);


--
-- Name: weather_cache_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.weather_cache_id_seq', 1, true);


--
-- PostgreSQL database dump complete
--

