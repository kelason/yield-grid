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
-- Name: tiger; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA tiger;


--
-- Name: tiger_data; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA tiger_data;


--
-- Name: topology; Type: SCHEMA; Schema: -; Owner: -
--

CREATE SCHEMA topology;


--
-- Name: SCHEMA topology; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON SCHEMA topology IS 'PostGIS Topology schema';


--
-- Name: fuzzystrmatch; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS fuzzystrmatch WITH SCHEMA public;


--
-- Name: EXTENSION fuzzystrmatch; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION fuzzystrmatch IS 'determine similarities and distance between strings';


--
-- Name: postgis; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis WITH SCHEMA public;


--
-- Name: EXTENSION postgis; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION postgis IS 'PostGIS geometry and geography spatial types and functions';


--
-- Name: postgis_tiger_geocoder; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis_tiger_geocoder WITH SCHEMA tiger;


--
-- Name: EXTENSION postgis_tiger_geocoder; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION postgis_tiger_geocoder IS 'PostGIS tiger geocoder and reverse geocoder';


--
-- Name: postgis_topology; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS postgis_topology WITH SCHEMA topology;


--
-- Name: EXTENSION postgis_topology; Type: COMMENT; Schema: -; Owner: -
--

COMMENT ON EXTENSION postgis_topology IS 'PostGIS topology spatial types and functions';


SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: cache; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache (
    key character varying(255) NOT NULL,
    value text NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: cache_locks; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.cache_locks (
    key character varying(255) NOT NULL,
    owner character varying(255) NOT NULL,
    expiration integer NOT NULL
);


--
-- Name: chat_attachments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.chat_attachments (
    id bigint NOT NULL,
    message_id bigint NOT NULL,
    file_path character varying(255) NOT NULL,
    file_type character varying(255) NOT NULL,
    file_size integer NOT NULL,
    original_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: chat_attachments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.chat_attachments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: chat_attachments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.chat_attachments_id_seq OWNED BY public.chat_attachments.id;


--
-- Name: chat_conversations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.chat_conversations (
    id bigint NOT NULL,
    last_message_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: chat_conversations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.chat_conversations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: chat_conversations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.chat_conversations_id_seq OWNED BY public.chat_conversations.id;


--
-- Name: chat_messages; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.chat_messages (
    id bigint NOT NULL,
    conversation_id bigint NOT NULL,
    user_id bigint NOT NULL,
    body text NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: chat_messages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.chat_messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: chat_messages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.chat_messages_id_seq OWNED BY public.chat_messages.id;


--
-- Name: chat_participants; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.chat_participants (
    id bigint NOT NULL,
    conversation_id bigint NOT NULL,
    user_id bigint NOT NULL,
    last_read_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: chat_participants_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.chat_participants_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: chat_participants_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.chat_participants_id_seq OWNED BY public.chat_participants.id;


--
-- Name: contact_messages; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: contact_messages_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.contact_messages_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: contact_messages_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.contact_messages_id_seq OWNED BY public.contact_messages.id;


--
-- Name: crop_demand_offers; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.crop_demand_offers (
    id bigint NOT NULL,
    crop_demand_id bigint NOT NULL,
    farmer_id bigint NOT NULL,
    quantity_kg numeric(10,2) NOT NULL,
    price_per_kg numeric(10,2) NOT NULL,
    total_price numeric(12,2) NOT NULL,
    currency character(3) DEFAULT 'PHP'::bpchar NOT NULL,
    message text,
    status character varying(20) DEFAULT 'pending'::character varying NOT NULL,
    accepted_at timestamp(0) without time zone,
    paid_at timestamp(0) without time zone,
    delivered_at timestamp(0) without time zone,
    completed_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: crop_demand_offers_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.crop_demand_offers_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: crop_demand_offers_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.crop_demand_offers_id_seq OWNED BY public.crop_demand_offers.id;


--
-- Name: crop_demands; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.crop_demands (
    id bigint NOT NULL,
    buyer_id bigint NOT NULL,
    address_id bigint NOT NULL,
    title character varying(255) NOT NULL,
    description text,
    crop_name character varying(255) NOT NULL,
    quantity_kg numeric(10,2) NOT NULL,
    remaining_quantity_kg numeric(10,2) NOT NULL,
    target_price_per_kg numeric(10,2) NOT NULL,
    total_budget numeric(12,2) NOT NULL,
    currency character(3) DEFAULT 'PHP'::bpchar NOT NULL,
    needed_by_date date NOT NULL,
    expiry_date date NOT NULL,
    status character varying(20) DEFAULT 'open'::character varying NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: crop_demands_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.crop_demands_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: crop_demands_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.crop_demands_id_seq OWNED BY public.crop_demands.id;


--
-- Name: crop_recommendations; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: crop_recommendations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.crop_recommendations_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: crop_recommendations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.crop_recommendations_id_seq OWNED BY public.crop_recommendations.id;


--
-- Name: failed_jobs; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.failed_jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.failed_jobs_id_seq OWNED BY public.failed_jobs.id;


--
-- Name: farms; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: COLUMN farms.total_area; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.farms.total_area IS 'Total area in hectares or acres';


--
-- Name: farms_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.farms_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: farms_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.farms_id_seq OWNED BY public.farms.id;


--
-- Name: forum_attachments; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.forum_attachments (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    attachable_type character varying(255) NOT NULL,
    attachable_id bigint,
    file_path character varying(255) NOT NULL,
    file_type character varying(255) NOT NULL,
    file_size integer NOT NULL,
    original_name character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: forum_attachments_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.forum_attachments_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: forum_attachments_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.forum_attachments_id_seq OWNED BY public.forum_attachments.id;


--
-- Name: forum_categories; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.forum_categories (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    description character varying(255) NOT NULL,
    icon_emoji character varying(255) NOT NULL,
    sort_order integer DEFAULT 0 NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: forum_categories_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.forum_categories_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: forum_categories_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.forum_categories_id_seq OWNED BY public.forum_categories.id;


--
-- Name: forum_replies; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.forum_replies (
    id bigint NOT NULL,
    thread_id bigint NOT NULL,
    user_id bigint NOT NULL,
    parent_id bigint,
    body text NOT NULL,
    vote_score integer DEFAULT 0 NOT NULL,
    is_accepted boolean DEFAULT false NOT NULL,
    is_anonymous boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: forum_replies_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.forum_replies_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: forum_replies_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.forum_replies_id_seq OWNED BY public.forum_replies.id;


--
-- Name: forum_reports; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.forum_reports (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    reportable_type character varying(255) NOT NULL,
    reportable_id bigint NOT NULL,
    reason character varying(255) NOT NULL,
    description text,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: forum_reports_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.forum_reports_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: forum_reports_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.forum_reports_id_seq OWNED BY public.forum_reports.id;


--
-- Name: forum_tags; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.forum_tags (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    slug character varying(255) NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: forum_tags_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.forum_tags_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: forum_tags_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.forum_tags_id_seq OWNED BY public.forum_tags.id;


--
-- Name: forum_thread_tag; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.forum_thread_tag (
    thread_id bigint NOT NULL,
    tag_id bigint NOT NULL
);


--
-- Name: forum_threads; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.forum_threads (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    category_id bigint NOT NULL,
    accepted_reply_id bigint,
    title character varying(255) NOT NULL,
    body text NOT NULL,
    vote_score integer DEFAULT 0 NOT NULL,
    reply_count integer DEFAULT 0 NOT NULL,
    is_pinned boolean DEFAULT false NOT NULL,
    is_locked boolean DEFAULT false NOT NULL,
    is_anonymous boolean DEFAULT false NOT NULL,
    last_activity_at timestamp(0) without time zone,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    deleted_at timestamp(0) without time zone
);


--
-- Name: forum_threads_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.forum_threads_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: forum_threads_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.forum_threads_id_seq OWNED BY public.forum_threads.id;


--
-- Name: forward_contracts; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: forward_contracts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.forward_contracts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: forward_contracts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.forward_contracts_id_seq OWNED BY public.forward_contracts.id;


--
-- Name: harvest_listings; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: harvest_listings_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.harvest_listings_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: harvest_listings_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.harvest_listings_id_seq OWNED BY public.harvest_listings.id;


--
-- Name: job_batches; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: jobs; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: jobs_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.jobs_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: jobs_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.jobs_id_seq OWNED BY public.jobs.id;


--
-- Name: migrations; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.migrations (
    id integer NOT NULL,
    migration character varying(255) NOT NULL,
    batch integer NOT NULL
);


--
-- Name: migrations_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.migrations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: migrations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.migrations_id_seq OWNED BY public.migrations.id;


--
-- Name: password_reset_tokens; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.password_reset_tokens (
    email character varying(255) NOT NULL,
    token character varying(255) NOT NULL,
    created_at timestamp(0) without time zone
);


--
-- Name: personal_access_tokens; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.personal_access_tokens_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.personal_access_tokens_id_seq OWNED BY public.personal_access_tokens.id;


--
-- Name: plots; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: COLUMN plots.calculated_area; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON COLUMN public.plots.calculated_area IS 'Calculated automatically from PostGIS ST_Area';


--
-- Name: plots_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.plots_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: plots_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.plots_id_seq OWNED BY public.plots.id;


--
-- Name: purchases; Type: TABLE; Schema: public; Owner: -
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
    total_contract_amount numeric(12,2),
    crop_demand_offer_id bigint
);


--
-- Name: purchases_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.purchases_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: purchases_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.purchases_id_seq OWNED BY public.purchases.id;


--
-- Name: reply_votes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.reply_votes (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    reply_id bigint NOT NULL,
    value smallint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: reply_votes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.reply_votes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: reply_votes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.reply_votes_id_seq OWNED BY public.reply_votes.id;


--
-- Name: restricted_zones; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.restricted_zones (
    id bigint NOT NULL,
    name character varying(255) NOT NULL,
    type character varying(255) DEFAULT 'restricted'::character varying NOT NULL,
    polygon public.geometry(Polygon,4326),
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: restricted_zones_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.restricted_zones_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: restricted_zones_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.restricted_zones_id_seq OWNED BY public.restricted_zones.id;


--
-- Name: sessions; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.sessions (
    id character varying(255) NOT NULL,
    user_id bigint,
    ip_address character varying(45),
    user_agent text,
    payload text NOT NULL,
    last_activity integer NOT NULL
);


--
-- Name: thread_votes; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.thread_votes (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    thread_id bigint NOT NULL,
    value smallint NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone
);


--
-- Name: thread_votes_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.thread_votes_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: thread_votes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.thread_votes_id_seq OWNED BY public.thread_votes.id;


--
-- Name: user_addresses; Type: TABLE; Schema: public; Owner: -
--

CREATE TABLE public.user_addresses (
    id bigint NOT NULL,
    user_id bigint NOT NULL,
    label character varying(50),
    region_code character varying(10) NOT NULL,
    province_code character varying(10),
    city_municipality_code character varying(10) NOT NULL,
    barangay_code character varying(10) NOT NULL,
    street character varying(255),
    latitude numeric(10,7),
    longitude numeric(10,7),
    is_default boolean DEFAULT false NOT NULL,
    created_at timestamp(0) without time zone,
    updated_at timestamp(0) without time zone,
    location public.geography(Point,4326)
);


--
-- Name: user_addresses_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.user_addresses_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: user_addresses_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.user_addresses_id_seq OWNED BY public.user_addresses.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.users_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: weather_cache; Type: TABLE; Schema: public; Owner: -
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


--
-- Name: weather_cache_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE public.weather_cache_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


--
-- Name: weather_cache_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE public.weather_cache_id_seq OWNED BY public.weather_cache.id;


--
-- Name: chat_attachments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_attachments ALTER COLUMN id SET DEFAULT nextval('public.chat_attachments_id_seq'::regclass);


--
-- Name: chat_conversations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_conversations ALTER COLUMN id SET DEFAULT nextval('public.chat_conversations_id_seq'::regclass);


--
-- Name: chat_messages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_messages ALTER COLUMN id SET DEFAULT nextval('public.chat_messages_id_seq'::regclass);


--
-- Name: chat_participants id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_participants ALTER COLUMN id SET DEFAULT nextval('public.chat_participants_id_seq'::regclass);


--
-- Name: contact_messages id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_messages ALTER COLUMN id SET DEFAULT nextval('public.contact_messages_id_seq'::regclass);


--
-- Name: crop_demand_offers id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.crop_demand_offers ALTER COLUMN id SET DEFAULT nextval('public.crop_demand_offers_id_seq'::regclass);


--
-- Name: crop_demands id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.crop_demands ALTER COLUMN id SET DEFAULT nextval('public.crop_demands_id_seq'::regclass);


--
-- Name: crop_recommendations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.crop_recommendations ALTER COLUMN id SET DEFAULT nextval('public.crop_recommendations_id_seq'::regclass);


--
-- Name: failed_jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs ALTER COLUMN id SET DEFAULT nextval('public.failed_jobs_id_seq'::regclass);


--
-- Name: farms id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.farms ALTER COLUMN id SET DEFAULT nextval('public.farms_id_seq'::regclass);


--
-- Name: forum_attachments id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_attachments ALTER COLUMN id SET DEFAULT nextval('public.forum_attachments_id_seq'::regclass);


--
-- Name: forum_categories id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_categories ALTER COLUMN id SET DEFAULT nextval('public.forum_categories_id_seq'::regclass);


--
-- Name: forum_replies id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_replies ALTER COLUMN id SET DEFAULT nextval('public.forum_replies_id_seq'::regclass);


--
-- Name: forum_reports id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_reports ALTER COLUMN id SET DEFAULT nextval('public.forum_reports_id_seq'::regclass);


--
-- Name: forum_tags id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_tags ALTER COLUMN id SET DEFAULT nextval('public.forum_tags_id_seq'::regclass);


--
-- Name: forum_threads id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_threads ALTER COLUMN id SET DEFAULT nextval('public.forum_threads_id_seq'::regclass);


--
-- Name: forward_contracts id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forward_contracts ALTER COLUMN id SET DEFAULT nextval('public.forward_contracts_id_seq'::regclass);


--
-- Name: harvest_listings id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.harvest_listings ALTER COLUMN id SET DEFAULT nextval('public.harvest_listings_id_seq'::regclass);


--
-- Name: jobs id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs ALTER COLUMN id SET DEFAULT nextval('public.jobs_id_seq'::regclass);


--
-- Name: migrations id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations ALTER COLUMN id SET DEFAULT nextval('public.migrations_id_seq'::regclass);


--
-- Name: personal_access_tokens id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens ALTER COLUMN id SET DEFAULT nextval('public.personal_access_tokens_id_seq'::regclass);


--
-- Name: plots id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plots ALTER COLUMN id SET DEFAULT nextval('public.plots_id_seq'::regclass);


--
-- Name: purchases id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchases ALTER COLUMN id SET DEFAULT nextval('public.purchases_id_seq'::regclass);


--
-- Name: reply_votes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reply_votes ALTER COLUMN id SET DEFAULT nextval('public.reply_votes_id_seq'::regclass);


--
-- Name: restricted_zones id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.restricted_zones ALTER COLUMN id SET DEFAULT nextval('public.restricted_zones_id_seq'::regclass);


--
-- Name: thread_votes id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.thread_votes ALTER COLUMN id SET DEFAULT nextval('public.thread_votes_id_seq'::regclass);


--
-- Name: user_addresses id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_addresses ALTER COLUMN id SET DEFAULT nextval('public.user_addresses_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Name: weather_cache id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.weather_cache ALTER COLUMN id SET DEFAULT nextval('public.weather_cache_id_seq'::regclass);


--
-- Data for Name: chat_attachments; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.chat_attachments (id, message_id, file_path, file_type, file_size, original_name, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: chat_conversations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.chat_conversations (id, last_message_at, created_at, updated_at) FROM stdin;
2	2026-09-29 11:18:05	2026-09-29 11:18:00	2026-09-29 11:18:05
1	2026-10-01 03:04:08	2026-09-29 10:11:00	2026-10-01 03:04:08
\.


--
-- Data for Name: chat_messages; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.chat_messages (id, conversation_id, user_id, body, created_at, updated_at) FROM stdin;
1	1	9	yooooh	2026-09-29 10:11:06	2026-09-29 10:11:06
2	1	8	hello	2026-09-29 10:11:57	2026-09-29 10:11:57
3	2	10	bugok you?	2026-09-29 11:18:05	2026-09-29 11:18:05
4	1	9	xczczxczxc	2026-10-01 03:03:42	2026-10-01 03:03:42
5	1	8	fdfsf	2026-10-01 03:04:08	2026-10-01 03:04:08
\.


--
-- Data for Name: chat_participants; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.chat_participants (id, conversation_id, user_id, last_read_at, created_at, updated_at) FROM stdin;
4	2	9	\N	2026-09-29 11:18:00	2026-09-29 11:18:00
3	2	10	2026-09-29 11:18:00	2026-09-29 11:18:00	2026-09-29 11:18:00
2	1	8	2026-10-01 03:03:56	2026-09-29 10:11:00	2026-10-01 03:03:56
1	1	9	2026-10-01 03:27:14	2026-09-29 10:11:00	2026-10-01 03:27:14
\.


--
-- Data for Name: contact_messages; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.contact_messages (id, name, email, subject, message, status, replied_at, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: crop_demand_offers; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.crop_demand_offers (id, crop_demand_id, farmer_id, quantity_kg, price_per_kg, total_price, currency, message, status, accepted_at, paid_at, delivered_at, completed_at, created_at, updated_at) FROM stdin;
1	1	8	200.00	13.00	2600.00	PHP	hello	withdrawn	\N	\N	\N	\N	2026-09-29 09:57:05	2026-09-29 10:03:39
2	1	8	200.00	13.00	2600.00	PHP	asdasdasd	completed	2026-09-29 10:10:51	2026-09-29 10:12:17	2026-09-29 10:42:14	2026-09-29 11:16:58	2026-09-29 10:04:00	2026-09-29 11:16:58
4	2	10	50.00	8.00	400.00	PHP	\N	completed	2026-09-29 11:36:09	2026-09-29 11:56:55	2026-09-29 11:57:44	2026-09-29 12:07:30	2026-09-29 11:34:06	2026-09-29 12:07:30
3	1	10	100.00	15.00	1500.00	PHP	bugok	delivered	2026-09-29 11:17:01	2026-09-29 11:17:27	2026-09-29 12:21:19	\N	2026-09-29 10:54:11	2026-09-29 12:21:19
\.


--
-- Data for Name: crop_demands; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.crop_demands (id, buyer_id, address_id, title, description, crop_name, quantity_kg, remaining_quantity_kg, target_price_per_kg, total_budget, currency, needed_by_date, expiry_date, status, created_at, updated_at) FROM stdin;
1	9	1	600kg Fresh Tomato	Sample test sdadadsadasdasdasdadasdasdsad adsadasdas dad a	Tomato	300.00	0.00	15.00	4500.00	PHP	2026-10-10	2026-10-10	fully_allocated	2026-09-29 09:52:20	2026-09-29 11:17:01
2	9	1	500kg Fresh Mango	ssssas acasdasdas	Mango	500.00	450.00	8.00	4000.00	PHP	2026-10-10	2026-10-10	open	2026-09-29 11:29:37	2026-09-29 11:36:09
\.


--
-- Data for Name: crop_recommendations; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.crop_recommendations (id, plot_id, crop_name, confidence_score, reasoning, projected_yield, status, created_at, updated_at) FROM stdin;
4	4	Rice (Lowland Paddy)	98	La Paz, Tarlac is a premier rice-producing region in Central Luzon. The 1.3653202785604 hectare plot size is ideal for medium-scale commercial lowland rice farming. The heavy clay soil possesses excellent water retention capabilities, minimizing percolation losses and maintaining the flooded conditions required for optimal paddy rice growth under the warm, humid local climate.	6.14 tons total (4.5 tons/ha on 1.3653202785604 ha)	pending	2026-09-29 09:28:29	2026-09-29 09:28:29
5	4	Sugarcane	95	Tarlac is historically renowned for extensive sugarcane production, supported by nearby milling infrastructure. The 1.3653202785604 hectare area accommodates a profitable commercial block of sugarcane. Clay soils are well-suited for sugarcane due to their high nutrient-holding capacity, which feeds the crop throughout its long maturation cycle, paired with the high ambient temperatures of La Paz.	95.57 tons total (70 tons/ha on 1.3653202785604 ha)	pending	2026-09-29 09:28:29	2026-09-29 09:28:29
6	4	Corn (Yellow)	90	Yellow corn is a major rotation and cash crop in Tarlac, vital for the local livestock feed industry. For a 1.3653202785604 hectare plot, corn offers straightforward mechanization and solid commercial returns. While clay soils require proper drainage management to prevent waterlogging during heavy tropical rains, they provide exceptional fertility and moisture retention during dry spells.	6.83 tons total (5.0 tons/ha on 1.3653202785604 ha)	pending	2026-09-29 09:28:29	2026-09-29 09:28:29
7	4	Cassava	88	Cassava thrives in the Tarlac agricultural landscape as a resilient root crop used for food and industrial starch. The 1.3653202785604 hectare plot size allows for efficient tuber production. Although clay can compact, well-managed clay soils in La Paz provide robust structural support and nutrient reserves that yield heavy, starch-dense roots.	34.13 tons total (25 tons/ha on 1.3653202785604 ha)	pending	2026-09-29 09:28:29	2026-09-29 09:28:29
8	4	Sweet Potato (Kamote)	85	Sweet potato is widely cultivated across Central Luzon for local wet markets and commercial snack processing. A 1.3653202785604 hectare plot provides a highly manageable scale for ridge-planted sweet potato. The clay soil helps retain the consistent moisture needed for root initiation, while ridging mitigates the density of the clay to allow proper tuber expansion.	19.11 tons total (14 tons/ha on 1.3653202785604 ha)	pending	2026-09-29 09:28:29	2026-09-29 09:28:29
9	4	Eggplant (Talong)	83	Eggplant is a staple vegetable intensely favored in Philippine agriculture and heavily traded in Tarlac markets. On 1.3653202785604 hectares, intensive vegetable cultivation can yield high revenues. Clay soils excel in supplying the continuous moisture and heavy potassium/nitrogen nutrition that prolific fruiting eggplants demand in the humid local climate.	21.84 tons total (16 tons/ha on 1.3653202785604 ha)	pending	2026-09-29 09:28:29	2026-09-29 09:28:29
10	4	Mung Bean (Munggo)	80	Mung bean is a popular post-rice or rotation crop in Tarlac due to its short maturity cycle and nitrogen-fixing abilities. Cultivating 1.3653202785604 hectares of mung beans optimizes land use intensity. Clay soils retain residual moisture from the wet season perfectly, allowing the crop to mature successfully with minimal supplemental irrigation.	1.64 tons total (1.2 tons/ha on 1.3653202785604 ha)	pending	2026-09-29 09:28:29	2026-09-29 09:28:29
11	4	Bitter Gourd (Ampalaya)	78	Bitter gourd is a high-value vine vegetable commonly grown by farmers in La Paz, Tarlac using trellis systems. A 1.3653202785604 hectare commercial trellis setup can be exceptionally lucrative. The clay soil's superior nutrient retention supports the heavy vegetative growth and continuous fruiting of the crop, provided surface drainage is maintained.	13.65 tons total (10 tons/ha on 1.3653202785604 ha)	pending	2026-09-29 09:28:29	2026-09-29 09:28:29
12	4	Watermelon	75	Watermelon is a profitable seasonal cash crop extensively grown in Central Luzon river basins and flatlands following grain harvests. The 1.3653202785604 hectare plot size is ideal for managing vine spread and manual harvesting. Clay soils, when mounded, supply the steady moisture reservoir required to size up large, sweet melons during warm weather.	24.58 tons total (18 tons/ha on 1.3653202785604 ha)	pending	2026-09-29 09:28:29	2026-09-29 09:28:29
13	4	Peanut (Mani)	72	Peanuts are traditionally grown in parts of Tarlac as an alternative legume crop. Managing 1.3653202785604 hectares of peanuts offers good market demand locally. While loams are often preferred, clay soils with good organic matter management provide ample calcium and moisture for robust pod development and kernel filling.	2.18 tons total (1.6 tons/ha on 1.3653202785604 ha)	pending	2026-09-29 09:28:29	2026-09-29 09:28:29
\.


--
-- Data for Name: farms; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.farms (id, user_id, name, address, city, state, zip, total_area, created_at, updated_at, country) FROM stdin;
4	8	Kevin's Farm	123	La Paz	Tarlac	2314	22	2026-09-29 09:27:03	2026-09-29 09:27:03	Philippines
\.


--
-- Data for Name: forum_attachments; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.forum_attachments (id, user_id, attachable_type, attachable_id, file_path, file_type, file_size, original_name, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: forum_categories; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.forum_categories (id, name, slug, description, icon_emoji, sort_order, created_at, updated_at) FROM stdin;
3	General Discussion	general	Open conversation about farming and agriculture	💬	10	2026-09-29 04:07:06	2026-09-29 04:07:06
4	Crop Help & Advice	crop-help	Ask questions about crop health, planting, and harvesting	🌱	20	2026-09-29 04:07:06	2026-09-29 04:07:06
5	Soil & Land Management	soil-management	Soil types, fertilization, land preparation topics	🏔️	30	2026-09-29 04:07:06	2026-09-29 04:07:06
6	Weather & Climate	weather	Weather patterns, climate adaptation, seasonal planning	🌦️	40	2026-09-29 04:07:06	2026-09-29 04:07:06
7	Marketplace Talk	marketplace	Buying, selling, pricing, and contract discussions	🛒	50	2026-09-29 04:07:06	2026-09-29 04:07:06
8	Tools & Equipment	tools-equipment	Farm equipment, tools, and technology recommendations	🔧	60	2026-09-29 04:07:06	2026-09-29 04:07:06
9	Pest & Disease Control	pest-control	Managing insects, weeds, and plant diseases	🐛	45	2026-09-29 04:07:06	2026-09-29 04:07:06
10	Irrigation & Water	irrigation	Water management, pumps, and irrigation systems	💧	47	2026-09-29 04:07:06	2026-09-29 04:07:06
11	Organic & Sustainable	organic	Organic farming practices and sustainability	🌿	55	2026-09-29 04:07:06	2026-09-29 04:07:06
12	Livestock & Poultry	livestock	Animal husbandry and care	🐄	65	2026-09-29 04:07:06	2026-09-29 04:07:06
13	Success Stories	success-stories	Share your wins — great harvests, solved problems	🏆	70	2026-09-29 04:07:06	2026-09-29 04:07:06
14	Farm Finance & Grants	finance	Discuss loans, subsidies, and farm accounting	💵	80	2026-09-29 04:07:06	2026-09-29 04:07:06
\.


--
-- Data for Name: forum_replies; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.forum_replies (id, thread_id, user_id, parent_id, body, vote_score, is_accepted, is_anonymous, created_at, updated_at, deleted_at) FROM stdin;
\.


--
-- Data for Name: forum_reports; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.forum_reports (id, user_id, reportable_type, reportable_id, reason, description, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: forum_tags; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.forum_tags (id, name, slug, created_at, updated_at) FROM stdin;
1	Rice	rice	2026-09-29 04:07:07	2026-09-29 04:07:07
2	Corn	corn	2026-09-29 04:07:07	2026-09-29 04:07:07
3	Vegetables	vegetables	2026-09-29 04:07:07	2026-09-29 04:07:07
4	Fruits	fruits	2026-09-29 04:07:07	2026-09-29 04:07:07
5	Organic	organic	2026-09-29 04:07:07	2026-09-29 04:07:07
6	Pest Control	pest-control	2026-09-29 04:07:07	2026-09-29 04:07:07
7	Fertilizer	fertilizer	2026-09-29 04:07:07	2026-09-29 04:07:07
8	Irrigation	irrigation	2026-09-29 04:07:07	2026-09-29 04:07:07
9	Harvest	harvest	2026-09-29 04:07:07	2026-09-29 04:07:07
10	Market Price	market-price	2026-09-29 04:07:07	2026-09-29 04:07:07
11	Contract	contract	2026-09-29 04:07:07	2026-09-29 04:07:07
12	Logistics	logistics	2026-09-29 04:07:07	2026-09-29 04:07:07
13	Weather Alert	weather-alert	2026-09-29 04:07:07	2026-09-29 04:07:07
14	Pesticides	pesticides	2026-09-29 04:07:07	2026-09-29 04:07:07
15	Insects	insects	2026-09-29 04:07:07	2026-09-29 04:07:07
16	Weeds	weeds	2026-09-29 04:07:07	2026-09-29 04:07:07
17	Disease	disease	2026-09-29 04:07:07	2026-09-29 04:07:07
18	Pumps	pumps	2026-09-29 04:07:07	2026-09-29 04:07:07
19	Drip Irrigation	drip-irrigation	2026-09-29 04:07:07	2026-09-29 04:07:07
20	Water Supply	water-supply	2026-09-29 04:07:07	2026-09-29 04:07:07
21	Compost	compost	2026-09-29 04:07:07	2026-09-29 04:07:07
22	Sustainable	sustainable	2026-09-29 04:07:07	2026-09-29 04:07:07
23	Permaculture	permaculture	2026-09-29 04:07:07	2026-09-29 04:07:07
24	Cattle	cattle	2026-09-29 04:07:07	2026-09-29 04:07:07
25	Poultry	poultry	2026-09-29 04:07:07	2026-09-29 04:07:07
26	Swine	swine	2026-09-29 04:07:07	2026-09-29 04:07:07
27	Animal Feed	animal-feed	2026-09-29 04:07:07	2026-09-29 04:07:07
28	Loans	loans	2026-09-29 04:07:07	2026-09-29 04:07:07
29	Grants	grants	2026-09-29 04:07:07	2026-09-29 04:07:07
30	Subsidies	subsidies	2026-09-29 04:07:07	2026-09-29 04:07:07
31	Insurance	insurance	2026-09-29 04:07:07	2026-09-29 04:07:07
\.


--
-- Data for Name: forum_thread_tag; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.forum_thread_tag (thread_id, tag_id) FROM stdin;
4	24
4	29
4	10
4	13
5	9
5	21
5	18
5	13
\.


--
-- Data for Name: forum_threads; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.forum_threads (id, user_id, category_id, accepted_reply_id, title, body, vote_score, reply_count, is_pinned, is_locked, is_anonymous, last_activity_at, created_at, updated_at, deleted_at) FROM stdin;
4	8	4	\N	hello world	dsad asdasd sad sadasd sad sad asd d asdasd ada s	0	0	f	f	t	2026-09-29 04:12:29	2026-09-29 04:12:29	2026-09-29 04:12:29	\N
5	8	3	\N	Goodbye RareJob	Paalam sa lahat mga kaibigan	1	0	f	f	f	2026-10-01 03:04:54	2026-10-01 03:04:54	2026-10-01 03:08:53	\N
\.


--
-- Data for Name: forward_contracts; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.forward_contracts (id, farmer_id, crop_recommendation_id, title, description, crop_name, quantity_kg, price_per_kg, total_price, currency, estimated_harvest_date, expiry_date, status, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: harvest_listings; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.harvest_listings (id, farmer_id, title, description, crop_name, quantity_kg, price_per_kg, total_price, currency, estimated_harvest_date, expiry_date, status, shelf_life_days, is_harvest_available, created_at, updated_at) FROM stdin;
2	8	Sakura Rice	Japanese rice for affordable price	Rice	500.00	20.76	10380.00	PHP	2026-09-29	2027-03-28	available	180	t	2026-09-29 09:29:30	2026-10-01 03:02:56
3	8	Sakura Rice	Japanese rice for affordable price	Rice	150.00	20.76	3114.00	PHP	2026-09-29	2027-03-28	sold	180	t	2026-10-01 03:02:56	2026-10-01 03:03:33
\.


--
-- Data for Name: migrations; Type: TABLE DATA; Schema: public; Owner: -
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
17	2026_09_23_114403_create_chat_tables	1
18	2026_09_23_114403_create_forum_tables	1
19	2026_09_29_100000_create_user_addresses_table	2
20	2026_09_29_110000_create_crop_demands_table	2
21	2026_09_29_110001_create_crop_demand_offers_table	2
22	2026_09_29_120000_add_demand_offer_to_purchases_table	2
\.


--
-- Data for Name: password_reset_tokens; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.password_reset_tokens (email, token, created_at) FROM stdin;
\.


--
-- Data for Name: plots; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.plots (id, farm_id, name, polygon, soil_type, calculated_area, created_at, updated_at, agromonitoring_polyid) FROM stdin;
4	4	Plot 1	0103000020E61000000100000005000000FE7E315BB22C5E4033DE567A6DCE2E400CB265F9BA2C5E4020B58993FBCD2E40399D64ABCB2C5E40A3073E062BCE2E4023111AC1C62C5E4014D1AFAD9FCE2E40FE7E315BB22C5E4033DE567A6DCE2E40	clay	1.3653202785603702	2026-09-29 09:28:13	2026-09-29 09:28:21	6abb84b54e13cb166ffaf3a9
\.


--
-- Data for Name: purchases; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.purchases (id, buyer_id, forward_contract_id, paymongo_payment_id, paymongo_checkout_id, payment_method, amount_paid, currency, payment_status, purchased_at, created_at, updated_at, harvest_listing_id, quantity_kg, cash_payment_status, cash_amount_confirmed, farmer_confirmed_at, is_downpayment, total_contract_amount, crop_demand_offer_id) FROM stdin;
3	9	\N	\N	\N	cash	260.00	PHP	completed	2026-09-29 10:12:17	2026-09-29 10:11:32	2026-09-29 10:12:17	\N	200.00	fully_paid	2600.00	2026-09-29 10:12:17	t	2600.00	2
4	9	\N	pay_GtDcqdtuPzrQxtZWaMMp9hYd	cs_5cb3dba95b931ad3d414a703	gcash	150.00	PHP	completed	2026-09-29 11:17:27	2026-09-29 11:17:05	2026-09-29 11:17:27	\N	100.00	\N	0.00	\N	t	1500.00	3
5	9	\N	\N	cs_31e89719067d495788f1ca2f	gcash	40.00	PHP	failed	\N	2026-09-29 11:36:14	2026-09-29 11:55:14	\N	50.00	\N	0.00	\N	t	400.00	4
6	9	\N	\N	cs_e3fd493d16282abc95259e2e	gcash	40.00	PHP	failed	\N	2026-09-29 11:55:40	2026-09-29 11:55:47	\N	50.00	\N	0.00	\N	t	400.00	4
7	9	\N	pay_ureknTHk81Gd8Eo49td7pndt	cs_ea5fb31b85c903c1833eed85	paymaya	400.00	PHP	completed	2026-09-29 11:56:55	2026-09-29 11:56:18	2026-09-29 12:12:57	\N	50.00	\N	0.00	\N	t	400.00	4
8	9	\N	pay_kxzmvYs21xav8JZHYJ3ctwfz	cs_23b420882cd97bd94836ebaf	paymaya	3114.00	PHP	completed	2026-10-01 03:03:33	2026-10-01 03:02:56	2026-10-01 03:03:33	3	150.00	\N	0.00	\N	f	3114.00	\N
\.


--
-- Data for Name: reply_votes; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.reply_votes (id, user_id, reply_id, value, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: restricted_zones; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.restricted_zones (id, name, type, polygon, created_at, updated_at) FROM stdin;
\.


--
-- Data for Name: spatial_ref_sys; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.spatial_ref_sys (srid, auth_name, auth_srid, srtext, proj4text) FROM stdin;
\.


--
-- Data for Name: thread_votes; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.thread_votes (id, user_id, thread_id, value, created_at, updated_at) FROM stdin;
1	9	5	1	2026-10-01 03:08:53	2026-10-01 03:08:53
\.


--
-- Data for Name: user_addresses; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.user_addresses (id, user_id, label, region_code, province_code, city_municipality_code, barangay_code, street, latitude, longitude, is_default, created_at, updated_at, location) FROM stdin;
1	9	\N	030000000	036900000	036907000	036907005	Purok 4	15.4007850	120.6999740	t	2026-09-29 09:49:58	2026-09-29 09:49:58	0101000020E61000003883BF5FCC2C5E40697407B133CD2E40
2	8	Kevin farm	030000000	034900000	034903000	034903011	Purok 4	15.4884680	120.9815960	t	2026-09-29 09:56:47	2026-09-29 09:56:47	0101000020E61000009A780778D23E5E40F9484A7A18FA2E40
3	10	\N	010000000	012800000	012816000	012816031	123	18.0435840	120.5255560	t	2026-09-29 10:53:38	2026-09-29 10:53:38	0101000020E6100000DC0DA2B5A2215E40FDA02E52280B3240
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.users (id, name, email, email_verified_at, password, role, avatar_url, phone, address, remember_token, created_at, updated_at) FROM stdin;
8	Kevin Sapang	kevin.sapang@gmail.com	2026-09-29 04:05:52	$2y$12$/5UH7OPyqOjDqACHsSGuyuOcgoxKRBFQmWRuMDDKqAuGJmxs2lUVS	farmer	\N	\N	\N	\N	2026-09-29 04:05:43	2026-09-29 04:05:52
9	Carolyn Arceo	carolyn.arceo@gmail.com	2026-09-29 09:50:18	$2y$12$Mma9kz7S9Tca0pTcrvkm9ur13V3Albs99x1hgnjjghYwin3zVsltm	buyer	\N	\N	\N	\N	2026-09-29 09:49:58	2026-09-29 09:50:18
10	Toby Maguire	toby.maguire@gmail.com	2026-09-29 10:53:45	$2y$12$b48X779vALCb0Ec7luTwbe/4tUBIj120Wxsh0LSwUtzbst00zLiW6	farmer	\N	\N	\N	\N	2026-09-29 10:53:38	2026-09-29 10:53:45
\.


--
-- Data for Name: weather_cache; Type: TABLE DATA; Schema: public; Owner: -
--

COPY public.weather_cache (id, latitude, longitude, weather_data, expires_at, created_at, updated_at) FROM stdin;
1	15.4029000	120.6992000	{"soil": {"dt": 1790640000, "t0": 300.276, "t10": 299.905, "moisture": 0.292}, "weather": {"dt": 1790674102, "main": {"temp": 304.26, "humidity": 78, "pressure": 1011, "temp_max": 304.26, "temp_min": 303.92, "sea_level": 1011, "feels_like": 311.26, "grnd_level": 1007}, "wind": {"deg": 343, "gust": 1.31, "speed": 1.41}, "clouds": {"all": 96}, "weather": [{"id": 804, "icon": "04d", "main": "Clouds", "description": "overcast clouds"}]}}	2026-09-29 11:28:23	2026-09-29 09:28:23	2026-09-29 09:28:23
\.


--
-- Data for Name: geocode_settings; Type: TABLE DATA; Schema: tiger; Owner: -
--

COPY tiger.geocode_settings (name, setting, unit, category, short_desc) FROM stdin;
\.


--
-- Data for Name: pagc_gaz; Type: TABLE DATA; Schema: tiger; Owner: -
--

COPY tiger.pagc_gaz (id, seq, word, stdword, token, is_custom) FROM stdin;
\.


--
-- Data for Name: pagc_lex; Type: TABLE DATA; Schema: tiger; Owner: -
--

COPY tiger.pagc_lex (id, seq, word, stdword, token, is_custom) FROM stdin;
\.


--
-- Data for Name: pagc_rules; Type: TABLE DATA; Schema: tiger; Owner: -
--

COPY tiger.pagc_rules (id, rule, is_custom) FROM stdin;
\.


--
-- Data for Name: topology; Type: TABLE DATA; Schema: topology; Owner: -
--

COPY topology.topology (id, name, srid, "precision", hasz) FROM stdin;
\.


--
-- Data for Name: layer; Type: TABLE DATA; Schema: topology; Owner: -
--

COPY topology.layer (topology_id, layer_id, schema_name, table_name, feature_column, feature_type, level, child_id) FROM stdin;
\.


--
-- Name: chat_attachments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.chat_attachments_id_seq', 1, false);


--
-- Name: chat_conversations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.chat_conversations_id_seq', 2, true);


--
-- Name: chat_messages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.chat_messages_id_seq', 5, true);


--
-- Name: chat_participants_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.chat_participants_id_seq', 4, true);


--
-- Name: contact_messages_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.contact_messages_id_seq', 1, false);


--
-- Name: crop_demand_offers_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.crop_demand_offers_id_seq', 4, true);


--
-- Name: crop_demands_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.crop_demands_id_seq', 2, true);


--
-- Name: crop_recommendations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.crop_recommendations_id_seq', 13, true);


--
-- Name: failed_jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.failed_jobs_id_seq', 4, true);


--
-- Name: farms_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.farms_id_seq', 4, true);


--
-- Name: forum_attachments_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.forum_attachments_id_seq', 1, false);


--
-- Name: forum_categories_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.forum_categories_id_seq', 14, true);


--
-- Name: forum_replies_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.forum_replies_id_seq', 1, false);


--
-- Name: forum_reports_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.forum_reports_id_seq', 1, false);


--
-- Name: forum_tags_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.forum_tags_id_seq', 31, true);


--
-- Name: forum_threads_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.forum_threads_id_seq', 5, true);


--
-- Name: forward_contracts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.forward_contracts_id_seq', 3, true);


--
-- Name: harvest_listings_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.harvest_listings_id_seq', 3, true);


--
-- Name: jobs_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.jobs_id_seq', 1, false);


--
-- Name: migrations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.migrations_id_seq', 22, true);


--
-- Name: personal_access_tokens_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.personal_access_tokens_id_seq', 32, true);


--
-- Name: plots_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.plots_id_seq', 4, true);


--
-- Name: purchases_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.purchases_id_seq', 8, true);


--
-- Name: reply_votes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.reply_votes_id_seq', 1, false);


--
-- Name: restricted_zones_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.restricted_zones_id_seq', 1, false);


--
-- Name: thread_votes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.thread_votes_id_seq', 1, true);


--
-- Name: user_addresses_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.user_addresses_id_seq', 3, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.users_id_seq', 10, true);


--
-- Name: weather_cache_id_seq; Type: SEQUENCE SET; Schema: public; Owner: -
--

SELECT pg_catalog.setval('public.weather_cache_id_seq', 1, true);


--
-- Name: topology_id_seq; Type: SEQUENCE SET; Schema: topology; Owner: -
--

SELECT pg_catalog.setval('topology.topology_id_seq', 1, false);


--
-- Name: cache_locks cache_locks_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache_locks
    ADD CONSTRAINT cache_locks_pkey PRIMARY KEY (key);


--
-- Name: cache cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.cache
    ADD CONSTRAINT cache_pkey PRIMARY KEY (key);


--
-- Name: chat_attachments chat_attachments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_attachments
    ADD CONSTRAINT chat_attachments_pkey PRIMARY KEY (id);


--
-- Name: chat_conversations chat_conversations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_conversations
    ADD CONSTRAINT chat_conversations_pkey PRIMARY KEY (id);


--
-- Name: chat_messages chat_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_messages
    ADD CONSTRAINT chat_messages_pkey PRIMARY KEY (id);


--
-- Name: chat_participants chat_participants_conversation_id_user_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_participants
    ADD CONSTRAINT chat_participants_conversation_id_user_id_unique UNIQUE (conversation_id, user_id);


--
-- Name: chat_participants chat_participants_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_participants
    ADD CONSTRAINT chat_participants_pkey PRIMARY KEY (id);


--
-- Name: contact_messages contact_messages_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.contact_messages
    ADD CONSTRAINT contact_messages_pkey PRIMARY KEY (id);


--
-- Name: crop_demand_offers crop_demand_offers_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.crop_demand_offers
    ADD CONSTRAINT crop_demand_offers_pkey PRIMARY KEY (id);


--
-- Name: crop_demands crop_demands_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.crop_demands
    ADD CONSTRAINT crop_demands_pkey PRIMARY KEY (id);


--
-- Name: crop_recommendations crop_recommendations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.crop_recommendations
    ADD CONSTRAINT crop_recommendations_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_pkey PRIMARY KEY (id);


--
-- Name: failed_jobs failed_jobs_uuid_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.failed_jobs
    ADD CONSTRAINT failed_jobs_uuid_unique UNIQUE (uuid);


--
-- Name: farms farms_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.farms
    ADD CONSTRAINT farms_pkey PRIMARY KEY (id);


--
-- Name: forum_attachments forum_attachments_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_attachments
    ADD CONSTRAINT forum_attachments_pkey PRIMARY KEY (id);


--
-- Name: forum_categories forum_categories_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_categories
    ADD CONSTRAINT forum_categories_name_unique UNIQUE (name);


--
-- Name: forum_categories forum_categories_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_categories
    ADD CONSTRAINT forum_categories_pkey PRIMARY KEY (id);


--
-- Name: forum_categories forum_categories_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_categories
    ADD CONSTRAINT forum_categories_slug_unique UNIQUE (slug);


--
-- Name: forum_replies forum_replies_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_replies
    ADD CONSTRAINT forum_replies_pkey PRIMARY KEY (id);


--
-- Name: forum_reports forum_reports_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_reports
    ADD CONSTRAINT forum_reports_pkey PRIMARY KEY (id);


--
-- Name: forum_reports forum_reports_user_id_reportable_type_reportable_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_reports
    ADD CONSTRAINT forum_reports_user_id_reportable_type_reportable_id_unique UNIQUE (user_id, reportable_type, reportable_id);


--
-- Name: forum_tags forum_tags_name_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_tags
    ADD CONSTRAINT forum_tags_name_unique UNIQUE (name);


--
-- Name: forum_tags forum_tags_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_tags
    ADD CONSTRAINT forum_tags_pkey PRIMARY KEY (id);


--
-- Name: forum_tags forum_tags_slug_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_tags
    ADD CONSTRAINT forum_tags_slug_unique UNIQUE (slug);


--
-- Name: forum_thread_tag forum_thread_tag_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_thread_tag
    ADD CONSTRAINT forum_thread_tag_pkey PRIMARY KEY (thread_id, tag_id);


--
-- Name: forum_threads forum_threads_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_threads
    ADD CONSTRAINT forum_threads_pkey PRIMARY KEY (id);


--
-- Name: forward_contracts forward_contracts_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forward_contracts
    ADD CONSTRAINT forward_contracts_pkey PRIMARY KEY (id);


--
-- Name: harvest_listings harvest_listings_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.harvest_listings
    ADD CONSTRAINT harvest_listings_pkey PRIMARY KEY (id);


--
-- Name: job_batches job_batches_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.job_batches
    ADD CONSTRAINT job_batches_pkey PRIMARY KEY (id);


--
-- Name: jobs jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- Name: migrations migrations_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.migrations
    ADD CONSTRAINT migrations_pkey PRIMARY KEY (id);


--
-- Name: password_reset_tokens password_reset_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.password_reset_tokens
    ADD CONSTRAINT password_reset_tokens_pkey PRIMARY KEY (email);


--
-- Name: personal_access_tokens personal_access_tokens_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_pkey PRIMARY KEY (id);


--
-- Name: personal_access_tokens personal_access_tokens_token_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.personal_access_tokens
    ADD CONSTRAINT personal_access_tokens_token_unique UNIQUE (token);


--
-- Name: plots plots_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plots
    ADD CONSTRAINT plots_pkey PRIMARY KEY (id);


--
-- Name: purchases purchases_paymongo_checkout_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_paymongo_checkout_id_unique UNIQUE (paymongo_checkout_id);


--
-- Name: purchases purchases_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_pkey PRIMARY KEY (id);


--
-- Name: reply_votes reply_votes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reply_votes
    ADD CONSTRAINT reply_votes_pkey PRIMARY KEY (id);


--
-- Name: reply_votes reply_votes_user_id_reply_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reply_votes
    ADD CONSTRAINT reply_votes_user_id_reply_id_unique UNIQUE (user_id, reply_id);


--
-- Name: restricted_zones restricted_zones_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.restricted_zones
    ADD CONSTRAINT restricted_zones_pkey PRIMARY KEY (id);


--
-- Name: sessions sessions_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.sessions
    ADD CONSTRAINT sessions_pkey PRIMARY KEY (id);


--
-- Name: thread_votes thread_votes_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.thread_votes
    ADD CONSTRAINT thread_votes_pkey PRIMARY KEY (id);


--
-- Name: thread_votes thread_votes_user_id_thread_id_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.thread_votes
    ADD CONSTRAINT thread_votes_user_id_thread_id_unique UNIQUE (user_id, thread_id);


--
-- Name: user_addresses user_addresses_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_addresses
    ADD CONSTRAINT user_addresses_pkey PRIMARY KEY (id);


--
-- Name: users users_email_unique; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_unique UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: weather_cache weather_cache_pkey; Type: CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.weather_cache
    ADD CONSTRAINT weather_cache_pkey PRIMARY KEY (id);


--
-- Name: cache_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_expiration_index ON public.cache USING btree (expiration);


--
-- Name: cache_locks_expiration_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX cache_locks_expiration_index ON public.cache_locks USING btree (expiration);


--
-- Name: chat_messages_conversation_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX chat_messages_conversation_id_created_at_index ON public.chat_messages USING btree (conversation_id, created_at);


--
-- Name: chat_participants_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX chat_participants_user_id_index ON public.chat_participants USING btree (user_id);


--
-- Name: crop_demand_offers_crop_demand_id_status_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX crop_demand_offers_crop_demand_id_status_index ON public.crop_demand_offers USING btree (crop_demand_id, status);


--
-- Name: crop_demand_offers_farmer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX crop_demand_offers_farmer_id_index ON public.crop_demand_offers USING btree (farmer_id);


--
-- Name: crop_demand_offers_one_active_per_farmer; Type: INDEX; Schema: public; Owner: -
--

CREATE UNIQUE INDEX crop_demand_offers_one_active_per_farmer ON public.crop_demand_offers USING btree (crop_demand_id, farmer_id) WHERE ((status)::text = ANY ((ARRAY['pending'::character varying, 'accepted'::character varying, 'paid'::character varying, 'delivered'::character varying])::text[]));


--
-- Name: crop_demands_buyer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX crop_demands_buyer_id_index ON public.crop_demands USING btree (buyer_id);


--
-- Name: crop_demands_crop_name_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX crop_demands_crop_name_index ON public.crop_demands USING btree (crop_name);


--
-- Name: crop_demands_status_expiry_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX crop_demands_status_expiry_date_index ON public.crop_demands USING btree (status, expiry_date);


--
-- Name: forum_attachments_attachable_type_attachable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX forum_attachments_attachable_type_attachable_id_index ON public.forum_attachments USING btree (attachable_type, attachable_id);


--
-- Name: forum_replies_thread_id_created_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX forum_replies_thread_id_created_at_index ON public.forum_replies USING btree (thread_id, created_at);


--
-- Name: forum_replies_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX forum_replies_user_id_index ON public.forum_replies USING btree (user_id);


--
-- Name: forum_reports_reportable_type_reportable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX forum_reports_reportable_type_reportable_id_index ON public.forum_reports USING btree (reportable_type, reportable_id);


--
-- Name: forum_threads_category_id_last_activity_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX forum_threads_category_id_last_activity_at_index ON public.forum_threads USING btree (category_id, last_activity_at);


--
-- Name: forum_threads_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX forum_threads_user_id_index ON public.forum_threads USING btree (user_id);


--
-- Name: forward_contracts_crop_recommendation_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX forward_contracts_crop_recommendation_id_index ON public.forward_contracts USING btree (crop_recommendation_id);


--
-- Name: forward_contracts_farmer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX forward_contracts_farmer_id_index ON public.forward_contracts USING btree (farmer_id);


--
-- Name: forward_contracts_status_estimated_harvest_date_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX forward_contracts_status_estimated_harvest_date_index ON public.forward_contracts USING btree (status, estimated_harvest_date);


--
-- Name: harvest_listings_farmer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX harvest_listings_farmer_id_index ON public.harvest_listings USING btree (farmer_id);


--
-- Name: hl_availability_idx; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX hl_availability_idx ON public.harvest_listings USING btree (is_harvest_available, status, estimated_harvest_date);


--
-- Name: jobs_queue_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX jobs_queue_index ON public.jobs USING btree (queue);


--
-- Name: personal_access_tokens_expires_at_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_expires_at_index ON public.personal_access_tokens USING btree (expires_at);


--
-- Name: personal_access_tokens_tokenable_type_tokenable_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX personal_access_tokens_tokenable_type_tokenable_id_index ON public.personal_access_tokens USING btree (tokenable_type, tokenable_id);


--
-- Name: purchases_buyer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX purchases_buyer_id_index ON public.purchases USING btree (buyer_id);


--
-- Name: purchases_crop_demand_offer_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX purchases_crop_demand_offer_id_index ON public.purchases USING btree (crop_demand_offer_id);


--
-- Name: purchases_forward_contract_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX purchases_forward_contract_id_index ON public.purchases USING btree (forward_contract_id);


--
-- Name: sessions_last_activity_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_last_activity_index ON public.sessions USING btree (last_activity);


--
-- Name: sessions_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX sessions_user_id_index ON public.sessions USING btree (user_id);


--
-- Name: user_addresses_barangay_code_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_addresses_barangay_code_index ON public.user_addresses USING btree (barangay_code);


--
-- Name: user_addresses_location_gist; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_addresses_location_gist ON public.user_addresses USING gist (location);


--
-- Name: user_addresses_user_id_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_addresses_user_id_index ON public.user_addresses USING btree (user_id);


--
-- Name: user_addresses_user_id_is_default_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX user_addresses_user_id_is_default_index ON public.user_addresses USING btree (user_id, is_default);


--
-- Name: weather_cache_latitude_longitude_index; Type: INDEX; Schema: public; Owner: -
--

CREATE INDEX weather_cache_latitude_longitude_index ON public.weather_cache USING btree (latitude, longitude);


--
-- Name: chat_attachments chat_attachments_message_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_attachments
    ADD CONSTRAINT chat_attachments_message_id_foreign FOREIGN KEY (message_id) REFERENCES public.chat_messages(id) ON DELETE CASCADE;


--
-- Name: chat_messages chat_messages_conversation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_messages
    ADD CONSTRAINT chat_messages_conversation_id_foreign FOREIGN KEY (conversation_id) REFERENCES public.chat_conversations(id) ON DELETE CASCADE;


--
-- Name: chat_messages chat_messages_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_messages
    ADD CONSTRAINT chat_messages_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: chat_participants chat_participants_conversation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_participants
    ADD CONSTRAINT chat_participants_conversation_id_foreign FOREIGN KEY (conversation_id) REFERENCES public.chat_conversations(id) ON DELETE CASCADE;


--
-- Name: chat_participants chat_participants_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.chat_participants
    ADD CONSTRAINT chat_participants_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: crop_demand_offers crop_demand_offers_crop_demand_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.crop_demand_offers
    ADD CONSTRAINT crop_demand_offers_crop_demand_id_foreign FOREIGN KEY (crop_demand_id) REFERENCES public.crop_demands(id) ON DELETE CASCADE;


--
-- Name: crop_demand_offers crop_demand_offers_farmer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.crop_demand_offers
    ADD CONSTRAINT crop_demand_offers_farmer_id_foreign FOREIGN KEY (farmer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: crop_demands crop_demands_address_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.crop_demands
    ADD CONSTRAINT crop_demands_address_id_foreign FOREIGN KEY (address_id) REFERENCES public.user_addresses(id) ON DELETE RESTRICT;


--
-- Name: crop_demands crop_demands_buyer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.crop_demands
    ADD CONSTRAINT crop_demands_buyer_id_foreign FOREIGN KEY (buyer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: crop_recommendations crop_recommendations_plot_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.crop_recommendations
    ADD CONSTRAINT crop_recommendations_plot_id_foreign FOREIGN KEY (plot_id) REFERENCES public.plots(id) ON DELETE CASCADE;


--
-- Name: farms farms_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.farms
    ADD CONSTRAINT farms_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: forum_attachments forum_attachments_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_attachments
    ADD CONSTRAINT forum_attachments_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: forum_replies forum_replies_parent_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_replies
    ADD CONSTRAINT forum_replies_parent_id_foreign FOREIGN KEY (parent_id) REFERENCES public.forum_replies(id) ON DELETE CASCADE;


--
-- Name: forum_replies forum_replies_thread_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_replies
    ADD CONSTRAINT forum_replies_thread_id_foreign FOREIGN KEY (thread_id) REFERENCES public.forum_threads(id) ON DELETE CASCADE;


--
-- Name: forum_replies forum_replies_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_replies
    ADD CONSTRAINT forum_replies_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: forum_reports forum_reports_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_reports
    ADD CONSTRAINT forum_reports_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: forum_thread_tag forum_thread_tag_tag_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_thread_tag
    ADD CONSTRAINT forum_thread_tag_tag_id_foreign FOREIGN KEY (tag_id) REFERENCES public.forum_tags(id) ON DELETE CASCADE;


--
-- Name: forum_thread_tag forum_thread_tag_thread_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_thread_tag
    ADD CONSTRAINT forum_thread_tag_thread_id_foreign FOREIGN KEY (thread_id) REFERENCES public.forum_threads(id) ON DELETE CASCADE;


--
-- Name: forum_threads forum_threads_accepted_reply_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_threads
    ADD CONSTRAINT forum_threads_accepted_reply_id_foreign FOREIGN KEY (accepted_reply_id) REFERENCES public.forum_replies(id) ON DELETE SET NULL;


--
-- Name: forum_threads forum_threads_category_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_threads
    ADD CONSTRAINT forum_threads_category_id_foreign FOREIGN KEY (category_id) REFERENCES public.forum_categories(id) ON DELETE CASCADE;


--
-- Name: forum_threads forum_threads_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forum_threads
    ADD CONSTRAINT forum_threads_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: forward_contracts forward_contracts_crop_recommendation_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forward_contracts
    ADD CONSTRAINT forward_contracts_crop_recommendation_id_foreign FOREIGN KEY (crop_recommendation_id) REFERENCES public.crop_recommendations(id) ON DELETE CASCADE;


--
-- Name: forward_contracts forward_contracts_farmer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.forward_contracts
    ADD CONSTRAINT forward_contracts_farmer_id_foreign FOREIGN KEY (farmer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: harvest_listings harvest_listings_farmer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.harvest_listings
    ADD CONSTRAINT harvest_listings_farmer_id_foreign FOREIGN KEY (farmer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: plots plots_farm_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.plots
    ADD CONSTRAINT plots_farm_id_foreign FOREIGN KEY (farm_id) REFERENCES public.farms(id) ON DELETE CASCADE;


--
-- Name: purchases purchases_buyer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_buyer_id_foreign FOREIGN KEY (buyer_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: purchases purchases_crop_demand_offer_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_crop_demand_offer_id_foreign FOREIGN KEY (crop_demand_offer_id) REFERENCES public.crop_demand_offers(id) ON DELETE CASCADE;


--
-- Name: purchases purchases_forward_contract_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_forward_contract_id_foreign FOREIGN KEY (forward_contract_id) REFERENCES public.forward_contracts(id) ON DELETE CASCADE;


--
-- Name: purchases purchases_harvest_listing_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.purchases
    ADD CONSTRAINT purchases_harvest_listing_id_foreign FOREIGN KEY (harvest_listing_id) REFERENCES public.harvest_listings(id) ON DELETE CASCADE;


--
-- Name: reply_votes reply_votes_reply_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reply_votes
    ADD CONSTRAINT reply_votes_reply_id_foreign FOREIGN KEY (reply_id) REFERENCES public.forum_replies(id) ON DELETE CASCADE;


--
-- Name: reply_votes reply_votes_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.reply_votes
    ADD CONSTRAINT reply_votes_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: thread_votes thread_votes_thread_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.thread_votes
    ADD CONSTRAINT thread_votes_thread_id_foreign FOREIGN KEY (thread_id) REFERENCES public.forum_threads(id) ON DELETE CASCADE;


--
-- Name: thread_votes thread_votes_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.thread_votes
    ADD CONSTRAINT thread_votes_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: user_addresses user_addresses_user_id_foreign; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY public.user_addresses
    ADD CONSTRAINT user_addresses_user_id_foreign FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

