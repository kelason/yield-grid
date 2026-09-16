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
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.cache VALUES ('yieldgrid-cache-geo_coord_15.401_120.699', 'a:3:{s:4:"city";s:6:"La Paz";s:5:"state";s:6:"Tarlac";s:7:"country";s:11:"Philippines";}', 1792062955);
INSERT INTO public.cache VALUES ('yieldgrid-cache-geo_coord_15.401_120.7', 'a:3:{s:4:"city";s:6:"La Paz";s:5:"state";s:6:"Tarlac";s:7:"country";s:11:"Philippines";}', 1792065743);
INSERT INTO public.cache VALUES ('yieldgrid-cache-geo_coord_15.445_120.717', 'a:3:{s:4:"city";s:6:"La Paz";s:5:"state";s:6:"Tarlac";s:7:"country";s:11:"Philippines";}', 1792066281);
INSERT INTO public.cache VALUES ('yieldgrid-cache-analyzing_plot_20', 'b:1;', 1789474798);
INSERT INTO public.cache VALUES ('yieldgrid-cache-analyzing_plot_19', 'b:1;', 1789474854);
INSERT INTO public.cache VALUES ('yieldgrid-cache-geo_coord_15.474_121.034', 'a:3:{s:4:"city";s:10:"Cabanatuan";s:5:"state";s:11:"Nueva Ecija";s:7:"country";s:11:"Philippines";}', 1792066844);
INSERT INTO public.cache VALUES ('yieldgrid-cache-analyzing_plot_18', 'b:1;', 1789474892);
INSERT INTO public.cache VALUES ('yieldgrid-cache-91032ad7bbcb6cf72875e8e8207dcfba80173f7c:timer', 'i:1789517074;', 1789517074);
INSERT INTO public.cache VALUES ('yieldgrid-cache-91032ad7bbcb6cf72875e8e8207dcfba80173f7c', 'i:1;', 1789517074);
INSERT INTO public.cache VALUES ('yieldgrid-cache-analyzing_plot_16', 'b:1;', 1789513489);


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: contact_messages; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.users VALUES (20, 'kevin sapang', 'kevin.sapang@gmail.com', NULL, '$2y$12$AY4cn.HP/8WL3/uSAE9V2uQmSZPGyr8Ujg2u1Xvcq4ZpCaux72jJ6', 'farmer', NULL, NULL, NULL, NULL, '2026-09-15 08:20:49', '2026-09-15 08:20:49');
INSERT INTO public.users VALUES (21, 'Farmer Test', 'farmer@example.com', NULL, '$2y$12$Die6.M8Tdy5vC8qA.pj9QuOBTwUT2EPBMlfOB8SRn/8B/AQR0Kh2a', 'farmer', NULL, NULL, NULL, NULL, '2026-09-15 08:51:23', '2026-09-15 08:51:23');


--
-- Data for Name: farms; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.farms VALUES (16, 20, 'Kevin''s Farm', 'Purok 4', 'Cabanatuan City', 'Nueva Ecija', '3100', 85, '2026-09-15 08:21:04', '2026-09-15 08:21:04', 'Philippines');
INSERT INTO public.farms VALUES (17, 21, 'Green Valley Farm', NULL, NULL, NULL, NULL, NULL, '2026-09-15 08:52:05', '2026-09-15 08:52:05', NULL);
INSERT INTO public.farms VALUES (18, 20, 'Kevin''s Farm', NULL, NULL, NULL, NULL, NULL, '2026-09-15 09:06:45', '2026-09-15 09:06:45', NULL);
INSERT INTO public.farms VALUES (19, 20, 'Kevin''s Farm', '123', 'La Paz', 'Tarlac', '2314', 33, '2026-09-15 11:11:49', '2026-09-15 11:11:49', 'Philippines');


