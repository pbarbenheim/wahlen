# wahlen

verschiedene Wahlverfahren:

- Postenwahl:
  - 1 Vorschlag: Ja/Nein/Enthaltung
  - n Vorschläge: Entscheidung zwischen n Vorschlägen 
- Gremienwahl (mit n Mitgliedern):
  - n Kreuze auf einer Liste an Vorschlägen







table user {
id integer [pk]
username varchar [unique]
passhash varchar
}

table wahl {
id integer [pk]
name varchar [not null]
user_id integer [ref: > user.id, not null]
}

table wahlgang {
id integer [pk]
wahl_id integer [ref: > wahl.id, not null]
anzahl_posten integer [not null, default: 1]
titel varchar [not null]
serial integer [not null, default: 1]
start timestamp
end timestamp

indexes {
wahl_id
(wahl_id, serial) [unique]
}
}

table vorschlag {
id integer [pk]
name varchar [not null]
}

table wahlgang_vorschlag {
wahlgang_id integer [ref: > wahlgang.id]
vorschlag_id integer [ref: > vorschlag.id]
result integer [not null, default: 0]

indexes {
(vorschlag_id, wahlgang_id) [pk]
}
}

table waehler {
id integer [pk]
name varchar
code varchar [not null]
secret_hash varchar [not null]
wahl_id integer [ref: > wahl.id, not null]
}

table stimmzettel {
wahlgang_id integer [ref: > wahlgang.id, not null]
waehler_id integer [ref: > waehler.id, not null]
inhalt varchar [not null]

indexes {
(wahlgang_id, waehler_id) [pk]
}
}
