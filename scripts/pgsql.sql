-- sequence for objects
DROP SEQUENCE IF EXISTS galette_lend_objects_id_seq;
CREATE SEQUENCE galette_lend_objects_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

-- sequence for lend rents
DROP SEQUENCE IF EXISTS galette_lend_rents_id_seq;
CREATE SEQUENCE galette_lend_rents_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

-- sequence for lend status
DROP SEQUENCE IF EXISTS galette_lend_status_id_seq;
CREATE SEQUENCE galette_lend_status_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

-- sequence for lend parameters
DROP SEQUENCE IF EXISTS galette_lend_parameters_id_seq;
CREATE SEQUENCE galette_lend_parameters_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;

-- sequence for lend cetegories
DROP SEQUENCE IF EXISTS galette_lend_category_id_seq;
CREATE SEQUENCE galette_lend_category_id_seq
    START 1
    INCREMENT 1
    MAXVALUE 2147483647
    MINVALUE 1
    CACHE 1;


-- Schema
-- REMINDER: Create order IS important, dependencies first !!
DROP TABLE IF EXISTS galette_lend_category CASCADE;
CREATE TABLE galette_lend_category (
    category_id integer DEFAULT nextval('galette_lend_category_id_seq'::text) NOT NULL,
    name character varying(100) NOT NULL,
    is_active boolean NOT NULL,
    PRIMARY KEY (category_id)
);


DROP TABLE IF EXISTS galette_lend_status CASCADE;
CREATE TABLE galette_lend_status (
    status_id integer DEFAULT nextval('galette_lend_status_id_seq'::text) NOT NULL,
    status_text character varying(100) NOT NULL,
    is_home_location boolean NOT NULL,
    is_active boolean NOT NULL,
    rent_day_number integer NULL DEFAULT NULL,
    PRIMARY KEY (status_id)
);


