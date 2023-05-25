<?php
    // If we have GET vars, put them in an array.
    if(isset($_GET))
    {
        $get = $_GET;
    }
    else
    {
        $get = array();
    }
    $book = 1;
    $page = 1;
    if(count($get) > 0)
    {
        if(isset($get['book']))
        {
            $book = $get['book'];
        }
        if(isset($get['page']))
        {
            $page = $get['page'];
        }
    }
    $data = array('book' => $book, 'page' => $page);
    $dataAttr = 'data-tome="' . htmlspecialchars(json_encode($data)) . '"';
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Starfall Tome</title>
        <link rel="stylesheet" type="text/css" href="css/tome.css">
        <script src="https://code.jquery.com/jquery-latest.min.js"></script>
        <script>window.jQuery || document.write('<script src="js/jquery-3.7.0.js"><\/script>')</script>
        <script id="tome-script" src="js/tome.js" <?php echo $dataAttr; ?>></script>
    </head>
    <body>
        <content>
            <div class="tome">
                <h1 id="title"></h1>
                <br />
                <div id="text">
                    
                </div>
            </div>
        </content>
    </body>
</html>
