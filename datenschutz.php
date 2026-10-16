<?php
/**
 * @file        datenschutz.php
 * @layer       1 – Seite
 * @description Datenschutzerklärung des SCHWITZKASTEN Athletic Club. Beschreibt
 *              jede Verarbeitung, die die Website tatsächlich vornimmt - wer
 *              einen neuen Dienst einbindet oder neue Daten speichert, ergänzt
 *              hier einen Abschnitt.
 * @see         partials/footer.php
 * @see         impressum.php
 * @see         assets/js/components/cookie-banner.js
 */

declare(strict_types=1);

require __DIR__ . '/src/bootstrap.php';

$pageTitle       = 'Datenschutz — SCHWITZKASTEN Athletic Club';
$pageDescription = 'Datenschutzerklärung des SCHWITZKASTEN Athletic Club, eines Studienprojekts der DHBW Stuttgart.';

require ROOT_PATH . '/partials/head.php';
require ROOT_PATH . '/partials/header.php';
?>

  <main class="legal-page">
    <div class="wrap legal-content">
      <p class="eyebrow">Rechtliches</p>
      <h1 class="display legal-title">Datenschutz</h1>

      <p class="legal-intro">Diese Website ist ein Studienprojekt der DHBW Stuttgart und kein echtes Fitnessstudio. Trotzdem werden beim Besuch und bei der Nutzung Daten verarbeitet. Was genau passiert, steht hier. Bitte gib beim Ausprobieren keine echten persönlichen Daten ein und lade keine echten Ausweise hoch.</p>

      <section class="legal-section">
        <h2>1. Verantwortliche Stelle</h2>
        <p>SCHWITZKASTEN Athletic Club<br>
        Studienprojekt der DHBW Stuttgart, Kurs WWI2025C<br>
        Venloer Straße 213, 50823 Köln<br>
        E-Mail: kontakt@schwitzkasten.example</p>
        <p>Einen Datenschutzbeauftragten gibt es für dieses Projekt nicht. Fragen zum Datenschutz richtest du an die oben genannte E-Mail-Adresse.</p>
      </section>

      <section class="legal-section">
        <h2>2. Hosting und Server-Protokolle</h2>
        <p>Die Website läuft bei Vercel Inc. (USA). Bei jedem Aufruf verarbeitet Vercel technische Daten: deine IP-Adresse, Datum und Uhrzeit, die aufgerufene Adresse, die Art deines Browsers und Fehlermeldungen. Ohne diese Daten kann die Seite nicht ausgeliefert werden. Vercel bewahrt die Protokolle nur kurzzeitig auf.</p>
        <p>Rechtsgrundlage ist unser berechtigtes Interesse an einem sicheren und funktionierenden Betrieb nach Art. 6 Abs. 1 lit. f DSGVO.</p>
      </section>

      <section class="legal-section">
        <h2>3. Datenbank</h2>
        <p>Alle Daten zu Konten, Mitgliedschaften und Buchungen liegen in einer Datenbank bei Supabase Inc. (USA). Supabase verarbeitet sie nur in unserem Auftrag und nicht für eigene Zwecke (Art. 28 DSGVO).</p>
      </section>

      <section class="legal-section">
        <h2>4. Übermittlung in Drittländer</h2>
        <p>Mehrere der hier genannten Anbieter sitzen in den USA oder gehören zu Unternehmen dort. Dadurch können Daten in die USA gelangen. Die Übermittlung stützt sich auf das EU-US Data Privacy Framework (Art. 45 DSGVO) oder, wo ein Anbieter daran nicht teilnimmt, auf Standardvertragsklauseln der EU-Kommission (Art. 46 Abs. 2 lit. c DSGVO).</p>
      </section>

      <section class="legal-section">
        <h2>5. Cookies und Speicher im Browser</h2>
        <p><strong>Sitzungs-Cookie.</strong> Damit du nach der Anmeldung angemeldet bleibst, setzt die Seite das Cookie <code>PHPSESSID</code>. Es enthält nur eine zufällige Kennung und wird gelöscht, wenn du den Browser schließt. Die zugehörige Sitzung auf dem Server verfällt nach kurzer Inaktivität. Das Cookie ist technisch notwendig und braucht keine Einwilligung (§ 25 Abs. 2 Nr. 2 TDDDG).</p>
        <p><strong>Speicher im Browser.</strong> Im localStorage merkt sich die Seite deine Entscheidung zu Google Analytics und die zuletzt gewählte Stufe und Form eines Trainingsprogramms. Im sessionStorage merkt sie sich, dass der Ladebildschirm schon gezeigt wurde. Diese Angaben verlassen deinen Browser nicht. Du kannst sie jederzeit über die Einstellungen deines Browsers löschen.</p>
      </section>

      <section class="legal-section">
        <h2>6. Inhalte von anderen Anbietern</h2>
        <p>Einige Bestandteile der Seite lädt dein Browser direkt von anderen Servern. Dabei erfährt der jeweilige Anbieter deine IP-Adresse und welche Seite du aufrufst.</p>
        <p><strong>Google Fonts.</strong> Die Schriften kommen von Google Ireland Limited, Gordon House, Barrow Street, Dublin 4, Irland.</p>
        <p><strong>Unsplash.</strong> Einige Hintergrundbilder kommen von images.unsplash.com (Unsplash Inc.).</p>
        <p><strong>Karte.</strong> Auf der Seite „Locations" zeigt eine Karte den Standort. Die Kartensoftware Leaflet wird von unpkg.com geladen, die Kartenbilder von der OpenStreetMap Foundation (Vereinigtes Königreich).</p>
        <p>Rechtsgrundlage ist unser berechtigtes Interesse an einer einheitlichen und ansprechenden Darstellung nach Art. 6 Abs. 1 lit. f DSGVO.</p>
      </section>

      <section class="legal-section">
        <h2>7. Google Analytics</h2>
        <p>Mit deiner Einwilligung nutzen wir Google Analytics 4, einen Webanalysedienst der Google Ireland Limited, Gordon House, Barrow Street, Dublin 4, Irland. Google Analytics setzt die Cookies <code>_ga</code> und <code>_ga_*</code>, die bis zu zwei Jahre gespeichert bleiben. Damit wird ausgewertet, wie die Website genutzt wird, etwa welche Seiten aufgerufen werden und wie lange. Google Analytics 4 speichert keine vollständigen IP-Adressen. Daten können dabei an Server von Google in den USA übertragen werden.</p>
        <p>Rechtsgrundlage ist deine Einwilligung nach Art. 6 Abs. 1 lit. a DSGVO und § 25 Abs. 1 TDDDG. Ohne Einwilligung wird Google Analytics nicht geladen. Du kannst deine Einwilligung jederzeit über den Link „Cookie-Einstellungen" im Footer widerrufen. Die Cookies von Google Analytics werden dann gelöscht.</p>
      </section>

      <section class="legal-section">
        <h2>8. Konto und Anmeldung</h2>
        <p>Für ein Konto speichern wir deine E-Mail-Adresse, deinen Vor- und Nachnamen und dein Passwort. Das Passwort wird nur als Hashwert gespeichert, den niemand zurückrechnen kann. Dazu kommen der Zeitpunkt der Registrierung und der letzten Anmeldung.</p>
        <p>Zum Schutz vor Missbrauch protokolliert die Seite Ereignisse wie Anmeldungen oder Passwortänderungen. Dabei werden eine gekürzte IP-Adresse und ein Hashwert deiner Browserkennung gespeichert. Damit niemand Passwörter durchprobieren kann, zählt die Seite außerdem Anmeldeversuche pro Anschluss.</p>
        <p><strong>Passwort vergessen.</strong> Die Seite verschickt derzeit keine E-Mails. Der Link zum Zurücksetzen wird stattdessen zusammen mit deiner E-Mail-Adresse im Server-Protokoll vermerkt und ist 60 Minuten gültig.</p>
        <p>Rechtsgrundlage ist die Bereitstellung des Kontos nach Art. 6 Abs. 1 lit. b DSGVO. Für den Schutz vor Missbrauch ist es unser berechtigtes Interesse nach Art. 6 Abs. 1 lit. f DSGVO.</p>
      </section>

      <section class="legal-section">
        <h2>9. Mitgliederbereich und Empfang</h2>
        <p>Wenn du die Funktionen im Mitgliederbereich nutzt, speichern wir die Angaben dazu: gewählte Mitgliedschaften und Tarife, gebuchte Kurse und Probetrainings samt deiner Einschätzung der eigenen Erfahrung, angekündigte und tatsächliche Besuche im Studio, deine Auswahl bei den Trainingsprogrammen sowie Gutscheine.</p>
        <p>Die Besuche fließen ohne Namen in die Anzeige der Auslastung ein. Mitarbeitende am Empfang sehen deinen Namen, deine Mitgliedschaft und deine Nachweise, um deine Identität zu bestätigen.</p>
        <p>Rechtsgrundlage ist die Bereitstellung der gewählten Funktionen nach Art. 6 Abs. 1 lit. b DSGVO.</p>
      </section>

      <section class="legal-section">
        <h2>10. Nachweise für ermäßigte Tarife</h2>
        <p>Für einen Schüler-, Studenten- oder Seniorentarif kannst du ein Foto deines Nachweises hochladen. Das Foto wird an die Gemini-API von Google geschickt. Gemini liest daraus die Art des Nachweises, den Namen, das Ablaufdatum und bei Senioren das Geburtsdatum aus.</p>
        <p><strong>Wichtig:</strong> Das Projekt nutzt die kostenlose Stufe von Gemini. Dort darf Google die Eingaben zur Verbesserung seiner Dienste verwenden, und Menschen dürfen sie lesen. <strong>Lade deshalb nie einen echten Ausweis hoch.</strong></p>
        <p>Das Foto selbst speichern wir nicht. Gespeichert werden die Art des Nachweises, das Datum, bis zu dem er gilt, und wie und wann er geprüft wurde.</p>
        <p>Ob du den ermäßigten Tarif bekommst, entscheidet zunächst die automatische Prüfung (Art. 22 DSGVO). Du kannst verlangen, dass eine Person die Entscheidung überprüft, und deinen Standpunkt darlegen. Spätestens 14 Tage nach dem ersten Nachweis zeigst du deinen Ausweis ohnehin am Empfang vor.</p>
        <p>Rechtsgrundlage ist deine Einwilligung nach Art. 6 Abs. 1 lit. a DSGVO, die du mit dem Hochladen erteilst. Du kannst sie jederzeit widerrufen.</p>
      </section>

      <section class="legal-section">
        <h2>11. Bewertungen und Profilbild</h2>
        <p>Wenn du eine Bewertung schreibst, erscheinen dein Vorname mit dem Anfangsbuchstaben des Nachnamens, deine Sterne, dein Text und ob du beim Schreiben Mitglied warst öffentlich auf der Website.</p>
        <p>Lädst du ein Profilbild hoch, wird es neben deiner Bewertung gezeigt. Das kannst du in „Mein Konto" abschalten. Das Bild wird in der Datenbank gespeichert, bis du es ersetzt oder dein Konto gelöscht wird.</p>
        <p>Rechtsgrundlage ist deine Einwilligung nach Art. 6 Abs. 1 lit. a DSGVO.</p>
      </section>

      <section class="legal-section">
        <h2>12. Freunde werben</h2>
        <p>Wenn du jemanden einlädst, speichern wir den Namen und die E-Mail-Adresse, die du einträgst. Die Seite schreibt diese Person nicht an. Die Angaben dienen nur dazu, eine spätere Registrierung mit dieser E-Mail-Adresse deiner Einladung zuzuordnen, damit du einen Gutschein bekommst.</p>
        <p>Trage nur Personen ein, die damit einverstanden sind. Wer eingeladen wurde und das nicht möchte, kann über die oben genannte E-Mail-Adresse die Löschung verlangen.</p>
        <p>Rechtsgrundlage ist unser berechtigtes Interesse an der Abwicklung des Werbeprogramms nach Art. 6 Abs. 1 lit. f DSGVO.</p>
      </section>

      <section class="legal-section">
        <h2>13. Speicherdauer</h2>
        <p>Die Daten deines Kontos bleiben gespeichert, bis das Konto gelöscht wird. Dafür genügt eine E-Mail an die oben genannte Adresse. Mit dem Konto verschwinden auch deine Mitgliedschaften, Buchungen, Besuche, Nachweise, Bewertungen, dein Profilbild und die Einladungen, die du verschickt hast.</p>
        <p>Sitzungen verfallen nach kurzer Inaktivität, Links zum Zurücksetzen des Passworts nach 60 Minuten. Da es sich um ein Studienprojekt handelt, wird die Datenbank spätestens nach Abschluss des Projekts gelöscht.</p>
      </section>

      <section class="legal-section">
        <h2>14. Pflicht zur Angabe</h2>
        <p>Du musst keine Daten angeben. Ohne E-Mail-Adresse, Namen und Passwort kannst du aber kein Konto anlegen und den Mitgliederbereich nicht nutzen. Die übrigen Seiten funktionieren auch ohne Konto.</p>
      </section>

      <section class="legal-section">
        <h2>15. Deine Rechte</h2>
        <p>Du hast das Recht auf Auskunft (Art. 15 DSGVO), Berichtigung (Art. 16), Löschung (Art. 17), Einschränkung der Verarbeitung (Art. 18) und Datenübertragbarkeit (Art. 20). Eine Einwilligung kannst du jederzeit mit Wirkung für die Zukunft widerrufen (Art. 7 Abs. 3).</p>
        <p><strong>Widerspruch.</strong> Verarbeiten wir Daten aufgrund unseres berechtigten Interesses, kannst du dem aus Gründen, die sich aus deiner besonderen Situation ergeben, jederzeit widersprechen (Art. 21 DSGVO).</p>
        <p><strong>Beschwerde.</strong> Du kannst dich bei einer Datenschutz-Aufsichtsbehörde beschweren (Art. 77 DSGVO), etwa an deinem Wohnort oder beim Landesbeauftragten für den Datenschutz und die Informationsfreiheit Baden-Württemberg, Lautenschlagerstraße 20, 70173 Stuttgart.</p>
      </section>

      <p class="legal-note">Stand: Oktober 2026.</p>
    </div>
  </main>

<?php require ROOT_PATH . '/partials/footer.php'; ?>
