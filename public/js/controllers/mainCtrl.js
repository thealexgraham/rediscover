angular.module('mainCtrl', [])

// inject the Comment service into our controller
.controller('mainController', function($scope, $http, RandomTrack) {
	
	// Initialize variables
	$scope.username = window.username; 
	$scope.creating = false;
	$scope.playlistMessage = false;
	$scope.playlistTracks = [];
	$scope.playlistName = 'ReDiscover Playlist';

	// Get 5 new tracks and replace all of the random ones
	$scope.refreshTracks = function() {

		// Create and add 5 loading tracks
		$scope.tracks = [];
		for (var i=0; i<5; i++) {
			$scope.tracks.push($scope.createLoadingTrack());
		}
		
		// Get five tracks and put them in the tracks array
		RandomTrack.get(5)
			.success(function(res) {
				if (res.success) {
					$scope.tracks = res.data;
				} else {
					// Show error track
					$scope.tracks = [];
					$scope.tracks.unshift($scope.createErrorTrack());
				}
			})
			.error(function(res) {
				// Show error track
				$scope.tracks = [];
				$scope.tracks.unshift($scope.createErrorTrack());
			});
	}

	// Create a track that shows an error
	$scope.createErrorTrack = function() {
		var problemObject = {
			name:'There was a problem, please try again.',
			album_name:'---',
			artist_name:'---',
			album_img:'img/blank.png'
		}
		return problemObject;
	}

	// Create a track that shows the page is loading a new track
	$scope.createLoadingTrack = function() {
		var loadingObject = {
			name:'Retreiving new track...',
			album_name:'---',
			artist_name:'---',
			album_img:'img/blank.png',
			loading:true
		}
		return loadingObject;
	}

	// Move a track from the list of random tracks to the
	// playlist at the bottom
	$scope.addToPlaylist = function(track, index) {

		// If we were showing a created message, stop
		$scope.playlistMessage = false;

		// Add the playlist to the beginning of the track
		$scope.playlistTracks.unshift(track);

		// Get a new track from Spotify to replace that track in the random list
		$scope.replaceTrack(index);
	}

	// Replace a specific track in the random list with a new one from spotify
	$scope.replaceTrack = function(index) {

		// Create a temporary loading object
		var loadObject = $scope.createLoadingTrack();
		loadObject.position = $scope.tracks.length - 1;

		// Remove this track
		$scope.tracks.splice(index, 1);

		// Decrement all the positions of any other loading objects so we load them in the right place
		for(var i=0; i < $scope.tracks.length;i++) {
			if($scope.tracks[i].loading)
				$scope.tracks[i].position -= 1;
		}

		// Push our new loading object at the end
		$scope.tracks.push(loadObject);

		// Get a new random track
		RandomTrack.get(1)
			.success(function(res) {
				if (res.success) {
					// Replace the load object at the correct position with the retreived track
					$scope.tracks[loadObject.position] = res.data[0];
				} else {
					loadObject.name = 'Error, please retry.';
				}

			})
			.error(function(error) {
				loadObject.name = 'Error, please retry.';
			});
	}

	// Just remove the track from the playlist
	$scope.removeFromPlaylist = function(index) {
		$scope.playlistTracks.splice(index, 1);
	}

	// Take the playlist and send it to Laravel to be sent to Spotify
	$scope.createPlaylist = function() {
		$scope.creating = true;
		// Get just the track URIs
		var trackIds = [];
		for(var i=0; i < $scope.playlistTracks.length; i++) {
			trackIds.push($scope.playlistTracks[i].spotify_uri);
		}

		// Create the playlist in spotify
		RandomTrack.createPlaylist($scope.playlistName, trackIds)
			.success(function(data) {
				// Reset the playlist name, show the playlist message and remove the tracks
				$scope.playlistName = "ReDiscover Playlist";
				$scope.playlistMessage = true;
				$scope.creating = false;
				$scope.playlistTracks = [];
			});
	}

	// Refresh the tracks at the very beginning
	$scope.refreshTracks();

});