## ReDiscover

Rediscover music you haven't listened to in ages.

Spotify ReDiscover will show you random tracks from your saved music library. Pick your favorites and add them to a playlist to Spotify and get listening!

## Information
This is a sample app I wrote to show off some of my web development skills.
The backend, which communicates with the Spotify API is done in Laravel 5. The frontend uses AngularJS.

Once the user is authenticated with Spotify, they are presented with 5 random tracks from their "Saved" playlist. The user can then choose tracks to be added to a list, which can eventually be published to their Spotify account as a playlist. As the random tracks are selected or removed, new ones are added in their place. The user can also refresh and get five new choices.

## Live Demo

Try out a live demo at [rediscover.alexgraham.net](http://rediscover.alexgraham.net).

Note that this requires SAVED tracks in your Spotify account. If you do not have saved tracks, or you do not have a Spotify account at all, use the following account:

username: faketestuser password: Test%%%

## Code Samples

- https://github.com/thealexgraham/rediscover/blob/master/app/Http/Controllers/SpotifyAuthController.php - Handles the Spotify authentication process
- https://github.com/thealexgraham/rediscover/blob/master/app/Http/Controllers/SpotifyController.php – Handles incoming requests from the frontend
- https://github.com/thealexgraham/rediscover/blob/master/app/SpotifyService.php - Does the actual calls and token refresh
- https://github.com/thealexgraham/rediscover/blob/master/resources/views/index.php - The frontend HTML
- https://github.com/thealexgraham/rediscover/blob/master/public/js/controllers/mainCtrl.js - The main angular controller



