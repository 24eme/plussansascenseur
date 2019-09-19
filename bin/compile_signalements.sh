#!/bin/bash
. $(dirname $0)/config.inc

if [[ -f /tmp/signalements.csv ]]; then
	rm -f /tmp/signalements.json
fi
if [[ -f /tmp/ascenseurs.csv ]]; then
	rm -f /tmp/ascenseurs.csv
fi
if [[ -f /tmp/signalements_clean.csv ]]; then
	rm -f /tmp/signalements_clean.json
fi
if [[ -f /tmp/ascenseurs_clean.csv ]]; then
	rm -f /tmp/ascenseurs_clean.csv
fi
if [[ -f "$PSA_TARGET_FILE" ]]; then
	rm -f "$PSA_TARGET_FILE"
fi

mongoexport --db "$PSA_DB" --collection "$PSA_COL_SIGNALEMENT" --type csv --out /tmp/signalements.csv --fields "$PSA_FIELDS_ASCENSEUR"

mongoexport --db "$PSA_DB" --collection "$PSA_COL_ASCENSEUR" --type csv --out /tmp/ascenseurs.csv --fields "$PSA_FIELDS_SIGNALEMENT"

cat /tmp/signalement.csv | sed 's/"{""$ref"":""'$PSA_COL_ASCENSEUR'"",""$id"":{""$oid"":""//g' | sed 's/""},""$db"":""'$PSA_DB'""}"//g' | tr "\n" "|" | sed -r "s/\|([a-z0-9]+),/\n\1,/g" | sed 's/|/ /g' | tr -d "\r" | sort > /tmp/signalements_clean.csv

cat /tmp/ascenseur.csv | sed 's/ObjectId(//g' | sed 's/),/,/g' | sort > /tmp/ascenseurs_clean.csv

echo "$PSA_ENTETES_FILE" > "$PSA_TARGET_FILE"

join -t "," -1 1 -2 1 /tmp/signalements_clean.csv /tmp/ascenseurs_clean.csv >> "$PSA_TARGET_FILE"