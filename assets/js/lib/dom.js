/**
 * @file        assets/js/lib/dom.js
 * @layer       Hilfsfunktionen
 * @description Zwei winzige Abkürzungen für die Suche im DOM.
 *
 *              lib/ enthält allgemeine Helfer ohne Bezug zu einem konkreten
 *              Feature. Faustregel: Wenn eine Funktion auch in einem völlig
 *              anderen Projekt Sinn ergäbe, gehört sie hierher.
 */

/**
 * Sucht das erste passende Element.
 *
 * @param {string} selector       CSS-Selektor, z. B. '#nav-links' oder '.button'
 * @param {ParentNode} [scope]    Bereich, in dem gesucht wird (Standard: ganzes Dokument)
 * @returns {Element|null}        Das Element, oder null wenn es nicht existiert
 */
export function $(selector, scope = document) {
  return scope.querySelector(selector);
}

/**
 * Sucht alle passenden Elemente.
 *
 * Gibt ein echtes Array zurück (nicht die NodeList von querySelectorAll),
 * damit map(), filter() und reduce() direkt funktionieren.
 *
 * @param {string} selector       CSS-Selektor
 * @param {ParentNode} [scope]    Bereich, in dem gesucht wird
 * @returns {Element[]}           Array der gefundenen Elemente, ggf. leer
 */
export function $$(selector, scope = document) {
  return Array.from(scope.querySelectorAll(selector));
}
