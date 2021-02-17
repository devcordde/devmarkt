<?php

if(isset($_POST['text'])) {
    echo strlen(testInput($_POST['text']));
}

function testInput($data): string
{
    return htmlspecialchars(stripslashes(trim($data)));

}