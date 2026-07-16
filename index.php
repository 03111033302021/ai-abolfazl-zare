<!DOCTYPE html>
<html lang="fa" dir="rtl">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>سامانه هوشمند تولید آزمون</title>

<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;700&display=swap" rel="stylesheet">

<style>

*{
margin:0;
padding:0;
box-sizing:border-box;
font-family:'Vazirmatn',sans-serif;
}

body{

background:linear-gradient(135deg,#4F46E5,#7C3AED,#06B6D4);
height:100vh;
display:flex;
justify-content:center;
align-items:center;

}

.container{

width:100%;
max-width:600px;
padding:20px;

}

.logo{

font-size:60px;
text-align:center;
margin-bottom:10px;

}

.title{

text-align:center;
color:white;
font-size:34px;
font-weight:bold;
margin-top:100px;
margin-bottom:30px;

}

.card{

background:rgba(255,255,255,.15);
backdrop-filter:blur(15px);
padding:35px;
border-radius:25px;
box-shadow:0 20px 50px rgba(0,0,0,.2);
border:1px solid rgba(255,255,255,.2);

}

.section{

margin-bottom:25px;

}

.section h3{

margin-bottom:12px;
color:white;
font-size:18px;

}

input[type=file],
textarea,
select{

width:100%;
padding:12px;
border:none;
border-radius:12px;
outline:none;
font-size:15px;

}

textarea{

resize:none;
height:130px;

}

.row{

display:flex;
gap:15px;
flex-wrap:wrap;

}

.option{

flex:1;
min-width:90px;

}

.option select{

width:100%;

}

.checkbox-group{

display:flex;
gap:25px;
flex-wrap:wrap;
color:white;
font-size:16px;

}

.checkbox-group label{

cursor:pointer;

}

button{

width:100%;
padding:17px;
border:none;
border-radius:15px;
font-size:18px;
font-weight:bold;
cursor:pointer;

background:linear-gradient(135deg,#FFD54F,#FF9800);

color:#222;

transition:.3s;

}

button:hover{

transform:translateY(-3px);
box-shadow:0 15px 30px rgba(0,0,0,.3);

}

</style>

</head>

<body>

<div class="container">

<div class="logo">
🧠
</div>

<div class="title">
سامانه هوشمند تولید آزمون
</div>

<div class="card">

<form action="upload.php" method="POST" enctype="multipart/form-data">

    <!-- فایل -->
    <div class="section">
        <h3>📄 انتخاب فایل PDF</h3>
        <input type="file" name="pdf" accept=".pdf">
    </div>

    <!-- متن -->
    <div class="section">
        <h3>📝 یا وارد کردن متن</h3>
        <textarea
            name="text"
            placeholder="متن درس را وارد کنید..."></textarea>
    </div>

    <!-- تعداد سوال -->
    <div class="section">
        <h3>تعداد سوال</h3>

        <select name="question_count">
            <option value="5">5</option>
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="20">20</option>
        </select>
    </div>

    <!-- نوع سوال -->
    <div class="section">
        <h3>نوع سوال</h3>

        <div class="checkbox-group">

            <label>
                <input type="radio"
                       name="question_type"
                       value="multiple_choice"
                       checked>

                تستی
            </label>

            <label>
                <input type="radio"
                       name="question_type"
                       value="descriptive">

                تشریحی
            </label>

            <label>
                <input type="radio"
                       name="question_type"
                       value="both">

                هر دو
            </label>

        </div>
    </div>

    <!-- سطح -->
    <div class="section">
        <h3>سطح سوال</h3>

        <select name="difficulty">
            <option value="easy">آسان</option>
            <option value="medium" selected>متوسط</option>
            <option value="hard">سخت</option>
        </select>
    </div>

    <button type="submit">
        ✨ تولید آزمون
    </button>

</form>

</div>

</div>

</body>

</html>