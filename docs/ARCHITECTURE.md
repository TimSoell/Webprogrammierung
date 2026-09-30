# Architektur

Diese Datei erklärt den Aufbau des Projekts in einem Durchgang. Wer sie gelesen
hat, weiß, wo jede neue Zeile Code hingehört.

---

## Die Grundidee

Das Projekt ist in **sechs Schichten** aufgeteilt. Jede Schicht hat genau eine
Aufgabe und spricht **nur mit der Schicht direkt darunter**.

```
   ┌─ 1 ─────────────────────────────────────────────────────────┐
   │  Seite            index.php · kurse.php · programme/*.php    │
   │                   HTML und Struktur. Sonst nichts.           │
   └──────────────────────────┬──────────────────────────────────-┘
                              │ ruft auf
   ┌─ 2 ──────────────────────▼──────────────────────────────────┐
   │  Seitenskript     assets/js/pages/*.page.js                  │
   │                   Klicks, Formulare, Anzeige.                │
   └──────────────────────────┬──────────────────────────────────-┘
                              │ ruft auf
   ┌─ 3 ──────────────────────▼──────────────────────────────────┐
   │  Service          assets/js/services/*.js                    │
   │                   Der einzige Ort mit fetch().               │
   │                   ◄── HIER IST DIE NAHT ZUR DATENBANK ──►    │
   └──────────────────────────┬──────────────────────────────────-┘
                              │ HTTP, überträgt JSON
   ┌─ 4 ──────────────────────▼──────────────────────────────────┐
   │  API-Endpunkt     api/*.php                                  │
   │                   Prüft Eingaben, antwortet mit JSON.        │
   └──────────────────────────┬──────────────────────────────────-┘
                              │ ruft auf
   ┌─ 5 ──────────────────────▼──────────────────────────────────┐
   │  Repository       src/Repositories/*.php                     │
   │                   Der einzige Ort mit SQL.                   │
   └──────────────────────────┬──────────────────────────────────-┘
                              │ PDO
   ┌─ 6 ──────────────────────▼──────────────────────────────────┐
   │  Datenbank        database/schema.sql · PostgreSQL (Supabase)│
   └─────────────────────────────────────────────────────────────┘
```

**Nie eine Schicht überspringen.** Eine Seite ruft kein `fetch()` auf. Ein
Endpunkt schreibt kein SQL. Wer das einhält, kann jede Schicht einzeln
verstehen und ändern.

---

## Warum Schicht 3 der Kern ist

Schicht 3 ist der Grund, warum das Projekt heute ohne Datenbank läuft und
morgen mit einer — **ohne dass Seiten angefasst werden müssen**.

Ein Service hat einen festen Vertrag nach oben: `alleLaden()` gibt ein Array
von Kursen zurück. Was darunter passiert, ist seine Sache.

**Heute**, ohne Datenbank:

```js
export async function alleLaden() {
  return [{ id: 1, name: 'Kraftzirkel', tag: 'Montag' }];
}
```

**Später**, mit der Datenbank:

```js
import { getJson } from './api.js';

export async function alleLaden() {
  return getJson('api/kurse.php');
}
```

Das Seitenskript ruft in beiden Fällen `alleLaden()` auf und merkt keinen
Unterschied. Genau dafür gibt es diese Schicht.

---

## Wo liegt was

| Ordner | Inhalt | Schicht |
|---|---|---|
| `/` | Seiten der obersten Ebene (`index.php`) | 1 |
| `programme/` | Programm-Detailseiten | 1 |
| `partials/` | Bausteine für jede Seite: Kopfzeile, Fußzeile | 1 |
| `assets/css/` | Aussehen. Tokens, Basis, Layout, Komponenten | – |
| `assets/js/pages/` | Verhalten einer bestimmten Seite | 2 |
| `assets/js/components/` | Verhalten, das mehrere Seiten brauchen | 2 |
| `assets/js/services/` | Datenbeschaffung | 3 |
| `assets/js/lib/` | Allgemeine Helfer ohne Feature-Bezug | – |
| `api/` | Endpunkte, geben JSON zurück | 4 |
| `api/index.php` | Einziger Einstieg auf Vercel, bindet Seiten und Endpunkte ein. Kein Endpunkt | – |
| `src/` | PHP-Klassen, nicht direkt aufrufbar (`Database`, `Auth`, `Api`, `SitzungsSpeicher`) | 4–5 |
| `src/Repositories/` | Datenzugriff, der einzige Ort mit SQL | 5 |
| `database/` | Bauplan und Testdaten | 6 |
| `config/` | Zugangsdaten (nicht im Repository), auf Vercel aus Umgebungsvariablen | – |
| `vercel.json`, `.github/` | Veröffentlichung auf Vercel, siehe [ADR-0016](decisions/ADR-0016-hosting-auf-vercel.md) | – |
| `vendor/` | Fremde Bibliotheken, aktuell nur der Login. Nie von Hand ändern | – |
| `docs/` | Diese Dokumentation | – |

---

## Zwei Dateien, die alles zusammenhalten

