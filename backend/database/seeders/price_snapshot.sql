--
-- PostgreSQL database dump
--


-- Dumped from database version 16.15 (Postgres.app)
-- Dumped by pg_dump version 16.15 (Homebrew)

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
-- Data for Name: crop_price_aliases; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.crop_price_aliases VALUES (1, 'palay', 'rice', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (2, 'bigas', 'rice', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (3, 'mais', 'corn', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (4, 'kamatis', 'tomato', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (5, 'tomatoes', 'tomato', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (6, 'sibuyas', 'onion', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (7, 'onions', 'onion', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (8, 'bawang', 'garlic', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (9, 'luya', 'ginger', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (10, 'talong', 'eggplant', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (11, 'eggplants', 'eggplant', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (12, 'bitter-gourd', 'ampalaya', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (13, 'sili', 'chili', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (14, 'calamansi', 'calamansi', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (15, 'kalamansi', 'calamansi', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (16, 'sitaw', 'sitao', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (17, 'string-beans', 'sitao', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (18, 'okra', 'okra', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (19, 'repolyo', 'cabbage', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (20, 'carrots', 'carrot', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (21, 'karot', 'carrot', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (22, 'patatas', 'potato', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (23, 'potatoes', 'potato', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (24, 'kamote', 'sweet-potato', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (25, 'saging', 'banana', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (26, 'bananas', 'banana', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (27, 'mangga', 'mango', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (28, 'pinya', 'pineapple', 'seed', true, '2026-10-01 11:24:01', '2026-10-01 11:24:01');
INSERT INTO public.crop_price_aliases VALUES (29, 'bitter-gourd-ampalaya', 'ampalaya', 'ai', false, '2026-10-01 11:51:41', '2026-10-01 11:51:41');


--
-- Data for Name: crop_price_sync_runs; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.crop_price_sync_runs VALUES (1, 'success', 215, NULL, '2026-10-01 11:45:35', '2026-10-01 11:45:49', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_price_sync_runs VALUES (2, 'success', 215, NULL, '2026-10-01 12:03:34', '2026-10-01 12:03:46', '2026-10-01 12:03:46', '2026-10-01 12:03:46');
INSERT INTO public.crop_price_sync_runs VALUES (3, 'success', 254, NULL, '2026-10-01 12:04:30', '2026-10-01 12:04:44', '2026-10-01 12:04:44', '2026-10-01 12:04:44');


--
-- Data for Name: crop_reference_prices; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

INSERT INTO public.crop_reference_prices VALUES (1, 'rice', 'Rice', 'farmgate', 23.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 11:24:55', '2026-10-01 11:24:55');
INSERT INTO public.crop_reference_prices VALUES (2, 'rice', 'Rice', 'wholesale', 38.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 11:24:55', '2026-10-01 11:24:55');
INSERT INTO public.crop_reference_prices VALUES (4, 'sweet-potato-kamote', 'Sweet Potato (Kamote)', 'farmgate', 25.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 11:25:30', '2026-10-01 11:25:30');
INSERT INTO public.crop_reference_prices VALUES (5, 'sweet-potato-kamote', 'Sweet Potato (Kamote)', 'wholesale', 40.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 11:25:30', '2026-10-01 11:25:30');
INSERT INTO public.crop_reference_prices VALUES (6, 'sweet-potato-kamote', 'Sweet Potato (Kamote)', 'retail', 70.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 11:25:30', '2026-10-01 11:25:30');
INSERT INTO public.crop_reference_prices VALUES (7, 'onion', 'Onion', 'farmgate', 60.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 11:26:07', '2026-10-01 11:26:07');
INSERT INTO public.crop_reference_prices VALUES (8, 'onion', 'Onion', 'wholesale', 90.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 11:26:07', '2026-10-01 11:26:07');
INSERT INTO public.crop_reference_prices VALUES (10, 'potato', 'Potato', 'farmgate', 60.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 11:43:34', '2026-10-01 11:43:34');
INSERT INTO public.crop_reference_prices VALUES (11, 'potato', 'Potato', 'wholesale', 85.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 11:43:34', '2026-10-01 11:43:34');
INSERT INTO public.crop_reference_prices VALUES (13, 'commercial-imported-premium-yellow-tagged', 'COMMERCIAL (IMPORTED) Premium (Yellow tagged)', 'retail', 52.66, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (14, 'commercial-imported-regular-milled', 'COMMERCIAL (IMPORTED) Regular Milled', 'retail', 37.50, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (15, 'commercial-imported-special-blue-tagged', 'COMMERCIAL (IMPORTED) Special (Blue tagged)', 'retail', 60.00, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (16, 'commercial-imported-well-milled', 'COMMERCIAL (IMPORTED) Well Milled', 'retail', 45.23, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (17, 'commercial-local-premium-yellow-tagged', 'COMMERCIAL (LOCAL) Premium (Yellow tagged)', 'retail', 52.04, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (18, 'commercial-local-regular-milled', 'COMMERCIAL (LOCAL) Regular Milled', 'retail', 42.00, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (19, 'commercial-local-special-blue-tagged', 'COMMERCIAL (LOCAL) Special (Blue tagged)', 'retail', 59.89, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (20, 'commercial-local-well-milled', 'COMMERCIAL (LOCAL) Well Milled', 'retail', 47.42, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (21, 'commercial-imported-premium-yellow-tagged', 'COMMERCIAL (IMPORTED) Premium (Yellow tagged)', 'retail', 46.55, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (22, 'commercial-imported-regular-milled', 'COMMERCIAL (IMPORTED) Regular Milled', 'retail', 30.00, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (23, 'commercial-imported-special-blue-tagged', 'COMMERCIAL (IMPORTED) Special (Blue tagged)', 'retail', 58.14, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (24, 'commercial-imported-well-milled', 'COMMERCIAL (IMPORTED) Well Milled', 'retail', 36.67, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (25, 'commercial-local-premium-yellow-tagged', 'COMMERCIAL (LOCAL) Premium (Yellow tagged)', 'retail', 51.08, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (26, 'commercial-local-regular-milled', 'COMMERCIAL (LOCAL) Regular Milled', 'retail', 42.83, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (27, 'commercial-local-special-blue-tagged', 'COMMERCIAL (LOCAL) Special (Blue tagged)', 'retail', 60.18, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (28, 'commercial-local-well-milled', 'COMMERCIAL (LOCAL) Well Milled', 'retail', 46.45, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (29, 'nfa', 'NFA', 'retail', 55.00, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (30, 'commercial-imported-premium-yellow-tagged', 'COMMERCIAL (IMPORTED) Premium (Yellow tagged)', 'retail', 50.67, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (31, 'commercial-imported-regular-milled', 'COMMERCIAL (IMPORTED) Regular Milled', 'retail', 43.33, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (32, 'commercial-imported-special-blue-tagged', 'COMMERCIAL (IMPORTED) Special (Blue tagged)', 'retail', 55.08, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (33, 'commercial-imported-well-milled', 'COMMERCIAL (IMPORTED) Well Milled', 'retail', 41.88, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (34, 'commercial-local-premium-yellow-tagged', 'COMMERCIAL (LOCAL) Premium (Yellow tagged)', 'retail', 51.51, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (35, 'commercial-local-regular-milled', 'COMMERCIAL (LOCAL) Regular Milled', 'retail', 43.68, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (36, 'commercial-local-special-blue-tagged', 'COMMERCIAL (LOCAL) Special (Blue tagged)', 'retail', 55.76, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (37, 'commercial-local-well-milled', 'COMMERCIAL (LOCAL) Well Milled', 'retail', 47.76, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (38, 'corn-white', 'Corn (White)', 'retail', 113.33, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (39, 'corn-yellow', 'Corn (Yellow)', 'retail', 90.17, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (40, 'corn-yellow', 'Corn (Yellow)', 'retail', 78.00, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (41, 'corn-cracked-yellow-feed-grade', 'Corn Cracked (Yellow, Feed Grade)', 'retail', 40.00, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (42, 'corn-grits-feed-grade', 'Corn Grits (Feed Grade)', 'retail', 40.00, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (43, 'corn-white', 'Corn (White)', 'retail', 74.05, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (44, 'corn-yellow', 'Corn (Yellow)', 'retail', 80.43, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (45, 'corn-cracked-yellow-feed-grade', 'Corn Cracked (Yellow, Feed Grade)', 'retail', 37.43, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (46, 'corn-grits-feed-grade', 'Corn Grits (Feed Grade)', 'retail', 38.60, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (47, 'corn-grits-yellow-food-grade', 'Corn Grits (Yellow, Food Grade)', 'retail', 45.00, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (12, 'potato', 'Potato', 'retail', 127.50, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:43:34', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (9, 'onion', 'Onion', 'retail', 121.82, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:26:07', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (48, 'cabbage-scorpio', 'Cabbage (Scorpio)', 'retail', 285.00, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (49, 'carrots', 'Carrots', 'retail', 112.36, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (50, 'habichuelas-baguio-bean', 'Habichuelas (Baguio Bean)', 'retail', 181.08, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (51, 'white-potato', 'White Potato', 'retail', 142.13, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (52, 'pechay-baguio', 'Pechay (Baguio)', 'retail', 214.57, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (53, 'chayote', 'Chayote', 'retail', 110.68, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (54, 'broccoli', 'Broccoli', 'retail', 104.50, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (55, 'cauliflower', 'Cauliflower', 'retail', 281.63, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (56, 'bell-pepper-green', 'Bell Pepper (Green)', 'retail', 325.37, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (57, 'bell-pepper-red', 'Bell Pepper (Red)', 'retail', 266.87, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (58, 'celery', 'Celery', 'retail', 353.66, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (59, 'cabbage-rare-ball', 'Cabbage (Rare Ball)', 'retail', 227.06, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (60, 'cabbage-wonder-ball', 'Cabbage (Wonder Ball)', 'retail', 188.75, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (61, 'lettuce-green-ice', 'Lettuce (Green Ice)', 'retail', 367.14, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (62, 'lettuce-iceberg', 'Lettuce (Iceberg)', 'retail', 396.38, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (63, 'lettuce-romaine', 'Lettuce (Romaine)', 'retail', 407.50, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (64, 'cabbage-scorpio', 'Cabbage (Scorpio)', 'retail', 274.44, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (65, 'carrots', 'Carrots', 'retail', 79.80, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (66, 'habichuelas-baguio-bean', 'Habichuelas (Baguio Bean)', 'retail', 159.09, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (67, 'white-potato', 'White Potato', 'retail', 127.00, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (68, 'pechay-baguio', 'Pechay (Baguio)', 'retail', 188.89, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (69, 'chayote', 'Chayote', 'retail', 99.55, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (70, 'broccoli', 'Broccoli', 'retail', 239.86, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (71, 'cauliflower', 'Cauliflower', 'retail', 257.25, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (72, 'bell-pepper-green', 'Bell Pepper (Green)', 'retail', 340.06, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (73, 'bell-pepper-red', 'Bell Pepper (Red)', 'retail', 295.04, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (74, 'celery', 'Celery', 'retail', 390.17, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (75, 'cabbage-rare-ball', 'Cabbage (Rare Ball)', 'retail', 296.67, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (76, 'cabbage-wonder-ball', 'Cabbage (Wonder Ball)', 'retail', 510.00, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (77, 'lettuce-green-ice', 'Lettuce (Green Ice)', 'retail', 266.67, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (78, 'lettuce-iceberg', 'Lettuce (Iceberg)', 'retail', 375.50, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (79, 'cabbage-scorpio', 'Cabbage (Scorpio)', 'retail', 119.30, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (80, 'carrots', 'Carrots', 'retail', 106.11, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (81, 'habichuelas-baguio-bean', 'Habichuelas (Baguio Bean)', 'retail', 136.85, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (82, 'white-potato', 'White Potato', 'retail', 113.36, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (83, 'pechay-baguio', 'Pechay (Baguio)', 'retail', 113.14, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (84, 'chayote', 'Chayote', 'retail', 78.28, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (85, 'broccoli', 'Broccoli', 'retail', 156.11, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (86, 'cauliflower', 'Cauliflower', 'retail', 155.34, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (87, 'bell-pepper-green', 'Bell Pepper (Green)', 'retail', 257.58, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (88, 'bell-pepper-red', 'Bell Pepper (Red)', 'retail', 236.10, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (89, 'celery', 'Celery', 'retail', 276.60, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (90, 'cabbage-rare-ball', 'Cabbage (Rare Ball)', 'retail', 160.00, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (91, 'cabbage-wonder-ball', 'Cabbage (Wonder Ball)', 'retail', 140.00, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (92, 'lettuce-green-ice', 'Lettuce (Green Ice)', 'retail', 262.78, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (93, 'lettuce-iceberg', 'Lettuce (Iceberg)', 'retail', 313.23, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (94, 'lettuce-romaine', 'Lettuce (Romaine)', 'retail', 283.75, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (95, 'ampalaya', 'Ampalaya', 'retail', 152.49, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (96, 'squash', 'Squash', 'retail', 65.54, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (97, 'pechay-native', 'Pechay (Native)', 'retail', 163.43, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (98, 'sitao', 'Sitao', 'retail', 162.80, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (99, 'eggplant', 'Eggplant', 'retail', 126.41, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (100, 'tomato', 'Tomato', 'retail', 119.12, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (101, 'ampalaya', 'Ampalaya', 'retail', 110.38, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (102, 'squash', 'Squash', 'retail', 53.64, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (103, 'pechay-native', 'Pechay (Native)', 'retail', 110.44, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (104, 'sitao', 'Sitao', 'retail', 108.35, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (105, 'eggplant', 'Eggplant', 'retail', 87.19, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (106, 'tomato', 'Tomato', 'retail', 100.96, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (107, 'ampalaya', 'Ampalaya', 'retail', 118.55, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (108, 'squash', 'Squash', 'retail', 54.22, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (109, 'pechay-native', 'Pechay (Native)', 'retail', 97.48, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (110, 'sitao', 'Sitao', 'retail', 121.43, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (111, 'eggplant', 'Eggplant', 'retail', 100.03, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (112, 'tomato', 'Tomato', 'retail', 83.84, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (113, 'calamansi', 'Calamansi', 'retail', 106.08, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (114, 'banana-lakatan', 'Banana (Lakatan)', 'retail', 93.29, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (115, 'banana-latundan', 'Banana (Latundan)', 'retail', 77.88, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (116, 'mango-carabao', 'Mango (Carabao)', 'retail', 228.68, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (117, 'papaya', 'Papaya', 'retail', 75.21, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (118, 'melon', 'Melon', 'retail', 111.33, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (119, 'avocado', 'Avocado', 'retail', 264.29, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (120, 'pomelo', 'Pomelo', 'retail', 189.17, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (121, 'watermelon', 'Watermelon', 'retail', 77.14, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (122, 'banana-saba', 'Banana (Saba)', 'retail', 65.91, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (123, 'calamansi', 'Calamansi', 'retail', 72.27, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (124, 'banana-lakatan', 'Banana (Lakatan)', 'retail', 85.69, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (125, 'banana-latundan', 'Banana (Latundan)', 'retail', 64.25, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (126, 'mango-carabao', 'Mango (Carabao)', 'retail', 225.58, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (127, 'papaya', 'Papaya', 'retail', 77.81, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (128, 'melon', 'Melon', 'retail', 102.86, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (129, 'pomelo', 'Pomelo', 'retail', 167.50, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (130, 'watermelon', 'Watermelon', 'retail', 68.89, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (131, 'banana-saba', 'Banana (Saba)', 'retail', 47.27, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (132, 'calamansi', 'Calamansi', 'retail', 86.66, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (133, 'banana-lakatan', 'Banana (Lakatan)', 'retail', 100.04, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (134, 'banana-latundan', 'Banana (Latundan)', 'retail', 69.97, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (135, 'mango-carabao', 'Mango (Carabao)', 'retail', 179.67, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (136, 'papaya', 'Papaya', 'retail', 65.65, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (137, 'melon', 'Melon', 'retail', 78.33, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (138, 'avocado', 'Avocado', 'retail', 153.18, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (139, 'pomelo', 'Pomelo', 'retail', 130.59, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (140, 'watermelon', 'Watermelon', 'retail', 60.00, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (141, 'banana-saba', 'Banana (Saba)', 'retail', 56.72, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (142, 'rice', 'Rice', 'retail', 49.59, 'PHP', '130000000', 'DA monitored average (8 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (143, 'rice', 'Rice', 'retail', 46.49, 'PHP', '040000000', 'DA monitored average (8 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (144, 'rice', 'Rice', 'retail', 49.41, 'PHP', '030000000', 'DA monitored average (9 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (145, 'corn', 'Corn', 'retail', 101.75, 'PHP', '130000000', 'DA monitored average (2 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (146, 'corn', 'Corn', 'retail', 52.67, 'PHP', '040000000', 'DA monitored average (3 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (147, 'corn', 'Corn', 'retail', 55.10, 'PHP', '030000000', 'DA monitored average (5 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (148, 'cabbage', 'Cabbage', 'retail', 233.60, 'PHP', '130000000', 'DA monitored average (3 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (149, 'habichuelas', 'Habichuelas', 'retail', 181.08, 'PHP', '130000000', 'DA monitored average (1 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (150, 'potato', 'Potato', 'retail', 142.13, 'PHP', '130000000', 'DA monitored average (1 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (151, 'pechay', 'Pechay', 'retail', 189.00, 'PHP', '130000000', 'DA monitored average (2 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (152, 'bell-pepper', 'Bell Pepper', 'retail', 296.12, 'PHP', '130000000', 'DA monitored average (2 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (153, 'lettuce', 'Lettuce', 'retail', 390.34, 'PHP', '130000000', 'DA monitored average (3 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (154, 'cabbage', 'Cabbage', 'retail', 360.37, 'PHP', '040000000', 'DA monitored average (3 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (155, 'habichuelas', 'Habichuelas', 'retail', 159.09, 'PHP', '040000000', 'DA monitored average (1 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (156, 'potato', 'Potato', 'retail', 127.00, 'PHP', '040000000', 'DA monitored average (1 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (157, 'pechay', 'Pechay', 'retail', 149.67, 'PHP', '040000000', 'DA monitored average (2 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (158, 'bell-pepper', 'Bell Pepper', 'retail', 317.55, 'PHP', '040000000', 'DA monitored average (2 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (159, 'lettuce', 'Lettuce', 'retail', 321.09, 'PHP', '040000000', 'DA monitored average (2 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (160, 'cabbage', 'Cabbage', 'retail', 139.77, 'PHP', '030000000', 'DA monitored average (3 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (161, 'habichuelas', 'Habichuelas', 'retail', 136.85, 'PHP', '030000000', 'DA monitored average (1 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (162, 'potato', 'Potato', 'retail', 113.36, 'PHP', '030000000', 'DA monitored average (1 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (163, 'pechay', 'Pechay', 'retail', 105.31, 'PHP', '030000000', 'DA monitored average (2 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (164, 'bell-pepper', 'Bell Pepper', 'retail', 246.84, 'PHP', '030000000', 'DA monitored average (2 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (165, 'lettuce', 'Lettuce', 'retail', 286.59, 'PHP', '030000000', 'DA monitored average (3 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (166, 'banana', 'Banana', 'retail', 79.03, 'PHP', '130000000', 'DA monitored average (3 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (167, 'mango', 'Mango', 'retail', 228.68, 'PHP', '130000000', 'DA monitored average (1 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (168, 'banana', 'Banana', 'retail', 65.74, 'PHP', '040000000', 'DA monitored average (3 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (169, 'mango', 'Mango', 'retail', 225.58, 'PHP', '040000000', 'DA monitored average (1 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (170, 'banana', 'Banana', 'retail', 75.58, 'PHP', '030000000', 'DA monitored average (3 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (171, 'mango', 'Mango', 'retail', 179.67, 'PHP', '030000000', 'DA monitored average (1 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (172, 'commercial-imported-premium-yellow-tagged', 'COMMERCIAL (IMPORTED) Premium (Yellow tagged)', 'retail', 49.96, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (173, 'commercial-imported-regular-milled', 'COMMERCIAL (IMPORTED) Regular Milled', 'retail', 36.94, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (174, 'commercial-imported-special-blue-tagged', 'COMMERCIAL (IMPORTED) Special (Blue tagged)', 'retail', 57.74, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (175, 'commercial-imported-well-milled', 'COMMERCIAL (IMPORTED) Well Milled', 'retail', 41.26, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (176, 'commercial-local-premium-yellow-tagged', 'COMMERCIAL (LOCAL) Premium (Yellow tagged)', 'retail', 51.54, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (177, 'commercial-local-regular-milled', 'COMMERCIAL (LOCAL) Regular Milled', 'retail', 42.84, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (178, 'commercial-local-special-blue-tagged', 'COMMERCIAL (LOCAL) Special (Blue tagged)', 'retail', 58.61, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (179, 'commercial-local-well-milled', 'COMMERCIAL (LOCAL) Well Milled', 'retail', 47.21, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (180, 'nfa', 'NFA', 'retail', 55.00, 'PHP', NULL, 'DA national average (1 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (181, 'corn-white', 'Corn (White)', 'retail', 93.69, 'PHP', NULL, 'DA national average (2 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (182, 'corn-yellow', 'Corn (Yellow)', 'retail', 82.87, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (183, 'corn-cracked-yellow-feed-grade', 'Corn Cracked (Yellow, Feed Grade)', 'retail', 38.72, 'PHP', NULL, 'DA national average (2 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (184, 'corn-grits-feed-grade', 'Corn Grits (Feed Grade)', 'retail', 39.30, 'PHP', NULL, 'DA national average (2 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (185, 'corn-grits-yellow-food-grade', 'Corn Grits (Yellow, Food Grade)', 'retail', 45.00, 'PHP', NULL, 'DA national average (1 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (186, 'cabbage-scorpio', 'Cabbage (Scorpio)', 'retail', 226.25, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (187, 'carrots', 'Carrots', 'retail', 99.42, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (188, 'habichuelas-baguio-bean', 'Habichuelas (Baguio Bean)', 'retail', 159.01, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (189, 'white-potato', 'White Potato', 'retail', 127.50, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (190, 'pechay-baguio', 'Pechay (Baguio)', 'retail', 172.20, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (191, 'chayote', 'Chayote', 'retail', 96.17, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (192, 'broccoli', 'Broccoli', 'retail', 166.82, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:48', '2026-10-01 11:45:48');
INSERT INTO public.crop_reference_prices VALUES (193, 'cauliflower', 'Cauliflower', 'retail', 231.41, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (194, 'bell-pepper-green', 'Bell Pepper (Green)', 'retail', 307.67, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (195, 'bell-pepper-red', 'Bell Pepper (Red)', 'retail', 266.00, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (196, 'celery', 'Celery', 'retail', 340.14, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (197, 'cabbage-rare-ball', 'Cabbage (Rare Ball)', 'retail', 227.91, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (198, 'cabbage-wonder-ball', 'Cabbage (Wonder Ball)', 'retail', 279.58, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (199, 'lettuce-green-ice', 'Lettuce (Green Ice)', 'retail', 298.86, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (200, 'lettuce-iceberg', 'Lettuce (Iceberg)', 'retail', 361.70, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (201, 'lettuce-romaine', 'Lettuce (Romaine)', 'retail', 345.63, 'PHP', NULL, 'DA national average (2 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (203, 'squash', 'Squash', 'retail', 57.80, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (204, 'pechay-native', 'Pechay (Native)', 'retail', 123.78, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (205, 'sitao', 'Sitao', 'retail', 130.86, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (206, 'eggplant', 'Eggplant', 'retail', 104.54, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (207, 'tomato', 'Tomato', 'retail', 101.31, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (208, 'calamansi', 'Calamansi', 'retail', 88.34, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (209, 'banana-lakatan', 'Banana (Lakatan)', 'retail', 93.01, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (210, 'banana-latundan', 'Banana (Latundan)', 'retail', 70.70, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (211, 'mango-carabao', 'Mango (Carabao)', 'retail', 211.31, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (212, 'papaya', 'Papaya', 'retail', 72.89, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (213, 'melon', 'Melon', 'retail', 97.51, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (214, 'avocado', 'Avocado', 'retail', 208.74, 'PHP', NULL, 'DA national average (2 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (215, 'pomelo', 'Pomelo', 'retail', 162.42, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (216, 'watermelon', 'Watermelon', 'retail', 68.68, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (217, 'banana-saba', 'Banana (Saba)', 'retail', 56.63, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (3, 'rice', 'Rice', 'retail', 48.50, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:24:55', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (219, 'cabbage', 'Cabbage', 'retail', 244.58, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (220, 'habichuelas', 'Habichuelas', 'retail', 159.01, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (221, 'pechay', 'Pechay', 'retail', 147.99, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (222, 'bell-pepper', 'Bell Pepper', 'retail', 286.84, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (223, 'lettuce', 'Lettuce', 'retail', 332.67, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (224, 'banana', 'Banana', 'retail', 73.45, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (225, 'mango', 'Mango', 'retail', 211.31, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 11:45:49');
INSERT INTO public.crop_reference_prices VALUES (226, 'corn', 'Corn', 'farmgate', 18.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 11:50:52', '2026-10-01 11:50:52');
INSERT INTO public.crop_reference_prices VALUES (227, 'corn', 'Corn', 'wholesale', 25.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 11:50:52', '2026-10-01 11:50:52');
INSERT INTO public.crop_reference_prices VALUES (251, 'garlicnative', 'Garlic(Native)', 'retail', 158.00, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (228, 'ampalaya', 'Ampalaya', 'farmgate', 45.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 11:51:48', '2026-10-01 11:51:48');
INSERT INTO public.crop_reference_prices VALUES (229, 'ampalaya', 'Ampalaya', 'wholesale', 65.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 11:51:48', '2026-10-01 11:51:48');
INSERT INTO public.crop_reference_prices VALUES (202, 'ampalaya', 'Ampalaya', 'retail', 127.14, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 12:03:46');
INSERT INTO public.crop_reference_prices VALUES (218, 'corn', 'Corn', 'retail', 69.84, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 11:45:49', '2026-10-01 12:03:46');
INSERT INTO public.crop_reference_prices VALUES (230, 'garlic', 'Garlic', 'farmgate', 80.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 12:03:50', '2026-10-01 12:03:50');
INSERT INTO public.crop_reference_prices VALUES (231, 'garlic', 'Garlic', 'wholesale', 120.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 12:03:50', '2026-10-01 12:03:50');
INSERT INTO public.crop_reference_prices VALUES (233, 'ginger', 'Ginger', 'farmgate', 60.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 12:03:53', '2026-10-01 12:03:53');
INSERT INTO public.crop_reference_prices VALUES (234, 'ginger', 'Ginger', 'wholesale', 90.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 12:03:53', '2026-10-01 12:03:53');
INSERT INTO public.crop_reference_prices VALUES (236, 'chili', 'Chili', 'farmgate', 80.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 12:03:54', '2026-10-01 12:03:54');
INSERT INTO public.crop_reference_prices VALUES (237, 'chili', 'Chili', 'wholesale', 120.00, 'PHP', NULL, 'AI estimate', 'ai_estimate', '2026-10-01', '2026-10-01 12:03:54', '2026-10-01 12:03:54');
INSERT INTO public.crop_reference_prices VALUES (239, 'garlicimported', 'Garlic(Imported)', 'retail', 149.47, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (240, 'garlicnative', 'Garlic(Native)', 'retail', 350.00, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (241, 'ginger', 'Ginger', 'retail', 187.97, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (242, 'red-onion', 'Red Onion', 'retail', 118.31, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (243, 'white-onion', 'White Onion', 'retail', 139.44, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (244, 'chili-red', 'Chili (Red)', 'retail', 214.69, 'PHP', '130000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (245, 'garlicimported', 'Garlic(Imported)', 'retail', 147.27, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (246, 'ginger', 'Ginger', 'retail', 157.92, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (247, 'red-onion', 'Red Onion', 'retail', 106.08, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (248, 'white-onion', 'White Onion', 'retail', 143.33, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (249, 'chili-red', 'Chili (Red)', 'retail', 218.00, 'PHP', '040000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (250, 'garlicimported', 'Garlic(Imported)', 'retail', 139.55, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (252, 'ginger', 'Ginger', 'retail', 153.96, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (253, 'red-onion', 'Red Onion', 'retail', 113.64, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (254, 'red-onion-imported', 'Red Onion (Imported)', 'retail', 105.00, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (255, 'white-onion', 'White Onion', 'retail', 105.49, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (256, 'white-onion-imported', 'White Onion (Imported)', 'retail', 123.33, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (257, 'chili-red', 'Chili (Red)', 'retail', 193.85, 'PHP', '030000000', 'DA monitored average', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (238, 'chili', 'Chili', 'retail', 208.85, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:03:54', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (258, 'garlic', 'Garlic', 'retail', 249.74, 'PHP', '130000000', 'DA monitored average (2 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (259, 'onion', 'Onion', 'retail', 128.88, 'PHP', '130000000', 'DA monitored average (2 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (260, 'chili', 'Chili', 'retail', 214.69, 'PHP', '130000000', 'DA monitored average (1 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (261, 'garlic', 'Garlic', 'retail', 147.27, 'PHP', '040000000', 'DA monitored average (1 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (262, 'onion', 'Onion', 'retail', 124.71, 'PHP', '040000000', 'DA monitored average (2 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (263, 'chili', 'Chili', 'retail', 218.00, 'PHP', '040000000', 'DA monitored average (1 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (264, 'garlic', 'Garlic', 'retail', 148.78, 'PHP', '030000000', 'DA monitored average (2 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (265, 'onion', 'Onion', 'retail', 111.87, 'PHP', '030000000', 'DA monitored average (4 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (266, 'chili', 'Chili', 'retail', 193.85, 'PHP', '030000000', 'DA monitored average (1 variants)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (267, 'garlicimported', 'Garlic(Imported)', 'retail', 145.43, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (268, 'garlicnative', 'Garlic(Native)', 'retail', 254.00, 'PHP', NULL, 'DA national average (2 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (235, 'ginger', 'Ginger', 'retail', 166.62, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:03:53', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (269, 'red-onion', 'Red Onion', 'retail', 112.68, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (270, 'white-onion', 'White Onion', 'retail', 129.42, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (271, 'chili-red', 'Chili (Red)', 'retail', 208.85, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (272, 'red-onion-imported', 'Red Onion (Imported)', 'retail', 105.00, 'PHP', NULL, 'DA national average (1 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (273, 'white-onion-imported', 'White Onion (Imported)', 'retail', 123.33, 'PHP', NULL, 'DA national average (1 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:04:44', '2026-10-01 12:04:44');
INSERT INTO public.crop_reference_prices VALUES (232, 'garlic', 'Garlic', 'retail', 181.93, 'PHP', NULL, 'DA national average (3 regions)', 'da_bantay_presyo', '2026-10-01', '2026-10-01 12:03:50', '2026-10-01 12:04:44');


--
-- Name: crop_price_aliases_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.crop_price_aliases_id_seq', 29, true);


--
-- Name: crop_price_sync_runs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.crop_price_sync_runs_id_seq', 3, true);


--
-- Name: crop_reference_prices_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.crop_reference_prices_id_seq', 273, true);


--
-- PostgreSQL database dump complete
--


