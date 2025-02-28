<?php
function cuu_chuong($number)
{
    $table = "<table border='1' cellspacing='0' cellpadding='5'>";
    $table .= "<tr>";
    for ($i = 1; $i <= 10; $i++) {
        $result = $number * $i;
        $table .= "<td>" . $number . " x " . $i . " = " . $result . "</td>";
    }
    $table .= "</tr>";
    $table .= "</table>";
    return $table;
}
echo cuu_chuong(5);
?>