**`src/bootstrap.php`** wird als erste Zeile jeder Seite eingebunden. Sie lädt
die Konfiguration und definiert drei Dinge, die überall gebraucht werden:

| Name | Bedeutung | Verwendung |
|---|---|---|
| `ROOT_PATH` | Pfad im **Dateisystem** | `require ROOT_PATH . '/partials/head.php'` |
| `BASE_URL` | Pfad im **Browser** | `href="<?= e(BASE_URL) ?>index.php"` |
| `e()` | Text sicher ausgeben | `<?= e($kurs['name']) ?>` |

Die beiden Pfade zu verwechseln ist der häufigste Fehler. Merkhilfe:
`require` bekommt immer `ROOT_PATH`, `href` und `src` bekommen immer `BASE_URL`.

`BASE_URL` wird **berechnet**, nicht eingetragen — aus `DOCUMENT_ROOT` und dem
Pfad des Projektordners. Dadurch funktioniert das Projekt bei allen im Team,
egal wo der Ordner liegt, und in beiden Startvarianten:

| Start | `DOCUMENT_ROOT` | ergibt `BASE_URL` |
|---|---|---|
| `./start.sh` (PHP-Server) | der Projektordner selbst | `/` |
| Apache aus `htdocs` | `…/xampp/htdocs` | `/<projektordner>/` |
| Vercel | wird nicht gerechnet | immer `/` |

Außerdem lädt `bootstrap.php` die Konfiguration (lokal `config/config.php`,
auf Vercel aus Umgebungsvariablen), stellt die Zeitzone auf Berlin und legt
die Sitzung in der Datenbank ab statt als Datei, siehe
[ADR-0017](decisions/ADR-0017-postgresql-auf-supabase.md).

Genau daran scheitert übrigens ein **Symlink** aus `htdocs` in den
Projektordner: PHP löst Symlinks in `__DIR__` auf, `ROOT_PATH` und
`DOCUMENT_ROOT` passen dann nicht mehr zusammen, und die Rechnung ergibt
Unsinn. Siehe [`ADR-0004`](decisions/ADR-0004-php-entwicklungsserver.md).

**`assets/css/main.css`** ist die einzige CSS-Datei, die eine Seite einbindet.
Sie enthält selbst keine Regeln, sondern lädt alle anderen in fester Reihenfolge.
Eine neue Komponente muss dort eingetragen werden, sonst wird sie nicht geladen.

---

## Das Beispiel-Feature

Sechs Dateien, eine pro Schicht, die **nur aus Kommentaren bestehen**. Sie
beschreiben, was in die jeweilige Schicht gehört und was nicht. Als Vorlage
gedacht — beim ersten echten Feature kopieren und umbenennen.

1. `beispiel-feature.php`
2. `assets/js/pages/beispiel-feature.page.js`
3. `assets/js/services/beispiel-feature.js`
4. `api/beispiel-feature.php`
5. `src/Repositories/BeispielFeatureRepository.php`
6. `database/schema.sql`

---

## Ein neues Feature bauen

Am Beispiel „Kursplan". Von unten nach oben ist meistens am einfachsten:

1. **Tabelle** in `database/schema.sql` ergänzen und einspielen.
2. **Repository** `src/Repositories/KursRepository.php` — hier steht das SQL.
3. **Endpunkt** `api/kurse.php` — prüft Eingaben, gibt JSON zurück.
4. **Service** `assets/js/services/kurse.js` — `alleLaden()` und Co.
5. **Seitenskript** `assets/js/pages/kurse.page.js` — Anzeige und Klicks.
6. **Seite** `kurse.php` — das HTML-Gerüst.
7. **Komponenten-CSS** `assets/css/components/kursliste.css` — und in
   `main.css` eintragen nicht vergessen.
8. **Menüpunkt** in `partials/header.php` im Array `$navItems` ergänzen.
9. **Dokumentation** `docs/features/kursplan.md` aus der Vorlage anlegen.

Schneller Zwischenstand ist möglich: Schritt 4 zuerst mit erfundenen Daten
schreiben und 6 + 5 bauen. Dann sieht man sofort etwas, und 1–3 kommen später
nach — ohne dass sich an 5 und 6 etwas ändert.

---

## Sicherheit — die drei Regeln

1. **Jede Ausgabe escapen.** `<?= e($wert) ?>`, nie `<?= $wert ?>`.
   Sonst kann jemand über ein Formular JavaScript einschleusen.
2. **Nie Werte in SQL-Strings schreiben.** Immer `prepare()` mit `?`.
   Sonst kann jemand über ein Formular die Datenbank leeren.
3. **Immer serverseitig prüfen.** Die Prüfung im Browser ist Komfort,
   kein Schutz — ein Endpunkt lässt sich auch ohne Browser aufrufen.

---

## Entscheidungen

Warum das Projekt so aussieht und nicht anders, steht in
[`decisions/`](decisions/). Jede Entscheidung ist eine nummerierte Datei mit
Kontext, Entscheidung, Konsequenzen und den verworfenen Alternativen.
