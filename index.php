<?php include("includes/header.php"); ?>
<?php include("models/model_api.php"); ?>
<?php

//clear images folder if no post variables
if($_POST == array()) {
    $files = glob('images/*.png');
    foreach($files as $file){
        if(is_file($file)) {
            unlink($file);
        }
    }
}

//check for number of matches to make graph for
if (isset($_POST["matchBtn10"])) {
    $matchNums = 10;
}
elseif (isset($_POST["matchBtn15"])) {
    $matchNums = 15;
}
elseif (isset($_POST["matchBtn20"])) {
    $matchNums = 20;
}
elseif (isset($_POST["matchBtn25"])) {
    $matchNums = 25;
}
else {
    $matchNums = 0;
}

//if placements already exist, save them instead of re-calling api
if(isset($_POST["placements"])) {
    $placements = json_decode($_POST["placements"], true);
}

//filter post variables for searching, and handle api calls accordingly
if (isset($_POST["summName"]) && isset($_POST["tagLine"])) {
    $summName = filter_input(INPUT_POST,"summName");
    $tagLine = filter_input(INPUT_POST,"tagLine");
    $imgPath = "images/" . $summName . $tagLine . ".png";
    $posted = True;

    //handle checkbox input
    if (isset($_POST["refreshCheck"])) {
        $refresh = True;
    }
    else {
        $refresh = False;
    }

    //reduce API calls using existing data
    if($matchNums != 0) {
        //encode json for hidden input
        $jsonPlace = json_encode($placements);
        formatTable($placements, $matchNums, $summName, $tagLine);
    }
    elseif(!file_exists($imgPath) || $refresh) {
        if(!isset($placements)) {
            $puuid = getPuuid($summName, $tagLine);
            $matches = getMatchIDs($puuid);
            $matchData = getMatchData($matches, $puuid);
            $placements = $matchData[0];
        }
        //encode json for hidden input
        $jsonPlace = json_encode($placements);
        formatTable($placements, 20, $summName, $tagLine);
        
    }
}
else {
    //fake path for "if(fileexists())"
    $imgPath = "images/error.png";
    $posted = False;
}
if(isset($placements)) {
    $firstPlaces = firstPlaces($placements);
    $avgPlacement = averagePlace($placements);
}

//clear post variables
unset($_POST);

?>
    <div id="search">
        <div id="searchLabelBar">
            <div class="searchLabel">Stat Tracker</div>
        </div>
        <form method="POST" id="searchForm">
            <div class="searchInfo">Summoner Name:</div>
            <div class="searchInput"><input type="text" id="summName" name="summName"></div>
            <div class="searchInfo">Tagline:</div>
            <div class="searchInput"><input type="text" id="tagLine" name="tagLine"></div>
            <div class="searchInfo">Refresh?:</div>
            <div class="searchInput"><input type="checkbox" id="refreshCheck" name="refreshCheck"></div>
            <div id="searchBtnBox"><button type="submit" id="searchBtn" name="searchBtn">Search</button></div>
        </form>
    </div>
<!-- refills user's values on post -->
<div class="container">
    <div id="info">
        <div class="info-block">
            <div class="info-label">Summoner Name</div>
            <div class="info-value"><?= isset($summName) ? $summName : "N/A" ?></div>
        </div>
        <div class="info-block">
            <div class="info-label">Tagline</div>
            <div class="info-value"><?= isset($tagLine) ? $tagLine : "N/A" ?></div>
        </div>
        <div class="info-block">
            <div class="info-label">Average Placement (25 Games)</div>
            <div class="info-value"><?= isset($avgPlacement) ? $avgPlacement : "N/A" ?></div>
        </div>
        <div class="info-block">
            <div class="info-label">First Places (25 Games)</div>
            <div class="info-value"><?= isset($firstPlaces) ? $firstPlaces : "N/A" ?></div>
        </div>
    </div>
    <form method="POST" id="matches">
        <div id="matchLabel" class="matchBtn">Matches</div>
        <br>
        <!-- hide until user data inputted -->
        <?php if($posted) {?>
        <div id="matchNumsLabel" class="matchBtn">Number of Matches</div>
        <div id="matchNums">
            <input type="hidden" id="summName" name="summName" value="<?=$summName?>">
            <input type="hidden" id="tagLine" name="tagLine" value="<?=$tagLine?>">
            <input type="hidden" id="placements" name="placements" value="<?=$jsonPlace?>">
            <button type="submit" id="matchBtn10" name="matchBtn10" class="matchBtn">10</button>
            <button type="submit" id="matchBtn15" name="matchBtn15" class="matchBtn">15</button>
            <button type="submit" id="matchBtn20" name="matchBtn20" class="matchBtn">20</button>
            <button type="submit" id="matchBtn25" name="matchBtn25" class="matchBtn">25</button>
        </div>
        <?php } ?>
        <div id="matchTable">
            <!-- image handling -->
            <?php if(file_exists($imgPath)){?>
            <img id="graph" src="<?= $imgPath ?>" alt="graph">
            <?php }
            else {?>
                <div id="matchLabel" class="matchBtn">Search For Your Matches Above</div>
            <?php } ?>
        </div>
    </form>
</div>
<?php include("includes/footer.php"); ?>