--
-- Data for Name: plots; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.plots VALUES (14, 17, 'Plot A', '0103000020E6100000010000000400000037A79201A0F85B40B1C05774EBE522408690F3FE3F855C40B1C05774EBE52240EA3C2AFEEF3E5C40D8666325E6191D4037A79201A0F85B40B1C05774EBE52240', 'loamy', 2903337.408438991, '2026-09-15 08:55:05', '2026-09-15 08:55:05', NULL);
INSERT INTO public.plots VALUES (15, 17, 'Plot B', '0103000020E610000001000000040000000803CFBD870B02403B1DC87A6A6B48408066101FD85102403B1DC87A6A6B484047938B31B02E0240BCB376DB856848400803CFBD870B02403B1DC87A6A6B4840', 'clay', 316.7531085298538, '2026-09-15 09:28:05', '2026-09-15 09:28:05', NULL);
INSERT INTO public.plots VALUES (16, 16, 'Plot 1', '0103000020E6100000010000000400000040BE840A0E425E409FAEEE586CF32E40C2C2499A3F425E40ECA353573EF32E40CBBA7F2C44425E4037C30DF8FCF02E4040BE840A0E425E409FAEEE586CF32E40', 'clay', 7.853059980098903, '2026-09-15 10:08:24', '2026-09-15 10:08:24', NULL);
INSERT INTO public.plots VALUES (17, 17, 'North Field', '0103000020E610000001000000050000000000000000205EC000000000004042401F85EB51B81E5EC000000000004042401F85EB51B81E5EC0C3F5285C8F4242400000000000205EC0C3F5285C8F4242400000000000205EC00000000000404240', 'loamy', 12.5, '2026-09-15 11:08:49', '2026-09-15 11:08:49', NULL);
INSERT INTO public.plots VALUES (18, 19, 'Plot 1', '0103000020E6100000010000000500000015AA9B8BBF2C5E40E06932E36DCD2E401FA2D11DC42C5E4049A12C7C7DCD2E4095B88E71C52C5E40E8D9ACFA5CCD2E400E9F7422C12C5E409696917A4FCD2E4015AA9B8BBF2C5E40E06932E36DCD2E40', 'sandy', 0.08899079158715904, '2026-09-15 11:15:55', '2026-09-15 11:15:55', NULL);
INSERT INTO public.plots VALUES (19, 19, 'Plot 2', '0103000020E6100000010000000500000013286211C32C5E406687F8872DCD2E40A5BBEB6CC82C5E40B020CD5834CD2E40A453573ECB2C5E40C9AA083719CD2E40A587A1D5C92C5E402DCE18E604CD2E4013286211C32C5E406687F8872DCD2E40', 'loamy', 0.09936665883921086, '2026-09-15 12:02:23', '2026-09-15 12:02:23', NULL);
INSERT INTO public.plots VALUES (20, 19, 'Plot 3', '0103000020E61000000100000005000000CA367007EA2D5E407EC3448314E42E40D314014EEF2D5E408733BF9A03E42E4052D2C3D0EA2D5E40C6A4BF97C2E32E40C03E3A75E52D5E40AC1A84B9DDE32E40CA367007EA2D5E407EC3448314E42E40', 'sandy', 0.23127966027334332, '2026-09-15 12:11:21', '2026-09-15 12:11:21', NULL);


