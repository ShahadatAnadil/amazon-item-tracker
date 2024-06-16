<?php
$a1 = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
$a2 = [0, 1, 3, 5, 7, 9, 2, 4, 6, 8, 10];
$a3 = [10, 9, 8, 7, 6, 5, 4, 3, 2, 1, 0];
$a4 = [0, 0, 1, 1, 2, 2, 3, 3, 4, 4, 5, 5, 6, 6, 7, 7, 8, 8, 9, 9, 10, 10];
function processArray($arr) {
    echo "<br>Processing Array:<br><pre>" . var_export($arr, true) . "</pre>";
    echo "<br>Odds output:<br>";
    //start edits
    //note: use the $arr variable to iterate over, don't directly touch $a1-$a4
    //we start by iterating over each number in the array. We are taking each number from the array and dividing by 2 and getting the remainder. The '!=0' check if the remainder is not equal to zero, if remainder is not zero, the number is odd. If the number is odd (true), it will print out the number. 
    //sha38 5-31-2024
    foreach ($arr as $value) {
        if ($value % 2 !=0) {
           
            echo $value . " ";
        }
    }

    //end edits
}
echo "Problem 1: Odd Output<br>";
?>
<table>
    <thread>
        <th>A1</th>
        <th>A2</th>
        <th>A3</th>
        <th>A4</th>
    </thread>
    <tbody>
        <tr>
            <td>
                <?php processArray($a1); ?>
            </td>
            <td>
                <?php processArray($a2); ?>
            </td>
            <td>
                <?php processArray($a3); ?>
            </td>
            <td>
                <?php processArray($a4); ?>
            </td>
        </tr>
</table>
<style>
    table {
        border-spacing: 2em 3em;
        border-collapse: separate;
    }

    td {
        border-right: solid 1px black;
        border-left: solid 1px black;
    }
</style>