Generate an Atom RSS feed from Google Play Store app reviews.

It uses Google's [Reply to Reviews API](https://developers.google.com/android-publisher/reply-to-reviews) to generate the feed based on apps you own. (It does _not_ work with apps you don't control; this is Google's restriction.)

# Installation

1. Download [Composer](https://getcomposer.org/) if you don't have it already.
2. Run `composer install` to install the Google API client.
3. Follow Google's GPS API [Getting Started Guide](https://developers.google.com/android-publisher/getting_started#using_a_service_account) to set up a "service account." (Beware, Google's OAuth documentation is a labyrinth, and they change it every year.)

  Here's what it says as of May 21, 2016:

  > 1. Go to the API Access page on the Google Play Developer Console.
  >2. Under Service Accounts, click Create Service Account.
  >3. Follow the instructions on the page to create your service account.
  >4. Once you’ve created the service account on the Google Developers Console, click Done. The API Access page automatically refreshes, and your service account will be listed.
  >5. Click Grant Access to provide the service account the necessary rights to perform actions.

4. While setting up your service account, you'll download a JSON file containing your secret credentials. Save that file as `client_secret.json` where the PHP file can reach it.  
  **Make sure your users cannot access this file.** 
5. Open index.php in your RSS reader, with an URL parameter specifying the package you want to follow, e.g.:

  ```
  index.php?package=com.example.app
  ```

