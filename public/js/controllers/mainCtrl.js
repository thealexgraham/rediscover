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
    // get all the comments first and bind it to the $scope.comments object
    // use the function we created in our service
    // GET ALL COMMENTS ==============
    RandomTrack.get(5)
        .success(function(data) {
            console.log(data);
            $scope.tracks = data;
            $scope.loading = false;
        });

    $scope.refreshTracks = function() {
        // for (var i=0; i<$scope.tracks.length; i++) {
        //     $scope.replaceTrack(i);
        // }
        $scope.loading = true;
        
        RandomTrack.get(5)
            .success(function(data) {
                $scope.tracks = data;
                $scope.loading = false;
            });
    }
    $scope.addToPlaylist = function(track, index) {
        $scope.playlistTracks.push(track);
        //$scope.tracks.splice(index, 1);

        $scope.replaceTrack(index);

        if ($scope.tracks.length == 0) {
            $scope.refreshTracks();
        }
    }

    $scope.replaceTrack = function(index) {
        // Create a temporary loading object
        var loadObject = {
            name:'Loading...',
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
            .success(function(data) {
                // Replace the load object at the correct position with the retreived track
                $scope.tracks[loadObject.position] = data[0];
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

    // function to handle submitting the form
    // SAVE A COMMENT ================
    $scope.submitComment = function() {
        $scope.loading = true;

        // save the comment. pass in comment data from the form
        // use the function we created in our service
        Comment.save($scope.commentData)
            .success(function(data) {

                // if successful, we'll need to refresh the comment list
                Comment.get()
                    .success(function(getData) {
                        $scope.comments = getData;
                        $scope.loading = false;
                    });

            })
            .error(function(data) {
                console.log(data);
            });
    };

    // function to handle deleting a comment
    // DELETE A COMMENT ====================================================
    $scope.deleteComment = function(id) {
        $scope.loading = true;

        // use the function we created in our service
        Comment.destroy(id)
            .success(function(data) {
                // if successful, we'll need to refresh the comment list
                Comment.get()
                    .success(function(getData) {
                        $scope.comments = getData;
                        $scope.loading = false;
                    });

            })
            .error(function(data) {
            	console.log(data);
            });
    };

});