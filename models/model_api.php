<?php
include("models\API.php");

//append api key for proper call formatting
$riotAPI = "api_key=" . $APIKEY;

//get the player's puuid from API
function getPuuid($gameName, $tagLine) {
    global $riotAPI;
    $puuidCall = "https://americas.api.riotgames.com/riot/account/v1/accounts/by-riot-id/" . $gameName . "/" . $tagLine . "?" . $riotAPI;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $puuidCall);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
        return null;
    }

    curl_close($ch);

    $data = json_decode($response, true);

    $puuid = $data["puuid"];

    return $puuid;
}

//get the player's recent match history ID's using previously obtained puuid
function getMatchIDs($puuid) {
    global $riotAPI;
    $matchCall = "https://americas.api.riotgames.com/tft/match/v1/matches/by-puuid/" . $puuid . "/ids?start=0&count=25&" .  $riotAPI;

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $matchCall);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo 'Curl error: ' . curl_error($ch);
        return null;
    }

    curl_close($ch);

    $temp = str_replace("[", "", $response);
    $temp = str_replace("]", "", $temp);
    $temp = str_replace('"', "", $temp);
    $matches = explode(",",$temp);

    return $matches;
}

//use match history ID's to get individual results per match
function getMatchData($matches, $puuid) {
    global $riotAPI;
    $placements = [];
    $datetimes = [];

    foreach ($matches as $match) {
        $mDataCall = "https://americas.api.riotgames.com/tft/match/v1/matches/";
        $mDataCall .= $match . "?" . $riotAPI;

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $mDataCall);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
            return null;
        }

        curl_close($ch);

        // Decode JSON response
        $data = json_decode($response, true);

        // Check if JSON decoding was successful
        if ($data === null) {
            echo "Error: Failed to decode JSON for match ID: $match<br>";
            continue;
        }

        // Check if 'metadata' and 'participants' keys exist
        if (!isset($data['metadata']) || !isset($data['metadata']['participants'])) {
            echo "Error: Missing 'metadata' or 'participants' in match ID: $match<br>";
            continue;
        }

        $participants = $data['metadata']['participants'];
        //this value is changed due to the JSON format having different participant orders each time
        $matchingIndex = -1;

        // Find the index of the matching PUUID
        foreach ($participants as $index => $participant) {
            if ($participant === $puuid) {
                $matchingIndex = $index;
                break;
            }
        }

        if ($matchingIndex !== -1) {
            // Check if 'info' and 'participants' keys exist
            if (!isset($data['info']['participants'][$matchingIndex])) {
                echo "Error: Missing 'info' or 'participants' data for match ID: $match<br>";
                continue;
            }

            $placement = $data['info']['participants'][$matchingIndex]['placement'];
            $game_datetime = $data['info']['game_datetime'];
            array_push($placements, $placement);
            array_push($datetimes, $game_datetime);
        }
    }

    return [$placements, $datetimes];
}

//format and create a table using the placements of the user
function formatTable($placements, $matchNums, $summName, $tagLine) {
    //image formatting info
    $img_width = 600;
    $img_height = 300;
    $margins = 35;
    $graph_width = $img_width - ($margins * 2);
    $graph_height = $img_height - ($margins * 2);

    //create image and fill background
    $img = imagecreate($img_width, $img_height);
    $background_color = imagecolorallocate($img, 77, 77, 77);
    imagefill($img, 0, 0, $background_color);

    //used later to calculate position for placement markers
    $center_y_last = 0;
    $center_x_last = 0;

    //create inner rectangle for use as the graph area
    imagefilledrectangle($img, $margins-5, $margins-5, ($img_width - $margins)+5, ($img_height - $margins)+5, imagecolorallocate($img, 64, 64, 64));

    $line_color = imagecolorallocate($img, 120, 120, 120);
    $label_color = imagecolorallocate($img, 220, 220, 220);

    // Draw title at the top
    $num_matches = $matchNums;
    $numDiff = count($placements) - $num_matches;
    if($numDiff == 0) {
        $num_matches = count($placements);
    }
    else {
        $numDiff--;
        array_splice($placements, 0, $numDiff);
    }
    $title = "Last $num_matches Matches";
    $font = 5; // Larger built-in font
    $title_width = imagefontwidth($font) * strlen($title);
    $title_x = ($img_width - $title_width) / 2;
    $title_y = 8;
    imagestring($img, $font, $title_x, $title_y, $title, $label_color);

    // Draw 8 horizontal lines to divide the graph area into 8 sections and add labels
    for ($i = 0; $i < 8; $i++) {
        $y = $margins + ($graph_height / 7) * $i;
        $x1 = $margins-5;
        $x2 = $img_width - $margins+5;
        imageline($img, $x1, $y, $x2, $y, $line_color);

        // Draw number labels (1 at top, 8 at bottom)
        $label = (string)($i + 1);
        $font_label = 3; // Built-in font size for labels
        $label_x = 5; // Padding from left edge
        $label_y = $y - imagefontheight($font_label) / 2;
        imagestring($img, $font_label, $label_x, $label_y, $label, $label_color);
    }

    $num_points = $num_matches;
    if ($num_points < 2) $num_points = 2; // Prevent division by zero

    for ($i = 0; $i < $num_matches; $i++) {
        // X: Evenly spaced across graph area
        $center_x = $margins + ($graph_width / ($num_matches - 1)) * $i;

        // Y: Placement scaled to fit graph area (1 is top, 8 is bottom)
        $max_placement = 8;
        $placement = $placements[$i];
        $center_y = $margins + (($placement - 1) / ($max_placement - 1)) * $graph_height;

        // placement marker values
        $c_width = 10;
        $c_height = 10;
        $c_color = imagecolorallocate($img, 220, 220, 220);
        $line_color = imagecolorallocate($img, 220, 220, 220);

        // create placement markers
        imagefilledellipse($img, $center_x, $center_y, $c_width, $c_height, $c_color);

        if ($i != 0) {
            imageline($img, $center_x_last, $center_y_last, $center_x, $center_y, $line_color);
        }
        //roll values over for next marker
        $center_y_last = $center_y;
        $center_x_last = $center_x;
    }
    //output image
    $file = "images/" . $summName . $tagLine . ".png";
    imagepng($img, $file);

    //deleted for memory efficiency
    imagedestroy($img);
}

//determine the average placement of the user
function averagePlace($placements) {
    $running = 0;
    for ($i = 0; $i < count($placements); $i++) {
        $running += $placements[$i];
    }
    $out = $running / count($placements);
    return $out;
}

//determine the number of first place placements of the user
function firstPlaces($placements) {
    $ttl = 0;
    for ($i = 0; $i < count($placements); $i++) {
        if($placements[$i] == 1) {
            $ttl++;
        }
    }
    return $ttl;
}