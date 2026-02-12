<?php

function format_inr($amount) {
    $formatter = new NumberFormatter("en_IN", NumberFormatter::CURRENCY);
    /* return '₹' . number_format($amount, 2); */
    return $formatter->formatCurrency($amount, "INR");
}
function format_currency($amount) {
    $decimal = number_format($amount, 2, '.', '');
    list($intPart, $decPart) = explode('.', $decimal);

    $lastThree = substr($intPart, -3);
    $restUnits = substr($intPart, 0, -3);

    if ($restUnits != '') {
        $restUnits = preg_replace("/\B(?=(\d{2})+(?!\d))/", ",", $restUnits);
        $intPart = $restUnits . "," . $lastThree;
    }

    return $intPart . "." . $decPart;
}

