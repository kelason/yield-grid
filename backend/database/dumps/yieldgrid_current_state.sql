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
-- Name: tiger; Type: SCHEMA; Schema: -; Owner: yieldgrid
--

CREATE SCHEMA tiger;


ALTER SCHEMA tiger OWNER TO yieldgrid;

--
-- Name: tiger_data; Type: SCHEMA; Schema: -; Owner: yieldgrid
--

CREATE SCHEMA tiger_data;


ALTER SCHEMA tiger_data OWNER TO yieldgrid;

--
-- Name: topology; Type: SCHEMA; Schema: -; Owner: yieldgrid
--

CREATE SCHEMA topology;


ALTER SCHEMA topology OWNER TO yieldgrid;

--
-- Name: SCHEMA topology; Type: COMMENT; Schema: -; Owner: yieldgrid
--

COMMENT ON SCHEMA topology IS 'PostGIS Topology schema';


--
-- Name: fuzzystrmatch; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS fuzzystrmatch WITH SCHEMA public;


--
-- Name: EXTENSION fuzzystrmatch; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION fuzzystrmatch IS 'determine similarities and distance between strings';


--
-- Name: postgis; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis WITH SCHEMA public;


--
-- Name: EXTENSION postgis; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION postgis IS 'PostGIS geometry and geography spatial types and functions';


--
-- Name: postgis_tiger_geocoder; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis_tiger_geocoder WITH SCHEMA tiger;


--
-- Name: EXTENSION postgis_tiger_geocoder; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION postgis_tiger_geocoder IS 'PostGIS tiger geocoder and reverse geocoder';


--
-- Name: postgis_topology; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis_topology WITH SCHEMA topology;


--
-- Name: EXTENSION postgis_topology; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION postgis_topology IS 'PostGIS topology spatial types and functions';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache OWNER TO yieldgrid;

--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


ALTER TABLE public.cache_locks OWNER TO yieldgrid;

--
-- Name: contact_messages; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.contact_messages (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    subject character varying(255),
    message text NOT NULL,
    status character varying(255) DEFAULT 'unread'::character varying NOT NULL,
    replied_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    CONSTRAINT contact_messages_status_check CHECK (((status)::text = ANY ((ARRAY['unread'::character varying, 'read'::character varying, 'replied'::character varying])::text[])))
);


ALTER TABLE public.contact_messages OWNER TO yieldgrid;

--
-- Name: contact_messages_id_seq; Type: SEQUENCE; Schema: public; Owner: yieldgrid
--

CREATE SEQUENCE public.contact_messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.contact_messages_id_seq OWNER TO yieldgrid;

--
-- Name: contact_messages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: yieldgrid
--

ALTER SEQUENCE public.contact_messages_id_seq OWNED BY public.contact_messages.id;


