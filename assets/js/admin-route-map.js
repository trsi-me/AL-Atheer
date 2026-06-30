/**
 * تعديل نقاط الخريطة من لوحة التحكم — Leaflet + OpenStreetMap
 */
(function () {
    'use strict';

    var ta = document.getElementById('map_json');
    var host = document.getElementById('admin-route-map');
    var btnAdd = document.getElementById('admin-map-add-mode');
    var btnFit = document.getElementById('admin-map-fit');
    if (!ta || !host || typeof L === 'undefined') {
        return;
    }

    function readJson() {
        try {
            var o = JSON.parse(ta.value.trim() || '{}');
            if (typeof o === 'object' && o !== null) {
                return o;
            }
        } catch (e) {}
        return { center: [25.3843, 49.5877], zoom: 11, points: [] };
    }

    function writeJson(obj) {
        ta.value = JSON.stringify(obj, null, 2);
    }

    var data = readJson();
    if (!Array.isArray(data.points)) {
        data.points = [];
    }
    if (!Array.isArray(data.center) || data.center.length !== 2) {
        data.center = [25.3843, 49.5877];
    }
    if (typeof data.zoom !== 'number') {
        data.zoom = 11;
    }

    var map = L.map(host).setView([parseFloat(data.center[0]), parseFloat(data.center[1])], data.zoom);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap'
    }).addTo(map);

    var markers = [];
    var routePolyline = null;

    var polyOpts = {
        color: '#0b6b6b',
        weight: 5,
        opacity: 0.88,
        lineCap: 'round',
        lineJoin: 'round'
    };

    function syncRoutePolyline() {
        if (routePolyline) {
            map.removeLayer(routePolyline);
            routePolyline = null;
        }
        var seg = [];
        data.points.forEach(function (p) {
            var lat = parseFloat(p.lat);
            var lng = parseFloat(p.lng);
            if (isNaN(lat) || isNaN(lng)) {
                return;
            }
            seg.push([lat, lng]);
        });
        if (seg.length >= 2) {
            routePolyline = L.polyline(seg, polyOpts).addTo(map);
        }
    }

    function syncMarkersFromData() {
        markers.forEach(function (m) {
            map.removeLayer(m);
        });
        markers = [];
        syncRoutePolyline();
        data.points.forEach(function (p, idx) {
            var lat = parseFloat(p.lat);
            var lng = parseFloat(p.lng);
            if (isNaN(lat) || isNaN(lng)) {
                return;
            }
            var label = (p.label && String(p.label)) || ('نقطة ' + (idx + 1));
            var m = L.marker([lat, lng], { draggable: true }).addTo(map);
            m.bindPopup(label);
            m.on('dragend', function () {
                var ll = m.getLatLng();
                data.points[idx].lat = ll.lat;
                data.points[idx].lng = ll.lng;
                writeJson(data);
                syncRoutePolyline();
                markers.forEach(function (mk) {
                    if (typeof mk.bringToFront === 'function') {
                        mk.bringToFront();
                    }
                });
            });
            markers.push(m);
        });
    }

    var saveView = function () {
        var c = map.getCenter();
        data.center = [c.lat, c.lng];
        data.zoom = map.getZoom();
        writeJson(data);
    };

    map.on('moveend', saveView);
    map.on('zoomend', saveView);

    var addMode = false;
    if (btnAdd) {
        btnAdd.addEventListener('click', function () {
            addMode = !addMode;
            btnAdd.textContent = addMode ? 'إيقاف إضافة النقاط' : 'إضافة نقطة بالنقر على الخريطة';
            btnAdd.classList.toggle('btn--primary', addMode);
        });
    }

    map.on('click', function (e) {
        if (!addMode) {
            return;
        }
        var lab = window.prompt('اسم النقطة', 'نقطة جديدة');
        if (lab === null) {
            return;
        }
        data.points.push({ lat: e.latlng.lat, lng: e.latlng.lng, label: lab.trim() || 'نقطة' });
        writeJson(data);
        syncMarkersFromData();
    });

    if (btnFit) {
        btnFit.addEventListener('click', function () {
            var b = [];
            data.points.forEach(function (p) {
                var la = parseFloat(p.lat);
                var ln = parseFloat(p.lng);
                if (!isNaN(la) && !isNaN(ln)) {
                    b.push([la, ln]);
                }
            });
            if (b.length > 1) {
                map.fitBounds(b, { padding: [30, 30] });
            } else if (b.length === 1) {
                map.setView(b[0], 13);
            }
        });
    }

    ta.addEventListener('change', function () {
        data = readJson();
        if (!Array.isArray(data.points)) {
            data.points = [];
        }
        syncMarkersFromData();
        if (Array.isArray(data.center) && data.center.length === 2) {
            map.setView([parseFloat(data.center[0]), parseFloat(data.center[1])], data.zoom || 11);
        }
    });

    syncMarkersFromData();
})();
