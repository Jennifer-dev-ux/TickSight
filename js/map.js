document.addEventListener('DOMContentLoaded', () => {
    console.log('map.js loaded');
    console.log('Leaflet L type:', typeof L);
    console.log('apiSightings length:', Array.isArray(apiSightings) ? apiSightings.length : 'not an array');
    console.log('First api sighting:', Array.isArray(apiSightings) ? apiSightings[0] : null);

    const mapElement = document.getElementById('map');
    if (!mapElement) return;
    if (typeof L === 'undefined') return;

    // 1. Base map
    const map = L.map('map').setView([54.5, -3.0], 5);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 18,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    const detailsContainer = document.getElementById('sighting-details');

    // City coordinates
    const cityCoordinates = {
        'Nottingham':  [52.9548, -1.1581],
        'Glasgow':     [55.8642, -4.2518],
        'London':      [51.5074, -0.1278],
        'Manchester':  [53.4808, -2.2426],
        'Sheffield':   [53.3811, -1.4701],
        'Liverpool':   [53.4084, -2.9916],
        'Bristol':     [51.4545, -2.5879],
        'Birmingham':  [52.4862, -1.8904],
        'Edinburgh':   [55.9533, -3.1883],
        'Cardiff':     [51.4816, -3.1791],
        'Southampton': [50.9097, -1.4043],
        'Newcastle':   [54.9783, -1.6178],
        'Leeds':       [53.8008, -1.5491],
        'Leicester':   [52.6369, -1.1398],
    };

    // Species → severity mapping
    const speciesSeverity = {
        'Marsh tick':           'medium',
        'Southern rodent tick': 'medium',
        'Passerine tick':       'high',
        'Tree-hole tick':       'low',
        'Fox/badger tick':      'high'
    };

    function getSeverityForSighting(sighting) {
        const species = sighting.species || '';
        const sev = speciesSeverity[species];
        return sev || 'low'; // default to low if unknown
    }

    function getSeverityColor(severity) {
        if (severity === 'high')   return '#e74c3c';   // red
        if (severity === 'medium') return '#f1c40f';   // yellow
        return '#2ecc71';                                // green (low)
    }

    function matchesSeverityFilter(severity) {
        if (typeof selectedSeverity === 'undefined' || !selectedSeverity) {
            return true; // no severity filter selected
        }
        return severity === selectedSeverity;
    }

    // spread around city centre to simulate separate points
    function jitterCoords(baseCoords) {
        const [lat, lng] = baseCoords;
        const maxOffset = 0.08; // degrees (~a few km radius)
        const offsetLat = (Math.random() - 0.5) * maxOffset;
        const offsetLng = (Math.random() - 0.5) * maxOffset;
        return [lat + offsetLat, lng + offsetLng];
    }

    // Date formatting
    function formatDate(rawDate) {
        if (!rawDate) return 'Unknown date';
        const d = new Date(rawDate);
        if (isNaN(d)) return 'Unknown date';
        return d.toLocaleString('en-GB', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    // Sidebar sighting + city timeline details
    function showSightingDetails(sighting, citySightings) {
        if (!detailsContainer) return;

        const city     = sighting.location || sighting.city || 'Unknown location';
        const dateStr  = formatDate(sighting.date);
        const species  = sighting.species ?? 'Unknown species';
        const latin    = sighting.latinName || '';
        const severity = getSeverityForSighting(sighting);
        const severityLabel = severity.charAt(0).toUpperCase() + severity.slice(1);

        // 🔹 If this is a user sighting and it has an image_path, show the photo
        let photoHtml = '';
        if (sighting.image_path) {
            const imgSrc = sighting.image_path;
            photoHtml = `
            <div class="sighting-photo">
                <h6>Photo from reporter</h6>
                <img src="${imgSrc}"
                     alt="User tick sighting photo from ${city}">
            </div>
        `;
        }

        // Sort all city sightings by date (newest first)
        const sorted = (citySightings || [])
            .filter(s => !!s.date)
            .slice()
            .sort((a, b) => new Date(b.date) - new Date(a.date));

        //show activity progression in this area
        const timelineItems = sorted.slice(0, 10)
            .map(s => {
                const date = formatDate(s.date);
                const sp   = s.species ?? 'Unknown species';
                return `
                <li class="timeline-item">
                    <span class="timeline-date">${date}</span>
                    <span class="timeline-species">${sp}</span>
                </li>
            `;
            }).join('');

        const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(city + ', UK')}`;
        const shareUrl = `${window.location.origin}${window.location.pathname}?page=map&city=${encodeURIComponent(city)}`;

        detailsContainer.innerHTML = `
        <h5>${city}</h5>
        <p><strong>Selected sighting</strong></p>
        <p><strong>Date &amp; time:</strong> ${dateStr}</p>
        <p><strong>Species:</strong> ${species}${
            latin ? ` <span class="species-latin"><em>${latin}</em></span>` : ''
        }</p>
        <p><strong>Severity (by species):</strong> ${severityLabel}</p>

        ${photoHtml}

        <h5>Timeline of recent activity</h5>
        <p class="timeline-subtitle">
            Last reports of popular ticks in this area:
        </p>
        <ul class="sightings-timeline">
            ${timelineItems || '<li>No timeline available.</li>'}
        </ul>

        <div class="quick-actions">
            <button type="button" class="qa-report">Report a sighting</button>
            <a href="${mapsUrl}" target="_blank" rel="noopener noreferrer" class="qa-directions">
                Get directions
            </a>
            <button type="button" class="qa-share" data-share-url="${shareUrl}">
                Share
            </button>
        </div>
    `;

        // Wire up quick action buttons
        const reportBtn = detailsContainer.querySelector('.qa-report');
        const shareBtn  = detailsContainer.querySelector('.qa-share');

        if (reportBtn) {
            reportBtn.addEventListener('click', () => {
                window.location.href = 'index.php?page=report';
            });
        }
        if (shareBtn) {
            shareBtn.addEventListener('click', () => {
                const url = shareBtn.getAttribute('data-share-url');
                if (navigator.clipboard && url) {
                    navigator.clipboard.writeText(url)
                        .then(() => alert('Link copied to clipboard!'))
                        .catch(() => alert('Could not copy link. You can copy it from the address bar.'));
                } else {
                    alert('Sharing not supported here. You can copy the page URL from the address bar.');
                }
            });
        }
    }

    // MAIN RENDER LOGIC

    if (!Array.isArray(apiSightings) || apiSightings.length === 0) {
        if (detailsContainer) {
            detailsContainer.innerHTML = `<p>No sightings available for the current filters.</p>`;
        }
        return;
    }

    // Group sightings by city for timelines
    const cityGroups = {};
    apiSightings.forEach(sighting => {
        const city = sighting.location || sighting.city;
        if (!city) return;
        if (!cityCoordinates[city]) return;

        if (!cityGroups[city]) {
            cityGroups[city] = [];
        }
        cityGroups[city].push(sighting);
    });

    const markers = [];

    // One jittered marker per sighting
    apiSightings.forEach(sighting => {
        const city = sighting.location || sighting.city;
        if (!city) return;
        const baseCoords = cityCoordinates[city];
        if (!baseCoords) return;

        const severity = getSeverityForSighting(sighting);

        // Apply severity filter per sighting
        if (!matchesSeverityFilter(severity)) {
            return;
        }

        const coords = jitterCoords(baseCoords);
        const color  = getSeverityColor(severity);

        const marker = L.circleMarker(coords, {
            radius: 8,
            color: '#ffffff',   // white outline
            weight: 3,
            fillColor: color,   // inner severity colour
            fillOpacity: 0.9
        }).addTo(map);

        marker.on('click', () => {
            showSightingDetails(sighting, cityGroups[city] || []);
        });

        markers.push(marker);
    });

    if (markers.length > 0) {
        const group = L.featureGroup(markers);
        map.fitBounds(group.getBounds().pad(0.2));
    } else if (detailsContainer) {
        detailsContainer.innerHTML = `<p>No sightings available for the current filters.</p>`;
    }
});
