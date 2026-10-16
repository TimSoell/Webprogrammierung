/**
 * @file        tests/auslastung-diagramm.test.mjs
 * @layer       Test
 * @description Prüft Füllung, Wachstum und Uhrzeiten des Auslastungsdiagramms.
 * @see         assets/js/components/auslastung-diagramm.js
 */
import assert from 'node:assert/strict';
import { readFile } from 'node:fs/promises';
import test from 'node:test';

// Nur die benötigten DOM-Operationen ersetzen; gezeichnet wird mit dem echten Modul.
globalThis.document = {
  createElementNS(_namespace, name) {
    return {
      name, attributes: {}, children: [], textContent: '',
      setAttribute(key, value) { this.attributes[key] = value; },
      append(child) { this.children.push(child); },
    };
  },
};
const source = await readFile(new URL('../assets/js/components/auslastung-diagramm.js', import.meta.url), 'utf8');
const { diagrammZeichnen } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);

function zeichnen(basis, gebucht) {
  const container = { replaceChildren(svg) { this.svg = svg; } };
  diagrammZeichnen(container, {
    kapazitaet: 100, jetzt: { stunde: 9 },
    verlauf: [{ stunde: 9, basis, gebucht, gesamt: Math.max(basis, gebucht) }],
  });
  return container.svg.children;
}

test('Besuche füllen den Durchschnittsbalken von unten statt ihn aufzustocken', () => {
  const rects = zeichnen(60, 20).filter((node) => node.name === 'rect');
  assert.equal(Number(rects[0].attributes.height), 120);
  assert.equal(Number(rects[1].attributes.height), 40);
  assert.equal(Number(rects[1].attributes.y), 174);
});

test('Mehr Besuche als üblich wachsen über den Durchschnitt hinaus', () => {
  const rects = zeichnen(20, 80).filter((node) => node.name === 'rect');
  assert.equal(Number(rects[1].attributes.y), 54);
  assert.equal(Number(rects[1].attributes.height), 160);
});

test('Ein einzelner zusätzlicher Check-in verändert die grüne Füllhöhe', () => {
  const vorher = zeichnen(60, 20).find((node) => node.attributes.class === 'auslastung-balken-gebucht');
  const nachher = zeichnen(60, 21).find((node) => node.attributes.class === 'auslastung-balken-gebucht');
  assert.equal(Number(nachher.attributes.height) - Number(vorher.attributes.height), 2);
});

test('Uhrzeiten enthalten die Minuten', () => {
  const texte = zeichnen(60, 0).filter((node) => node.name === 'text');
  assert.ok(texte.some((node) => node.textContent === '9:00'));
});

test('Ohne Besuche bleibt der Durchschnittsbalken grau', () => {
  const rects = zeichnen(60, 0).filter((node) => node.name === 'rect');
  assert.equal(rects.length, 1);
});
