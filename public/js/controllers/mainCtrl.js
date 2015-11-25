angular.module('mainCtrl', [])

// inject the Comment service into our controller
.controller('mainController', function($scope, $http, RandomTrack) {


    // object to hold all the data for the new comment form
    $scope.randomTracks = {};

    // loading variable to show the spinning loading icon
    $scope.loading = true;
    $scope.creating = false;
    $scope.playlistTracks = [];
    $scope.playlistName = 'ReDiscover Playlist 12/12/12';

    $scope.refreshTracks = function() {
        // for (var i=0; i<$scope.tracks.length; i++) {
        //     $scope.replaceTrack(i);
        // }
        $scope.loading = true;
        $scope.tracks = [];
        
        RandomTrack.get(5)
            .success(function(res) {
                $scope.loading = false;
                if (res.success) {
                    $scope.tracks = res.data;
                } else {
                    var problemObject = {
                        name:'There was a problem, please try again.',
                        album_name:'---',
                        artist_name:'---',
                        position: $scope.tracks.length - 1,
                        album_img:'img/blank.png'
                    }
                    $scope.tracks.push(problemObject);
                }
            })
            .error(function(res) {
                $scope.loading = false;
                var problemObject = {
                        name:'There was a problem, please try again.',
                        album_name:'---',
                        artist_name:'---',
                        position: $scope.tracks.length - 1,
                        album_img:'img/blank.png'
                }
                $scope.tracks.push(problemObject);
            });
    }
    $scope.addToPlaylist = function(track, index) {
        $scope.playlistTracks.unshift(track);
        //$scope.tracks.splice(index, 1);

        $scope.replaceTrack(index);

        if ($scope.tracks.length == 0) {
            $scope.refreshTracks();
        }
    }

    $scope.replaceTrack = function(index) {
        // Create a temporary loading object
        var loadObject = {
            name:'Retrieving new track...',
            album_name:'---',
            artist_name:'---',
            position: $scope.tracks.length - 1,
            album_img:'img/blank.png',
            loading:true
        };
        // Remove this track
        $scope.tracks.splice(index, 1);

        // Decrement all the positions of any other loading objects
        for(var i=0; i < $scope.tracks.length;i++) {
            if($scope.tracks[i].loading)
                $scope.tracks[i].position -= 1;
        }

        // Push our new loading object at the end
        $scope.tracks.push(loadObject);

        RandomTrack.get(1)
            .success(function(res) {
                if (res.success) {
                    // Replace the load object at the correct position with the retreived track
                    $scope.tracks[loadObject.position] = res.data[0];
                } else {
                    loadObject.name = 'Error, please retry.';
                }

            });
    }

    $scope.removeFromPlaylist = function(index) {
        $scope.playlistTracks.splice(index, 1);
    }

    $scope.createPlaylist = function() {
        $scope.creating = true;
        // Get just the track URIs
        var trackIds = [];
        for(var i=0; i < $scope.playlistTracks.length; i++) {
            trackIds.push($scope.playlistTracks[i].spotify_uri);
        }

        RandomTrack.createPlaylist($scope.playlistName, trackIds)
            .success(function(data) {
                $scope.creating = false;
                alert("Playlist successfully created!");
            });
    }

    $scope.refreshTracks();

});