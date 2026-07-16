<?php

session_start();

$questions = $_SESSION['generated_questions'] ?? [];

if (empty($questions)) {
    die("هیچ آزمونی برای دانلود وجود ندارد.");
}

require 'vendor/autoload.php';

$html = '
<!DOCTYPE html>
<html dir="rtl" lang="fa">
<head>
<meta charset="UTF-8">
<style>

@font-face{
    font-family: Vazirmatn;
    src:url("fonts/Vazirmatn-Regular.ttf") format("truetype");
}

body{
    font-family: Vazirmatn;
    unicode-bidi:bidi-override;
    direction:rtl;
    text-align:right;
    font-size:14px;
}

h1{
    text-align:center;
}

.question{
    margin-bottom:25px;
    padding-bottom:10px;
    border-bottom:1px solid #ccc;
}

.answer{
    color:green;
    margin-top:8px;
}

.option{
    margin-right:20px;
}

</style>
</head>
<body>

<h1>آزمون تولید شده توسط سامانه هوشمند</h1>

';
foreach($questions as $i=>$q){

$html.='

<div class="question">

<b>سوال '.($i+1).'</b>

<p>'.$q['question'].'</p>

';

if(isset($q['options'])){

foreach($q['options'] as $key=>$value){

$html.='<div class="option">'.$key.') '.$value.'</div>';

}

}

$html.='

<div class="answer">

پاسخ صحیح:
'.$q['correct_answer'].'

</div>

</div>

';

}
$html.='

</body>
</html>

';

$mpdf = new \Mpdf\Mpdf([
    'mode' => 'utf-8',
    'format' => 'A4',
    'default_font' => 'Vazirmatn'
]);

$mpdf->WriteHTML($html);

$mpdf->Output('Exam.pdf', 'D');