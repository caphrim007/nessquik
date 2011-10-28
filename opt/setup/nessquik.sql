--
-- PostgreSQL database dump
--

SET statement_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = off;
SET check_function_bodies = false;
SET client_min_messages = warning;
SET escape_string_warning = off;

--
-- Name: plpgsql; Type: PROCEDURAL LANGUAGE; Schema: -; Owner: -
--

CREATE PROCEDURAL LANGUAGE plpgsql;


SET search_path = public, pg_catalog;

--
-- Name: create_audit_progress(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION create_audit_progress() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	INSERT INTO audits_progress (audit_id)
	VALUES (NEW.id);
	RETURN NULL;
END
$$;


--
-- Name: remove_permission(); Type: FUNCTION; Schema: public; Owner: -
--

CREATE FUNCTION remove_permission() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	DELETE FROM roles_permissions
	WHERE roles_permissions.permission_id = OLD.id;
	RETURN NULL;
END
$$;


SET default_tablespace = '';

SET default_with_oids = false;

--
-- Name: accounts; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE accounts (
    id integer NOT NULL,
    username character varying(255) NOT NULL,
    password character(32),
    proper_name character varying(255),
    primary_role integer,
    firstboot integer DEFAULT 1
);


--
-- Name: TABLE accounts; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE accounts IS 'Contains accounts that are known by nessquik. Even if an account uses an external means of authentication, this table will still contain the account name so that other functionality in nessquik will work for these accounts';


--
-- Name: accounts_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE accounts_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: accounts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE accounts_id_seq OWNED BY accounts.id;


--
-- Name: accounts_maps; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE accounts_maps (
    account_id integer,
    username character varying(255),
    id integer NOT NULL,
    date_created timestamp with time zone
);


--
-- Name: accounts_maps_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE accounts_maps_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: accounts_maps_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE accounts_maps_id_seq OWNED BY accounts_maps.id;


--
-- Name: accounts_notifications; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE accounts_notifications (
    account_id integer,
    message text,
    id integer NOT NULL,
    created timestamp with time zone DEFAULT now()
);


--
-- Name: TABLE accounts_notifications; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE accounts_notifications IS 'Contains mapping for account IDs to notification IDs. Allows a user to selectively delete notifications that have been sent to them';


--
-- Name: accounts_notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE accounts_notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: accounts_notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE accounts_notifications_id_seq OWNED BY accounts_notifications.id;


--
-- Name: accounts_roles; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE accounts_roles (
    account_id integer NOT NULL,
    role_id integer NOT NULL,
    id integer NOT NULL
);


--
-- Name: TABLE accounts_roles; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE accounts_roles IS 'Contains mappings of accounts to roles that are assigned to them';


--
-- Name: accounts_roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE accounts_roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: accounts_roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE accounts_roles_id_seq OWNED BY accounts_roles.id;


--
-- Name: audits; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE audits (
    name character varying(255),
    status character(16) DEFAULT 'N'::bpchar NOT NULL,
    date_scheduled timestamp with time zone,
    date_finished timestamp with time zone,
    id uuid NOT NULL,
    created timestamp with time zone NOT NULL,
    last_modified timestamp with time zone NOT NULL,
    policy_id uuid,
    scanner_id uuid,
    date_started timestamp with time zone,
    scheduling boolean DEFAULT false
);


--
-- Name: TABLE audits; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE audits IS 'Contains all the audits that are stored in nessquik. These IDs can then be mapped to permissions';


--
-- Name: audits_progress; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE audits_progress (
    audit_id uuid NOT NULL,
    current integer DEFAULT 0 NOT NULL,
    total integer DEFAULT 0 NOT NULL
);


--
-- Name: audits_reports; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE audits_reports (
    name character varying(255),
    audit_id uuid,
    created timestamp with time zone,
    start_time timestamp with time zone,
    stop_time timestamp with time zone,
    id uuid
);


--
-- Name: last_audit; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE last_audit (
    id integer NOT NULL,
    target inet,
    last_audit timestamp with time zone
);


--
-- Name: last_audit_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE last_audit_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: last_audit_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE last_audit_id_seq OWNED BY last_audit.id;


--
-- Name: maintenance_state; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE maintenance_state (
    task character varying(64),
    status integer,
    last_run timestamp with time zone,
    last_finish timestamp with time zone
);


--
-- Name: message; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE message (
    message_id integer NOT NULL,
    queue_id integer,
    handle character(32),
    body character varying(8192) NOT NULL,
    md5 character(32) NOT NULL,
    timeout real,
    created integer DEFAULT 0 NOT NULL
);


--
-- Name: message_message_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE message_message_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: message_message_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE message_message_id_seq OWNED BY message.message_id;


--
-- Name: notifications; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE notifications (
    id integer NOT NULL,
    name text
);


--
-- Name: TABLE notifications; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE notifications IS 'Contains all known notifications that can be raised by nessquik';


--
-- Name: notifications_accounts; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE notifications_accounts (
    notification_id integer,
    account_id integer
);


--
-- Name: TABLE notifications_accounts; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE notifications_accounts IS 'Contains mappings of notification types that specific accounts have subscribed to.';


--
-- Name: notifications_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE notifications_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: notifications_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE notifications_id_seq OWNED BY notifications.id;


--
-- Name: permissions_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE permissions_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: SEQUENCE permissions_id_seq; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON SEQUENCE permissions_id_seq IS 'This sequence is used for all permission tables';


--
-- Name: permissions_address; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE permissions_address (
    resource inet,
    id integer DEFAULT nextval('permissions_id_seq'::regclass) NOT NULL
);


--
-- Name: TABLE permissions_address; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE permissions_address IS 'Contains IP addresses and CIDR blocks so that permissions can be assigned to roles which will allow administrators to restrict access to what can be scanned';


--
-- Name: permissions_api; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE permissions_api (
    resource character varying(255),
    id integer DEFAULT nextval('permissions_id_seq'::regclass) NOT NULL
);


--
-- Name: TABLE permissions_api; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE permissions_api IS 'Contains API methods that are defined in nessquik so that permissions can be assigned to roles which will allow administrators to restrict direct access to API methods';


--
-- Name: permissions_audit; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE permissions_audit (
    id integer DEFAULT nextval('permissions_id_seq'::regclass) NOT NULL,
    resource uuid
);


--
-- Name: permissions_capability; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE permissions_capability (
    id integer DEFAULT nextval('permissions_id_seq'::regclass) NOT NULL,
    resource character varying(255)
);


--
-- Name: permissions_cluster; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE permissions_cluster (
    id integer DEFAULT nextval('permissions_id_seq'::regclass) NOT NULL,
    resource character varying(255) NOT NULL
);


--
-- Name: permissions_hostname; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE permissions_hostname (
    id integer DEFAULT nextval('permissions_id_seq'::regclass) NOT NULL,
    resource character varying(255) NOT NULL
);


--
-- Name: permissions_policy; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE permissions_policy (
    id integer DEFAULT nextval('permissions_id_seq'::regclass) NOT NULL,
    resource uuid
);


--
-- Name: permissions_queue; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE permissions_queue (
    id integer DEFAULT nextval('permissions_id_seq'::regclass) NOT NULL,
    resource integer
);


--
-- Name: permissions_scanner; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE permissions_scanner (
    id integer DEFAULT nextval('permissions_id_seq'::regclass) NOT NULL,
    resource uuid
);


--
-- Name: permissions_vhost; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE permissions_vhost (
    id integer DEFAULT nextval('permissions_id_seq'::regclass) NOT NULL,
    resource character varying(255) NOT NULL
);


--
-- Name: plugin_preferences; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE plugin_preferences (
    id character varying(255),
    preference text
);


--
-- Name: plugins; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE plugins (
    id integer NOT NULL,
    name character varying(255),
    family character varying(255),
    category character varying(255),
    copyright character varying(255),
    summary character varying(255),
    description text,
    version character varying(255),
    cve_id text,
    bugtraq_id character varying(255),
    xref text,
    script character varying(255),
    hash character(32),
    vuln_publication_date timestamp with time zone,
    solution text,
    risk_factor character varying(255),
    plugin_publication_date timestamp with time zone,
    cvss_vector text,
    synopsis text,
    cvss_base_score character varying(255)
);


--
-- Name: TABLE plugins; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE plugins IS 'Contains all the information about vulnerability scanner plugins';


--
-- Name: policies; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE policies (
    name character varying(256) NOT NULL,
    id uuid NOT NULL,
    created timestamp with time zone,
    last_modified timestamp with time zone
);


--
-- Name: TABLE policies; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE policies IS 'Contains information about all the policies';


--
-- Name: queue; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE queue (
    queue_id integer NOT NULL,
    queue_name character varying(100),
    timeout integer DEFAULT 30,
    "desc" text
);


--
-- Name: queue_queue_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE queue_queue_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: queue_queue_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE queue_queue_id_seq OWNED BY queue.queue_id;


--
-- Name: regex; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE regex (
    id integer NOT NULL,
    pattern character varying(255) NOT NULL,
    "desc" text,
    type character varying(32) DEFAULT 'tag'::character varying NOT NULL,
    application character varying(32) DEFAULT 'account'::character varying NOT NULL
);


--
-- Name: regex_automations; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE regex_automations (
    automation_id integer,
    regex_id integer
);


--
-- Name: TABLE regex_automations; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE regex_automations IS 'Contains a mapping of automation IDs to the regex that they are associated with';


--
-- Name: regex_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE regex_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: regex_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE regex_id_seq OWNED BY regex.id;


--
-- Name: roles; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE roles (
    name character varying(64) DEFAULT 'role'::character varying NOT NULL,
    id integer NOT NULL,
    description text,
    immutable integer DEFAULT 0,
    created timestamp with time zone,
    last_modified timestamp with time zone
);


--
-- Name: TABLE roles; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE roles IS 'Contains a list of all known roles';


--
-- Name: roles_id_seq; Type: SEQUENCE; Schema: public; Owner: -
--

CREATE SEQUENCE roles_id_seq
    START WITH 1
    INCREMENT BY 1
    NO MAXVALUE
    NO MINVALUE
    CACHE 1;


--
-- Name: roles_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: -
--

ALTER SEQUENCE roles_id_seq OWNED BY roles.id;


--
-- Name: roles_permissions; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE roles_permissions (
    permission_id integer,
    role_id integer
);


--
-- Name: TABLE roles_permissions; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE roles_permissions IS 'Contains a mapping of permission IDs to the roles that they are associated with';


--
-- Name: scanners; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE scanners (
    id uuid,
    name character varying(255),
    description text,
    adapter character varying(255),
    host character varying(255),
    port integer,
    username character varying(255),
    password character varying(255),
    plugin_dir character varying(255),
    max_audits integer DEFAULT 20,
    for_update boolean DEFAULT false
);


--
-- Name: TABLE scanners; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE scanners IS 'Contains information about all the scanners controlled by nessquik';


--
-- Name: tokens; Type: TABLE; Schema: public; Owner: -; Tablespace: 
--

CREATE TABLE tokens (
    account_id integer,
    proxy_id integer DEFAULT 0,
    token character(32),
    remote_address inet,
    valid_from timestamp with time zone,
    valid_to timestamp with time zone
);


--
-- Name: TABLE tokens; Type: COMMENT; Schema: public; Owner: -
--

COMMENT ON TABLE tokens IS 'Contains tokens that are granted to accounts that access nessquik via the API';


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE accounts ALTER COLUMN id SET DEFAULT nextval('accounts_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE accounts_maps ALTER COLUMN id SET DEFAULT nextval('accounts_maps_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE accounts_notifications ALTER COLUMN id SET DEFAULT nextval('accounts_notifications_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE accounts_roles ALTER COLUMN id SET DEFAULT nextval('accounts_roles_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE last_audit ALTER COLUMN id SET DEFAULT nextval('last_audit_id_seq'::regclass);


--
-- Name: message_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE message ALTER COLUMN message_id SET DEFAULT nextval('message_message_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE notifications ALTER COLUMN id SET DEFAULT nextval('notifications_id_seq'::regclass);


--
-- Name: queue_id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE queue ALTER COLUMN queue_id SET DEFAULT nextval('queue_queue_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE regex ALTER COLUMN id SET DEFAULT nextval('regex_id_seq'::regclass);


--
-- Name: id; Type: DEFAULT; Schema: public; Owner: -
--

ALTER TABLE roles ALTER COLUMN id SET DEFAULT nextval('roles_id_seq'::regclass);


--
-- Name: last_audit_target_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY last_audit
    ADD CONSTRAINT last_audit_target_key UNIQUE (target);


--
-- Name: message_pk; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY message
    ADD CONSTRAINT message_pk PRIMARY KEY (message_id);


--
-- Name: permissions_address_resource_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY permissions_address
    ADD CONSTRAINT permissions_address_resource_key UNIQUE (resource);


--
-- Name: permissions_api_resource_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY permissions_api
    ADD CONSTRAINT permissions_api_resource_key UNIQUE (resource);


--
-- Name: permissions_audit_resource_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY permissions_audit
    ADD CONSTRAINT permissions_audit_resource_key UNIQUE (resource);


--
-- Name: permissions_capability_resource_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY permissions_capability
    ADD CONSTRAINT permissions_capability_resource_key UNIQUE (resource);


--
-- Name: permissions_cluster_resource_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY permissions_cluster
    ADD CONSTRAINT permissions_cluster_resource_key UNIQUE (resource);


--
-- Name: permissions_hostname_resource_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY permissions_hostname
    ADD CONSTRAINT permissions_hostname_resource_key UNIQUE (resource);


--
-- Name: permissions_policy_resource_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY permissions_policy
    ADD CONSTRAINT permissions_policy_resource_key UNIQUE (resource);


--
-- Name: permissions_scanner_resource_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY permissions_scanner
    ADD CONSTRAINT permissions_scanner_resource_key UNIQUE (resource);


--
-- Name: permissions_vhost_resource_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY permissions_vhost
    ADD CONSTRAINT permissions_vhost_resource_key UNIQUE (resource);


--
-- Name: pk_accounts_id; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY accounts
    ADD CONSTRAINT pk_accounts_id PRIMARY KEY (id);


--
-- Name: pk_audit_id; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY audits
    ADD CONSTRAINT pk_audit_id PRIMARY KEY (id);


--
-- Name: pk_id; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY notifications
    ADD CONSTRAINT pk_id PRIMARY KEY (id);


--
-- Name: pk_plugins_id; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY plugins
    ADD CONSTRAINT pk_plugins_id PRIMARY KEY (id);


--
-- Name: pk_policy_id; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY policies
    ADD CONSTRAINT pk_policy_id PRIMARY KEY (id);


--
-- Name: pk_role_id; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY roles
    ADD CONSTRAINT pk_role_id PRIMARY KEY (id);


--
-- Name: plugin_preferences_id_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY plugin_preferences
    ADD CONSTRAINT plugin_preferences_id_key UNIQUE (id);


--
-- Name: queue_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY queue
    ADD CONSTRAINT queue_pkey PRIMARY KEY (queue_id);


--
-- Name: regex_pkey; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY regex
    ADD CONSTRAINT regex_pkey PRIMARY KEY (id);


--
-- Name: scanners_id_key; Type: CONSTRAINT; Schema: public; Owner: -; Tablespace: 
--

ALTER TABLE ONLY scanners
    ADD CONSTRAINT scanners_id_key UNIQUE (id);


--
-- Name: fki_accounts_maps_account_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_accounts_maps_account_id ON accounts_maps USING btree (account_id);


--
-- Name: fki_accounts_notifications_account_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_accounts_notifications_account_id ON accounts_notifications USING btree (account_id);


--
-- Name: fki_accounts_roles_account_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_accounts_roles_account_id ON accounts_roles USING btree (account_id);


--
-- Name: fki_accounts_roles_role_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_accounts_roles_role_id ON accounts_roles USING btree (role_id);


--
-- Name: fki_audits_progress_audit_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_audits_progress_audit_id ON audits_progress USING btree (audit_id);


--
-- Name: fki_notifications_account_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_notifications_account_id ON notifications_accounts USING btree (account_id);


--
-- Name: fki_notifications_accounts_notification_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_notifications_accounts_notification_id ON notifications_accounts USING btree (notification_id);


--
-- Name: fki_permissions_audit_resource; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_permissions_audit_resource ON permissions_audit USING btree (resource);


--
-- Name: fki_permissions_policy_resource; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_permissions_policy_resource ON permissions_policy USING btree (resource);


--
-- Name: fki_regex_automation_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_regex_automation_id ON regex_automations USING btree (regex_id);


--
-- Name: fki_reports_audit_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_reports_audit_id ON audits_reports USING btree (audit_id);


--
-- Name: fki_roles_permission_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_roles_permission_id ON roles_permissions USING btree (role_id);


--
-- Name: fki_tokens_account_id; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX fki_tokens_account_id ON tokens USING btree (account_id);


--
-- Name: idx_plugins_category; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_plugins_category ON plugins USING btree (category);


--
-- Name: idx_plugins_family; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_plugins_family ON plugins USING btree (family);


--
-- Name: idx_plugins_script; Type: INDEX; Schema: public; Owner: -; Tablespace: 
--

CREATE INDEX idx_plugins_script ON plugins USING btree (script);


--
-- Name: trig_create_progress; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trig_create_progress
    AFTER INSERT ON audits
    FOR EACH ROW
    EXECUTE PROCEDURE create_audit_progress();


--
-- Name: trig_remove_permission; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trig_remove_permission
    AFTER DELETE ON permissions_hostname
    FOR EACH ROW
    EXECUTE PROCEDURE remove_permission();


--
-- Name: trig_remove_permission; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trig_remove_permission
    AFTER DELETE ON permissions_capability
    FOR EACH ROW
    EXECUTE PROCEDURE remove_permission();


--
-- Name: trig_remove_permission; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trig_remove_permission
    AFTER DELETE ON permissions_audit
    FOR EACH ROW
    EXECUTE PROCEDURE remove_permission();


--
-- Name: trig_remove_permission; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trig_remove_permission
    AFTER DELETE ON permissions_api
    FOR EACH ROW
    EXECUTE PROCEDURE remove_permission();


--
-- Name: trig_remove_permission; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trig_remove_permission
    AFTER DELETE ON permissions_address
    FOR EACH ROW
    EXECUTE PROCEDURE remove_permission();


--
-- Name: trig_remove_permission; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trig_remove_permission
    AFTER DELETE ON permissions_scanner
    FOR EACH ROW
    EXECUTE PROCEDURE remove_permission();


--
-- Name: trig_remove_permission; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trig_remove_permission
    AFTER DELETE ON permissions_cluster
    FOR EACH ROW
    EXECUTE PROCEDURE remove_permission();


--
-- Name: trig_remove_permission; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trig_remove_permission
    AFTER DELETE ON permissions_vhost
    FOR EACH ROW
    EXECUTE PROCEDURE remove_permission();


--
-- Name: trig_remove_permission; Type: TRIGGER; Schema: public; Owner: -
--

CREATE TRIGGER trig_remove_permission
    AFTER DELETE ON permissions_queue
    FOR EACH ROW
    EXECUTE PROCEDURE remove_permission();


--
-- Name: accounts_maps_account_id; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY accounts_maps
    ADD CONSTRAINT accounts_maps_account_id FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE;


--
-- Name: accounts_notifications_account_id; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY accounts_notifications
    ADD CONSTRAINT accounts_notifications_account_id FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE;


--
-- Name: accounts_roles_account_id; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY accounts_roles
    ADD CONSTRAINT accounts_roles_account_id FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE;


--
-- Name: accounts_roles_role_id; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY accounts_roles
    ADD CONSTRAINT accounts_roles_role_id FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE;


--
-- Name: audits_progress_audit_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY audits_progress
    ADD CONSTRAINT audits_progress_audit_id_fkey FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE;


--
-- Name: message_ibfk_1; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY message
    ADD CONSTRAINT message_ibfk_1 FOREIGN KEY (queue_id) REFERENCES queue(queue_id) ON UPDATE CASCADE ON DELETE CASCADE;


--
-- Name: notifications_account_id; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY notifications_accounts
    ADD CONSTRAINT notifications_account_id FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE;


--
-- Name: notifications_accounts_notification_id; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY notifications_accounts
    ADD CONSTRAINT notifications_accounts_notification_id FOREIGN KEY (notification_id) REFERENCES notifications(id) ON DELETE CASCADE;


--
-- Name: permissions_audit_resource; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY permissions_audit
    ADD CONSTRAINT permissions_audit_resource FOREIGN KEY (resource) REFERENCES audits(id) ON DELETE CASCADE;


--
-- Name: permissions_policy_resource; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY permissions_policy
    ADD CONSTRAINT permissions_policy_resource FOREIGN KEY (resource) REFERENCES policies(id) ON DELETE CASCADE;


--
-- Name: permissions_scanner_resource_fkey; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY permissions_scanner
    ADD CONSTRAINT permissions_scanner_resource_fkey FOREIGN KEY (resource) REFERENCES scanners(id) ON DELETE CASCADE;


--
-- Name: regex_automation_id; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY regex_automations
    ADD CONSTRAINT regex_automation_id FOREIGN KEY (regex_id) REFERENCES regex(id) ON DELETE CASCADE;


--
-- Name: reports_audit_id; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY audits_reports
    ADD CONSTRAINT reports_audit_id FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE;


--
-- Name: roles_permission_id; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY roles_permissions
    ADD CONSTRAINT roles_permission_id FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE;


--
-- Name: tokens_account_id; Type: FK CONSTRAINT; Schema: public; Owner: -
--

ALTER TABLE ONLY tokens
    ADD CONSTRAINT tokens_account_id FOREIGN KEY (account_id) REFERENCES accounts(id) ON DELETE CASCADE;


INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'admin_operator');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'edit_user');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'edit_role');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'edit_auth');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'edit_logging');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'edit_maintenance');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'edit_queue');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'view_charts');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'edit_docdb');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'edit_automation');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'edit_scanner');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'edit_audit');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'edit_policy');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'can_change_password');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'targets_view_ipextract');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'edit_xmpp');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'edit_ws_nq');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'edit_paths');
INSERT INTO permissions_capability ("id", "resource") VALUES (DEFAULT, 'edit_config');