--
-- Data for Name: crop_recommendations; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.crop_recommendations VALUES (3, 20, 'Sweet Potato (Camote)', 94, 'Loose sandy soil prevents subterranean compaction, enabling tubers to expand smoothly without root deformities or rot. Capitalizes on high commercial demand and regional root-crop processing hubs in La Paz, Tarlac. Perfectly scaled for 0.23 hectares.', '3.35 tons total (14.5 tons/ha on 0.23 ha)', 'pending', '2026-09-15 12:19:43', '2026-09-15 12:19:43');
INSERT INTO public.crop_recommendations VALUES (4, 20, 'Peanuts (Mani)', 91, 'Porous, aerated sandy soil allows flower pegs to easily penetrate beneath the surface to develop pods while fixing vital soil nitrogen. Strong local wholesale and food processor buying networks in La Paz, Tarlac. Perfectly scaled for 0.23 hectares.', '0.65 tons total (2.8 tons/ha on 0.23 ha)', 'pending', '2026-09-15 12:19:43', '2026-09-15 12:19:43');
INSERT INTO public.crop_recommendations VALUES (5, 20, 'Watermelon (Pakwan)', 90, 'Warm, quick-draining sandy soil promotes high sugar content (Brix) and prevents fungal root-rot during fruit set. High summer cash turnover and strong demand across wholesale markets in La Paz, Tarlac. Perfectly scaled for 0.23 hectares.', '6.01 tons total (26.0 tons/ha on 0.23 ha)', 'pending', '2026-09-15 12:19:43', '2026-09-15 12:19:43');
INSERT INTO public.crop_recommendations VALUES (6, 20, 'Cassava (Kamoteng Kahoy)', 87, 'Drought-hardy starch crop that thrives with minimal inputs in loose sandy ground with effortless harvest pulling. Steady industrial feed and starch mill demand in La Paz, Tarlac. Perfectly scaled for 0.23 hectares.', '5.09 tons total (22.0 tons/ha on 0.23 ha)', 'pending', '2026-09-15 12:19:43', '2026-09-15 12:19:43');
INSERT INTO public.crop_recommendations VALUES (7, 20, 'Hot Chili (Siling Labuyo)', 88, 'Warm, organic-rich sandy soil stimulates concentrated capsaicin synthesis and continuous pod set. Outstanding value per square meter, offering high income density on compact plots in La Paz, Tarlac. Perfectly scaled for 0.23 hectares.', '1.97 tons total (8.5 tons/ha on 0.23 ha)', 'pending', '2026-09-15 12:19:43', '2026-09-15 12:19:43');
INSERT INTO public.crop_recommendations VALUES (8, 20, 'Sunflowers (Commercial Sunflower)', 86, 'Deep taproot extracts calcium and micronutrients from alkaline sandy soil with superior drought resilience. Dual revenue potential from seed harvest and popular local agritourism in La Paz, Tarlac. Perfectly scaled for 0.23 hectares.', '0.74 tons total (3.2 tons/ha on 0.23 ha)', 'pending', '2026-09-15 12:19:43', '2026-09-15 12:19:43');
INSERT INTO public.crop_recommendations VALUES (9, 20, 'Mungbean (Munggo / Balatong)', 89, 'Short-duration legume that enriches sandy soil with fixed atmospheric nitrogen between primary rotations. High staple consumer demand and active wholesale consolidation hubs across La Paz, Tarlac. Perfectly scaled for 0.23 hectares.', '0.32 tons total (1.4 tons/ha on 0.23 ha)', 'pending', '2026-09-15 12:19:43', '2026-09-15 12:19:43');
INSERT INTO public.crop_recommendations VALUES (10, 20, 'Okra (Lady''s Finger)', 87, 'Hardy taproot tolerates both wet and dry cycles in sandy ground while continuously bearing tender pods. Growing export and domestic fresh processing linkages operating in La Paz, Tarlac. Perfectly scaled for 0.23 hectares.', '2.31 tons total (10.0 tons/ha on 0.23 ha)', 'pending', '2026-09-15 12:19:43', '2026-09-15 12:19:43');
INSERT INTO public.crop_recommendations VALUES (11, 20, 'Garlic (Bawang)', 83, 'Well-draining, non-crusting sandy soil facilitates uniform bulb cloves without fungal damping. High-value commodity enjoying premium local market pricing across La Paz, Tarlac. Perfectly scaled for 0.23 hectares.', '1.27 tons total (5.5 tons/ha on 0.23 ha)', 'pending', '2026-09-15 12:19:43', '2026-09-15 12:19:43');
INSERT INTO public.crop_recommendations VALUES (12, 20, 'Cucumber (Pipino)', 89, 'Fast-rooting crop that extracts balanced moisture from aerated sandy beds without root stagnation. Short 45-day turnaround delivering quick seasonal cash flow to farmers in La Paz, Tarlac. Perfectly scaled for 0.23 hectares.', '5.78 tons total (25.0 tons/ha on 0.23 ha)', 'pending', '2026-09-15 12:19:43', '2026-09-15 12:19:43');
INSERT INTO public.crop_recommendations VALUES (13, 19, 'Sweet Potato (Camote)', 92, 'Loose loamy soil prevents subterranean compaction, enabling tubers to expand smoothly without root deformities or rot. Capitalizes on high commercial demand and regional root-crop processing hubs in La Paz, Tarlac. Perfectly scaled for 0.10 hectares.', '1.44 tons total (14.5 tons/ha on 0.10 ha)', 'pending', '2026-09-15 12:20:39', '2026-09-15 12:20:39');
INSERT INTO public.crop_recommendations VALUES (14, 19, 'Peanuts (Mani)', 90, 'Porous, aerated loamy soil allows flower pegs to easily penetrate beneath the surface to develop pods while fixing vital soil nitrogen. Strong local wholesale and food processor buying networks in La Paz, Tarlac. Perfectly scaled for 0.10 hectares.', '0.28 tons total (2.8 tons/ha on 0.10 ha)', 'pending', '2026-09-15 12:20:39', '2026-09-15 12:20:39');
INSERT INTO public.crop_recommendations VALUES (15, 19, 'Hot Chili (Siling Labuyo)', 88, 'Warm, organic-rich loamy soil stimulates concentrated capsaicin synthesis and continuous pod set. Outstanding value per square meter, offering high income density on compact plots in La Paz, Tarlac. Perfectly scaled for 0.10 hectares.', '0.84 tons total (8.5 tons/ha on 0.10 ha)', 'pending', '2026-09-15 12:20:39', '2026-09-15 12:20:39');
INSERT INTO public.crop_recommendations VALUES (16, 19, 'String Beans (Sitaw)', 91, 'Rapid vine development in fertile loamy ground with high continuous pod yields on trellises. Consistent daily turnover through regional trading posts and public markets in La Paz, Tarlac. Perfectly scaled for 0.10 hectares.', '1.24 tons total (12.5 tons/ha on 0.10 ha)', 'pending', '2026-09-15 12:20:39', '2026-09-15 12:20:39');
INSERT INTO public.crop_recommendations VALUES (17, 19, 'Okra (Lady''s Finger)', 90, 'Hardy taproot tolerates both wet and dry cycles in loamy ground while continuously bearing tender pods. Growing export and domestic fresh processing linkages operating in La Paz, Tarlac. Perfectly scaled for 0.10 hectares.', '0.99 tons total (10.0 tons/ha on 0.10 ha)', 'pending', '2026-09-15 12:20:39', '2026-09-15 12:20:39');
INSERT INTO public.crop_recommendations VALUES (18, 19, 'Garlic (Bawang)', 86, 'Well-draining, non-crusting loamy soil facilitates uniform bulb cloves without fungal damping. High-value commodity enjoying premium local market pricing across La Paz, Tarlac. Perfectly scaled for 0.10 hectares.', '0.55 tons total (5.5 tons/ha on 0.10 ha)', 'pending', '2026-09-15 12:20:39', '2026-09-15 12:20:39');
INSERT INTO public.crop_recommendations VALUES (19, 19, 'Cucumber (Pipino)', 88, 'Fast-rooting crop that extracts balanced moisture from aerated loamy beds without root stagnation. Short 45-day turnaround delivering quick seasonal cash flow to farmers in La Paz, Tarlac. Perfectly scaled for 0.10 hectares.', '2.48 tons total (25.0 tons/ha on 0.10 ha)', 'pending', '2026-09-15 12:20:39', '2026-09-15 12:20:39');
INSERT INTO public.crop_recommendations VALUES (20, 19, 'Red Creole Onions (Sibuyas)', 95, 'Fine, non-crusting loamy soil allows onion bulbs to expand symmetrically without constriction. Direct proximity to Bongabon/Nueva Ecija cold-storage and national wholesale dispatch in La Paz, Tarlac. Perfectly scaled for 0.10 hectares.', '1.64 tons total (16.5 tons/ha on 0.10 ha)', 'pending', '2026-09-15 12:20:39', '2026-09-15 12:20:39');
INSERT INTO public.crop_recommendations VALUES (21, 19, 'Tomatoes (Kamatis)', 92, 'Balanced loamy soil provides optimal drainage and steady capillary water, suppressing bacterial wilt. High daily turnover and strong sales through local fresh wet markets in La Paz, Tarlac. Perfectly scaled for 0.10 hectares.', '2.19 tons total (22.0 tons/ha on 0.10 ha)', 'pending', '2026-09-15 12:20:39', '2026-09-15 12:20:39');
INSERT INTO public.crop_recommendations VALUES (22, 19, 'Eggplant (Talong)', 87, 'Resilient taproot system thrives in nutrient-dense loamy soil, delivering continuous weekly flushes. Year-round dietary staple with stable farm-gate prices in La Paz, Tarlac. Perfectly scaled for 0.10 hectares.', '1.89 tons total (19.0 tons/ha on 0.10 ha)', 'pending', '2026-09-15 12:20:39', '2026-09-15 12:20:39');
INSERT INTO public.crop_recommendations VALUES (33, 18, 'Sweet Potato (Camote)', 94, 'Loose sandy soil prevents subterranean compaction, enabling tubers to expand smoothly without root deformities or rot. Capitalizes on high commercial demand and regional root-crop processing hubs in La Paz, Tarlac. Perfectly scaled for 0.09 hectares.', '1.29 tons total (14.5 tons/ha on 0.09 ha)', 'pending', '2026-09-15 12:21:17', '2026-09-15 12:21:17');
INSERT INTO public.crop_recommendations VALUES (34, 18, 'Peanuts (Mani)', 90, 'Porous, aerated sandy soil allows flower pegs to easily penetrate beneath the surface to develop pods while fixing vital soil nitrogen. Strong local wholesale and food processor buying networks in La Paz, Tarlac. Perfectly scaled for 0.09 hectares.', '0.25 tons total (2.8 tons/ha on 0.09 ha)', 'pending', '2026-09-15 12:21:17', '2026-09-15 12:21:17');
INSERT INTO public.crop_recommendations VALUES (35, 18, 'Hot Chili (Siling Labuyo)', 88, 'Warm, organic-rich sandy soil stimulates concentrated capsaicin synthesis and continuous pod set. Outstanding value per square meter, offering high income density on compact plots in La Paz, Tarlac. Perfectly scaled for 0.09 hectares.', '0.76 tons total (8.5 tons/ha on 0.09 ha)', 'pending', '2026-09-15 12:21:17', '2026-09-15 12:21:17');
INSERT INTO public.crop_recommendations VALUES (36, 18, 'Okra (Lady''s Finger)', 88, 'Hardy taproot tolerates both wet and dry cycles in sandy ground while continuously bearing tender pods. Growing export and domestic fresh processing linkages operating in La Paz, Tarlac. Perfectly scaled for 0.09 hectares.', '0.89 tons total (10.0 tons/ha on 0.09 ha)', 'pending', '2026-09-15 12:21:17', '2026-09-15 12:21:17');
INSERT INTO public.crop_recommendations VALUES (37, 18, 'Garlic (Bawang)', 86, 'Well-draining, non-crusting sandy soil facilitates uniform bulb cloves without fungal damping. High-value commodity enjoying premium local market pricing across La Paz, Tarlac. Perfectly scaled for 0.09 hectares.', '0.49 tons total (5.5 tons/ha on 0.09 ha)', 'pending', '2026-09-15 12:21:17', '2026-09-15 12:21:17');
INSERT INTO public.crop_recommendations VALUES (38, 18, 'Cucumber (Pipino)', 87, 'Fast-rooting crop that extracts balanced moisture from aerated sandy beds without root stagnation. Short 45-day turnaround delivering quick seasonal cash flow to farmers in La Paz, Tarlac. Perfectly scaled for 0.09 hectares.', '2.22 tons total (25.0 tons/ha on 0.09 ha)', 'pending', '2026-09-15 12:21:17', '2026-09-15 12:21:17');
INSERT INTO public.crop_recommendations VALUES (39, 18, 'Red Creole Onions (Sibuyas)', 97, 'Fine, non-crusting sandy soil allows onion bulbs to expand symmetrically without constriction. Direct proximity to Bongabon/Nueva Ecija cold-storage and national wholesale dispatch in La Paz, Tarlac. Perfectly scaled for 0.09 hectares.', '1.47 tons total (16.5 tons/ha on 0.09 ha)', 'pending', '2026-09-15 12:21:17', '2026-09-15 12:21:17');
INSERT INTO public.crop_recommendations VALUES (40, 18, 'Native Ginger (Luya)', 88, 'Friable, organic-rich sandy soil prevents rhizome rot and enables massive underground cluster expansion. Lucrative cash-density per square meter in local culinary and herbal markets across La Paz, Tarlac. Perfectly scaled for 0.09 hectares.', '1.60 tons total (18.0 tons/ha on 0.09 ha)', 'pending', '2026-09-15 12:21:17', '2026-09-15 12:21:17');
INSERT INTO public.crop_recommendations VALUES (41, 18, 'String Beans (Sitaw)', 89, 'Rapid vine development in fertile sandy ground with high continuous pod yields on trellises. Consistent daily turnover through regional trading posts and public markets in La Paz, Tarlac. Perfectly scaled for 0.09 hectares.', '1.11 tons total (12.5 tons/ha on 0.09 ha)', 'pending', '2026-09-15 12:21:17', '2026-09-15 12:21:17');
INSERT INTO public.crop_recommendations VALUES (42, 18, 'Watermelon (Pakwan)', 88, 'Warm, quick-draining sandy soil promotes high sugar content (Brix) and prevents fungal root-rot during fruit set. High summer cash turnover and strong demand across wholesale markets in La Paz, Tarlac. Perfectly scaled for 0.09 hectares.', '2.31 tons total (26.0 tons/ha on 0.09 ha)', 'pending', '2026-09-15 12:21:17', '2026-09-15 12:21:17');
INSERT INTO public.crop_recommendations VALUES (43, 16, 'Lowland Rice (Palay)', 98, 'Heavy clay soil creates an impermeable hardpan that retains standing water efficiently, drastically reducing pumping and irrigation expenses. Direct access to major rice trading stations and drying mills across Cabanatuan, Nueva Ecija. Perfectly scaled for 7.85 hectares.', '45.55 tons total (5.8 tons/ha on 7.85 ha)', 'pending', '2026-09-15 23:04:34', '2026-09-15 23:04:34');
INSERT INTO public.crop_recommendations VALUES (44, 16, 'Yellow Corn (Maize)', 90, 'Sturdy root architecture draws deep nutrients from fertile clay soil. Surging feed-mill demand from livestock and poultry operators in Cabanatuan, Nueva Ecija. Perfectly scaled for 7.85 hectares.', '40.84 tons total (5.2 tons/ha on 7.85 ha)', 'pending', '2026-09-15 23:04:34', '2026-09-15 23:04:34');
INSERT INTO public.crop_recommendations VALUES (45, 16, 'Grain Sorghum', 85, 'Tolerates alkaline pH and mineral-heavy clay ground where sensitive vegetables struggle. Reliable climate-resilient feed grain with steady commercial off-takers in Cabanatuan, Nueva Ecija. Perfectly scaled for 7.85 hectares.', '36.12 tons total (4.6 tons/ha on 7.85 ha)', 'pending', '2026-09-15 23:04:34', '2026-09-15 23:04:34');
INSERT INTO public.crop_recommendations VALUES (46, 16, 'Eggplant (Talong)', 90, 'Resilient taproot system thrives in nutrient-dense clay soil, delivering continuous weekly flushes. Year-round dietary staple with stable farm-gate prices in Cabanatuan, Nueva Ecija. Perfectly scaled for 7.85 hectares.', '149.21 tons total (19.0 tons/ha on 7.85 ha)', 'pending', '2026-09-15 23:04:34', '2026-09-15 23:04:34');
INSERT INTO public.crop_recommendations VALUES (47, 16, 'String Beans (Sitaw)', 88, 'Rapid vine development in fertile clay ground with high continuous pod yields on trellises. Consistent daily turnover through regional trading posts and public markets in Cabanatuan, Nueva Ecija. Perfectly scaled for 7.85 hectares.', '98.16 tons total (12.5 tons/ha on 7.85 ha)', 'pending', '2026-09-15 23:04:34', '2026-09-15 23:04:34');
INSERT INTO public.crop_recommendations VALUES (48, 16, 'Okra (Lady''s Finger)', 88, 'Hardy taproot tolerates both wet and dry cycles in clay ground while continuously bearing tender pods. Growing export and domestic fresh processing linkages operating in Cabanatuan, Nueva Ecija. Perfectly scaled for 7.85 hectares.', '78.53 tons total (10.0 tons/ha on 7.85 ha)', 'pending', '2026-09-15 23:04:34', '2026-09-15 23:04:34');
INSERT INTO public.crop_recommendations VALUES (49, 16, 'Squash (Kalabasa)', 86, 'Wide trailing canopy shades clay soil, suppressing weed growth and conserving root-zone moisture. Long post-harvest shelf life providing price stability during transport across Cabanatuan, Nueva Ecija. Perfectly scaled for 7.85 hectares.', '157.06 tons total (20.0 tons/ha on 7.85 ha)', 'pending', '2026-09-15 23:04:34', '2026-09-15 23:04:34');
INSERT INTO public.crop_recommendations VALUES (50, 16, 'Taro (Gabi)', 87, 'Semi-aquatic root crop that thrives in wet, heavy clay soil where standard vegetables would suffocate. High regional culinary demand for both leaves and corms in Cabanatuan, Nueva Ecija. Perfectly scaled for 7.85 hectares.', '102.09 tons total (13.0 tons/ha on 7.85 ha)', 'pending', '2026-09-15 23:04:34', '2026-09-15 23:04:34');
INSERT INTO public.crop_recommendations VALUES (51, 16, 'Sunflowers (Commercial Sunflower)', 85, 'Deep taproot extracts calcium and micronutrients from alkaline clay soil with superior drought resilience. Dual revenue potential from seed harvest and popular local agritourism in Cabanatuan, Nueva Ecija. Perfectly scaled for 7.85 hectares.', '25.13 tons total (3.2 tons/ha on 7.85 ha)', 'pending', '2026-09-15 23:04:34', '2026-09-15 23:04:34');
INSERT INTO public.crop_recommendations VALUES (52, 16, 'Sugarcane (Tubo)', 92, 'Dense clay soil anchors heavy cane stalks securely against typhoon winds while maintaining continuous moisture for high sugar synthesis. Strategic proximity to regional sugar centrals and milling facilities in Cabanatuan, Nueva Ecija. Perfectly scaled for 7.85 hectares.', '565.42 tons total (72.0 tons/ha on 7.85 ha)', 'pending', '2026-09-15 23:04:34', '2026-09-15 23:04:34');


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.personal_access_tokens VALUES (2, 'Domain\Users\Models\User', 21, 'auth_token', 'd639fa824c55c3951438b66be5d21e4a395ec06b8a304c9360442e337e9c8992', '["*"]', '2026-09-15 09:45:45', NULL, '2026-09-15 08:51:24', '2026-09-15 09:45:45');
INSERT INTO public.personal_access_tokens VALUES (1, 'Domain\Users\Models\User', 20, 'auth_token', 'a0b4ebe2b6754174f5ca961fe6af204feac8292c7fed539b3a746a98f9bdab3f', '["*"]', '2026-09-15 12:23:37', NULL, '2026-09-15 08:20:50', '2026-09-15 12:23:37');
INSERT INTO public.personal_access_tokens VALUES (3, 'Domain\Users\Models\User', 20, 'auth_token', 'e3f81c7e852357c1514c77a6a237dd52a07cbf3e19722e9e06b4a114b546a4ba', '["*"]', '2026-09-15 23:21:44', NULL, '2026-09-15 23:04:24', '2026-09-15 23:21:44');


