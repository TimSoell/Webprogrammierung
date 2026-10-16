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
function knoten(name) {
  return {
    name, attributes: {}, children: [], textContent: '', hidden: false, offsetHeight: 130,
    style: { properties: {}, setProperty(key, value) { this.properties[key] = value; } }, events: {},
    setAttribute(key, value) { this.attributes[key] = value; },
    getAttribute(key) { return this.attributes[key]; },
    replaceChildren(...children) { this.children = children; },
    append(...children) { this.children.push(...children); },
    addEventListener(event, handler) { this.events[event] = handler; },
    getBoundingClientRect() { return { left: 0, top: 0, width: 720, height: 240 }; },
    focus() { globalThis.document.activeElement = this; this.events.focus?.(); },
  };
}
globalThis.window = { innerHeight: 900 };
globalThis.document = {
  events: {},
  addEventListener(event, handler) { this.events[event] = handler; },
  removeEventListener(event, handler) { if (this.events[event] === handler) delete this.events[event]; },
  createElementNS(_namespace, name) { return knoten(name); },
  createElement(name) { return knoten(name); },
};
const source = await readFile(new URL('../assets/js/components/auslastung-diagramm.js', import.meta.url), 'utf8');
const { diagrammZeichnen } = await import(`data:text/javascript;base64,${Buffer.from(source).toString('base64')}`);

function zeichnen(basis, gebucht) {
  const container = { replaceChildren(svg) { this.svg = svg; } };
  diagrammZeichnen(container, {
    kapazitaet: 100, jetzt: { stunde: 9 },
    verlauf: [{ stunde: 9, basis, gebucht, checkins: 0, gesamt: Math.max(basis, gebucht) }],
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

function hovercardZeichnen() {
  const container = knoten('div');
  container.id = 'diagramm-test';
  container.querySelector = () => null;
  container.replaceChildren = (...children) => { container.children = children; };
  diagrammZeichnen(container, {
    kapazitaet: 100, jetzt: { stunde: 9, personen: 60 },
    verlauf: [{ stunde: 9, basis: 60, gebucht: 20, checkins: 7, gesamt: 60 }],
  });
  return container;
}

function textIn(node) {
  return [node.textContent, ...node.children.map(textIn)].join(' ');
}

test('Hovercard zeigt echte Check-ins getrennt von Ankündigungen und üblicher Auslastung', () => {
  const container = hovercardZeichnen();
  const trigger = container.children[0].children.find(node => node.attributes.class === 'auslastung-spalte');
  assert.ok(trigger, 'Jede Stunde braucht eine zugängliche Trefferfläche');
  trigger.events.pointerenter({ pointerType: 'mouse' });
  const card = container.children[1];
  assert.equal(card.hidden, false);
  assert.match(textIn(card), /9:00/);
  assert.match(textIn(card), /Check-ins\s+7/);
  assert.match(textIn(card), /Üblich\s+60/);
  assert.match(textIn(card), /Angekündigt\s+13/);
  trigger.events.pointerleave({ pointerType: 'mouse' });
  assert.equal(card.hidden, true);
});

test('Tastaturfokus öffnet die Hovercard, Escape schließt sie', () => {
  const container = hovercardZeichnen();
  const trigger = container.children[0].children.find(node => node.attributes.class === 'auslastung-spalte');
  assert.ok(trigger);
  trigger.focus();
  assert.equal(container.children[1].hidden, false);
  trigger.events.keydown({ key: 'Escape', preventDefault() {} });
  assert.equal(container.children[1].hidden, true);
});

test('Antippen öffnet die Karte und hält sie nach dem Touch-Ende sichtbar', () => {
  const container = hovercardZeichnen();
  const trigger = container.children[0].children.find(node => node.attributes.class === 'auslastung-spalte');
  trigger.events.pointerenter({ pointerType: 'touch' });
  assert.equal(container.children[1].hidden, true);
  trigger.events.click();
  trigger.events.pointerleave({ pointerType: 'touch' });
  assert.equal(container.children[1].hidden, false);
});

test('Automatisches Neuzeichnen erhält den Tastaturfokus und aktualisiert die Karte', () => {
  const container = hovercardZeichnen();
  const trigger = container.children[0].children.find(node => node.attributes.class === 'auslastung-spalte');
  trigger.focus();
  container.querySelector = () => trigger;
  diagrammZeichnen(container, {
    kapazitaet: 100, jetzt: { stunde: 9, personen: 60 },
    verlauf: [{ stunde: 9, basis: 60, gebucht: 21, checkins: 8, gesamt: 60 }],
  });
  assert.notEqual(document.activeElement, trigger);
  assert.equal(document.activeElement.getAttribute('data-stunde'), '9');
  assert.equal(container.children[1].hidden, false);
  assert.match(textIn(container.children[1]), /Check-ins\s+8/);
});

test('Escape schließt auch eine nur per Maus geöffnete Karte', () => {
  const container = hovercardZeichnen();
  const trigger = container.children[0].children.find(node => node.attributes.class === 'auslastung-spalte');
  trigger.events.pointerenter({ pointerType: 'mouse' });
  assert.equal(typeof document.events.keydown, 'function');
  document.events.keydown({ key: 'Escape' });
  assert.equal(container.children[1].hidden, true);
});

test('Karte bleibt bei hohen Balken unterhalb der festen Navigation sichtbar', () => {
  const container = hovercardZeichnen();
  container.getBoundingClientRect = () => ({ top: -100 });
  document.querySelector = () => ({ getBoundingClientRect: () => ({ bottom: 88 }) });
  try {
    const trigger = container.children[0].children.find(node => node.attributes.class === 'auslastung-spalte');
    trigger.events.pointerenter({ pointerType: 'mouse' });
    assert.equal(container.children[1].style.properties['--hover-y'], '196px');
  } finally {
    delete document.querySelector;
  }
});