DROP TABLE IF EXISTS galette_lend_rents CASCADE;
CREATE TABLE galette_lend_rents (
    rent_id integer DEFAULT nextval('galette_lend_rents_id_seq'::text) NOT NULL,
    object_id integer,
    date_begin timestamp NOT NULL,
    date_forecast timestamp NULL DEFAULT NULL,
    date_end timestamp DEFAULT NULL,
    status_id integer REFERENCES galette_lend_status (status_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    adherent_id integer REFERENCES galette_adherents (id_adh) ON DELETE CASCADE ON UPDATE CASCADE,
    comments character varying(200) NOT NULL,
    PRIMARY KEY (rent_id)
);



DROP TABLE IF EXISTS galette_lend_objects CASCADE;
CREATE TABLE galette_lend_objects (
    object_id integer DEFAULT nextval('galette_lend_objects_id_seq'::text) NOT NULL,
    name character varying(100) NOT NULL,
    description character varying(500) NOT NULL,
    serial_number character varying(30) NOT NULL,
    price real NOT NULL,
    price_per_day boolean NOT NULL DEFAULT FALSE,
    dimension character varying(100) NOT NULL,
    weight real NOT NULL,
    is_active boolean NOT NULL,
    category_id integer REFERENCES galette_lend_category (category_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    rent_price real NULL,
    nb_available integer NULL,
    rent_id integer REFERENCES galette_lend_rents (rent_id) ON DELETE RESTRICT ON UPDATE CASCADE,
    PRIMARY KEY (object_id)
);

ALTER TABLE galette_lend_rents ADD CONSTRAINT galette_lend_rents_object_fkey FOREIGN KEY (object_id) REFERENCES galette_lend_objects(object_id);

DROP TABLE IF EXISTS galette_lend_pictures;
CREATE TABLE galette_lend_pictures (
  object_id integer DEFAULT '0' NOT NULL,
  picture bytea NOT NULL,
  format character varying(10) DEFAULT '' NOT NULL,
  PRIMARY KEY (object_id)
);


DROP TABLE IF EXISTS galette_lend_parameters CASCADE;
CREATE TABLE IF NOT EXISTS galette_lend_parameters (
    parameter_id integer DEFAULT nextval('galette_lend_parameters_id_seq'::text) NOT NULL,
    code character varying(30) NOT NULL,
    is_date boolean NOT NULL,
    value_date timestamp DEFAULT NULL,
    is_text boolean NOT NULL,
    value_text character varying(300) DEFAULT NULL,
    is_numeric boolean NOT NULL,
    nb_digits integer DEFAULT NULL,
    value_numeric real DEFAULT NULL,
    date_creation timestamp NOT NULL,
    date_modification timestamp NOT NULL,
    PRIMARY KEY (parameter_id)
);


DROP TABLE IF EXISTS galette_lend_categories_pictures;
CREATE TABLE galette_lend_categories_pictures (
    category_id integer DEFAULT '0' NOT NULL,
    picture bytea NOT NULL,
    format character varying(10) DEFAULT '' NOT NULL,
    PRIMARY KEY (category_id)
);


-- Statuses example data
INSERT INTO galette_lend_status (status_text, is_home_location, is_active) VALUES('Garage A (exemple)', TRUE, TRUE);
INSERT INTO galette_lend_status (status_text, is_home_location, is_active) VALUES('Maison B (exemple)', TRUE, TRUE);
INSERT INTO galette_lend_status (status_text, is_home_location, is_active) VALUES('Bibliotheque C (exemple)', TRUE, TRUE);
INSERT INTO galette_lend_status (status_text, is_home_location, is_active, rent_day_number) VALUES('Location courte durée (exemple)', FALSE, TRUE, 7);
INSERT INTO galette_lend_status (status_text, is_home_location, is_active, rent_day_number) VALUES('Location longue durée (exemple)', FALSE, TRUE, 30);
INSERT INTO galette_lend_status (status_text, is_home_location, is_active, rent_day_number) VALUES('Reparation (exemple)', FALSE, TRUE, 14);
INSERT INTO galette_lend_status (status_text, is_home_location, is_active) VALUES('Vendu (exemple)', FALSE, TRUE);
INSERT INTO galette_lend_status (status_text, is_home_location, is_active) VALUES('Detruit (exemple)', FALSE, TRUE);

-- Default parameters
INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
VALUES
('VIEW_CATEGORY', FALSE, FALSE, TRUE, 0, 0, NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
VALUES
('VIEW_SERIAL', FALSE, FALSE, TRUE, 0, 0, NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
VALUES
('VIEW_THUMBNAIL', FALSE, FALSE, TRUE, 0, 1, NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
VALUES
('VIEW_FULLSIZE', FALSE, FALSE, TRUE, 0, 1, NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
VALUES
('VIEW_DESCRIPTION', FALSE, FALSE, TRUE, 0, 1, NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
VALUES
('VIEW_PRICE', FALSE, FALSE, TRUE, 0, 0, NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
VALUES
('VIEW_DIMENSION', FALSE, FALSE, TRUE, 0, 0, NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
VALUES
('VIEW_WEIGHT', FALSE, FALSE, TRUE, 0, 0, NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
VALUES
('VIEW_LEND_PRICE', FALSE, FALSE, TRUE, 0, 0, NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
VALUES
('THUMB_MAX_WIDTH', FALSE, FALSE, TRUE, 0, 128, NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
VALUES
('THUMB_MAX_HEIGHT', FALSE, FALSE, TRUE, 0, 128, NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
values
('AUTO_GENERATE_CONTRIBUTION', FALSE, FALSE, TRUE, 0, 1, NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
values
('GENERATED_CONTRIBUTION_TYPE_ID', FALSE, FALSE, TRUE, 0, 5, NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, value_text, date_creation, date_modification)
VALUES
('GENERATED_CONTRIB_INFO_TEXT', FALSE, TRUE, FALSE, 'Location de {NAME} {DESCRIPTION} {SERIAL_NUMBER}', NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
VALUES
('ENABLE_MEMBER_RENT_OBJECT', FALSE, FALSE, TRUE, 0, 1, NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
VALUES
('VIEW_DATE_FORECAST', FALSE, FALSE, TRUE, 0, 1, NOW(), NOW());

INSERT INTO galette_lend_parameters
(code, is_date, is_text, is_numeric, nb_digits, value_numeric, date_creation, date_modification)
VALUES
('VIEW_LIST_PRICE_SUM', FALSE, FALSE, TRUE, 0, 0, NOW(), NOW());
