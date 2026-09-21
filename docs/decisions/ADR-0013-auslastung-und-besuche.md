# ADR-0013 — Auslastung als Summe aus fester Kurve und gezählten Besuchen

**Status:** angenommen
**Datum:** 2026-09-21

## Kontext

Die Website soll zeigen, wie voll das Studio gerade ist und wie voll es im
Tagesverlauf wird. Dafür fehlen uns zwei Dinge.

**Erstens gibt es keine echten Messwerte.** Ein Fitnessstudio zählt seine
Besucher über Drehkreuze oder Zutrittskarten. Ein fiktives Studio hat davon
nichts. Die Zahlen müssen also erfunden werden — die Frage ist nur, wo sie
herkommen.

**Zweitens soll bei der Vorführung sichtbar sein, dass sich die Anzeige
bewegt.** Eine Kurve, die immer gleich aussieht, beweist nichts. Wenn jemand
im Mitgliedsbereich sagt "ich komme um 18 Uhr", muss man das im Diagramm
sehen können.

Dazu kommt eine Modellfrage: Ein Mitglied kann sich *jetzt* einchecken oder
einen Besuch für *später* ankündigen. Das sind zwei Bedienvorgänge — aber
sind es auch zwei Datenstrukturen?

## Entscheidung

**Die angezeigte Auslastung ist immer eine Summe aus zwei Quellen.**

```
Anzeige = Basiswert aus auslastung_basis  +  Anzahl Zeilen aus besuche
```

Der Basiswert ist erfunden und steht in der Tabelle `auslastung_basis`, eine
Zeile je Wochentag und Stunde. Er ändert sich im Betrieb nie. Befüllt wird
er aus `database/seed.sql`.

Der Aufschlag kommt aus echten Zeilen in `besuche` und ändert sich bei jedem
Check-in.

Daraus folgen drei Regeln:

- **Erfundene Werte gehören in die Datenbank, nicht in den Quelltext.** Wer
  die Kurve ändern will, ändert `seed.sql`. Kein PHP anfassen.
- **Die beiden Anteile bleiben im Diagramm getrennt sichtbar.** Der
  Basiswert gedeckt, der Aufschlag in der Markenfarbe obendrauf. Sonst wäre
  nicht erkennbar, welcher Teil echt ist.
- **Einchecken und Ankündigen sind dieselbe Zeile.** Eine Tabelle `besuche`
  mit `beginn` und `ende`. "Jetzt" heißt `beginn = jetzt`, "um 18:00" heißt
  `beginn = heute 18:00`. Die Spalte `art` merkt sich nur, welcher der
  beiden Knöpfe gedrückt wurde — für die Anzeige im Mitgliedsbereich, nicht
  für die Zählung.

**Es gibt kein Auschecken.** Ein Besuch wird 90 Minuten lang gezählt und
verschwindet dann von selbst aus der Zählung.

## Konsequenzen

**Positiv**

- Die Zählung ist **eine** Abfrage: zähle alle Zeilen, deren Zeitraum den
  gefragten Moment enthält. Kein Unterschied zwischen den beiden Arten.
- Niemand muss ans Auschecken denken. Eine vergessene Zeile verfälscht die
  Anzeige nicht dauerhaft, sie läuft aus.
- Die Vorführung funktioniert ohne Vorbereitung: einchecken, Startseite,
  fertig. Und `DELETE FROM besuche;` setzt alles zurück, ohne die Kurve zu
  beschädigen.
- Die Vorhersage kommt aus der Datenbank. Für eine Studienarbeit im Fach
  Webprogrammierung ist das der Punkt, an dem man die Schichtentrennung
  zeigen kann.

**Negativ**

- **Die Zahlen sind erfunden und sehen trotzdem präzise aus.** "42 von 170"
  wirkt gemessen. Wer das Feature vorführt, sollte dazusagen, dass die
  Grundkurve Fiktion ist.
- **90 Minuten sind geraten.** Wer zwei Stunden bleibt, fällt vorher aus der
  Zählung; wer nach 40 Minuten geht, bleibt zu lange drin. Eine Dauer für
  alle ist eine grobe Näherung.
- **Die Kurve kennt keine Feiertage und keine Ferien.** Ein Montag im August
  sieht aus wie ein Montag im November.
- **168 Zeilen Seed-Daten** wollen gepflegt sein. Ändert sich das Konzept
  der Kurve, ist das mehr Arbeit als eine Formel im Code gewesen wäre.
- Ein Mitglied kann sich für viele verschiedene Zeiten eintragen und die
  Anzeige damit beeinflussen. Gesperrt sind nur Überschneidungen. Für ein
  echtes Studio wäre das zu wenig, für die Vorführung genügt es.

## Verworfene Alternativen

**Die Kurve im PHP berechnen.** Eine Sinusfunktion mit zwei Spitzen hätte
dieselbe Optik erzeugt, ohne 168 Zeilen Seed. Verworfen, weil die Vorhersage
dann aus dem Quelltext käme — und weil das Fach Datenbankzugriff behandelt,
nicht Trigonometrie.

**Zwei Tabellen für Check-in und geplanten Besuch.** Hätte die Zählung auf
zwei Abfragen und eine Addition verteilt, ohne dass die Trennung irgendwo
gebraucht worden wäre. Der Unterschied liegt allein im Startzeitpunkt.

**Auschecken mit einem zweiten Knopf.** Realistischer, aber in der
Vorführung ein Stolperstein: Wer vergisst auszuchecken, treibt die Anzeige
dauerhaft hoch, und beim nächsten Vorführen stimmt nichts mehr.
