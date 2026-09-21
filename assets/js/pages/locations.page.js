/**
 * @file        assets/js/pages/locations.page.js
 * @layer       2 – Seitenskript
 * @description Erstellt die Leaflet-Karte, synchronisiert sie mit der
 *              interaktiven Liste der vier SCHWITZKASTEN Standorte und
 *              startet den scrollgesteuerten Studio-Rundgang darunter.
 * @see         locations.php
 * @see         https://leafletjs.com/
 */

import { initScrollVideo } from '../components/scroll-video.js';

// Der Rundgang unter der Karte. Die Komponente steigt von selbst aus,
// wenn der Abschnitt fehlt - siehe assets/js/components/scroll-video.js.
initScrollVideo('[data-studio-tour]');

const locations = [
  {
    city: 'Köln',
    address: 'Venloer Straße 213, 50823 Köln',
    coordinates: [50.9472, 6.9242],
  },
  {
    city: 'München',
    address: 'Sendlinger Straße 12, 80331 München',
    coordinates: [48.1362, 11.5732],
  },
  {
    city: 'Berlin',
    address: 'Friedrichstraße 180, 10117 Berlin',
    coordinates: [52.5126, 13.3889],
  },
  {
    city: 'Stuttgart',
    address: 'Königstraße 26, 70173 Stuttgart',
    coordinates: [48.7784, 9.1794],
  },
];

const mapElement = document.querySelector('#map');
const listElement = document.querySelector('#locations-list');

if (mapElement && listElement && window.L) {
  const germanyBounds = L.latLngBounds(
    [47.2, 5.8],
    [55.1, 15.2],
  );
  const map = L.map(mapElement, {
    maxBounds: germanyBounds,
    maxBoundsViscosity: 1,
    minZoom: 5.5,
    zoomControl: false,
  });

  map.fitBounds(germanyBounds, { padding: [16, 16] });

  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap-Mitwirkende',
    maxZoom: 19,
  }).addTo(map);

  L.control.zoom({ position: 'bottomright' }).addTo(map);

  const resetButton = L.control({ position: 'topleft' });
  resetButton.onAdd = () => {
    const button = L.DomUtil.create('button', 'map-reset-button');
    button.type = 'button';
    button.title = 'Deutschland anzeigen';
    button.setAttribute('aria-label', 'Deutschland anzeigen');
    button.innerHTML = 'DE';
    L.DomEvent.disableClickPropagation(button);
    L.DomEvent.on(button, 'click', () => {
      map.flyToBounds(germanyBounds, { padding: [16, 16], duration: 1 });
    });
    return button;
  };
  resetButton.addTo(map);

  locations.forEach((location, index) => {
    const popup = `<strong>${location.city}</strong><br>${location.address}`;
    const marker = L.marker(location.coordinates).addTo(map);
    marker.bindPopup(popup);

    const listItem = document.createElement('button');
    listItem.className = 'location-item';
    listItem.type = 'button';
    listItem.innerHTML = `
      <span class="location-number">0${index + 1}</span>
      <span class="location-details">
        <strong>${location.city}</strong>
        <span>${location.address}</span>
      </span>
      <span class="location-arrow" aria-hidden="true">↗</span>
    `;

    listItem.addEventListener('click', () => {
      map.flyTo(location.coordinates, 15, { duration: 1.2 });
      marker.openPopup();
    });

    listElement.append(listItem);
  });
}