--
-- Data for Name: restricted_zones; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.restricted_zones VALUES (1, 'Farm House', 'house', '0103000020E61000000100000005000000D7A3703D0A1F5EC0713D0AD7A3404240F4FDD478E91E5EC0713D0AD7A3404240F4FDD478E91E5EC037894160E5404240D7A3703D0A1F5EC037894160E5404240D7A3703D0A1F5EC0713D0AD7A3404240', '2026-09-15 11:09:21', '2026-09-15 11:09:21');
INSERT INTO public.restricted_zones VALUES (2, 'Local River', 'river', '0103000020E6100000010000000500000048E17A14AE1F5EC08FC2F5285C3F42408FC2F5285C1F5EC01F85EB51B83E4240AC1C5A643B1F5EC0E5D022DBF93E4240643BDF4F8D1F5EC0560E2DB29D3F424048E17A14AE1F5EC08FC2F5285C3F4240', '2026-09-15 11:09:21', '2026-09-15 11:09:21');


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.sessions VALUES ('hDPCGK45FshHYHKKNEi8GMUKWCq2tqNI5iu805TL', NULL, '127.0.0.1', 'Symfony', 'YTozOntzOjY6Il90b2tlbiI7czo0MDoic0ZOR2pIZmhadnF6SktHTFFEaldld2RmVmR4YUpFYlVXbm4zdlN0UCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6MjE6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMCI7czo1OiJyb3V0ZSI7Tjt9czo2OiJfZmxhc2giO2E6Mjp7czozOiJvbGQiO2E6MDp7fXM6MzoibmV3IjthOjA6e319fQ==', 1789460214);


