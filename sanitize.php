<?php

function sanitize($string){
    
    return preg_replace("/[\t\n\r=\"%;*<>$\/]/i", "", $string);

}
