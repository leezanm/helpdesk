/* ------------------------------------------------------------------------------
 *
 *  # Vector maps
 *
 *  Specific JS code additions for maps_vector.html page
 *
 * ---------------------------------------------------------------------------- */


// Setup module
// ------------------------------

var VectorMaps = function() {


    //
    // Setup module components
    //

    // Line chart
    var _vectorMapsExamples = function() {
        if (!$().vectorMap) {
            console.warn('Warning - jvectormap.min.js is not loaded.');
            return;
        }

        var showMarkers = [
            {details: '<b>Population:</b></br>'+'<b>Unemployment rate: </b>%'}
        ];

        // Configuration
        var map = new jvm.WorldMap({
            container: $('#malaysia-vectormap'),
            map: 'my_mill_en',
            backgroundColor: 'transparent',
            regionsSelectable: false,
            markersSelectable: false,


            regionStyle: {
                initial: {
                    "stroke-width": 1.6,
                    'stroke': '#fff',
                    'fill': '#93D389'
                },
                selected: {
                    'fill': '#00a2ca'
                }
            },
            series: {
                markers: [{
                    attribute: 'r',
                    scale: [5, 15],
                    // values: cityAreaData
                }]
            },

            onRegionSelected: function(){
                if (window.localStorage) {
                    window.localStorage.setItem(
                        'jvectormap-selected-regions',
                        JSON.stringify(map.getSelectedRegions())
                    );
                }
            },
            onMarkerSelected: function(){
                if (window.localStorage) {
                    window.localStorage.setItem(
                        'jvectormap-selected-markers',
                        JSON.stringify(map.getSelectedMarkers())
                    );
                }
            },

            // onMarkerClick: function(event, index) {
            //     location.href = markers[index].weburl;
            // },

            onMarkerLabelShow: function(event, label, code) {
                $(label).append($("<br/>"));
                $(label).append($("<span/>", {
                    // 'class': 'population',
                    'html': 'Equipment List :' + ' <table> <tr><td>Equipment 1</td></tr> <tr><td>Equipment 2</td></tr> <tr><td>Equipment 2</td></tr> </table> '
                    //  + parseInt(Math.random() * (1000000 - 1000) + 1000)
                }));
              },

        });

        // Set regions
        map.setSelectedRegions( JSON.parse( window.localStorage.getItem('jvectormap-selected-regions') || '[]' ) );
        map.setSelectedMarkers( JSON.parse( window.localStorage.getItem('jvectormap-selected-markers') || '[]' ) );
    };


    //
    // Return objects assigned to module
    //

    return {
        init: function() {
            _vectorMapsExamples();
        }
    }
}();


// Initialize module
// ------------------------------

document.addEventListener('DOMContentLoaded', function() {
    VectorMaps.init();
});