--
-- Name: crop_recommendations; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.crop_recommendations (
    id bigint NOT NULL,
    plot_id bigint NOT NULL,
    crop_name character varying(255) NOT NULL,
    confidence_score integer NOT NULL,
    reasoning text NOT NULL,
    projected_yield character varying(255),
    status character varying(255) DEFAULT 'pending'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.crop_recommendations OWNER TO yieldgrid;

--
-- Name: crop_recommendations_id_seq; Type: SEQUENCE; Schema: public; Owner: yieldgrid
--

CREATE SEQUENCE public.crop_recommendations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.crop_recommendations_id_seq OWNER TO yieldgrid;

--
-- Name: crop_recommendations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: yieldgrid
--

ALTER SEQUENCE public.crop_recommendations_id_seq OWNED BY public.crop_recommendations.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.failed_jobs (
    id bigint NOT NULL,
    uuid character varying(255) NOT NULL,
    connection text NOT NULL,
    queue text NOT NULL,
    payload text NOT NULL,
    exception text NOT NULL,
    failed_at timestamp(0) without time zone DEFAULT CURRENT_TIMESTAMP NOT NULL
);


ALTER TABLE public.failed_jobs OWNER TO yieldgrid;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: yieldgrid
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.failed_jobs_id_seq OWNER TO yieldgrid;

--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: yieldgrid
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: farms; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.farms (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    address character varying(255),
    city character varying(255),
    state character varying(255),
    zip character varying(255),
    total_area double precision,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    country character varying(255)
);


ALTER TABLE public.farms OWNER TO yieldgrid;

--
-- Name: COLUMN farms.total_area; Type: COMMENT; Schema: public; Owner: yieldgrid
--

COMMENT ON COLUMN public.farms.total_area IS 'Total area in hectares or acres';


--
-- Name: farms_id_seq; Type: SEQUENCE; Schema: public; Owner: yieldgrid
--

CREATE SEQUENCE public.farms_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.farms_id_seq OWNER TO yieldgrid;

--
-- Name: farms_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: yieldgrid
--

ALTER SEQUENCE public.farms_id_seq OWNED BY public.farms.id;


--
-- Name: forward_contracts; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.forward_contracts (
    id bigint NOT NULL,
    farmer_id bigint NOT NULL,
    crop_recommendation_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    crop_name character varying(255) NOT NULL,
    quantity_kg numeric(10,2) NOT NULL,
    price_per_kg numeric(10,2) NOT NULL,
    total_price numeric(12,2) NOT NULL,
    currency character(3) DEFAULT 'PHP'::bpchar NOT NULL,
    estimated_harvest_date date NOT NULL,
    expiry_date date NOT NULL,
    status character varying(20) DEFAULT 'available'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.forward_contracts OWNER TO yieldgrid;

--
-- Name: forward_contracts_id_seq; Type: SEQUENCE; Schema: public; Owner: yieldgrid
--

CREATE SEQUENCE public.forward_contracts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.forward_contracts_id_seq OWNER TO yieldgrid;

--
-- Name: forward_contracts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: yieldgrid
--

ALTER SEQUENCE public.forward_contracts_id_seq OWNED BY public.forward_contracts.id;


--
-- Name: harvest_listings; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.harvest_listings (
    id bigint NOT NULL,
    farmer_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    crop_name character varying(255) NOT NULL,
    quantity_kg numeric(10,2) NOT NULL,
    price_per_kg numeric(10,2) NOT NULL,
    total_price numeric(12,2) NOT NULL,
    currency character(3) DEFAULT 'PHP'::bpchar NOT NULL,
    estimated_harvest_date date NOT NULL,
    expiry_date date NOT NULL,
    status character varying(20) DEFAULT 'available'::character varying NOT NULL,
    shelf_life_days integer,
    is_harvest_available boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.harvest_listings OWNER TO yieldgrid;

--
-- Name: harvest_listings_id_seq; Type: SEQUENCE; Schema: public; Owner: yieldgrid
--

CREATE SEQUENCE public.harvest_listings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.harvest_listings_id_seq OWNER TO yieldgrid;

--
-- Name: harvest_listings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: yieldgrid
--

ALTER SEQUENCE public.harvest_listings_id_seq OWNED BY public.harvest_listings.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.job_batches (
    id character varying(255) NOT NULL,
    name character varying(255) NOT NULL,
    total_jobs integer NOT NULL,
    pending_jobs integer NOT NULL,
    failed_jobs integer NOT NULL,
    failed_job_ids text NOT NULL,
    options text,
    cancelled_at integer,
    created_at integer NOT NULL,
    finished_at integer
);


ALTER TABLE public.job_batches OWNER TO yieldgrid;

--
-- Name: jobs; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.jobs (
    id bigint NOT NULL,
    queue character varying(255) NOT NULL,
    payload text NOT NULL,
    attempts smallint NOT NULL,
    reserved_at integer,
    available_at integer NOT NULL,
    created_at integer NOT NULL
);


ALTER TABLE public.jobs OWNER TO yieldgrid;

--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: yieldgrid
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.jobs_id_seq OWNER TO yieldgrid;

--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: yieldgrid
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


ALTER TABLE public.migrations OWNER TO yieldgrid;

--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: yieldgrid
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.migrations_id_seq OWNER TO yieldgrid;

--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: yieldgrid
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


ALTER TABLE public.password_reset_tokens OWNER TO yieldgrid;

--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.personal_access_tokens (
    id bigint NOT NULL,
    tokenable_type character varying(255) NOT NULL,
    tokenable_id bigint NOT NULL,
    name text NOT NULL,
    token character varying(64) NOT NULL,
    abilities text,
    last_used_at timestamp(0) without time zone,
    expires_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.personal_access_tokens OWNER TO yieldgrid;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: yieldgrid
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.personal_access_tokens_id_seq OWNER TO yieldgrid;

--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: yieldgrid
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: plots; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.plots (
    id bigint NOT NULL,
    farm_id bigint NOT NULL,
    name character varying(255) NOT NULL,
    polygon public.geometry(Polygon,4326),
    soil_type character varying(255),
    calculated_area double precision,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    agromonitoring_polyid character varying(255)
);


ALTER TABLE public.plots OWNER TO yieldgrid;

--
-- Name: COLUMN plots.calculated_area; Type: COMMENT; Schema: public; Owner: yieldgrid
--

COMMENT ON COLUMN public.plots.calculated_area IS 'Calculated automatically from PostGIS ST_Area';


--
-- Name: plots_id_seq; Type: SEQUENCE; Schema: public; Owner: yieldgrid
--

CREATE SEQUENCE public.plots_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.plots_id_seq OWNER TO yieldgrid;

--
-- Name: plots_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: yieldgrid
--

ALTER SEQUENCE public.plots_id_seq OWNED BY public.plots.id;


--
-- Name: purchases; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.purchases (
    id bigint NOT NULL,
    buyer_id bigint NOT NULL,
    forward_contract_id bigint,
    paymongo_payment_id character varying(255),
    paymongo_checkout_id character varying(255),
    payment_method character varying(20),
    amount_paid numeric(12,2) NOT NULL,
    currency character(3) DEFAULT 'PHP'::bpchar NOT NULL,
    payment_status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    purchased_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    harvest_listing_id bigint,
    quantity_kg numeric(10,2),
    cash_payment_status character varying(20),
    cash_amount_confirmed numeric(12,2) DEFAULT '0'::numeric NOT NULL,
    farmer_confirmed_at timestamp(0) without time zone,
    is_downpayment boolean DEFAULT false NOT NULL,
    total_contract_amount numeric(12,2)
);


ALTER TABLE public.purchases OWNER TO yieldgrid;

--
-- Name: purchases_id_seq; Type: SEQUENCE; Schema: public; Owner: yieldgrid
--

CREATE SEQUENCE public.purchases_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.purchases_id_seq OWNER TO yieldgrid;

--
-- Name: purchases_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: yieldgrid
--

ALTER SEQUENCE public.purchases_id_seq OWNED BY public.purchases.id;


--
-- Name: restricted_zones; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.restricted_zones (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255) DEFAULT 'restricted'::character varying NOT NULL,
    polygon public.geometry(Polygon,4326),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.restricted_zones OWNER TO yieldgrid;

--
-- Name: restricted_zones_id_seq; Type: SEQUENCE; Schema: public; Owner: yieldgrid
--

CREATE SEQUENCE public.restricted_zones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.restricted_zones_id_seq OWNER TO yieldgrid;

--
-- Name: restricted_zones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: yieldgrid
--

ALTER SEQUENCE public.restricted_zones_id_seq OWNED BY public.restricted_zones.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


ALTER TABLE public.sessions OWNER TO yieldgrid;

--
-- Name: users; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.users (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    email character varying(255) NOT NULL,
    email_verified_at timestamp(0) without time zone,
    password character varying(255) NOT NULL,
    role character varying(255) DEFAULT 'buyer'::character varying NOT NULL,
    avatar_url character varying(255),
    phone character varying(20),
    address text,
    remember_token character varying(100),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.users OWNER TO yieldgrid;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: yieldgrid
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.users_id_seq OWNER TO yieldgrid;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: yieldgrid
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: weather_cache; Type: TABLE; Schema: public; Owner: yieldgrid
--

CREATE TABLE public.weather_cache (
    id bigint NOT NULL,
    latitude numeric(10,7) NOT NULL,
    longitude numeric(10,7) NOT NULL,
    weather_data jsonb NOT NULL,
    expires_at timestamp(0) without time zone NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


ALTER TABLE public.weather_cache OWNER TO yieldgrid;

--
-- Name: weather_cache_id_seq; Type: SEQUENCE; Schema: public; Owner: yieldgrid
--

CREATE SEQUENCE public.weather_cache_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.weather_cache_id_seq OWNER TO yieldgrid;

--
-- Name: weather_cache_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: yieldgrid
--

ALTER SEQUENCE public.weather_cache_id_seq OWNED BY public.weather_cache.id;


--
-- Name: contact_messages id; Type: DEFAULT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.contact_messages ALTER COLUMN id SET DEFAULT nextval('public.contact_messages_id_seq'::regclass);


--
-- Name: crop_recommendations id; Type: DEFAULT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.crop_recommendations ALTER COLUMN id SET DEFAULT nextval('public.crop_recommendations_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: farms id; Type: DEFAULT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.farms ALTER COLUMN id SET DEFAULT nextval('public.farms_id_seq'::regclass);


--
-- Name: forward_contracts id; Type: DEFAULT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.forward_contracts ALTER COLUMN id SET DEFAULT nextval('public.forward_contracts_id_seq'::regclass);


--
-- Name: harvest_listings id; Type: DEFAULT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.harvest_listings ALTER COLUMN id SET DEFAULT nextval('public.harvest_listings_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: plots id; Type: DEFAULT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.plots ALTER COLUMN id SET DEFAULT nextval('public.plots_id_seq'::regclass);


--
-- Name: purchases id; Type: DEFAULT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.purchases ALTER COLUMN id SET DEFAULT nextval('public.purchases_id_seq'::regclass);


--
-- Name: restricted_zones id; Type: DEFAULT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.restricted_zones ALTER COLUMN id SET DEFAULT nextval('public.restricted_zones_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: weather_cache id; Type: DEFAULT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.weather_cache ALTER COLUMN id SET DEFAULT nextval('public.weather_cache_id_seq'::regclass);


--
-- Data for Name: cache; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.cache (key, value, expiration) FROM stdin;
\.


--
-- Data for Name: cache_locks; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.cache_locks (key, owner, expiration) FROM stdin;
\.


--
-- Data for Name: contact_messages; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.contact_messages (id, name, email, subject, message, status, replied_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: crop_recommendations; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.crop_recommendations (id, plot_id, crop_name, confidence_score, reasoning, projected_yield, status, created_at, updated_at) FROM stdin;
2	1	Sweet Potato	92	Sweet potatoes excel in sandy loam to sandy soils as the loose texture prevents root restriction and reduces rot. Highly suitable for intensive cultivation on a 0.7065 ha plot in La Paz for local market supply.	10.60 tons total (15 tons/ha on 0.7065049193982 ha)	pending	2026-09-23 09:14:37	2026-09-23 09:14:37
3	1	Peanut	90	Peanuts prefer light, sandy soils which allow easy pegging and effortless harvesting. This legume fits the 0.7065 ha scale for intensive cash cropping in La Paz, Tarlac, while naturally improving soil nitrogen.	1.41 tons total (2 tons/ha on 0.7065049193982 ha)	pending	2026-09-23 09:14:37	2026-09-23 09:14:37
4	1	Watermelon	88	Watermelons thrive in warm climates with sandy soils that provide rapid drainage and prevent fungal disease buildup. Perfect for high-value seasonal cash cropping on a 0.7065 ha plot in the dry months of Tarlac.	14.13 tons total (20 tons/ha on 0.7065049193982 ha)	pending	2026-09-23 09:14:37	2026-09-23 09:14:37
5	1	Mango	85	Tarlac is known for fruit tree orchards. Sandy soil provides the exceptional drainage required to prevent root rot in mango trees. A 0.7065 ha plot can support a high-density smallholder orchard of grafted varieties.	7.07 tons total (10 tons/ha on 0.7065049193982 ha)	pending	2026-09-23 09:14:37	2026-09-23 09:14:37
6	1	Mung Bean	84	Mung beans are short-duration legumes that adapt well to sandy soils with moderate moisture. Ideal for a 0.7065 ha plot in La Paz as a quick-turnaround cash crop following primary harvests.	0.85 tons total (1.2 tons/ha on 0.7065049193982 ha)	pending	2026-09-23 09:14:37	2026-09-23 09:14:37
7	1	Corn	82	Field and sweet corn are staples in Tarlac. While sandy soils require careful nutrient management, modern fertilization makes corn viable on this 0.7065 ha plot, benefiting from the warm tropical climate of La Paz.	3.18 tons total (4.5 tons/ha on 0.7065049193982 ha)	pending	2026-09-23 09:14:37	2026-09-23 09:14:37
8	1	Tomato	80	Tomatoes perform well under intensive vegetable management on a 0.7065 ha plot. Sandy soils minimize waterlogging issues during sudden rains in La Paz, provided drip irrigation and staking are utilized.	12.72 tons total (18 tons/ha on 0.7065049193982 ha)	pending	2026-09-23 09:14:37	2026-09-23 09:14:37
9	1	Eggplant	78	Eggplants are robust local vegetables that adapt to sandy loam conditions. Cultivating this crop on a 0.7065 ha plot allows local farmers in La Paz to supply continuous harvests to regional wet markets.	10.60 tons total (15 tons/ha on 0.7065049193982 ha)	pending	2026-09-23 09:14:37	2026-09-23 09:14:37
10	1	Bell Pepper	75	Bell peppers require well-aerated sandy soil to prevent root asphyxiation. Intensive high-value horticulture on a 0.7065 ha plot in La Paz can yield profitable returns with proper moisture management.	7.77 tons total (11 tons/ha on 0.7065049193982 ha)	pending	2026-09-23 09:14:37	2026-09-23 09:14:37
1	1	Cassava	95	Cassava is widely cultivated in Tarlac. For a 0.7065 ha plot, it represents a hardy root crop that thrives in the well-drained sandy soils typical of La Paz, requiring minimal inputs while offering reliable commercial processing demand.	17.66 tons total (25 tons/ha on 0.7065049193982 ha)	accepted	2026-09-23 09:14:37	2026-09-23 09:15:02
\.


--
-- Data for Name: failed_jobs; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.failed_jobs (id, uuid, connection, queue, payload, exception, failed_at) FROM stdin;
\.


--
-- Data for Name: farms; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.farms (id, user_id, name, address, city, state, zip, total_area, created_at, updated_at, country) FROM stdin;
1	1	Kevin's Farm	123	La Paz	Tarlac	2314	35	2026-09-23 09:13:49	2026-09-23 09:13:49	Philippines
\.


--
-- Data for Name: forward_contracts; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.forward_contracts (id, farmer_id, crop_recommendation_id, title, description, crop_name, quantity_kg, price_per_kg, total_price, currency, estimated_harvest_date, expiry_date, status, created_at, updated_at) FROM stdin;
2	1	1	Cassava — Forward Contract	\N	Cassava	1000.00	15.00	15000.00	PHP	2026-12-22	2026-12-07	partially_paid	2026-09-23 09:26:47	2026-09-23 09:26:47
1	1	1	Cassava — Forward Contract	\N	Cassava	15660.00	15.00	234900.00	PHP	2026-12-22	2026-12-07	available	2026-09-23 09:15:02	2026-09-23 09:29:25
3	1	1	Cassava — Forward Contract	\N	Cassava	1000.00	15.00	15000.00	PHP	2026-12-22	2026-12-07	reserved	2026-09-23 09:29:25	2026-09-23 09:29:25
\.


--
-- Data for Name: harvest_listings; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.harvest_listings (id, farmer_id, title, description, crop_name, quantity_kg, price_per_kg, total_price, currency, estimated_harvest_date, expiry_date, status, shelf_life_days, is_harvest_available, created_at, updated_at) FROM stdin;
1	1	Philippine Tomato	Kamatis kayo diyan	Tomato	800.00	10.00	8000.00	PHP	2026-09-23	2026-10-07	available	14	t	2026-09-23 09:13:37	2026-09-23 09:13:37
\.


--
-- Data for Name: job_batches; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.job_batches (id, name, total_jobs, pending_jobs, failed_jobs, failed_job_ids, options, cancelled_at, created_at, finished_at) FROM stdin;
\.


--
-- Data for Name: jobs; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.jobs (id, queue, payload, attempts, reserved_at, available_at, created_at) FROM stdin;
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.migrations (id, migration, batch) FROM stdin;
1	0001_01_01_000000_create_users_table	1
2	0001_01_01_000001_create_cache_table	1
3	0001_01_01_000002_create_jobs_table	1
4	2026_09_12_122459_create_personal_access_tokens_table	1
5	2026_09_12_125534_create_contact_messages_table	1
6	2026_09_12_141352_create_farms_table	1
7	2026_09_12_141420_create_plots_table	1
8	2026_09_14_112821_create_weather_cache_table	1
9	2026_09_14_112822_create_crop_recommendations_table	1
10	2026_09_14_114958_add_agromonitoring_polyid_to_plots_table	1
11	2026_09_14_124356_add_country_to_farms_table	1
12	2026_09_15_110318_create_restricted_zones_table	1
13	2026_09_16_002321_create_forward_contracts_table	1
14	2026_09_16_002324_create_purchases_table	1
15	2026_09_22_015843_create_harvest_listings_table	1
16	2026_09_22_015844_add_cash_fields_to_purchases_table	1
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: personal_access_tokens; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.personal_access_tokens (id, tokenable_type, tokenable_id, name, token, abilities, last_used_at, expires_at, created_at, updated_at) FROM stdin;
9	Domain\\Users\\Models\\User	1	auth_token	377ae29c57f8f39b7c470ec5e4425c7be2342c9fe67d68e0e5b9b0f2fa75de3e	["*"]	2026-09-23 09:52:00	2026-09-23 11:51:59	2026-09-23 09:51:59	2026-09-23 09:52:00
\.


--
-- Data for Name: plots; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.plots (id, farm_id, name, polygon, soil_type, calculated_area, created_at, updated_at, agromonitoring_polyid) FROM stdin;
1	1	Plot 1	0103000020E610000001000000050000005437177FDB2C5E400AF65FE7A6CD2E400B5D8940F52C5E405C397B67B4CD2E4010E6762FF72C5E40BB48A12C7CCD2E4060B1868BDC2C5E40D13C80457ECD2E405437177FDB2C5E400AF65FE7A6CD2E40	sandy	0.7065049193982035	2026-09-23 09:14:23	2026-09-23 09:14:23	\N
\.


--
-- Data for Name: purchases; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.purchases (id, buyer_id, forward_contract_id, paymongo_payment_id, paymongo_checkout_id, payment_method, amount_paid, currency, payment_status, purchased_at, created_at, updated_at, harvest_listing_id, quantity_kg, cash_payment_status, cash_amount_confirmed, farmer_confirmed_at, is_downpayment, total_contract_amount) FROM stdin;
1	2	2	pay_wwDUggJAaHj3W3SWy44C2RyL	cs_fb4d4a67a17f371266fdc317	gcash	1500.00	PHP	completed	2026-09-23 09:17:28	2026-09-23 09:16:47	2026-09-23 09:26:47	\N	1000.00	\N	0.00	\N	t	15000.00
2	2	3	\N	\N	cash	1500.00	PHP	pending	\N	2026-09-23 09:29:25	2026-09-23 09:29:25	\N	1000.00	pending_approval	0.00	\N	t	15000.00
\.


--
-- Data for Name: restricted_zones; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.restricted_zones (id, name, type, polygon, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: sessions; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.sessions (id, user_id, ip_address, user_agent, payload, last_activity) FROM stdin;
W9FHfC1oeAeEpBgrW9fALB222sLQGZb7auRRa05u	\N	127.0.0.1	Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/153.0.0.0 Safari/537.36	YTo0OntzOjY6Il90b2tlbiI7czo0MDoiMnpEV1RhN09iSkdNOFpUTGF3NEVxZldyNFAwZVlFa1VMa2FpUklXUCI7czo5OiJfcHJldmlvdXMiO2E6Mjp7czozOiJ1cmwiO3M6NTg6Imh0dHA6Ly9sb2NhbGhvc3Q6ODAwMC9sb2ctdmlld2VyP2ZpbGU9MWRkZTVjYzMtbGFyYXZlbC5sb2ciO3M6NToicm91dGUiO3M6MTY6ImxvZy12aWV3ZXIuaW5kZXgiO31zOjY6Il9mbGFzaCI7YToyOntzOjM6Im9sZCI7YTowOnt9czozOiJuZXciO2E6MDp7fX1zOjMxOiJsb2ctdmlld2VyOnNob3J0ZXItc3RhY2stdHJhY2VzIjtiOjA7fQ==	1790154925
\.


--
-- Data for Name: spatial_ref_sys; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.spatial_ref_sys (srid, auth_name, auth_srid, srtext, proj4text) FROM stdin;
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.users (id, name, email, email_verified_at, password, role, avatar_url, phone, address, remember_token, created_at, updated_at) FROM stdin;
1	Kevin Sapang	kevin.sapang@gmail.com	2026-09-23 09:12:59	$2y$12$92XhQHNBae9QWvCtQ/2t7OULuvu0RQoO0ceC0ntbSlOxDu9ponzGS	farmer	\N	\N	\N	\N	2026-09-23 09:11:33	2026-09-23 09:12:59
2	Carolyn Arceo	carolyn.arceo@gmail.com	2026-09-23 09:15:38	$2y$12$JdYtieIFgoe35YhcBLQPVeMXBiA.EZvs2j3vLCUugLxVsoEO6S1bu	buyer	\N	\N	\N	\N	2026-09-23 09:15:22	2026-09-23 09:15:38
\.


--
-- Data for Name: weather_cache; Type: TABLE DATA; Schema: public; Owner: yieldgrid
--

COPY public.weather_cache (id, latitude, longitude, weather_data, expires_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: geocode_settings; Type: TABLE DATA; Schema: tiger; Owner: yieldgrid
--

COPY tiger.geocode_settings (name, setting, unit, category, short_desc) FROM stdin;
\.


--
-- Data for Name: pagc_gaz; Type: TABLE DATA; Schema: tiger; Owner: yieldgrid
--

COPY tiger.pagc_gaz (id, seq, word, stdword, token, is_custom) FROM stdin;
\.


--
-- Data for Name: pagc_lex; Type: TABLE DATA; Schema: tiger; Owner: yieldgrid
--

COPY tiger.pagc_lex (id, seq, word, stdword, token, is_custom) FROM stdin;
\.


--
-- Data for Name: pagc_rules; Type: TABLE DATA; Schema: tiger; Owner: yieldgrid
--

COPY tiger.pagc_rules (id, rule, is_custom) FROM stdin;
\.


--
-- Data for Name: topology; Type: TABLE DATA; Schema: topology; Owner: yieldgrid
--

COPY topology.topology (id, name, srid, "precision", hasz) FROM stdin;
\.


--
-- Data for Name: layer; Type: TABLE DATA; Schema: topology; Owner: yieldgrid
--

COPY topology.layer (topology_id, layer_id, schema_name, table_name, feature_column, feature_type, level, child_id) FROM stdin;
\.


--
-- Name: contact_messages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.contact_messages_id_seq', 1, false);


--
-- Name: crop_recommendations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.crop_recommendations_id_seq', 10, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 1, false);


--
-- Name: farms_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.farms_id_seq', 1, true);


--
-- Name: forward_contracts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.forward_contracts_id_seq', 3, true);


--
-- Name: harvest_listings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.harvest_listings_id_seq', 1, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.migrations_id_seq', 16, true);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 9, true);


--
-- Name: plots_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.plots_id_seq', 1, true);


--
-- Name: purchases_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.purchases_id_seq', 2, true);


--
-- Name: restricted_zones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.restricted_zones_id_seq', 1, false);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.users_id_seq', 2, true);


--
-- Name: weather_cache_id_seq; Type: SEQUENCE SET; Schema: public; Owner: yieldgrid
--

SELECT pg_catalog.setval('public.weather_cache_id_seq', 1, false);


--
-- Name: topology_id_seq; Type: SEQUENCE SET; Schema: topology; Owner: yieldgrid
--

SELECT pg_catalog.setval('topology.topology_id_seq', 1, false);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: contact_messages contact_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.contact_messages
    ADD CONSTRAINT contact_messages_pkey PRIMARY KEY (id);


--
-- Name: crop_recommendations crop_recommendations_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.crop_recommendations
    ADD CONSTRAINT crop_recommendations_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: farms farms_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.farms
    ADD CONSTRAINT farms_pkey PRIMARY KEY (id);


--
-- Name: forward_contracts forward_contracts_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.forward_contracts
    ADD CONSTRAINT forward_contracts_pkey PRIMARY KEY (id);


--
-- Name: harvest_listings harvest_listings_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.harvest_listings
    ADD CONSTRAINT harvest_listings_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: plots plots_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.plots
    ADD CONSTRAINT plots_pkey PRIMARY KEY (id);


--
-- Name: purchases purchases_paymongo_checkout_id_unique; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_paymongo_checkout_id_unique UNIQUE (paymongo_checkout_id);


--
-- Name: purchases purchases_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_pkey PRIMARY KEY (id);


--
-- Name: restricted_zones restricted_zones_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.restricted_zones
    ADD CONSTRAINT restricted_zones_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: weather_cache weather_cache_pkey; Type: CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.weather_cache
    ADD CONSTRAINT weather_cache_pkey PRIMARY KEY (id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: yieldgrid
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: yieldgrid
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: forward_contracts_crop_recommendation_id_index; Type: INDEX; Schema: public; Owner: yieldgrid
--

CREATE INDEX forward_contracts_crop_recommendation_id_index ON public.forward_contracts USING btree (crop_recommendation_id);


--
-- Name: forward_contracts_farmer_id_index; Type: INDEX; Schema: public; Owner: yieldgrid
--

CREATE INDEX forward_contracts_farmer_id_index ON public.forward_contracts USING btree (farmer_id);


--
-- Name: forward_contracts_status_estimated_harvest_date_index; Type: INDEX; Schema: public; Owner: yieldgrid
--

CREATE INDEX forward_contracts_status_estimated_harvest_date_index ON public.forward_contracts USING btree (status, estimated_harvest_date);


--
-- Name: harvest_listings_farmer_id_index; Type: INDEX; Schema: public; Owner: yieldgrid
--

CREATE INDEX harvest_listings_farmer_id_index ON public.harvest_listings USING btree (farmer_id);


--
-- Name: hl_availability_idx; Type: INDEX; Schema: public; Owner: yieldgrid
--

CREATE INDEX hl_availability_idx ON public.harvest_listings USING btree (is_harvest_available, status, estimated_harvest_date);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: yieldgrid
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: yieldgrid
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: yieldgrid
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: purchases_buyer_id_index; Type: INDEX; Schema: public; Owner: yieldgrid
--

CREATE INDEX purchases_buyer_id_index ON public.purchases USING btree (buyer_id);


--
-- Name: purchases_forward_contract_id_index; Type: INDEX; Schema: public; Owner: yieldgrid
--

CREATE INDEX purchases_forward_contract_id_index ON public.purchases USING btree (forward_contract_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: yieldgrid
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: yieldgrid
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: weather_cache_latitude_longitude_index; Type: INDEX; Schema: public; Owner: yieldgrid
--

CREATE INDEX weather_cache_latitude_longitude_index ON public.weather_cache USING btree (latitude, longitude);


--
-- Name: crop_recommendations crop_recommendations_plot_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.crop_recommendations
    ADD CONSTRAINT crop_recommendations_plot_id_foreign FOREIGN KEY (plot_id) REFERENCES public.plots(id) ON DELETE CASCADE;


--
-- Name: farms farms_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.farms
    ADD CONSTRAINT farms_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: forward_contracts forward_contracts_crop_recommendation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.forward_contracts
    ADD CONSTRAINT forward_contracts_crop_recommendation_id_foreign FOREIGN KEY (crop_recommendation_id) REFERENCES public.crop_recommendations(id) ON DELETE CASCADE;


--
-- Name: forward_contracts forward_contracts_farmer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.forward_contracts
    ADD CONSTRAINT forward_contracts_farmer_id_foreign FOREIGN KEY (farmer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: harvest_listings harvest_listings_farmer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.harvest_listings
    ADD CONSTRAINT harvest_listings_farmer_id_foreign FOREIGN KEY (farmer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: plots plots_farm_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.plots
    ADD CONSTRAINT plots_farm_id_foreign FOREIGN KEY (farm_id) REFERENCES public.farms(id) ON DELETE CASCADE;


--
-- Name: purchases purchases_buyer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_buyer_id_foreign FOREIGN KEY (buyer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: purchases purchases_forward_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_forward_contract_id_foreign FOREIGN KEY (forward_contract_id) REFERENCES public.forward_contracts(id) ON DELETE CASCADE;


--
-- Name: purchases purchases_harvest_listing_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: yieldgrid
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_harvest_listing_id_foreign FOREIGN KEY (harvest_listing_id) REFERENCES public.harvest_listings(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

