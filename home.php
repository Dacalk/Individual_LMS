<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LearnX - School Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Landing Page Specific Styles */
        .landing-page {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            position: relative;
            overflow: hidden;
        }
        
        .landing-page::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 50%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .landing-nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 5%;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(15px);
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .landing-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            color: white;
            font-size: 28px;
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);
        }
        
        .mobile-menu-toggle {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            padding: 8px;
        }
        
        .mobile-menu-toggle span {
            width: 25px;
            height: 3px;
            background: white;
            border-radius: 3px;
            transition: all 0.3s ease;
        }
        
        .landing-nav-links {
            display: flex;
            gap: 30px;
            align-items: center;
        }
        
        .landing-nav-links a {
            color: white;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
        }
        
        .landing-nav-links a:hover {
            background: rgba(255, 255, 255, 0.25);
            transform: translateY(-2px);
        }
        
        .landing-nav-links .btn-login {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            padding: 10px 24px;
            border-radius: 8px;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4);
        }
        
        .landing-nav-links .btn-login:hover {
            background: linear-gradient(135deg, #ff5252 0%, #e91e63 100%);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 107, 107, 0.5);
        }
        
        .hero-section {
            padding: 100px 5% 80px;
            text-align: center;
            color: white;
            position: relative;
            z-index: 1;
        }
        
        .hero-content h1 {
            font-size: 56px;
            margin-bottom: 20px;
            font-weight: 800;
            line-height: 1.2;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #ffffff 0%, #e0e7ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .hero-content p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.95;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.2);
        }
        
        .hero-buttons {
            display: flex;
            gap: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .hero-btn {
            padding: 16px 40px;
            border-radius: 12px;
            font-size: 18px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        
        .hero-btn-primary {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
            color: white;
            box-shadow: 0 4px 20px rgba(255, 107, 107, 0.4);
        }
        
        .hero-btn-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 30px rgba(255, 107, 107, 0.5);
            background: linear-gradient(135deg, #ff5252 0%, #e91e63 100%);
        }
        
        .hero-btn-secondary {
            background: rgba(255, 255, 255, 0.25);
            color: white;
            border: 2px solid rgba(255, 255, 255, 0.5);
            backdrop-filter: blur(10px);
        }
        
        .hero-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.35);
            transform: translateY(-4px);
            border-color: white;
        }
        
        .features-section {
            padding: 100px 5%;
            background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%);
            position: relative;
        }
        
        .section-title {
            text-align: center;
            font-size: 42px;
            font-weight: 700;
            margin-bottom: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .section-subtitle {
            text-align: center;
            font-size: 18px;
            color: var(--text-light);
            margin-bottom: 60px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .feature-card {
            background: white;
            padding: 40px 30px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.4s ease;
            border: 2px solid transparent;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
            transform: scaleX(0);
            transition: transform 0.4s ease;
        }
        
        .feature-card:hover::before {
            transform: scaleX(1);
        }
        
        .feature-card:nth-child(1) .feature-icon { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .feature-card:nth-child(2) .feature-icon { background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); }
        .feature-card:nth-child(3) .feature-icon { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); }
        .feature-card:nth-child(4) .feature-icon { background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); }
        .feature-card:nth-child(5) .feature-icon { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); }
        .feature-card:nth-child(6) .feature-icon { background: linear-gradient(135deg, #30cfd0 0%, #330867 100%); }
        .feature-card:nth-child(7) .feature-icon { background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%); }
        .feature-card:nth-child(8) .feature-icon { background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%); }
        .feature-card:nth-child(9) .feature-icon { background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%); }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(102, 126, 234, 0.2);
            border-color: #667eea;
        }
        
        .feature-icon {
            width: 90px;
            height: 90px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 40px;
            color: white;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            transition: transform 0.3s ease;
        }
        
        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }
        
        .feature-card h3 {
            font-size: 22px;
            margin-bottom: 12px;
            color: var(--text-dark);
            font-weight: 600;
        }
        
        .feature-card p {
            color: var(--text-light);
            line-height: 1.6;
        }
        
        .roles-section {
            padding: 100px 5%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            position: relative;
        }
        
        .roles-section .section-title {
            color: white;
            -webkit-text-fill-color: white;
        }
        
        .roles-section .section-subtitle {
            color: rgba(255, 255, 255, 0.9);
        }
        
        .roles-grid {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 20px;
            max-width: 1400px;
            margin: 0 auto;
        }
        
        @media (max-width: 1400px) {
            .roles-grid {
                grid-template-columns: repeat(5, 1fr);
                gap: 15px;
            }
            
            .role-card {
                padding: 30px 20px;
            }
            
            .role-icon {
                font-size: 50px;
            }
            
            .role-card h3 {
                font-size: 18px;
            }
            
            .role-card p {
                font-size: 13px;
            }
        }
        
        .role-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            text-align: center;
            transition: all 0.4s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
            position: relative;
            overflow: hidden;
        }
        
        .role-card::after {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            opacity: 0;
            transition: opacity 0.4s ease;
        }
        
        .role-card:hover::after {
            opacity: 1;
        }
        
        .role-card:nth-child(1) .role-icon {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .role-card:nth-child(2) .role-icon {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .role-card:nth-child(3) .role-icon {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .role-card:nth-child(4) .role-icon {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .role-card:nth-child(5) .role-icon {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .role-card:hover {
            transform: scale(1.08) translateY(-5px);
            box-shadow: 0 15px 40px rgba(102, 126, 234, 0.3);
        }
        
        .role-icon {
            font-size: 70px;
            margin-bottom: 20px;
            display: inline-block;
        }
        
        .role-card h3 {
            font-size: 24px;
            margin-bottom: 12px;
            color: var(--text-dark);
            font-weight: 600;
        }
        
        .role-card p {
            color: var(--text-light);
            margin-bottom: 20px;
        }
        
        .stats-section {
            padding: 100px 5%;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: white;
            position: relative;
        }
        
        .stats-section .section-title {
            color: white;
            -webkit-text-fill-color: white;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 40px;
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }
        
        .stat-item {
            padding: 30px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-item:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.1);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .stat-item h2 {
            font-size: 52px;
            font-weight: 800;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #f093fb 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-item p {
            font-size: 18px;
            opacity: 0.9;
        }
        
        .cta-section {
            padding: 120px 5%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            text-align: center;
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .cta-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.15) 0%, transparent 70%);
            animation: pulse 3s ease-in-out infinite;
        }
        
        .cta-section::after {
            content: '';
            position: absolute;
            bottom: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            animation: pulse 4s ease-in-out infinite reverse;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.1); opacity: 0.8; }
        }
        
        .cta-section h2 {
            font-size: 42px;
            margin-bottom: 20px;
            font-weight: 700;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.2);
            background: linear-gradient(135deg, #ffffff 0%, #e0e7ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .cta-section p {
            font-size: 20px;
            margin-bottom: 40px;
            opacity: 0.95;
            position: relative;
            z-index: 1;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.2);
        }
        
        .cta-section .hero-btn-primary {
            background: linear-gradient(135deg, #ffffff 0%, #f0f4ff 100%);
            color: #667eea;
            box-shadow: 0 8px 30px rgba(255, 255, 255, 0.3);
            border: 2px solid rgba(255, 255, 255, 0.3);
            position: relative;
            z-index: 1;
        }
        
        .cta-section .hero-btn-primary:hover {
            background: linear-gradient(135deg, #f0f4ff 0%, #e0e7ff 100%);
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(255, 255, 255, 0.4);
            color: #764ba2;
        }
        
        .footer {
            padding: 70px 5% 30px;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            color: white;
            position: relative;
            overflow: hidden;
        }
        
        .footer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #ff6b6b);
        }
        
        .footer-content {
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 40px;
            margin-bottom: 40px;
        }
        
        .footer-column h3 {
            font-size: 20px;
            margin-bottom: 20px;
            color: white;
            font-weight: 600;
            position: relative;
            padding-bottom: 10px;
        }
        
        .footer-column h3::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 40px;
            height: 3px;
            background: linear-gradient(90deg, #667eea, #f093fb);
            border-radius: 2px;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            font-size: 24px;
            font-weight: 700;
        }
        
        .footer-logo i {
            font-size: 32px;
            background: linear-gradient(135deg, #667eea, #f093fb);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .footer-about p {
            color: rgba(255, 255, 255, 0.8);
            line-height: 1.6;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .footer-links {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-links li {
            margin-bottom: 12px;
        }
        
        .footer-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
        }
        
        .footer-links a:hover {
            color: #f093fb;
            transform: translateX(5px);
            padding-left: 5px;
        }
        
        .footer-links a i {
            font-size: 12px;
            opacity: 0.7;
        }
        
        .footer-contact {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .footer-contact li {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
            color: rgba(255, 255, 255, 0.8);
            font-size: 14px;
        }
        
        .footer-contact li i {
            font-size: 18px;
            color: #667eea;
            margin-top: 2px;
            flex-shrink: 0;
        }
        
        .footer-social {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }
        
        .footer-social a {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 18px;
            transition: all 0.3s ease;
            text-decoration: none;
            border: 2px solid transparent;
        }
        
        .footer-social a:hover {
            background: linear-gradient(135deg, #667eea, #f093fb);
            transform: translateY(-5px) scale(1.1);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
            border-color: rgba(255, 255, 255, 0.3);
        }
        
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 30px;
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .footer-bottom p {
            margin: 0;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }
        
        .footer-bottom-links {
            display: flex;
            gap: 25px;
            flex-wrap: wrap;
        }
        
        .footer-bottom-links a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .footer-bottom-links a:hover {
            color: #f093fb;
        }
        
        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .landing-nav {
                padding: 15px 5%;
            }
            
            .mobile-menu-toggle {
                display: flex;
            }
            
            .landing-nav-links {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: rgba(255, 255, 255, 0.95);
                backdrop-filter: blur(15px);
                flex-direction: column;
                padding: 20px;
                gap: 15px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            }
            
            .landing-nav-links.active {
                display: flex;
            }
            
            .landing-nav-links a {
                color: #667eea;
                width: 100%;
                text-align: center;
                padding: 12px;
                border-radius: 8px;
            }
            
            .landing-nav-links a:hover {
                background: rgba(102, 126, 234, 0.1);
            }
            
            .landing-nav-links .btn-login {
                background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
                color: white;
            }
            
            .hero-section {
                padding: 60px 5% 50px;
            }
            
            .hero-content h1 {
                font-size: 32px;
                line-height: 1.3;
            }
            
            .hero-content p {
                font-size: 16px;
                margin-bottom: 30px;
            }
            
            .hero-btn {
                padding: 14px 30px;
                font-size: 16px;
            }
            
            .features-section,
            .roles-section,
            .stats-section,
            .cta-section {
                padding: 60px 5%;
            }
            
            .section-title {
                font-size: 28px;
            }
            
            .section-subtitle {
                font-size: 16px;
                margin-bottom: 40px;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .roles-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }
            
            @media (max-width: 640px) {
                .roles-grid {
                    grid-template-columns: 1fr;
                }
            }
            
            .feature-card,
            .role-card {
                padding: 30px 20px;
            }
            
            .feature-icon {
                width: 70px;
                height: 70px;
                font-size: 32px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 20px;
            }
            
            .stat-item h2 {
                font-size: 36px;
            }
            
            .stat-item p {
                font-size: 14px;
            }
            
            .cta-section h2 {
                font-size: 28px;
            }
            
            .cta-section p {
                font-size: 16px;
            }
            
            .footer {
                padding: 50px 5% 25px;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 30px;
                margin-bottom: 30px;
            }
            
            .footer-column h3 {
                font-size: 18px;
                margin-bottom: 15px;
            }
            
            .footer-bottom {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
            
            .footer-bottom-links {
                justify-content: center;
                gap: 20px;
            }
            
            .footer-social {
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .hero-content h1 {
                font-size: 24px;
            }
            
            .hero-content p {
                font-size: 14px;
            }
            
            .hero-btn {
                padding: 12px 24px;
                font-size: 14px;
                width: 100%;
                justify-content: center;
            }
            
            .hero-buttons {
                flex-direction: column;
                gap: 15px;
            }
            
            .section-title {
                font-size: 24px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="landing-page">
        <!-- Navigation -->
        <nav class="landing-nav">
            <div class="landing-logo">
                <i class="fas fa-graduation-cap"></i>
                <span>LearnX</span>
            </div>
            <div class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="landing-nav-links" id="navLinks">
                <a href="#features">Features</a>
                <a href="#roles">User Roles</a>
                <a href="#about">About</a>
                <a href="login.php" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Login
                </a>
            </div>
        </nav>

        <!-- Hero Section -->
        <section class="hero-section">
            <div class="hero-content">
                <h1>Modern School Management<br>for Sri Lankan Schools</h1>
                <p>Comprehensive digital solution for managing Grades 6-13, designed specifically for Sri Lankan educational institutions with trilingual support.</p>
                <div class="hero-buttons">
                    <a href="login.php" class="hero-btn hero-btn-primary">
                        <i class="fas fa-rocket"></i> Get Started
                    </a>
                    <a href="#features" class="hero-btn hero-btn-secondary">
                        <i class="fas fa-info-circle"></i> Learn More
                    </a>
                </div>
            </div>
        </section>
    </div>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <h2 class="section-title">Powerful Features for Modern Education</h2>
        <p class="section-subtitle">Everything you need to manage your school efficiently</p>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3>Student Management</h3>
                <p>Comprehensive student profiles from Grade 6 to 13, including O/Level and A/Level streams with subject allocation.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h3>Academic Tracking</h3>
                <p>Track term test results, calculate rankings, and monitor student progress by subject across all grades.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <h3>Attendance System</h3>
                <p>Easy-to-use daily attendance marking with comprehensive reports and analytics.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <h3>Smart Timetable</h3>
                <p>Create and manage class schedules for all grades with automatic conflict detection.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-language"></i>
                </div>
                <h3>Trilingual Support</h3>
                <p>Full interface and reports in Sinhala, Tamil, and English for inclusive education.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-comments"></i>
                </div>
                <h3>Communication Hub</h3>
                <p>Built-in messaging system with SMS gateway integration for parent-teacher communication.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-book-reader"></i>
                </div>
                <h3>Library Management</h3>
                <p>Complete digital library system to manage books, track borrowing, and monitor returns.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <h3>Report Generation</h3>
                <p>Generate official reports for ministry requirements, term tests, and administrative needs.</p>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <h3>Student Promotion</h3>
                <p>Streamlined year-end promotion system to advance students from Grade 6 through to Grade 13.</p>
            </div>
        </div>
    </section>

    <!-- User Roles Section -->
    <section class="roles-section" id="roles">
        <h2 class="section-title">Built for Every User</h2>
        <p class="section-subtitle">Role-based access for administrators, teachers, students, parents, and librarians</p>
        
        <div class="roles-grid">
            <div class="role-card">
                <div class="role-icon">
                    <i class="fas fa-user-shield"></i>
                </div>
                <h3>Principal/Admin</h3>
                <p>Complete control over school operations, user management, and academic planning for Grades 6-13.</p>
            </div>
            
            <div class="role-card">
                <div class="role-icon">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <h3>Teachers</h3>
                <p>Mark attendance, enter term test marks, manage classes, and communicate with parents efficiently.</p>
            </div>
            
            <div class="role-card">
                <div class="role-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <h3>Students</h3>
                <p>Access timetables, view grades, check attendance, and stay updated with school notifications.</p>
            </div>
            
            <div class="role-card">
                <div class="role-icon">
                    <i class="fas fa-users"></i>
                </div>
                <h3>Parents</h3>
                <p>Monitor child's attendance, academic performance, and communicate with teachers directly.</p>
            </div>
            
            <div class="role-card">
                <div class="role-icon">
                    <i class="fas fa-book"></i>
                </div>
                <h3>Librarian</h3>
                <p>Manage library catalog, handle book transactions, and track overdue items seamlessly.</p>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <h2 class="section-title" style="color: white; margin-bottom: 60px;">System Capabilities</h2>
        <div class="stats-grid">
            <div class="stat-item">
                <h2>8</h2>
                <p>Grade Levels (6-13)</p>
            </div>
            <div class="stat-item">
                <h2>5+</h2>
                <p>User Roles</p>
            </div>
            <div class="stat-item">
                <h2>3</h2>
                <p>Languages Supported</p>
            </div>
            <div class="stat-item">
                <h2>∞</h2>
                <p>Students Supported</p>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section" id="about">
        <h2>Ready to Transform Your School?</h2>
        <p>Join hundreds of Sri Lankan schools already using LearnX for better education management</p>
        <a href="login.php" class="hero-btn hero-btn-primary">
            <i class="fas fa-arrow-right"></i> Access System Now
        </a>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-grid">
                <!-- About Column -->
                <div class="footer-column">
                    <div class="footer-logo">
                        <i class="fas fa-graduation-cap"></i>
                        <span>LearnX</span>
                    </div>
                    <div class="footer-about">
                        <p>A comprehensive digital solution for managing Grades 6-13, designed specifically for Sri Lankan educational institutions with trilingual support.</p>
                        <div class="footer-social">
                            <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="#" title="Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="#" title="LinkedIn"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Links Column -->
                <div class="footer-column">
                    <h3>Quick Links</h3>
                    <ul class="footer-links">
                        <li><a href="#features"><i class="fas fa-chevron-right"></i> Features</a></li>
                        <li><a href="#roles"><i class="fas fa-chevron-right"></i> User Roles</a></li>
                        <li><a href="#about"><i class="fas fa-chevron-right"></i> About Us</a></li>
                        <li><a href="login.php"><i class="fas fa-chevron-right"></i> Login</a></li>
                        <li><a href="home.php"><i class="fas fa-chevron-right"></i> Home</a></li>
                    </ul>
                </div>
                
                <!-- Features Column -->
                <div class="footer-column">
                    <h3>Key Features</h3>
                    <ul class="footer-links">
                        <li><a href="#features"><i class="fas fa-chevron-right"></i> Student Management</a></li>
                        <li><a href="#features"><i class="fas fa-chevron-right"></i> Academic Tracking</a></li>
                        <li><a href="#features"><i class="fas fa-chevron-right"></i> Attendance System</a></li>
                        <li><a href="#features"><i class="fas fa-chevron-right"></i> Library Management</a></li>
                        <li><a href="#features"><i class="fas fa-chevron-right"></i> Timetable System</a></li>
                    </ul>
                </div>
                
                <!-- Contact Column -->
                <div class="footer-column">
                    <h3>Contact Info</h3>
                    <ul class="footer-contact">
                        <li>
                            <i class="fas fa-envelope"></i>
                            <span>info@learnx.lk</span>
                        </li>
                        <li>
                            <i class="fas fa-phone"></i>
                            <span>+94 11 2XXX XXXX</span>
                        </li>
                        <li>
                            <i class="fas fa-map-marker-alt"></i>
                            <span>Colombo, Sri Lanka</span>
                        </li>
                        <li>
                            <i class="fas fa-clock"></i>
                            <span>Mon - Fri: 8:00 AM - 5:00 PM</span>
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2024 LearnX School Management System. All rights reserved.</p>
                <div class="footer-bottom-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Help Center</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Mobile menu toggle
        function toggleMobileMenu() {
            const navLinks = document.getElementById('navLinks');
            navLinks.classList.toggle('active');
        }
        
        // Close mobile menu when clicking on a link
        document.querySelectorAll('.landing-nav-links a').forEach(link => {
            link.addEventListener('click', function() {
                const navLinks = document.getElementById('navLinks');
                navLinks.classList.remove('active');
            });
        });
        
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>







