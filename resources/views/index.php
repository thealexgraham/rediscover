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

    <!-- ANGULAR -->
    <!-- all angular resources will be loaded from the /public folder -->
        <script src="js/controllers/mainCtrl.js"></script> <!-- load our controller -->
        <script src="js/services/randomTrackService.js"></script> <!-- load our service -->
        <script src="js/app.js"></script> <!-- load our application -->
        

</head> 
<!-- declare our angular app and controller --> 
<body class="container" ng-app="trackApp" ng-controller="mainController"> <div class="col-md-8 col-md-offset-2">

    <!-- PAGE TITLE =============================================== -->
    <div class="page-header">
        <h2>ReDiscover your Music</h2>
    </div>

    <div class="refresh">
      <a href="#" ng-click="refreshTracks()">Refresh Tracks</a>
    </div>
    
    <!-- LOADING ICON =============================================== -->
    <!-- show loading icon if the loading variable is set to true -->
    <p class="text-center" ng-show="loading"> <span class="fa fa-refresh fa-3x fa-spin"></span><br>Loading from Spotify...</p>
    
    <!-- THE COMMENTS =============================================== -->
    <!-- hide these comments if the loading variable is true -->

    <div class="tracks" ng-hide="loading">
      <table class="table">
        <thead>
          <tr>
            <th>Track Name</th>
            <th>Album</th>
            <th>Artist</th>
            <th>Add</th>
          </tr>
        </thead>
          <tbody>
            <tr ng-repeat="track in tracks track by $index">
              <td><a href="{{ track.url }}">{{ track.name }}</a></td>
              <td>{{ track.album_name }}</td>
              <td>{{ track.artist_name }}</td>
              <td><a href="#" ng-click="deleteComment(comment.id)" class="text-muted">Add to Playlist</a></td>
            </tr>
          </tbody>
      </table>
    </div>
    
</div> 
</body> 
</html>