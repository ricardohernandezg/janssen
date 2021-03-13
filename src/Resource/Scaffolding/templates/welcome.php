<?php 

$lobster = Janssen\Resource\EmbedFonts::$lobster;

?>
<html>
<title>Welcome to Janssen!</title>
<style>
    @font-face {
        font-family: 'Lobster';
        src: url(data:font/truetype;charset=utf-8;base64,<?= $lobster ?>) format('truetype');
        font-weight: normal;
        font-style: normal;
    }

    .container {
        min-height: 10em;
        position: relative;
        height: 93%;
    }

    .name {
        font-family: 'Lobster';
        font-size: 100px;
        font-weight:500;
        color: #ba0505;
    }
    
    .welcome-to {
        font-family: sans-serif;
        font-size: 20px;
    }

    .ls-wide {
        letter-spacing: 1em;
    }

    .full-p {
        margin: 0;
        top: 40%;
        text-align: center;
        position: relative;
    }

    .deco {
        position: absolute;
        margin: 5%;
        border: 1px solid black;
        width: 90%;
        height: 90%;

    }

</style>
<body style="background-color: lightgray">

<div class="container">
    <div class="deco"></div>
    <p class="full-p" >
        <span class="welcome-to ls-wide">welcome t</span><span class="welcome-to">o</span>
        <br/>
        <span style="padding-top: 25px;" class="name">Janssen</span>
        <br/>
        <span class="welcome-to" style="padding-top: 25px;">&mdash;&mdash;&mdash;&mdash;o&mdash;&mdash;&mdash;&mdash;</span>
    </p>
</div>


</body>
</html>