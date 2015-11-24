angular.module('mainCtrl', [])

// inject the Comment service into our controller
.controller('mainController', function($scope, $http, RandomTrack) {
    // object to hold all the data for the new comment form
    $scope.randomTracks = {};

    // loading variable to show the spinning loading icon
    $scope.loading = true;
    $scope.playlistTracks = [];

    // get all the comments first and bind it to the $scope.comments object
    // use the function we created in our service
    // GET ALL COMMENTS ==============
    RandomTrack.get()
        .success(function(data) {
            console.log(data);
            $scope.tracks = data;
            $scope.loading = false;
        });

    $scope.refreshTracks = function() {
        $scope.loading = true;
        
        RandomTrack.get()
            .success(function(data) {
                $scope.tracks = data;
                $scope.loading = false;
            });
    }
    $scope.addToPlaylist = function(track, index) {
        $scope.playlistTracks.push(track);
        $scope.tracks.splice(index, 1);

        if ($scope.tracks.length == 0) {
            $scope.refreshTracks();
        }
    }

    $scope.removeFromPlaylist = function(index) {
        $scope.playlistTracks.splice(index, 1);
    }

    $scope.doStuff = function(idx) {
        alert(idx);
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