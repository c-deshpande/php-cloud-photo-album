<?php
    // display all errors on the browser
    error_reporting(E_ALL);
    ini_set('display_errors','On');

    require_once 'demo-lib.php';
    //demo_init(); // this just enables nicer output

    // if there are many files in your Dropbox it can take some time, so disable the max. execution time
    set_time_limit( 0 );

    require_once 'DropboxClient.php';

    /** you have to create an app at @see https://www.dropbox.com/developers/apps and enter details below: */
    /** @noinspection SpellCheckingInspection */
    $dropbox = new DropboxClient( array(
        'app_key' => "",      // Put your Dropbox API key here
        'app_secret' => "",   // Put your Dropbox API secret here
        'app_full_access' => false,
    ) );


    /**
     * Dropbox will redirect the user here
     * @var string $return_url
     */
    $return_url = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . "?auth_redirect=1";

    // first, try to load existing access token
    $bearer_token = demo_token_load( "bearer" );

    if ( $bearer_token ) {
        $dropbox->SetBearerToken( $bearer_token );
        //echo "loaded bearer token: " . json_encode( $bearer_token, JSON_PRETTY_PRINT ) . "\n";
    } elseif ( ! empty( $_GET['auth_redirect'] ) ) // are we coming from dropbox's auth page?
    {
        // get & store bearer token
        $bearer_token = $dropbox->GetBearerToken( null, $return_url );
        demo_store_token( $bearer_token, "bearer" );
    } elseif ( ! $dropbox->IsAuthorized() ) {
        // redirect user to Dropbox auth page
        $auth_url = $dropbox->BuildAuthorizeUrl( $return_url );
        die( "Authentication required. <a href='$auth_url'>Continue.</a>" );
    }

    if(!empty($_POST['submit'])) {
        $target_directory = "uploads/";
        $filename = $_FILES['fileToUpload']['name'];

        if(!file_exists('uploads')) {
            mkdir('uploads', 0777, true);
        }
        if(move_uploaded_file($_FILES['fileToUpload']['tmp_name'], $target_directory.$filename)) {
            
        } else {
            echo 'Error while uploading the file, try again';
        }

        $dropbox->UploadFile($target_directory.$filename);
    }

    if(!empty($_GET['delete'])) {
        $dropbox->Delete($_GET['delete']);
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Using Cloud Storage</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat&display=swap" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            margin: 0;
            background-color: #1f2833;
            font-family: 'Montserrat', sans-serif;
            position: relative;
            min-height: 100vh;
        }

        main {
            display: flex;
            color: white;
            flex-grow: 1;
        }

        content {
            flex-basis: 80%;
            background-color: #151c23;
            flex-grow: 1;
        }

        sidebar {
            flex-basis: 20%;
            background-color: #1f2833;
            flex-grow: 1;
            min-height: 0;
            position: relative;
            order: -1;
            padding: 10px;
        }

        form {
            border: 2px dashed #eee;
			width: 50%;
			padding: 10px 10px;
			margin: auto;
			text-align: center;
        }

        #downloadImage {
            color: #FFF;
            text-decoration: none;
        }

        button {
            font-family: 'Montserrat', sans-serif;
            text-transform: uppercase;
            background-color: #4b525b;
            padding: 5px;
            cursor: pointer;
            color: white;
            border: none;
        }

        .dataitemcontainer {
            position: absolute; 
            padding:10px; 
            top: 0; 
            bottom: 0; 
            left: 0; 
            right: 0;
            overflow: auto;
        }

        a {
            color: #FFF;
            font-weight: bold;
        }

        @media all and (max-width: 640px) {
            main {
                flex-direction: column;
                flex-grow: 1;
            }
        
            nav {
                order: 0;
            }

            sidebar {
                flex-basis: 50%;
            }

            content {
                flex-basis: 50%;
            }
        }
    </style>
</head>
<body>
    <main>
    <content>
            <h3 align="center">File Upload using Cloud Storage</h3>
            <form action="album.php" method="post" enctype="multipart/form-data">
                <h4>Upload an image file</h2>
                <input type="file" name="fileToUpload" id="fileToUpload">
                <br>
                <br>
                <input type="submit" value="Upload" name="submit">
            </form>
            <?php

                $latest_downloaded_image_path = "";
                if(!empty($_GET['download'])) {
                    $download = explode(",", $_GET['download']);

                    if(file_put_contents($download[1], file_get_contents($download[0]))) {
                        $latest_downloaded_image_path = $download[1];
                    }
                }
            ?>
            <img src="<?php print $latest_downloaded_image_path ?>" id="thumbnail" width="300px" height="300px" style="display: block; margin: auto; margin-top: 15%; border: 0;" onerror='this.style.display = "none"'>
        </content>
        <sidebar>
            <div class="dataitemcontainer">
                <h3 align="center">List of Images</h3>
                <?php
                    $dropbox_files = $dropbox->GetFiles("", false);
                    if(!empty($dropbox_files)) {
                        foreach($dropbox_files as $k => $v) {
                            $img_link = $dropbox->GetLink($v, $preview=false);?>
                            <div>
                            <br>
                            <a href="album.php?download=<?php print $img_link ?>,<?php print $k ?>"><?php print $k; ?><a>
                            <br>
                            <br>
                            <a href="album.php?delete=/<?php print $k ?>"><button>Delete</button></a>
                            <div>
                            <br>
                            <hr>
                        <?php
                        }
                    }
                ?>
            <div>
        </sidebar>
    </main>
</body>
</html>