/**
 * خريطة OpenStreetMap عبر Leaflet — صفحة تفاصيل المسار
 * خط متصل بين المحطات: يُحاول اتباع الشبكة الطرقية (OSRM مشي)، وإلا خط مباشر.
 */
(function () {
    'use strict';

    var el = document.getElementById('route-map');
    var payloadEl = document.getElementById('route-map-payload');
    if (!el || !payloadEl || typeof L === 'undefined') {
        return;
    }

    var data;
    try {
        data = JSON.parse(payloadEl.textContent || '{}');
    } catch (e) {
        data = {};
    }

    var center = Array.isArray(data.center) && data.center.length === 2
        ? [parseFloat(data.center[0]), parseFloat(data.center[1])]
        : [25.3843, 49.5877];
    var zoom = typeof data.zoom === 'number' ? data.zoom : 11;
    var points = Array.isArray(data.points) ? data.points : [];
    /** false فقط لإجبار خط مستقيم بين النقاط */
    var followRoads = data.followRoads !== false;

    var map = L.map(el, { scrollWheelZoom: true }).setView(center, zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
    }).addTo(map);

    var polyOpts = {
        color: '#0b6b6b',
        weight: 6,
        opacity: 0.92,
        lineCap: 'round',
        lineJoin: 'round'
    };

    var items = [];
    points.forEach(function (p, i) {
        if (typeof p.lat !== 'number' && typeof p.lat !== 'string') {
            return;
        }
        var lat = parseFloat(p.lat);
        var lng = parseFloat(p.lng);
        if (isNaN(lat) || isNaN(lng)) {
            return;
        }
        items.push({ lat: lat, lng: lng, p: p, idx: i });
    });

    var plain = items.map(function (it) {
        return [it.lat, it.lng];
    });

    function addMarkers() {
        items.forEach(function (it, n) {
            var lat = it.lat;
            var lng = it.lng;
            var label = (it.p.label && String(it.p.label)) || ('نقطة ' + (n + 1));
            var safe = label.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            var backHref = '#route-top';
            var html = '<div class="route-map-popup">' +
                '<strong>' + safe + '</strong>' +
                '<p><a href="' + backHref + '">العودة للفعالية</a></p>' +
                '</div>';
            L.marker([lat, lng]).addTo(map).bindPopup(html);
        });
    }

    function fitToCoords(coords) {
        if (!coords || coords.length === 0) {
            return;
        }
        if (coords.length > 1) {
            map.fitBounds(L.latLngBounds(coords), { padding: [40, 40], maxZoom: 14 });
        } else {
            map.setView(coords[0], Math.max(zoom, 13));
        }
    }

    function addStraightLine() {
        if (plain.length < 2) {
            return;
        }
        L.polyline(plain, polyOpts).addTo(map);
        fitToCoords(plain);
    }

    /**
     * OSRM عام: ملف مشي على الشبكة الطرقية في OpenStreetMap
     */
    function fetchRoadGeometry() {
        if (plain.length < 2) {
            return Promise.resolve(false);
        }
        var coordStr = plain.map(function (ll) {
            return ll[1] + ',' + ll[0];
        }).join(';');
        var url = 'https://router.project-osrm.org/route/v1/walking/' + coordStr + '?overview=full&geometries=geojson';
        return fetch(url)
            .then(function (res) {
                return res.json();
            })
            .then(function (j) {
                if (j.code !== 'Ok' || !j.routes || !j.routes[0] || !j.routes[0].geometry) {
                    return false;
                }
                var geom = j.routes[0].geometry;
                var coords;
                if (geom.type === 'LineString' && Array.isArray(geom.coordinates)) {
                    coords = geom.coordinates.map(function (c) {
                        return [c[1], c[0]];
                    });
                } else {
                    return false;
                }
                if (coords.length < 2) {
                    return false;
                }
                L.polyline(coords, polyOpts).addTo(map);
                fitToCoords(coords);
                return true;
            })
            .catch(function () {
                return false;
            });
    }

    function run() {
        if (plain.length === 0) {
            return;
        }
        if (plain.length === 1) {
            fitToCoords(plain);
            addMarkers();
            return;
        }
        var done = function () {
            addMarkers();
        };
        if (followRoads) {
            fetchRoadGeometry().then(function (ok) {
                if (!ok) {
                    addStraightLine();
                }
                done();
            });
        } else {
            addStraightLine();
            done();
        }
    }

    run();
})();
