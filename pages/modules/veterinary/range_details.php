<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Under Construction</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        *{
            font-family: 'Poppins', sans-serif;
        }

        body{
            background:#f8fafc;
            min-height:100vh;
            display:flex;
            align-items:center;
            justify-content:center;
        }

        .construction-box{
            max-width:900px;
            width:100%;
            background:#fff;
            border-radius:20px;
            padding:60px;
            text-align:center;
            box-shadow:0 15px 40px rgba(0,0,0,.08);
        }

        .icon-circle{
            width:130px;
            height:130px;
            margin:auto;
            background:#fff3cd;
            border-radius:50%;
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:70px;
            color:#ffc107;
        }

        h1{
            font-size:55px;
            font-weight:700;
        }

        .subtitle{
            color:#6c757d;
            font-size:18px;
        }

        .countdown{
            margin-top:40px;
        }

        .count-item{
            background:#f8f9fa;
            padding:20px;
            border-radius:15px;
        }

        .count-item h3{
            font-size:35px;
            margin-bottom:0;
            color:#0d6efd;
            font-weight:700;
        }

        .contact{
            margin-top:40px;
            border-top:1px solid #eee;
            padding-top:30px;
        }

        .contact i{
            font-size:28px;
            color:#0d6efd;
        }

        footer{
            margin-top:35px;
            color:#888;
            font-size:14px;
        }

        @media(max-width:768px){

            .construction-box{
                padding:35px;
            }

            h1{
                font-size:36px;
            }

            .icon-circle{
                width:100px;
                height:100px;
                font-size:55px;
            }

        }
    </style>

</head>
<body>

<div class="container">

    <div class="construction-box">

        <div class="icon-circle">
            <i class="bi bi-cone-striped"></i>
        </div>

        <h1 class="mt-4">
            Under Construction
        </h1>

        <p class="subtitle mt-3">
            We're working hard to build the <strong>Range Management System</strong>.
            <br>
            This page will be available very soon.
        </p>

        <div class="row countdown g-3 justify-content-center">

            <div class="col-6 col-md-3">
                <div class="count-item">
                    <h3 id="days">00</h3>
                    Days
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="count-item">
                    <h3 id="hours">00</h3>
                    Hours
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="count-item">
                    <h3 id="minutes">00</h3>
                    Minutes
                </div>
            </div>

            <div class="col-6 col-md-3">
                <div class="count-item">
                    <h3 id="seconds">00</h3>
                    Seconds
                </div>
            </div>

        </div>

        <div class="contact">

            <div class="row text-center">

                <div class="col-md-4 mb-3">
                    <i class="bi bi-envelope-fill"></i>
                    <p class="mt-2 mb-0">
                        support@example.com
                    </p>
                </div>

                <div class="col-md-4 mb-3">
                    <i class="bi bi-telephone-fill"></i>
                    <p class="mt-2 mb-0">
                        +94 77 123 4567
                    </p>
                </div>

                <div class="col-md-4 mb-3">
                    <i class="bi bi-clock-fill"></i>
                    <p class="mt-2 mb-0">
                        Mon - Fri | 8.30 AM - 5.00 PM
                    </p>
                </div>

            </div>

        </div>

        <footer>
            © 2026 Range Management System. All Rights Reserved.
        </footer>

    </div>

</div>

<script>

const targetDate = new Date("December 31, 2026 23:59:59").getTime();

setInterval(function(){

    const now = new Date().getTime();

    const distance = targetDate - now;

    const days = Math.floor(distance / (1000 * 60 * 60 * 24));

    const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));

    const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));

    const seconds = Math.floor((distance % (1000 * 60)) / 1000);

    document.getElementById("days").innerHTML = days;
    document.getElementById("hours").innerHTML = hours;
    document.getElementById("minutes").innerHTML = minutes;
    document.getElementById("seconds").innerHTML = seconds;

},1000);

</script>

</body>
</html>