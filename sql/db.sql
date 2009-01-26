--
-- PostgreSQL database dump
--

-- Started on 2009-01-21 20:51:38 CET

SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

--
-- TOC entry 1734 (class 1262 OID 67613)
-- Name: mc; Type: DATABASE; Schema: -; Owner: mc
--

CREATE DATABASE mc WITH TEMPLATE = template0 ENCODING = 'UTF8';


ALTER DATABASE mc OWNER TO mc;

\connect mc

SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

SET search_path = public, pg_catalog;

SET default_tablespace = '';

SET default_with_oids = true;

--
-- TOC entry 1463 (class 1259 OID 67638)
-- Dependencies: 3
-- Name: jobs; Type: TABLE; Schema: public; Owner: mc; Tablespace: 
--

CREATE TABLE jobs (
    id text NOT NULL,
    downloaded timestamp without time zone,
    uploaded timestamp without time zone,
    converted timestamp without time zone,
    conversion_started timestamp without time zone,
    upload_started timestamp without time zone,
    deleted timestamp without time zone,
    deletion_reason text,
    filename text,
    format text NOT NULL,
    quality text NOT NULL,
    status_url text
);


ALTER TABLE public.jobs OWNER TO mc;

--
-- TOC entry 1731 (class 2606 OID 67676)
-- Dependencies: 1463 1463
-- Name: jobs_pkey; Type: CONSTRAINT; Schema: public; Owner: mc; Tablespace: 
--

ALTER TABLE ONLY jobs
    ADD CONSTRAINT jobs_pkey PRIMARY KEY (id);


--
-- TOC entry 1736 (class 0 OID 0)
-- Dependencies: 3
-- Name: public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE ALL ON SCHEMA public FROM PUBLIC;
REVOKE ALL ON SCHEMA public FROM postgres;
GRANT ALL ON SCHEMA public TO postgres;
GRANT ALL ON SCHEMA public TO PUBLIC;


-- Completed on 2009-01-21 20:51:39 CET

--
-- PostgreSQL database dump complete
--