INSERT INTO permissions_address ("id", "resource") VALUES (DEFAULT, '0.0.0.0/0');
INSERT INTO permissions_address ("id", "resource") VALUES (DEFAULT, '::/0');

INSERT INTO queue VALUES (DEFAULT, 'last-audit', DEFAULT, 'Maintains list of last time an IP address was audited');
INSERT INTO queue VALUES (DEFAULT, 'audit-finished', DEFAULT, 'Used for notifying individuals when an audit has finished');
INSERT INTO queue VALUES (DEFAULT, 'email-audit-finished', DEFAULT, 'Used for emailing individuals when an audit has finished');
INSERT INTO queue VALUES (DEFAULT, 'xmpp-audit-finished', DEFAULT, 'Used for instant messaging individuals when an audit has finished');
INSERT INTO queue VALUES (DEFAULT, 'rebuild-upcoming-audits', DEFAULT, 'Used for updating the document details for upcoming audits listed on the dashboard');

INSERT INTO plugin_preferences ("id", "preference") VALUES ('database-settings.login', 'Database settings[entry]:Login :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('database-settings.password', 'Database settings[password]:Password :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('database-settings.db-type', 'Database settings[radio]:DB Type :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('database-settings.database-sid', 'Database settings[entry]:Database SID :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('database-settings.database-port', 'Database settings[entry]:Database port to use :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('database-settings.oracle-auth-type', 'Database settings[radio]:Oracle auth type:');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('database-settings.sql-server-auth-type', 'Database settings[radio]:SQL Server auth type:');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('smb-registry.start-the-service', 'Start the Registry Service during the scan[checkbox]:Start the registry service during the scan');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('kerberos.kdc', 'Kerberos configuration[entry]:Kerberos Key Distribution Center (KDC) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('kerberos.kdc-port', 'Kerberos configuration[entry]:Kerberos KDC Port :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('kerberos.kdc-transport', 'Kerberos configuration[radio]:Kerberos KDC Transport :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('kerberos.realm', 'Kerberos configuration[entry]:Kerberos Realm (SSH only) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('smb-host-sid.start-uid', 'SMB use host SID to enumerate local users[entry]:Start UID :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('smb-host-sid.end-uid', 'SMB use host SID to enumerate local users[entry]:End UID :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('smb-domain-sid.start-uid', 'SMB use domain SID to enumerate users[entry]:Start UID :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('smb-domain-sid.end-uid', 'SMB use domain SID to enumerate users[entry]:End UID :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ping-remote-host.tcp-dest-port', 'Ping the remote host[entry]:TCP ping destination port(s) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ping-remote-host.do-arp-ping', 'Ping the remote host[checkbox]:Do an ARP ping');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ping-remote-host.do-tcp-ping', 'Ping the remote host[checkbox]:Do a TCP ping');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ping-remote-host.do-icmp-ping', 'Ping the remote host[checkbox]:Do an ICMP ping');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ping-remote-host.number-of-icmp-retries', 'Ping the remote host[entry]:Number of retries (ICMP) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ping-remote-host.do-udp-ping', 'Ping the remote host[checkbox]:Do an applicative UDP ping (DNS,RPC...)');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ping-remote-host.dead-hosts-in-report', 'Ping the remote host[checkbox]:Make the dead hosts appear in the report');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ping-remote-host.live-hosts-in-report', 'Ping the remote host[checkbox]:Log live hosts in the report');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ping-remote-host.test-local-nessus-host', 'Ping the remote host[checkbox]:Test the local Nessus host');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ping-remote-host.fast-network-discovery', 'Ping the remote host[checkbox]:Fast network discovery');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('service-detection.test-ssl-services', 'Service detection[radio]:Test SSL based services');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('smb-scope.req-info-about-domain', 'SMB Scope[checkbox]:Request information about the domain');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('port-scanners.check-open-tcp-ports', 'Port scanners settings[checkbox]:Check open TCP ports found by local port enumerators');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('port-scanners.network-scanners-if-local-failed', 'Port scanners settings[checkbox]:Only run network port scanners if local port enumeration failed');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('snmp.community-name', 'SNMP settings[entry]:Community name :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('snmp.udp-port', 'SNMP settings[entry]:UDP port :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('fragile-devices.network-printers', 'Do not scan fragile devices[checkbox]:Scan Network Printers');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('fragile-devices.novell-netware', 'Do not scan fragile devices[checkbox]:Scan Novell Netware hosts');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ssh.username', 'SSH settings[entry]:SSH user name :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ssh.password', 'SSH settings[password]:SSH password (unsafe!) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ssh.public-key', 'SSH settings[file]:SSH public key to use :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ssh.private-key', 'SSH settings[file]:SSH private key to use :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ssh.key-passphrase', 'SSH settings[password]:Passphrase for SSH key :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ssh.elevate-privileges-with', 'SSH settings[radio]:Elevate privileges with :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ssh.su-password', 'SSH settings[password]:su/sudo password :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ssh.known-hosts-file', 'SSH settings[file]:SSH known_hosts file :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('ssh.preferred-port', 'SSH settings[entry]:Preferred SSH port :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('web-app-tests.enable-tests', 'Web Application Tests Settings[checkbox]:Enable web applications tests');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('web-app-tests.max-run-time', 'Web Application Tests Settings[entry]:Maximum run time (min) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('web-app-tests.send-post-requests', 'Web Application Tests Settings[checkbox]:Send POST requests');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('web-app-tests.combo-arg-values', 'Web Application Tests Settings[radio]:Combinations of arguments values');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('web-app-tests.stop-at-first-flaw', 'Web Application Tests Settings[radio]:Stop at first flaw');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('web-app-tests.test-embedded', 'Web Application Tests Settings[checkbox]:Test embedded web servers');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('web-app-tests.parameter-pollution', 'Web Application Tests Settings[checkbox]:HTTP Parameter Pollution');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('tcp-scanner.firewall-detection', 'Nessus TCP scanner[radio]:Firewall detection :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.http-account', 'Login configurations[entry]:HTTP account :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.http-password', 'Login configurations[password]:HTTP password (sent in clear) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.nntp-account', 'Login configurations[entry]:NNTP account :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.nntp-password', 'Login configurations[password]:NNTP password (sent in clear) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.ftp-account', 'Login configurations[entry]:FTP account :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.ftp-password', 'Login configurations[password]:FTP password (sent in clear) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.ftp-writable-directory', 'Login configurations[entry]:FTP writeable directory :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.pop2-account', 'Login configurations[entry]:POP2 account :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.pop2-password', 'Login configurations[password]:POP2 password (sent in clear) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.pop3-account', 'Login configurations[entry]:POP3 account :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.pop3-password', 'Login configurations[password]:POP3 password (sent in clear) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.imap-account', 'Login configurations[entry]:IMAP account :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.imap-password', 'Login configurations[password]:IMAP password (sent in clear) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.smb-account', 'Login configurations[entry]:SMB account :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.smb-password', 'Login configurations[password]:SMB password :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.smb-domain', 'Login configurations[entry]:SMB domain (optional) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.smb-password-type', 'Login configurations[radio]:SMB password type :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.smb-account-1', 'Login configurations[entry]:Additional SMB account (1) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.smb-password-1', 'Login configurations[password]:Additional SMB password (1) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.smb-domain-1', 'Login configurations[entry]:Additional SMB domain (optional) (1) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.smb-account-2', 'Login configurations[entry]:Additional SMB account (2) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.smb-password-2', 'Login configurations[password]:Additional SMB password (2) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.smb-domain-2', 'Login configurations[entry]:Additional SMB domain (optional) (2) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.smb-account-3', 'Login configurations[entry]:Additional SMB account (3) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.smb-password-3', 'Login configurations[password]:Additional SMB password (3) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.smb-domain-3', 'Login configurations[entry]:Additional SMB domain (optional) (3) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.never-send-smb-creds-clear', 'Login configurations[checkbox]:Never send SMB credentials in clear text');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('login-config.only-ntlmv2', 'Login configurations[checkbox]:Only use NTLMv2');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('smtp.third-party-domain', 'SMTP settings[entry]:Third party domain :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('smtp.from', 'SMTP settings[entry]:From address :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('smtp.to', 'SMTP settings[entry]:To address :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('http-login.login-page', 'HTTP login page[entry]:Login page :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('http-login.login-form', 'HTTP login page[entry]:Login form :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('http-login.login-form-fields', 'HTTP login page[entry]:Login form fields :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('web-mirror.num-pages-to-mirror', 'Web mirroring[entry]:Number of pages to mirror :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('web-mirror.start-page', 'Web mirroring[entry]:Start page :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('web-mirror.follow-dynamic', 'Web mirroring[checkbox]:Follow dynamic pages :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('web-mirror.excluded-regex', 'Web mirroring[entry]:Excluded items regex :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('web-mirror.max-depth', 'Web mirroring[entry]:Maximum depth :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('cleartext.username', 'Cleartext protocols settings[entry]:User name :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('cleartext.password', 'Cleartext protocols settings[password]:Password (unsafe!) :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('cleartext.patch-checks-via-telnet', 'Cleartext protocols settings[checkbox]:Try to perform patch level checks over telnet');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('cleartext.patch-checks-via-rsh', 'Cleartext protocols settings[checkbox]:Try to perform patch level checks over rsh');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('cleartext.patch-checks-via-rexec', 'Cleartext protocols settings[checkbox]:Try to perform patch level checks over rexec');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('oracle.oracle-sid', 'Oracle settings[entry]:Oracle SID :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('oracle.test-default-accounts', 'Oracle settings[checkbox]:Test default accounts (slow)');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('nntp-info-disclosure.from-address', 'News Server (NNTP) Information Disclosure[entry]:From address :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('nntp-info-disclosure.group-name-regex', 'News Server (NNTP) Information Disclosure[entry]:Test group name regex :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('nntp-info-disclosure.max-crosspost', 'News Server (NNTP) Information Disclosure[entry]:Max crosspost :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('nntp-info-disclosure.local-distribution', 'News Server (NNTP) Information Disclosure[checkbox]:Local distribution');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('nntp-info-disclosure.no-archive', 'News Server (NNTP) Information Disclosure[checkbox]:No archive');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('syn-scanner.firewall-detection', 'Nessus SYN scanner[radio]:Firewall detection :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('global-var.probe-services-every-port', 'Global variable settings[checkbox]:Probe services on every port');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('global-var.accts-not-in-policy-login', 'Global variable settings[checkbox]:Do not log in with user accounts not specified in the policy');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('global-var.enable-cgi', 'Global variable settings[checkbox]:Enable CGI scanning');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('global-var.network-type', 'Global variable settings[radio]:Network type');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('global-var.enable-experimental', 'Global variable settings[checkbox]:Enable experimental scripts');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('global-var.thorough-tests', 'Global variable settings[checkbox]:Thorough tests (slow)');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('global-var.report-verbosity', 'Global variable settings[radio]:Report verbosity');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('global-var.report-paranoia', 'Global variable settings[radio]:Report paranoia');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('global-var.log-verbosity', 'Global variable settings[radio]:Log verbosity');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('global-var.debug-level', 'Global variable settings[entry]:Debug level');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('global-var.http-user-agent', 'Global variable settings[entry]:HTTP User-Agent');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('global-var.ssl-cert-to-use', 'Global variable settings[file]:SSL certificate to use :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('global-var.ssl-ca-to-trust', 'Global variable settings[file]:SSL CA to trust :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('global-var.ssl-key-to-use', 'Global variable settings[file]:SSL key to use :');
INSERT INTO plugin_preferences ("id", "preference") VALUES ('global-var.ssl-password-for-key', 'Global variable settings[password]:SSL password for SSL key :');

--
-- PostgreSQL database dump complete
--
