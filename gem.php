
    <style>


        .container {
            max-width: 1200px;
            margin: 0 auto;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(43, 20, 115, 0.1);
            border: 1px solid rgba(43, 20, 115, 0.1);
        }

        .header {
            text-align: center;
            margin-bottom: 50px;
        }

        .header h1 {
            font-size: 3rem;
            font-weight: 700;
            color: #2b1473;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.2rem;
            opacity: 0.8;
            font-weight: 300;
        }

        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 40px;
        }

        .month-card {
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 2px solid #2b1473;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .month-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(43, 20, 115, 0.05), transparent);
            transition: left 0.6s;
        }

        .month-card:hover::before {
            left: 100%;
        }

        .month-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 0 25px 50px rgba(43, 20, 115, 0.2);
            border-color: #311a31;
        }

        .month-icon {
            display: none;
        }

        .month-name {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2b1473;
        }

        .month-description {
            font-size: 0.95rem;
            color: #311a31;
            line-height: 1.6;
            margin-bottom: 20px;
        }

        .view-month-btn {
            background: #2b1473;
            color: #ccb484;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            box-shadow: 0 4px 15px rgba(43, 20, 115, 0.2);
        }

        .view-month-btn:hover {
            background: #311a31;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(43, 20, 115, 0.3);
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .header p {
                font-size: 1rem;
            }
            
            .calendar-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .month-card {
                padding: 20px;
            }
            
            .month-name {
                font-size: 1.5rem;
            }
        }

        .footer {
            text-align: center;
            margin-top: 60px;
            padding-top: 30px;
            border-top: 1px solid rgba(43, 20, 115, 0.1);
            color: #311a31;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>
    <div class="container">


        <div class="calendar-grid">
            <div class="month-card" onclick="navigateToMonth('january-2')">
                <div class="month-name">January</div>
                <div class="month-description">Start the year with new beginnings and fresh perspectives</div>
                <button class="view-month-btn">View January</button>
            </div>

            <div class="month-card" onclick="navigateToMonth('february')">
                <div class="month-name">February</div>
                <div class="month-description">The month of love, renewal and shortened days</div>
                <button class="view-month-btn">View February</button>
            </div>

            <div class="month-card" onclick="navigateToMonth('march')">
                <div class="month-name">March</div>
                <div class="month-description">Spring awakens with new growth and possibilities</div>
                <button class="view-month-btn">View March</button>
            </div>

            <div class="month-card" onclick="navigateToMonth('april')">
                <div class="month-name">April</div>
                <div class="month-description">Blossoming beauty and the promise of warmer days</div>
                <button class="view-month-btn">View April</button>
            </div>

            <div class="month-card" onclick="navigateToMonth('may')">
                <div class="month-name">May</div>
                <div class="month-description">Full bloom and the height of spring's energy</div>
                <button class="view-month-btn">View May</button>
            </div>

            <div class="month-card" onclick="navigateToMonth('june')">
                <div class="month-name">June</div>
                <div class="month-description">Summer begins with longest days and warm sunshine</div>
                <button class="view-month-btn">View June</button>
            </div>
        </div>

        <div class="footer">
            <p>Click on any month to view detailed calendar information</p>
        </div>
    </div>

    <script>
        function navigateToMonth(month) {
            const baseUrl = 'https://www.gjrti.gov.lk/';
            window.open(baseUrl + month, '_blank');
        }

        // Add hover sound effect (optional)
        document.querySelectorAll('.month-card').forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-10px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Add loading animation
        window.addEventListener('load', () => {
            const cards = document.querySelectorAll('.month-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(50px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        });
    </script>
</body>