--
-- Data for Name: spatial_ref_sys; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: weather_cache; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--



--
-- Data for Name: geocode_settings; Type: TABLE DATA; Schema: tiger; Owner: yieldgrid
--



--
-- Data for Name: pagc_gaz; Type: TABLE DATA; Schema: tiger; Owner: yieldgrid
--



--
-- Data for Name: pagc_lex; Type: TABLE DATA; Schema: tiger; Owner: yieldgrid
--



--
-- Data for Name: pagc_rules; Type: TABLE DATA; Schema: tiger; Owner: yieldgrid
--



--
-- Data for Name: topology; Type: TABLE DATA; Schema: topology; Owner: yieldgrid
--



--
-- Data for Name: layer; Type: TABLE DATA; Schema: topology; Owner: yieldgrid
--



--
-- Name: contact_messages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.contact_messages_id_seq', 1, false);


--
-- Name: crop_recommendations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.crop_recommendations_id_seq', 52, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: farms_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.farms_id_seq', 19, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.migrations_id_seq', 12, true);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 3, true);


--
-- Name: plots_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.plots_id_seq', 20, true);


--
-- Name: restricted_zones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.restricted_zones_id_seq', 2, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.users_id_seq', 21, true);


--
-- Name: weather_cache_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.weather_cache_id_seq', 1, false);


--
-- Name: topology_id_seq; Type: SEQUENCE SET; Schema: topology; Owner: yieldgrid
--

SELECT pg_catalog.setval('topology.topology_id_seq', 1, false);


--
-- PostgreSQL database dump complete
--

