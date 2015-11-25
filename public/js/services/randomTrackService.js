angular.module('randomTrackService', [])

.factory('RandomTrack', function($http) {

    return {
        // get all the comments
        get : function(n) {
            return $http.get('/spotify/tracks/random?count=' + n);
        },

        createPlaylist : function(name, tracks) {
            return $http({
                method: 'POST',
                url: '/spotify/playlists',
                data: {
                    name:name,
                    trackUris: tracks
                }
            });
        }
    }

});