<!doctype html> 
<html lang="en"> 
	<head> <meta charset="UTF-8"> <title>ReDiscover Spotify</title>

		<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css">
		<link rel="stylesheet" href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css">
		<style>
			body { padding-top:30px; }
		</style>

		<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
		<script src="//ajax.googleapis.com/ajax/libs/angularjs/1.2.8/angular.min.js"></script> <!-- load angular -->
		<link rel="stylesheet" href="http://css-spinners.com/css/spinner/throbber.css" type="text/css">

		<script src="js/controllers/mainCtrl.js"></script> 
		<script src="js/services/randomTrackService.js"></script>
		<script src="js/app.js"></script>
			

	</head> 

	<body class="container" ng-app="trackApp" ng-controller="mainController">
		
		<!-- Logout / User Info -->
		<div class="header col-md-10 col-md-offset-1 text-right">
			Logged in as {{ username }} | <a href="spotify/logout">Logout</a>
		</div>

		<div class="col-md-10 col-md-offset-1">

			<div class="page-header">
				<h2>ReDiscover your Music</h2>
			</div>

			<div class="refresh-button text-right">
				<button class="" ng-click="refreshTracks()">More Tracks</button>
			</div>

			<div class="tracks">
				<table class="table">
				<colgroup>
					<col>
					<col style="width: 35%;">
					<col style="width: 30%;">
					<col style="width: 20%;">
					<col>
				</colgroup>
				<thead>
					<tr>
						<th></th>
						<th>Track Name</th>
						<th>Album</th>
						<th>Artist</th>
						<th></th>
					</tr>
				</thead>
					<tbody>
						<tr ng-repeat="track in tracks track by $index">
							<td><img ng-src="{{track.album_img}}" width="42" height="42"></td>
							<td><a href="{{ track.url }}"><strong>{{ track.name }}</strong></a> <span class="fa fa-refresh fa-1x fa-spin" ng-show="track.loading"></span></td>
							<td><a href="{{ track.album_url }}" class="text-muted">{{ track.album_name }}</a></td>
							<td><a href="{{ track.artist_url }}" class="text-muted">{{ track.artist_name }}</a></td>
							<td>
								<div style="width:70px">
									<button ng-click="addToPlaylist(track, $index)" class="text-success btn-success">+</button> |
									<button ng-click="replaceTrack($index)" class="text-danger btn-danger">x</button>
								</div>
							</td>
						</tr>
					</tbody>
				</table>
			</div>

			
			<div class="page-header">
				<h3>Playlist Creation</h3>
			</div>

			<div class="row">
				<div class="col-md-6"><strong>{{playlistName}}</strong></div>
				<div class="col-md-6 text-right"> <button class="" ng-click="createPlaylist()">Add Playlist to Spotify</button></div>
			</div>

			<p class="text-center" ng-show="uploading"> <span class="fa fa-refresh fa-1x fa-spin"></span><br>Loading from Spotify...</p>
			
			<div class="playlist" ng-hide="creating">
				<table class="table">
				<colgroup>
					<col style="width:42px">
					<col style="width: 40%;">
					<col style="width: 35%;">
					<col style="width: 30%;">
					<col>
				</colgroup>
				<thead>
					<tr>
						<th></th>
						<th>Track Name</th>
						<th>Album</th>
						<th>Artist</th>
						<th>Edit</th>
					</tr>
				</thead>
					<tbody ng-hide="creating">
					<tr ng-repeat="track in playlistTracks track by $index">
						<td><img ng-src="{{track.album_img}}" width="42" height="42"></td>
						<td><a href="{{ track.url }}"><strong>{{ track.name }}</strong></a></td>
						<td><a href="{{ track.album_url }}" class="text-muted">{{ track.album_name }}</a></td>
						<td><a href="{{ track.artist_url }}" class="text-muted">{{ track.artist_name }}</a></td>
						<td><button ng-click="removeFromPlaylist($index)" class="btn-danger">X</button></td>
					</tr>
					</tbody>
				</table>
			</div>

			<p class="text-center" ng-show="creating"> <span class="fa fa-refresh fa-3x fa-spin"></span><br>Creating Playlist...</p>
			
			<div class="text-center" ng-show="playlistMessage">
				<p><h3>Playlist Created! </strong></h3><p>Note the playlist may take some time to show up in Spotify.</p>
			</div>
	
		</div>
	</body> 

	<script>
	    var username = '<?php echo $username; ?>';
	</script>

</html>