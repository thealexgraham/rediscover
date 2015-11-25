<!doctype html> 
<html lang="en"> <head> <meta charset="UTF-8"> <title>Laravel and Angular Comment System</title>

    <!-- CSS -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.0/css/bootstrap.min.css"> <!-- load bootstrap via cdn -->
    <link rel="stylesheet" href="//netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.min.css"> <!-- load fontawesome -->
    <style>
        body        { padding-top:30px; }
        form        { padding-bottom:20px; }
        .comment    { padding-bottom:20px; }
    </style>
    
    <!-- JS -->
    <script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
    <script src="//ajax.googleapis.com/ajax/libs/angularjs/1.2.8/angular.min.js"></script> <!-- load angular -->
    <link rel="stylesheet" href="http://css-spinners.com/css/spinner/throbber.css" type="text/css">

    <script src="lib/soundmanager2/script/soundmanager2-nodebug-jsmin.js"></script>

    <!-- ANGULAR -->
    <!-- all angular resources will be loaded from the /public folder -->
        <script src="js/controllers/mainCtrl.js"></script> <!-- load our controller -->
        <script src="js/services/randomTrackService.js"></script> <!-- load our service -->
        <script src="js/app.js"></script> <!-- load our application -->
        

</head> 
<!-- declare our angular app and controller --> 
<body class="container" ng-app="trackApp" ng-controller="mainController"> <div class="col-md-10 col-md-offset-1">

    <!-- PAGE TITLE =============================================== -->
    <div class="page-header">
        <h2>ReDiscover your Music</h2>
    </div>

    <div class="refresh-button text-right">
      <button class="btn" ng-click="refreshTracks()">More Tracks</button>
    </div>

    <!-- THE COMMENTS =============================================== -->
    <!-- hide these comments if the loading variable is true -->

    <div class="tracks">
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
          <tbody ng-hide="loading">
            <tr ng-repeat="track in tracks track by $index">
              <td><img ng-src="{{track.album_img}}" width="42" height="42"></td>
              <td><a href="{{ track.url }}"><strong>{{ track.name }}</strong></a></td>
              <td><a href="{{ track.album_url }}" class="text-muted">{{ track.album_name }}</a></td>
              <td><a href="{{ track.artist_url }}">{{ track.artist_name }}</a></td>
              <td><a href="#" ng-click="addToPlaylist(track, $index)" class="text-muted">+</a>
                / <a href="#" ng-click="replaceTrack($index)" class="text-muted">x</a>
              </td>
            </tr>
          </tbody>
      </table>
    </div>
    
    <!-- LOADING ICON =============================================== -->
    <!-- show loading icon if the loading variable is set to true -->
    <p class="text-center" ng-show="loading"> <span class="fa fa-refresh fa-3x fa-spin"></span><br>Loading from Spotify...</p>

    <div class="page-header">
        <h3>Playlist Creation</h3>
    </div>

    <div class="row">
        <div class="col-md-6"><strong>{{playlistName}}</strong></div>
        <div class="col-md-6 text-right"> <button class="btn" ng-click="createPlaylist()">Add Playlist to Spotify</button></div>
    </div>     


<!-- LOADING ICON =============================================== -->
    <!-- show loading icon if the loading variable is set to true -->
    <p class="text-center" ng-show="uploading"> <span class="fa fa-refresh fa-3x fa-spin"></span><br>Loading from Spotify...</p>
    
    <!-- THE COMMENTS =============================================== -->
    <!-- hide these comments if the loading variable is true -->

    <div class="playlist" ng-hide="creating">
      <table class="table">
        <colgroup>
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
          <tbody ng-hide="loading">
            <tr ng-repeat="track in playlistTracks track by $index">
              <td><img ng-src="{{track.album_img}}" width="42" height="42"></td>
              <td><a href="{{ track.url }}"><strong>{{ track.name }}</strong></a></td>
              <td><a href="{{ track.album_url }}" class="text-muted">{{ track.album_name }}</a></td>
              <td><a href="{{ track.artist_url }}">{{ track.artist_name }}</a></td>
              <td><a href="#" ng-click="removeFromPlaylist($index)" class="text-muted">X</a></td>
            </tr>
          </tbody>
      </table>
    </div>

    <p class="text-center" ng-show="creating"> <span class="fa fa-refresh fa-3x fa-spin"></span><br>Creating Playlist...</p>

</div> 
</body> 
</html>