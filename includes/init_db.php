<?php

$init = function($DBCONN) {
    // Create tables if they don't exist
    $sql = <<<SQL
    create table if not exists user (
        id integer primary key auto_increment,
        username varchar(255) not null unique,
        passhash varchar(255) not null
    );

    create table if not exists wahl (
        id integer primary key auto_increment,
        name varchar(255) not null,
        user_id integer not null references user(id),
        index (user_id)
    );

    create table if not exists wahlgang (
        id integer primary key auto_increment,
        wahl_id integer not null references wahl(id),
        titel varchar(255) not null,
        anzahl_posten integer not null default 1,
        serial integer not null default 1,
        start integer not null,
        end integer not null,
        index(wahl_id),
        unique index (wahl_id, serial)
    );

    create table if not exists vorschlag (
        id integer primary key auto_increment,
        name varchar(255) not null
    );

    create table if not exists wahlgang_vorschlag (
        wahlgang_id integer not null references wahlgang(id),
        vorschlag_id integer not null references vorschlag(id),
        result integer not null default 0,
        primary key (wahlgang_id, vorschlag_id)
    );

    create table if not exists waehler (
        id integer primary key auto_increment,
        code varchar(255) not null unique,
        wahl_id integer not null references wahl(id),
        index (wahl_id)
    );

    create table if not exists stimmzettel (
        wahlgang_id integer not null references wahlgang(id),
        waehler_id integer not null references waehler(id),
        inhalt text not null,
        primary key (wahlgang_id, waehler_id)
    );
    SQL;

    $result = $DBCONN->multi_query($sql);
    if (!$result) {
        die("Error creating tables: " . $DBCONN->error);
    }
    do {
        // Store the result set
        if ($result = $DBCONN->store_result()) {
            $result->free();
        }
        // Check if there are more results
    } while ($DBCONN->more_results() && $DBCONN->next_result());
    // Check for errors
    if ($DBCONN->errno) {
        die("Error creating tables: " . $DBCONN->error);
    }

    return true;
};
return $init;

?>