CREATE TABLE fotoalbums (directory varchar(255) NOT NULL, owner varchar(4) NOT NULL, PRIMARY KEY (directory)) ENGINE=InnoDB DEFAULT CHARSET=utf8 auto_increment=1;
CREATE TABLE fotos (directory varchar(255) NOT NULL, filename varchar(255) NOT NULL, rotation int(11) NULL DEFAULT NULL, owner varchar(4) NOT NULL, PRIMARY KEY (directory, filename)) ENGINE=InnoDB DEFAULT CHARSET=utf8 auto_increment=1;
