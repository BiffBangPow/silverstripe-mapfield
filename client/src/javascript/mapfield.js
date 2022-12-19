import Map from 'ol/Map.js';
import OSM from 'ol/source/OSM.js';
import TileLayer from 'ol/layer/Tile.js';
import View from 'ol/View.js';
import {fromLonLat} from 'ol/proj.js';
import Vector from 'ol/layer/Vector.js';
import SourceVector from 'ol/source/Vector.js';
import Style from 'ol/style/Style.js';
import Icon from 'ol/style/Icon.js';
import Feature from 'ol/Feature.js';
import Point from 'ol/geom/Point.js';

function initBBPMapFields() {

    const locationInputs = document.querySelectorAll('input.bbp-osm-map');

    locationInputs.forEach((locationInput) => {

        let fieldSettings = JSON.parse(locationInput.dataset.settings);
        console.log(fieldSettings);

        let mapHolder = document.createElement('div');
        mapHolder.classList.add('bbp-osm-mapholder');
        mapHolder.id = locationInput.name + '-map';

        locationInput.insertAdjacentElement('afterend', mapHolder);

        let mercatorPos = fromLonLat([fieldSettings.coords[1], fieldSettings.coords[0]]);

        let map = new Map({
            target: mapHolder.id,
            layers: [
                new TileLayer({
                    source: new OSM(),
                }),
            ],
            view: new View({
                center: mercatorPos,
                zoom: fieldSettings.map.zoom,
            }),
        });


        if ((fieldSettings.geokeo_api_key !== null) && (fieldSettings.geolocation_fieldname !== null)) {
            let locateBtn = document.createElement('button');
            locateBtn.classList.add('bbp-osm-locatebutton');
            locateBtn.id = locationInput.name + '_locatebutton';
            locateBtn.innerText = fieldSettings.geolocation_button_text;
            //locateBtn.dataset.glurl = fieldSettings.geokeo_base_url;
            locateBtn.dataset.glurl = 'http://api.positionstack.com/v1/forward';
            locateBtn.dataset.api = fieldSettings.geokeo_api_key;
            locationInput.insertAdjacentElement('afterend', locateBtn);

            let editForm = locationInput.parentElement.closest('form');
            let inputField = editForm.querySelector(':scope [name=' + fieldSettings.geolocation_fieldname + ']');

            if (inputField !== null) {
                initGeoLocation(locateBtn, map, inputField);
            } else {
                console.log('No input field found named "' + fieldSettings.geolocation_fieldname + '"');
            }

        }


        /**
         let markers = new Vector({
            source: new SourceVector(),
            style: new Style({
                image: new Icon({
                    anchor: [0.5, 1],
                    src: mapHolder.dataset.pinurl
                })
            })
        });
         map.addLayer(markers);
         let marker = new Feature(new Point(mercatorPos));
         markers.getSource().addFeature(marker);

         **/


    });
}

function initGeoLocation(button, map, inputField) {
    button.addEventListener('click', () => {
        let GLURL = new URL(button.dataset.glurl);
        let addressRaw = inputField.value;
        GLURL.searchParams.set('access_key', button.dataset.api);
        GLURL.searchParams.set('query', addressRaw.replace(new RegExp("[\r\n]", "gm"), ","));
        GLURL.searchParams.set('limit', 1);

        let XHR = new XMLHttpRequest();
        XHR.addEventListener("load", function (res) {
            processGeoLocationResult(res, map);
        })
        XHR.open("GET", GLURL.href);
        XHR.setRequestHeader('x-requested-with', 'XMLHttpRequest');
        XHR.send();
    });
}


function processGeoLocationResult(res, map) {
    console.log(res.target.responseText);
}

document.addEventListener("DOMContentLoaded", () => {

    initBBPMapFields();

    /**
     if ($(document.body).hasClass('cms')) {
        (function setupCMS() {
            let matchFunction = function () {
                initBBPMapFields();
            };
            $.entwine('initBBPMapFields', function ($) {
                $('.cms-tabset').entwine({
                    onmatch: matchFunction
                });
                $('.cms-tabset-nav-primary li').entwine({
                    onclick: matchFunction
                });
                $('.ss-tabset li').entwine({
                    onclick: matchFunction
                });
                $('.cms-edit-form').entwine({
                    onmatch: matchFunction
                });
            });
        }());
    }
     **/

});
